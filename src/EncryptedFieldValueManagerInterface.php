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
   * Create an encrypted field value, or update an existing one.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param string $field_name
   *   The field name to save.
   * @param int $delta
   *   The field delta to save.
   * @param string $property
   *   The field property to save.
   * @param string $encrypted_value
   *   The encrypted value to save.
   *
   * @return \Drupal\field_encrypt\Entity\EncryptedFieldValueInterface
   *   The created EncryptedFieldValue entity.
   */
  public function createEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property, $encrypted_value);

  /**
   * Save encrypted field values and link them to their parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to save EncryptedFieldValue entities for.
   */
  public function saveEncryptedFieldValues(ContentEntityInterface $entity);

  /**
   * Get an encrypted field value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param string $field_name
   *   The field name to retrieve.
   * @param int $delta
   *   The field delta to retrieve.
   * @param string $property
   *   The field property to retrieve.
   *
   * @return string
   *   The encrypted field value.
   */
  public function getEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property);

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
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the field to be deleted.
   * @param string $field_name
   *   The field name to delete encrypted values for.
   */
  public function deleteEncryptedFieldValuesForField(ContentEntityInterface $entity, $field_name);

}
