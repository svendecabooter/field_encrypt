<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptProcessEntities.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   An entity manager service.
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   *   The encryption service.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   The encryption profile manager.
   * @param \Drupal\field_encrypt\EncryptedFieldValueManagerInterface $encrypted_field_value_manager
   *   The EncryptedFieldValue entity manager.
   */
  public function __construct(QueryFactory $query_factory, EntityTypeManagerInterface $entity_manager, EncryptServiceInterface $encrypt_service, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptedFieldValueManagerInterface $encrypted_field_value_manager) {
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
   * @param bool $update
   *   Whether a batch re-encryption update is in progress.
   * @param array $original_encryption_settings
   *   Original encryption settings - used when updating in batch.
   */
  protected function processField(ContentEntityInterface $entity, FieldItemListInterface $field, $op = 'encrypt', $update = FALSE, $original_encryption_settings = []) {
    // Check if field is properly set up and allows encryption.
    if (!$update && !$this->checkField($field)) {
      return;
    }

    /* @var $definition \Drupal\Core\Field\BaseFieldDefinition */
    $definition = $field->getFieldDefinition();
    /* @var $storage \Drupal\Core\Field\FieldConfigStorageBase */
    $storage = $definition->get('fieldStorage');

    // If we are using the update flag, we always proceed.
    // The update flag is used when we are updating stored fields.
    if (!$update) {
      // Check if we are updating the field, in that case, skip it now (during
      // the initial entity load.
      if ($op == "decrypt" && $this->updatingStoredField === $definition->get('field_name')) {
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
    // Get encryption settings from storage, unless we are batch updating.
    if (isset($original_encryption_settings['encryption_profile'])) {
      $encryption_profile_id = $original_encryption_settings['encryption_profile'];
      $properties = $original_encryption_settings['properties'];
    }
    else {
      $encryption_profile_id = $storage->getThirdPartySetting('field_encrypt', 'encryption_profile', []);
      $properties = $storage->getThirdPartySetting('field_encrypt', 'properties', []);
    }
    $encryption_profile = $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_id);

    // Process the field with the given encryption provider.
    foreach ($field_value as $delta => &$value) {
      // Process each of the field properties that exist.
      foreach ($properties as $property_name) {
        if (isset($value[$property_name])) {
          $value[$property_name] = $this->processValue($entity, $field, $delta, $property_name, $encryption_profile, $value[$property_name], $op);
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
   * @param int $delta
   *   The field delta.
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
  protected function processValue(ContentEntityInterface $entity, FieldItemListInterface $field, $delta, $property_name, EncryptionProfileInterface $encryption_profile, $value = '', $op = 'encrypt') {
    // Do not modify empty strings.
    if ($value === '') {
      return '';
    }

    if ($op === 'encrypt') {
      // Encrypt property value.
      $processed_value = base64_encode($this->encryptService->encrypt($value, $encryption_profile));
      // Save encrypted value in EncryptedFieldValue entity.
      $this->encryptedFieldValueManager->createEncryptedFieldValue($entity, $field->getName(), $delta, $property_name, $processed_value);
      // Return value to store for unencrypted property.
      // We can't set this to NULL, because then the field values are not saved,
      // so we can't replace them with their unencrypted value on load.
      $unencrypted_storage_value = '[ENCRYPTED]';
      $context = [
        "entity" => $entity,
        "field" => $field,
        "property" => $property_name,
      ];
      \Drupal::modulehandler()->alter('field_encrypt_unencrypted_storage_value', $unencrypted_storage_value, $context);
      return $unencrypted_storage_value;

    }
    elseif ($op === 'decrypt') {
      // Get encrypted value from EncryptedFieldValue entity.
      if ($encrypted_value = $this->encryptedFieldValueManager->getEncryptedFieldValue($entity, $field->getName(), $delta, $property_name)) {
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
  public function updateStoredField($field_name, $field_entity_type, $original_encryption_settings, $entity_id) {
    // Before we load entities, we have to disable the encryption setting.
    // Otherwise, the act of loading the entity triggers an improper decryption
    // which messes up the batch encryption.
    $this->updatingStoredField = $field_name;

    $entity_storage = $this->entityManager->getStorage($field_entity_type);
    // Check if entity allows revisions.
    if ($this->entityManager->getDefinition($field_entity_type)->hasKey('revision')) {
      $entity = $entity_storage->loadRevision($entity_id);
    }
    else {
      $entity = $entity_storage->load($entity_id);
    }

    // Process all language variants of the entity.
    $languages = $entity->getTranslationLanguages();
    foreach ($languages as $language) {
      $entity = $entity->getTranslation($language->getId());
      $field = $entity->get($field_name);
      // Decrypt with original settings, if available.
      if (!empty($original_encryption_settings)) {
        $this->processField($entity, $field, 'decrypt', TRUE, $original_encryption_settings);
      }
    }
    $entity->save();

    // Deactivate encryption if field is no longer encrypted.
    if (!$this->checkField($field)) {
      $this->encryptedFieldValueManager->deleteEntityEncryptedFieldValuesForField($entity, $field_name);
    }
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function entitySetCacheTags(ContentEntityInterface $entity, &$build) {
    foreach ($entity->getFields() as $field) {
      if ($this->checkField($field)) {
        /* @var $definition \Drupal\Core\Field\BaseFieldDefinition */
        $definition = $field->getFieldDefinition();
        /* @var $storage \Drupal\Core\Field\FieldConfigStorageBase */
        $storage = $definition->get('fieldStorage');

        // If cache_exclude is set, set caching max-age to 0.
        if ($storage->getThirdPartySetting('field_encrypt', 'cache_exclude', TRUE) == TRUE) {
          $build[$field->getName()]['#cache']['max-age'] = 0;
        }
      }
    }
  }

}
