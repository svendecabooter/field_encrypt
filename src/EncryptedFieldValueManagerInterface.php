<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\EncryptedFieldValueManagerInterface.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface EncryptedFieldValueManagerInterface.
 *
 * @package Drupal\field_encrypt
 */
interface EncryptedFieldValueManagerInterface {

  /**
   * Save an encrypted field value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param string $field_name
   *   The field name to save.
   * @param string $property
   *   The field property to save.
   * @param string $encrypted_value
   *   The encrypted value to save.
   */
  public function saveEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $property, $encrypted_value);

  /**
   * Get an encrypted field value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param string $field_name
   *   The field name to retrieve.
   * @param string $property
   *   The field property to retrieve.
   *
   * @return string
   *   The encrypted field value.
   */
  public function getEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $property);

  /**
   * Delete encrypted field values for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be deleted.
   */
  public function deleteEncryptedFieldValues(ContentEntityInterface $entity);

  /**
   * Delete encrypted field values for a given field.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $field_name
   *   The field name to delete encrypted values for.
   */
  public function deleteEncryptedFieldValuesForField($entity_type, $field_name);
}
