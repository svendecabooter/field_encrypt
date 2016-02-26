<?php
/**
 * @file
 * Contains \Drupal\field_encrypt\Plugin\FieldEncryptionProvider\CoreStrings.
 */

namespace Drupal\field_encrypt\Plugin\FieldEncryptionProvider;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\field_encrypt\Plugin\FieldEncryptionProvider\EncryptProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Field Encryption Provider for core fields with string values.
 *
 * @FieldEncryptionProvider(
 *   id = "core_strings",
 *   label = @Translation("Core string fields"),
 *   field_types = {
 *     "string" = {"value"},
 *     "string_long" = {"value"},
 *     "text" = {"value"},
 *     "text_long" = {"value"},
 *     "text_with_summary" = {"value", "summary"},
 *     "comment" = {"value"},
 *     "email" = {"value"},
 *     "link" = {"uri", "title"}
 *   }
 * )
 */
class CoreStrings extends EncryptProviderBase implements ContainerFactoryPluginInterface {

//  /**
//   * {@inheritdoc}
//   */
//  public function __construct(array $configuration, $plugin_id, $plugin_definition, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
//    // Call parent construct method.
//    parent::__construct($configuration, $plugin_id, $plugin_definition, $encryption_profile_manager, $encrypt_service);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
//    return new static(
//      $configuration,
//      $plugin_id,
//      $plugin_definition,
//      $container->get('encrypt.encryption_profile.manager'),
//      // We inject our service here.
//      $container->get('encryption')
//    );
//  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($value, $settings) {
    if (!empty($settings) && isset($settings['encryption_profile'])) {
      $encryption_profile = $this->encryptionProfileManager->getEncryptionProfile($settings['encryption_profile']);
      if ($encryption_profile) {
        return base64_encode($this->encryptService->encrypt($value, $encryption_profile));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($value, $settings) {
    if (!empty($settings) && isset($settings['encryption_profile'])) {
      $encryption_profile = $this->encryptionProfileManager->getEncryptionProfile($settings['encryption_profile']);
      if ($encryption_profile) {
        return $this->encryptService->decrypt(base64_decode($value), $encryption_profile);
      }
    }
  }

}
