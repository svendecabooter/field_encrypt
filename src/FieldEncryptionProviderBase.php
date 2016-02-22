<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptionProviderBase.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * Provides a base class for FieldEncryptionProvider plugins.
 *
 * @package Drupal\field_encrypt
 */
abstract class FieldEncryptionProviderBase extends ContextAwarePluginBase implements FieldEncryptionProviderPluginInterface {

  /**
   * Get a list of properties that can be encrypted for a given field type.
   *
   * @param string $field_type
   *   The field type to return properties for.
   *
   * @return array
   *   A list of properties for the given field type that allow encryption.
   */
  public function getPropertiesToEncrypt($field_type) {
    $field_type_definition = $this->getPluginDefinition()['field_types'];
    return $field_type_definition[$field_type];
  }

}
