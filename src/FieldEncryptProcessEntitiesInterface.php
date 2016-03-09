<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptProcessEntitiesInterface.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\ContentEntityInterface;


/**
 * Interface for service class to process entities and fields for encryption.
 */
interface FieldEncryptProcessEntitiesInterface {

  /**
   * Check if entity has encrypted fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if entity has encrypted fields, FALSE if not.
   */
  public function entityHasEncryptedFields(ContentEntityInterface $entity);

  /**
   * Encrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to encrypt fields on.
   */
  public function encryptEntity(ContentEntityInterface $entity);

  /**
   * Decrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to decrypt fields on.
   */
  public function decryptEntity(ContentEntityInterface $entity);

  /**
   * Encrypt stored fields.
   *
   * This is performed when field storage settings are updated.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The name of the field to encrypt.
   */
  public function encryptStoredField($entity_type, $field_name);

  /**
   * Decrypt stored fields.
   *
   * This is performed when field storage settings are updated.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The name of the field to decrypt.
   */
  public function decryptStoredField($entity_type, $field_name);

}
