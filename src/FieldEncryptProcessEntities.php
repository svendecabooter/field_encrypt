<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptProcessEntities.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;

/**
 * Service class to process entities and fields for encryption.
 */
class FieldEncryptProcessEntities implements FieldEncryptProcessEntitiesInterface {

  /**
   * Flag to disable decryption when in the process of updating stored fields.
   */
  protected $updatingStoredField = 'none';

  /**
   * The query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryptService;

  /**
   * The encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * The EncryptedFieldValue entity manager.
   *
   * @var \Drupal\field_encrypt\EncryptedFieldValueManagerInterface
   */
  protected $encryptedFieldValueManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   A query factory service.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   An entity manager service.
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   *   The encryption service.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   The encryption profile manager.
   * @param \Drupal\field_encrypt\EncryptedFieldValueManagerInterface $encrypted_field_value_manager
   *   The EncryptedFieldValue entity manager.
   */
  public function __construct(QueryFactory $query_factory, EntityManager $entity_manager, EncryptServiceInterface $encrypt_service, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptedFieldValueManagerInterface $encrypted_field_value_manager) {
    $this->queryFactory = $query_factory;
    $this->entityManager = $entity_manager;
    $this->encryptService = $encrypt_service;
    $this->encryptionProfileManager = $encryption_profile_manager;
    $this->encryptedFieldValueManager = $encrypted_field_value_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function entityHasEncryptedFields(ContentEntityInterface $entity) {
    // Make sure we can get fields.
    if (!is_callable([$entity, 'getFields'])) {
      return FALSE;
    }

    $encryption_enabled = FALSE;
    foreach ($entity->getFields() as $field) {
      if ($this->checkField($field)) {
        $encryption_enabled = TRUE;
      }
    }

    return $encryption_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function encryptEntity(ContentEntityInterface $entity) {
    $this->processEntity($entity, 'encrypt');
  }

  /**
   * {@inheritdoc}
   */
  public function decryptEntity(ContentEntityInterface $entity) {
    $this->processEntity($entity, 'decrypt');
  }

  /**
   * Process an entity to either encrypt or decrypt its fields.
   *
   * Both processes are very similar, so we bundle the field processing part.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param string $op
   *   The operation to perform (encrypt / decrypt).
   */
  protected function processEntity(ContentEntityInterface $entity, $op = 'encrypt') {
    // Make sure we can get fields.
    if (!is_callable([$entity, 'getFields'])) {
      return;
    }

    // Process all language variants of the entity.
    $languages = $entity->getTranslationLanguages();
    foreach ($languages as $language) {
      $translated_entity = $entity->getTranslation($language->getId());
      foreach ($translated_entity->getFields() as $field) {
        $this->processField($translated_entity, $field, $op);
      }
    }
  }

  /**
   * Process a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to process.
   * @param string $op
   *   The operation to perform (encrypt / decrypt).
   * @param bool $force
   *   Whether to force the operation.
   *   If set, we don't check if encryption is enabled, we process the field
   *   anyway. This is used during batch processes.
   */
  protected function processField(ContentEntityInterface $entity, FieldItemListInterface $field, $op = 'encrypt', $force = FALSE) {
    // Check if field is properly set up and allows encryption.
    if (!$this->checkField($field)) {
      return;
    }

    /* @var $definition \Drupal\Core\Field\BaseFieldDefinition */
    $definition = $field->getFieldDefinition();
    /* @var $storage \Drupal\Core\Field\FieldConfigStorageBase */
    $storage = $definition->get('fieldStorage');

    // If we are using the force flag, we always proceed.
    // The force flag is used when we are updating stored fields.
    if (!$force) {
      // Check if we are updating the field, in that case, skip it now (during
      // the initial entity load.
      if ($this->updatingStoredField === $definition->get('field_name')) {
        return;
      }

      // Check if the field is encrypted.
      $encrypted = $storage->getThirdPartySetting('field_encrypt', 'encrypt', FALSE);
      if (!$encrypted) {
        return;
      }
    }

    /* @var $field \Drupal\Core\Field\FieldItemList */
    $field_value = $field->getValue();
    $encryption_profile_id = $storage->getThirdPartySetting('field_encrypt', 'encryption_profile', []);
    $encryption_profile = $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_id);

    // Process the field with the given encryption provider.
    foreach ($field_value as &$value) {
      $properties = $storage->getThirdPartySetting('field_encrypt', 'properties', []);
      // Process each of the field properties that exist.
      foreach ($properties as $property_name) {
        if (isset($value[$property_name])) {
          $value[$property_name] = $this->processValue($entity, $field, $property_name, $encryption_profile, $value[$property_name], $op);
        }
      }
    }
    // Set the new value.
    // We don't need to update the entity because setValue does that already.
    $field->setValue($field_value);
  }

  /**
   * Check if a given field has encryption enabled.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to check.
   *
   * @return bool
   *   Boolean indicating whether to encrypt the field.
   */
  protected function checkField(FieldItemListInterface $field) {
    if (!is_callable([$field, 'getFieldDefinition'])) {
      return FALSE;
    }

    /* @var $definition \Drupal\Core\Field\BaseFieldDefinition */
    $definition = $field->getFieldDefinition();

    if (!is_callable([$definition, 'get'])) {
      return FALSE;
    }

    /* @var $storage \Drupal\Core\Field\FieldConfigStorageBase */
    $storage = $definition->get('fieldStorage');
    if (is_null($storage)) {
      return FALSE;
    }

    // Check if the field is encrypted.
    $encrypted = $storage->getThirdPartySetting('field_encrypt', 'encrypt', FALSE);
    if ($encrypted) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Encrypt or decrypt a value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to process.
   * @param string $property_name
   *   The name of the property.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The encryption profile to use.
   * @param string $value
   *   The value to encrypt / decrypt.
   * @param string $op
   *   The operation ("encrypt" or "decrypt").
   *
   * @return string
   *   The processed value.
   */
  protected function processValue(ContentEntityInterface $entity, FieldItemListInterface $field, $property_name, EncryptionProfileInterface $encryption_profile, $value = '', $op = 'encrypt') {
    // Do not modify empty strings.
    if ($value === '') {
      return '';
    }

    if ($op === 'encrypt') {
      // Encrypt property value.
      $processed_value = base64_encode($this->encryptService->encrypt($value, $encryption_profile));
      // Save encrypted value in EncryptedFieldValue entity.
      $this->encryptedFieldValueManager->saveEncryptedFieldValue($entity, $field->getName(), $property_name, $processed_value);
      // Return value to store for unencrypted property.
      // We can't set this to NULL, because then the field values are not saved,
      // so we can't replace them with their unencrypted value on load.
      return '[ENCRYPTED]';

    }
    elseif ($op === 'decrypt') {
      // Get encrypted value from EncryptedFieldValue entity.
      if ($encrypted_value = $this->encryptedFieldValueManager->getEncryptedFieldValue($entity, $field->getName(), $property_name)) {
        // Decrypt value.
        $decrypted_value = $this->encryptService->decrypt(base64_decode($encrypted_value), $encryption_profile);
        return $decrypted_value;
      }
      else {
        return $value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function encryptStoredField($entity_type, $field_name) {
    // @TODO: refactor
    //$this->updateStoredField($entity_type, $field_name, 'encrypt');
  }

  /**
   * {@inheritdoc}
   */
  public function decryptStoredField($entity_type, $field_name) {
    // @TODO: refactor
    //$this->updateStoredField($entity_type, $field_name, 'decrypt');
  }

  /**
   * Update a field.
   *
   * This is used to process fields when the storage configuration changes.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The name of the field to update.
   * @param string $op
   *   The operation to perform (encrypt / decrypt).
   */
  protected function updateStoredField($entity_type, $field_name, $op = 'encrypt') {
    /**
     * Before we load entities, we have to disable the encryption setting.
     * Otherwise, the act of loading the entity triggers an improper decryption
     * Which messes up the batch encryption.
     */
    $this->updatingStoredField = $field_name;

    /* @var $query \Drupal\Core\Entity\Query\QueryInterface */
    $query = $this->queryFactory->get($entity_type);

    // The field is present.
    $query->exists($field_name);
    $query->allRevisions();

    $entity_ids = $query->execute();

    // Load entities.
    // @TODO: use EntityTypeManager instead of EntityManager
    /* @var $entity_storage \Drupal\Core\Entity\ContentEntityStorageBase */
    $entity_storage = $this->entityManager->getStorage($entity_type);

    foreach($entity_ids as $revision_id => $entity_id) {
      /** @var $entity \Drupal\Core\Entity\Entity */
      $entity = $entity_storage->loadRevision($revision_id);

      /** @var $field \Drupal\Core\Field\FieldItemList */
      $field = $entity->get($field_name);
      // @TODO: add Entity as first parameter
      $this->processField($field, $op, TRUE);

      // Save the entity.
      $entity->save();
    }
  }

}
