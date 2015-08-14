<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptMapPluginManager.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class FieldEncryptMapPluginManager
 * @package Drupal\field_encrypt
 */
class FieldEncryptMapPluginManager extends DefaultPluginManager {

  /**
   * This is copied mostly from the DefaultPluginManager.
   *
   * @param \Traversable $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldEncryptMap', $namespaces, $module_handler, 'Drupal\field_encrypt\FieldEncryptMapPluginInterface', 'Drupal\field_encrypt\Annotation\FieldEncryptMap');

    $this->alterInfo('field_encrypt_map');
    $this->setCacheBackend($cache_backend, 'field_encrypt_map');
  }

}
