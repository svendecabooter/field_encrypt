<?php
/**
 * @file
 * Contains
 * \Drupal\field_encrypt\Plugin\FieldEncryptionProvider\EncryptProviderBase.
 */

namespace Drupal\field_encrypt\Plugin\FieldEncryptionProvider;

use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field_encrypt\FieldEncryptionProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;

/**
 * A base FieldEncryptionProvider class that integrates with Encrypt service.
 *
 * Use this base class when defining FieldEncryptionProvider plugins that rely
 * on the Encrypt module EncryptService.
 */
abstract class EncryptProviderBase extends FieldEncryptionProviderBase {

  /**
   * The EncryptionProfileManager service.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryptService;

  /**
   * {@inheritdoc}
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    $this->encryptionProfileManager = $encryption_profile_manager;
    $this->encryptService = $encrypt_service;
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
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'encryption_profile' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['encryption_profile'] = array(
      '#type' => 'select',
      '#title' => $this->t('Encryption profile'),
      '#description' => $this->t('Select the encryption profile to use for encrypting this field.'),
      '#options' => $this->encryptionProfileManager->getEncryptionProfileNamesAsOptions(),
      '#default_value' => $this->getConfiguration()['encryption_profile'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
