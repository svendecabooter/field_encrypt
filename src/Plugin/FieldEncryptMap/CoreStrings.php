<?php
/**
 * @file
 * Contains \Drupal\field_encrypt\Plugin\FieldEncryptMap\CoreStrings.
 */

namespace Drupal\field_encrypt\Plugin\FieldEncryptMap;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\field_encrypt\FieldEncryptMapEncryptBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Field Encrypt Map.
 *
 * @FieldEncryptMap(
 *   id = "core_strings",
 *   label = @Translation("Core Strings")
 * )
 */
class CoreStrings extends FieldEncryptMapEncryptBase implements ContainerFactoryPluginInterface {

  /**
   * @var $stringService
   *
   * TODO: If we can rely on the encrypt module providing an interface,
   * we can include that here.
   */
  public $stringService;

  // @TODO: document extra parameter
  // @TODO: typehint extra parameter
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EncryptionProfileManagerInterface $encryption_profile_manager, $encryption) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition, $encryption_profile_manager);
    $this->stringService = $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('encrypt.encryption_profile.manager'),
      // We inject our service here.
      $container->get('encryption')
    );
  }

  /**
   * // @TODO
   * {@inheritdoc}
   */
  public function encrypt($value, $settings) {
    if (!empty($settings) && isset($settings['encryption_profile'])) {
      $encryption_profile = $this->encryptionProfileManager->getEncryptionProfile($settings['encryption_profile']);
      if ($encryption_profile) {
        return base64_encode($this->stringService->encrypt($value, $encryption_profile));
      }
    }
  }

  /**
   * // @TODO
   * {@inheritdoc}
   */
  public function decrypt($value, $settings) {
    if (!empty($settings) && isset($settings['encryption_profile'])) {
      $encryption_profile = $this->encryptionProfileManager->getEncryptionProfile($settings['encryption_profile']);
      if ($encryption_profile) {
        return $this->stringService->decrypt(base64_decode($value), $encryption_profile);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMap() {
    return [
      'string' => [
        'value' => $this,
      ],
      'text' => [
        'value' => $this,
      ],
      'text_with_summary' => [
        'value' => $this,
        'summary' => $this,
      ],
      'comment' => [
        'value' => $this,
      ],
      'email' => [
        'value' => $this,
      ],
      'link' => [
        'uri' => $this,
        'title' => $this,
      ],
    ];
  }
}
