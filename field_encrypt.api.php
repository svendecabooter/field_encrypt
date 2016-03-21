<?php
/**
 * @file
 * Hooks for Field Encrypt module.
 */

/**
 * Hook to alter values that will be stored in the unencrypted field storage.
 *
 * When a field gets encrypted, the unencrypted field storage gets the value
 * "[ENCRYPTED]" by default, to indicate there is data for the field, but it's
 * stored encrypted. For some field types this value would not be accepted, so
 * this hook makes it possible to store an alternative value for specific field
 * types.
 *
 * @param string &$unencrypted_storage_value
 *   The unencrypted field storage value to alter.
 * @param array $context
 *   An associative array with the following values:
 *   - "entity": \Drupal\Core\Entity\ContentEntityInterface
 *     The entity containing the field.
 *   - "field": \Drupal\Core\Field\FieldItemListInterface
 *     The field for which to store the unencrypted storage value.
 *   - "property": string
 *     The property for which to store the unencrypted storage value.
 */
function hook_field_encrypt_unencrypted_storage_value_alter(&$unencrypted_storage_value, $context) {
  $entity = $context['entity'];
  $field = $context['field'];
  $property = $context['property'];

  if ($entity->getEntityTypeId() == "node") {
    $field_type = $field->getFieldDefinition()->getType();
    if ($field_type == "text_with_summary") {
      if ($property == "summary") {
        $unencrypted_storage_value = "[ENCRYPTED SUMMARY]";
      }
    }
  }
}
