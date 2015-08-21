<?php
/**
 * @file
 * Contains \Drupal\field_encrypt\Plugin\FieldEncryptMap\CoreStrings.
 */

namespace Drupal\field_encrypt\Plugin\FieldEncryptMap;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field_encrypt\FieldEncryptMapBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Field Encrypt Map.
 *
 * @FieldEncryptMap(
 *   id = "core_strings",
 *   label = @Translation("Core Strings")
 * )
 */
class CoreStrings extends FieldEncryptMapBase implements ContainerFactoryPluginInterface {

  /**
   * @var $stringService
   *
   * TODO: If we can rely on the encrypt module providing an interface,
   * we can include that here.
   */
  public $stringService;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param $encryption
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $encryption) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->stringService = $encryption;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      // We inject our service here.
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMap() {
    return [
      'string' => [
        'value' => $this->stringService,
      ],
      'text' => [
        'value' => $this->stringService,
      ],
      'text_with_summary' => [
        'value' => $this->stringService,
        'summary' => $this->stringService,
      ],
      'comment' => [
        'value' => $this->stringService,
      ],
      'email' => [
        'value' => $this->stringService,
      ],
      'link' => [
        'uri' => $this->stringService,
        'title' => $this->stringService,
      ],
    ];
  }
}
