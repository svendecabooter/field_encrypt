<?php
/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptProcessEntities.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field_encrypt\Annotation\FieldEncryptionProvider;
use Drupal\field_encrypt\FieldEncryptionProviderPluginInterface;

/**
 * Service class to process entities and fields for encryption.
 */
class FieldEncryptProcessEntities {

  /**
   * A flag to disable decryption if we are in the process of updating stored
   * fields.
   */
  protected $updatingStoredField = 'none';

  /**
   * Field Encrypt Map Plugin Manager
   *
   * @var \Drupal\field_encrypt\FieldEncryptionProviderPluginManager
   */
  protected $providerPluginManager;

  /**
   * Query Factory
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Entity Manager
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * @param \Drupal\field_encrypt\FieldEncryptionProviderPluginManager $provider_plugin_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   */
  public function __construct(FieldEncryptionProviderPluginManager $provider_plugin_manager, QueryFactory $query_factory, EntityManager $entity_manager) {
    $this->providerPluginManager = $provider_plugin_manager;
    $this->queryFactory = $query_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * Encrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function encrypt_entity(\Drupal\Core\Entity\ContentEntityInterface $entity) {
    $this->process_entity($entity, 'encrypt');
  }

  /**
   * Decrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function decrypt_entity(\Drupal\Core\Entity\ContentEntityInterface $entity) {
    $this->process_entity($entity, 'decrypt');
  }

  /**
   * Encrypt or Decrypt a value.
   *
   * @param string $value
   * @param \Drupal\field_encrypt\FieldEncryptionProviderPluginInterface $provider
   * @param array $field_encrypt_settings
   * @param string $op
   * @return string
   *
   * TODO: If we can rely on the encrypt module providing an interface,
   * we can include that here.
   */
  protected function process_value($value = '', FieldEncryptionProviderPluginInterface $provider, $field_encrypt_settings = [], $op = 'encrypt') {
    // Do not modify empty strings.
    if ($value === ''){
      return '';
    }

    if ($op === 'encrypt') {
      return $provider->encrypt($value, $field_encrypt_settings);
    }
    elseif ($op === 'decrypt') {
      return $provider->decrypt($value, $field_encrypt_settings);
    }
    else {
      return '';
    }
  }

  /**
   * Process a field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   * @param string $op
   * @param boolean $force if set, we don't check if encryption is enabled, we process the field anyway. This is used during batch processes.
   */
  protected function process_field(\Drupal\Core\Field\FieldItemListInterface $field, $op = 'encrypt', $force = FALSE) {
    if (!is_callable([$field, 'getFieldDefinition'])){return;}

    /* @var $definition \Drupal\Core\Field\BaseFieldDefinition */
    $definition = $field->getFieldDefinition();

    if (!is_callable([$definition, 'get'])){
      return;
    }

    $field_type = $definition->get('field_type');

    // Filter out fields that do not have a defined encryption provider for
    // their field type.
    $supported_types = $this->providerPluginManager->getSupportedFieldTypes();
    if (!in_array($field_type, $supported_types)) {
      return;
    }

    /* @var $storage \Drupal\Core\Field\FieldConfigStorageBase */
    $storage = $definition->get('fieldStorage');
    if (is_null($storage)) {
      return;
    }

    /**
     * If we are using the force flag, we always proceed.
     * The force flag is used when we are updating stored fields.
     */
    if (!$force) {
      /**
       * Check if we are updating the field, in that case, skip it now (during
       * the initial entity load.
       */
      if ($this->updatingStoredField === $definition->get('field_name')) {
        return;
      }

      // Check if the field is encrypted.
      $encrypted = $storage->getThirdPartySetting('field_encrypt', 'encrypt', FALSE);
      if (!$encrypted) {
        return;
      }
    }

    /**
     * @var $field \Drupal\Core\Field\FieldItemList
     */
    $field_value = $field->getValue();
    $provider_id = $storage->getThirdPartySetting('field_encrypt', 'provider_id', FALSE);
    // @TODO: improve --> getInstance($options)?
    // Check if there is a provider set for this field.
    if ($provider_id) {
      $field_encryption_provider = $this->providerPluginManager->createInstance($provider_id);

      if ($field_encryption_provider) {
        $field_encrypt_settings = $storage->getThirdPartySetting('field_encrypt', 'provider_settings', []);
        // Process the field with the given encryption provider.
        $properties = $field_encryption_provider->getPropertiesToEncrypt($field_type);
        foreach ($field_value as &$value) {
          // Process each of the field properties that exist.
          foreach ($properties as $property_name) {
            if(isset($value[$property_name])){
              $value[$property_name] = $this->process_value($value[$property_name], $field_encryption_provider, $field_encrypt_settings, $op);
            }
          }
        }
        // Set the new value.
        // We don't need to update the entity because setValue does that already.
        $field->setValue($field_value);
      }
    }
  }

  /**
   * Process an entity to either encrypt or decrypt its fields.
   *
   * Both processes are very similar, so we bundle the field processing part.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $op
   */
  protected function process_entity(\Drupal\Core\Entity\ContentEntityInterface $entity, $op = 'encrypt') {
    // Make sure we can get fields.
    if (!is_callable([$entity, 'getFields'])){
      return;
    }

    foreach ($entity->getFields() as $field){
      $this->process_field($field, $op);
    }
  }

  /**
   * Encrypt stored fields.
   *
   * This is performed when field storage settings are updated.
   *
   * @param $entity_type
   * @param $field_name
   */
  public function encrypt_stored_field($entity_type, $field_name) {
    $this->update_stored_field($entity_type, $field_name, 'encrypt');
  }

  /**
   * Decrypt stored fields.
   *
   * This is performed when field storage settings are updated.
   *
   * @param $entity_type
   * @param $field_name
   */
  public function decrypt_stored_field($entity_type, $field_name) {
    $this->update_stored_field($entity_type, $field_name, 'decrypt');
  }

  /**
   * Update a field. This is used to process fields when the storage
   * configuration changes.
   *
   * @param $entity_type
   * @param $field_name
   * @param string $op (encrypt / decrypt)
   */
  protected function update_stored_field($entity_type, $field_name, $op = 'encrypt') {
    /**
     * Before we load entities, we have to disable the encryption setting.
     * Otherwise, the act of loading the entity triggers an improper decryption
     * Which messes up the batch encryption.
     */
    $this->updatingStoredField = $field_name;

    /**
     * @var $query \Drupal\Core\Entity\Query\QueryInterface
     */
    $query = $this->queryFactory->get($entity_type);

    // The field is present.
    $query->exists($field_name);
    $query->allRevisions();

    $entity_ids = $query->execute();

    // Load entities.
    /**
     * @var $entity_storage \Drupal\Core\Entity\ContentEntityStorageBase
     */
    $entity_storage = $this->entityManager->getStorage($entity_type);

    foreach($entity_ids as $revision_id => $entity_id) {
      /**
       * @var $entity \Drupal\Core\Entity\Entity
       */
      $entity = $entity_storage->loadRevision($revision_id);

      /**
       * @var $field \Drupal\Core\Field\FieldItemList
       */
      $field = $entity->get($field_name);
      $this->process_field($field, $op, TRUE);

      // Save the entity.
      $entity->save();
    }
  }

}
