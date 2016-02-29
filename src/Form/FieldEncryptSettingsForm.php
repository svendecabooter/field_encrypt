<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\Form\FieldEncryptSettingsForm.
 */

namespace Drupal\field_encrypt\Form;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Component\Serialization\Yaml;
/**
 * Form builder for the field_encrypt settings admin page.
 */
class FieldEncryptSettingsForm extends ConfigFormBase {

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Constructs a new FieldEncryptSettingsForm.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_plugin_manager) {
    $this->fieldTypePluginManager = $field_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_encrypt_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['field_encrypt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('field_encrypt.settings');
    $default_properties = $config->get('default_properties');

    $test = Yaml::encode($default_properties);

    $form['default_properties'] = array(
      '#type' => 'container',
      '#title' => $this->t('Default properties'),
      '#title_display' => FALSE,
      '#tree' => TRUE,
    );


    // Gather valid field types.
    foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {

      $form['default_properties'][$category] = array(
        '#type' => 'details',
        '#title' => $category,
        '#open' => TRUE,
      );

      foreach ($field_types as $name => $field_type) {
        $field_definition = BaseFieldDefinition::create($field_type['id']);
        $definitions = $field_definition->getPropertyDefinitions();
        $properties = [];
        foreach ($definitions as $property => $definition) {
          $properties[$property] = $property . ' (' . $definition->getLabel() . ' - ' . $definition->getDataType() . ')';
        }

        $form['default_properties'][$category][$name] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('@field_type properties', array('@field_type' => $field_type['label'])),
          '#description' => t('Specify the default properties to encrypt for this field type.'),
          '#options' => $properties,
          '#default_value' => isset($default_properties[$name]) ? $default_properties[$name] : [],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $default_properties = [];
    $form_state->getValue('default_properties');
    $values = $form_state->getValue('default_properties');
    foreach ($values as $category => $field_types) {
      foreach ($field_types as $field_type => $properties) {
        $default_properties[$field_type] = array_keys(array_filter($properties));
      }
    }

    $this->config('field_encrypt.settings')
      ->set('default_properties', $default_properties)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
