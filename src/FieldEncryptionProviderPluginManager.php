<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptionProviderPluginManager.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manager class for FieldEncryptionProvider plugins.
 */
class FieldEncryptionProviderPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldEncryptionProvider', $namespaces, $module_handler, 'Drupal\field_encrypt\FieldEncryptionProviderPluginInterface', 'Drupal\field_encrypt\Annotation\FieldEncryptionProvider');

    $this->alterInfo('field_encryption_provider');
    $this->setCacheBackend($cache_backend, 'field_encryption_provider');
  }


  /**
   * Get a list of field types that are supported for field encryption.
   *
   * @return array
   */
  public function getSupportedFieldTypes() {
    $supported_types = [];
    $definitions = $this->getDefinitions();
    foreach ($definitions as $provider => $definition) {
      if (isset($definition['field_types'])) {
        $supported_types = array_merge(array_keys($definition['field_types']), $supported_types);
      }
    }
    return $supported_types;
  }

  /**
   * Get plugin provider(s) that can be used for a given field and property.
   *
   * @TODO: create interface for class & document
   * @param $field_type
   *
   * @return array
   */
  public function getProvidersForFieldType($field_type) {
    $definitions = $this->getDefinitions();
    return [];
  }

}
