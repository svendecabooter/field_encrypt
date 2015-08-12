<?php
/**
 * @file
 * Contains \Drupal\field_encrypt\Plugin\FieldEncryptMap\CoreStrings.
 */

namespace Drupal\field_encrypt\Plugin\FieldEncryptMap;

use Drupal\field_encrypt\FieldEncryptMapBase;

/**
 * Provides a Field Encrypt Map.
 *
 * @FieldEncryptMap(
 *   id = "corestrings",
 *   label = @Translation("Core Strings")
 * )
 */
class CoreStrings extends FieldEncryptMapBase {

  /**
   * @var $stringService
   */
  public $stringService;

  public function __construct($configuration, $plugin_id, $plugin_definitions) {
    parent::__construct($configuration, $plugin_id, $plugin_definitions);

    // The Encrypt modules string encryption service.
    $this->stringService = $this->container->get('encryption');
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
