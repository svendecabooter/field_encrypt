<?php
/**
 * @file
 * Contains \Drupal\pants\FieldEncryptMapBase.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * This class structure mirrors the way blocks are constructed.
 * We have a base class and a plugin interface.
 *
 * @package Drupal\field_encrypt
 */
abstract class FieldEncryptMapBase extends ContextAwarePluginBase implements FieldEncryptMapPluginInterface {

}
