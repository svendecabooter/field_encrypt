<?php
/**
 * @file
 * Contains \Drupal\field_encrypt\Entity\EncryptedFieldValue.
 */

namespace Drupal\field_encrypt\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines the EncryptedFieldValue entity.
 *
 * @ingroup field_encrypt
 *
 * @ContentEntityType(
 *   id = "encrypted_field_value",
 *   label = @Translation("Encrypted field value"),
 *   base_table = "encrypted_field",
 *   render_cache = FALSE,
 *   admin_permission = "administer encrypted_field_value entity",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 * )
 *
 *   @TODO: check if the following settings would make sense
 *   entity_keys -->
 *   "revision" = "revision_id",
 *
 *   data_table = "encrypted_field_data",
 *   revision_table = "encrypted_field_revision",
 *   revision_data_table = "encrypted_field_revision_data",
 *   translatable = TRUE,
 *   handlers = {
 *     "storage" = "Drupal\field_encrypt\EncryptedFieldValueStorage",
 *     "storage_schema" = "Drupal\field_encrypt\EncryptedFieldValueStorageSchema",
 *     "translation" = "Drupal\field_encrypt\EncryptedFieldValueTranslationHandler"
 *   }
 *
 */
class EncryptedFieldValue extends ContentEntityBase implements EncryptedFieldValueInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the EncryptedFieldValue entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the EncryptedFieldValue entity.'))
      ->setReadOnly(TRUE);

    // @TODO: field language supports
//    $fields['langcode'] = BaseFieldDefinition::create('language')
//      ->setLabel(t('Language code'))
//      ->setDescription(t('The language code of EncryptedFieldValue entity.'));

    // @TODO: revision id

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type for which to store the encrypted value.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity for which to store the encrypted value.'))
      ->setRequired(TRUE);

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field name'))
      ->setDescription(t('The field name for which to store the encrypted value.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['field_property'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field property'))
      ->setDescription(t('The field property for which to store the encrypted value.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['encrypted_value'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Encrypted value'))
      ->setDescription(t('The encrypted value'));
      //->setTranslatable(TRUE)

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptedValue() {
    return $this->get('encrypted_value')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncryptedValue($value) {
    $this->set('encrypted_value', $value);
  }

}
