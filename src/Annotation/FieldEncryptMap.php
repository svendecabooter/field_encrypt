<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\Annotation\FieldEncryptMap.
 */

namespace Drupal\field_encrypt\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldEncryptMap annotation object.
 *
 * @Annotation
 */
class FieldEncryptMap extends Plugin {
  /**
   * The machine name for the plugin.
   */
  public $id;

  /**
   * The human-readable name of the pants type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}