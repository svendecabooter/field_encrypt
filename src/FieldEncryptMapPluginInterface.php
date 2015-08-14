<?php

/**
 * @file
 * Contains \Drupal\Core\Block\FieldEncryptMapPluginInterface.
 */

namespace Drupal\field_encrypt;

/**
 * This interface describes how field_encrypt plugins should be structured.
 *
 * @package Drupal\field_encrypt
 */
interface FieldEncryptMapPluginInterface {

  /**
   * @return array An array of field types that contains an array of field values and associated encryption services.
   *
   * See \Drupal\field_encrypt\Plugin\FieldEncryptMap\CoreStrings for an example.
   */
  public function getMap();

}
