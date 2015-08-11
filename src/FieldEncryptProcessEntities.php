<?php
/**
 * @file Contains \Drupal\field_encrypt\FieldEncryptProcessEntities
 */

namespace Drupal\field_encrypt;

use Drupal\encrypt\EncryptService;

/**
 *
 */
class FieldEncryptProcessEntities {

  /**
   * Filter (blacklist) field types that don't work with encryption.
   */
  public $blacklist_fields = [
    'image',
    'boolean',
    'integer',
    'entity_reference',
  ];

  /**
   * Encryption service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryptService;

  /**
   * @param \Drupal\encrypt\EncryptService $encrypt_service
   */
  public function __construct(EncryptService $encrypt_service) {
    $this->encryptService = $encrypt_service;
  }

  /**
   * Encrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function encrypt_fields(\Drupal\Core\Entity\ContentEntityInterface $entity) {
    $this->process_fields($entity, 'encrypt');
  }

  /**
   * Decrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function decrypt_fields(\Drupal\Core\Entity\ContentEntityInterface $entity) {
    $this->process_fields($entity, 'decrypt');
  }

  /**
   * Encrypt or Decrypt a value.
   *
   * @param string $value
   * @param string $op
   * @return string
   */
  protected function process_value($value = '', $op = 'encrypt') {
    if ($op === 'encrypt') {
      return $this->encryptService->encrypt($value);
    }
    elseif ($op === 'decrypt') {
      return $this->encryptService->decrypt($value);
    }
    else {
      return '';
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
  protected function process_fields(\Drupal\Core\Entity\ContentEntityInterface $entity, $op = 'encrypt') {
    // Make sure we can get field definitions.
    if (!is_callable([$entity, 'getFieldDefinitions'])){return;}
    if (!is_callable([$entity, 'getFields'])){return;}
    //$entity->getFieldDefinitions();
    $entity->getFields();

    /**
     * @var $definition \Drupal\Core\Field\FieldItemBase
     */
    foreach($entity->getFieldDefinitions() as $name => $definition) {
      if (!is_callable([$definition, 'get'])){
        continue;
      }

      /**
       * Filter (blacklist) field types that don't work with encryption.
       */
      if (in_array($definition->get('field_type'),$this->blacklist_fields)) {
        continue;
      }

      /**
       * @var $storage \Drupal\Core\Field\FieldConfigStorageBase
       */
      $storage = $definition->get('fieldStorage');
      if (is_null($storage)) {
        continue;
      }

      // Check if the field is encrypted.
      $encrypted = $storage->getThirdPartySetting('field_encrypt', 'encrypt', FALSE);
      if (!$encrypted) {
        continue;
      }

      // Check if we are using a multivalue field or not.
      $multi_value_field = $storage->get('cardinality') !== 1;
      if ($multi_value_field) {
        /**
         * @var $field \Drupal\Core\Field\FieldItemList
         */
        $field = $entity->get($name);
        $field_value = $field->getValue();
        foreach($field_value as &$value) {
          $value['value'] = $this->process_value($value['value'], $op);
        }
        // Set the new value.
        // We don't need to update the entity because the field setValue does that already.
        $field->setValue($field_value);
      }
      else {
        // Single value field.
        $replacement = $this->process_value($entity->get($name)->value, $op);
        $entity->set($name, $replacement);
        unset($replacement);
      }
    }
  }

}
