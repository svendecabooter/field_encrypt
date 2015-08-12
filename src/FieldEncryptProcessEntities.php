<?php
/**
 * @file Contains \Drupal\field_encrypt\FieldEncryptProcessEntities
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\encrypt\EncryptService;
use Drupal\pants\Annotation\FieldEncryptMap;

/**
 *
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
   * @var \Drupal\field_encrypt\FieldEncryptMapPluginManager
   */
  protected $fieldEncryptMapPluginManager;

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
   *
   */
  protected $fieldEncryptMap;

  /**
   * @param \Drupal\field_encrypt\FieldEncryptMapPluginManager $field_encrypt_map_plugin_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   */
  public function __construct(FieldEncryptMapPluginManager $field_encrypt_map_plugin_manager, QueryFactory $query_factory, EntityManager $entity_manager) {
    $this->fieldEncryptMapPluginManager = $field_encrypt_map_plugin_manager;
    $this->queryFactory = $query_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * Create a map of fields to services using our FieldEncryptMap plugins.
   */
  protected function getFieldEncryptMap() {
    if (!is_array($this->fieldEncryptMap)){
      $this->fieldEncryptMap = [];

      foreach ($this->fieldEncryptMapPluginManager->getDefinitions() as $map_id => $map_info) {
        $this->fieldEncryptMap += $this->fieldEncryptMapPluginManager->createInstance($map_id)->getMap();
      }
    }

    return $this->fieldEncryptMap;
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
   * @param $service
   * @param string $op
   * @return string
   */
  protected function process_value($value = '', $service, $op = 'encrypt') {
    // Do not modify empty strings.
    if ($value === ''){
      return '';
    }

    if ($op === 'encrypt') {
      return $service->encrypt($value);
    }
    elseif ($op === 'decrypt') {
      return $service->decrypt($value);
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

    /**
     * @var $definition \Drupal\Core\Field\BaseFieldDefinition
     */
    $definition = $field->getFieldDefinition();

    if (!is_callable([$definition, 'get'])){
      return;
    }

    $field_type = $definition->get('field_type');

    /**
     * Filter out fields that do not have a defined map.
     */
    if (!in_array($field_type, array_keys($this->getFieldEncryptMap()))) {
      return;
    }

    /**
     * @var $storage \Drupal\Core\Field\FieldConfigStorageBase
     */
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
    foreach($field_value as &$value) {
      // Process each of the sub fields that exits.
      $map = $this->fieldEncryptMap[$field_type];
      foreach($map as $value_name => $service) {
        if(isset($value[$value_name])){
          $value[$value_name] = $this->process_value($value[$value_name], $service, $op);
        }
      }
    }
    // Set the new value.
    // We don't need to update the entity because the field setValue does that already.
    $field->setValue($field_value);
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

    // The field is not null.
    $query->condition($field_name, NULL, '<>');
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
