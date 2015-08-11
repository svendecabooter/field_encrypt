<?php
/**
 * @file Contains \Drupal\field_encrypt\FieldEncryptProcessEntities
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\encrypt\EncryptService;

/**
 *
 */
class FieldEncryptProcessEntities {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $updateManager;

  protected $encryptService;

  /**
   * Constructs an updates manager instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\encrypt\EncryptService $encrypt_service
   */
  public function __construct(EntityManagerInterface $entity_manager, EncryptService $encrypt_service) {
    $this->entityManager = $entity_manager;
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
      if (!is_callable([$definition, 'get'])){continue;}

      /**
       * @var $storage \Drupal\Core\Field\FieldConfigStorageBase
       */
      $storage = $definition->get('fieldStorage');
      if (is_null($storage)) {continue;}

      $encrypted = $storage->getThirdPartySetting('field_encrypt', 'encrypt', FALSE);

      if ($encrypted) {
        $replacement = '';

        if ($op === 'encrypt') {
          $replacement = $this->encryptService->encrypt($entity->get($name)->value);
        }
        elseif ($op === 'decrypt') {
          $replacement = $this->encryptService->decrypt($entity->get($name)->value);
        }

        // TODO: This code only works for basic fields. It does not correctly work for fields like the body which have markup in them.
        $entity->set($name, $replacement, FALSE);

      }
    }
  }

}
