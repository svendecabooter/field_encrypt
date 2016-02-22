<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\Annotation\FieldEncryptionProvider.
 */

namespace Drupal\field_encrypt\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldEncryptionProvider annotation object.
 *
 * @Annotation
 */
class FieldEncryptionProvider extends Plugin {
  /**
   * The machine name for the plugin.
   */
  public $id;

  /**
   * The human-readable name for the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * Defines a mapping of field types and their properties to be encrypted.
   *
   * Return a multidimensional array. It contains entries with the field
   * type as array key, and an array of field properties as value.
   *
   * @see \Drupal\field_encrypt\Plugin\FieldEncryptProvider\CoreStrings.
   */
  public $field_types = [];

}
