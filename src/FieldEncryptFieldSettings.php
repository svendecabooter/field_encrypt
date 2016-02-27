<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\FieldEncryptFieldSettings.
 */

namespace Drupal\field_encrypt;

use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Service class to provide extra settings to field storage.
 */
class FieldEncryptFieldSettings {


  /**
   * The EncryptionProfile manager service.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface
   *   The EncryptionProfile manager service.
   */
  public function __construct(EncryptionProfileManagerInterface $encryption_profile_manager) {
    $this->encryptionProfileManager = $encryption_profile_manager;
  }

  /**
   * Get a list of field properties that can be selected for encryption.
   *
   * @TODO: add hook to allow modules to limit available properties?
   *
   * @param \Drupal\field\Entity\FieldStorageConfig $field
   *   The field storage config entity.
   *
   * @return array
   *   An array of field properties.
   */
  public function getFieldProperties(FieldStorageConfig $field) {
    $properties = [];
    $definitions = $field->getPropertyDefinitions();
    foreach ($definitions as $property => $definition) {
      $properties[$property] = $definition->getLabel();
    }
    return $properties;
  }

  /**
   * Get a list of reasonable default field properties eligible for encryption.
   *
   * @TODO: make this configurable via settings or hook?
   *
   * @return array
   *   List of properties to select by default.
   */
  public function getDefaultProperties() {
    return ["value", "summary", "title", "uri"];
  }

  /**
   * Get a list of encryption profiles
   *
   * @return array
   *   List of encryption profile names keyed by their ID.
   */
  public function getEncryptionProfiles() {
    return $this->encryptionProfileManager->getEncryptionProfileNamesAsOptions();
  }

}
