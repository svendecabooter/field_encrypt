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
