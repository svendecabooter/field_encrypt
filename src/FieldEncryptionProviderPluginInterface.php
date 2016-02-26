<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptionProviderPluginInterface.
 */

namespace Drupal\field_encrypt;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * This interface describes how field_encrypt plugins should be structured.
 *
 * @package Drupal\field_encrypt
 */
interface FieldEncryptionProviderPluginInterface extends ConfigurablePluginInterface, ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * @param $value
   * @param $settings
   * @return mixed
   */
  public function decrypt($value, $settings);

  /**
   * @param $value
   * @param $settings
   * @return mixed
   */
  public function encrypt($value, $settings);

}
