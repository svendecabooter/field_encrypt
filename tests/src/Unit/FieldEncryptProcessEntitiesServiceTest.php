<?php

/**
 * @file
 *
 * Contains \Drupal\Tests\field_encrypt\Unit\FieldEncryptProcessEntitiesServiceTest.
 */

namespace Drupal\Tests\field_encrypt\Unit;

use Drupal\field_encrypt\FieldEncryptMapPluginManager;
use Drupal\field_encrypt\FieldEncryptProcessEntities;
use Drupal\Tests\UnitTestCase;

/**
 * Unit Tests for the FieldEncryptProcessEntities service.
 *
 * @group pants
 */
class FieldEncryptProcessEntitiesServiceTest extends UnitTestCase {

  /**
   * The tested service.
   *
   * @var \Drupal\field_encrypt\FieldEncryptProcessEntities
   */
  public $service;

  /**
   * A test value so we don't have to mock up the whole field item list system.
   */
  public $testValue = [
    ['value' => 'world']
  ];

  /**
   * A test map that would be provided by plugins.
   */
  public $testMap = [
    'text' => [
      'value' => 'mock.service',
    ],
  ];

  /**
   * Our test field.
   */
  public $field;

  /**
   * Our test entity.
   */
  public $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // The goal of this setup is to provide a test entity with a test field.

    // We first have to mock up the dependencies of our service.

    // Plugin Manager mock.
    $plugin_manager = $this->getMockBuilder('\Drupal\field_encrypt\FieldEncryptMapPluginManager')
      ->disableOriginalConstructor()
      ->setMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    $plugin_manager->expects($this->any())->method('getDefinitions')
      ->willReturn(['mockMap' => []]);

    // Field Encrypt Map Plugin mock.
    $field_encrypt_map = $this->getMockBuilder('\Drupal\pants\FieldEncryptMapBase')
      ->setMethods(['getMap'])
      ->getMock();

    $field_encrypt_map->expects($this->any())->method('getMap')
      ->willReturn($this->testMap);

    $plugin_manager->expects($this->any())->method('createInstance')
      ->willReturn($field_encrypt_map);

    // Query Factory mock.
    $query_factory = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Entity Manager mock.
    $entity_manager = $this->getMockBuilder('\Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    // An Encryption service mock.
    $encrypt_service = $this->getMockBuilder('stdClass')
      ->setMethods(['encrypt', 'decrypt'])
      ->getMock();

    $encrypt_service->expects($this->any())->method('encrypt')
      ->will($this->returnCallback(
        function ($value) {
          return 'encrypted value';
        }
      ));

    $encrypt_service->expects($this->any())->method('decrypt')
      ->will($this->returnCallback(
        function ($value) {
          return 'decrypted value';
        }
      ));

    // Service Container mock that will return our single mock service.
    $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
      ->getMock();

    $container->expects($this->any())->method('get')
      ->willReturn($encrypt_service);

    // Store an instance of our service.
    $this->service = new FieldEncryptProcessEntities($plugin_manager, $query_factory, $entity_manager, $container);

    /**
     * Generate a test field.
     *
     * Our system uses the first storage system, so we need to mock that out too.
     */
    // Field Storage mock.
    $fieldStorage = $this->getMockBuilder('\Drupal\Core\Field\FieldConfigStorageBase')
      ->disableOriginalConstructor()
      ->setMethods(['getThirdPartySetting'])
      ->getMock();

    $fieldStorage->expects($this->any())->method('getThirdPartySetting')
      // We will always report that the field is encrypted.
      ->willReturn(TRUE);

    // Field Definition mock.
    $fieldDefinition = $this->getMockBuilder('\Drupal\Core\Field\BaseFieldDefinition')
      ->setMethods(['get'])
      ->getMock();

    $fieldDefinition->expects($this->any())->method('get')
      ->willReturnMap([
        ['field_type', 'text'],
        ['fieldStorage', $fieldStorage],
      ]);

    // Test field.
    $this->field = $this->getMockBuilder('\Drupal\Core\Field\FieldItemList')
      ->disableOriginalConstructor()
      ->setMethods(['getFieldDefinition', 'getValue', 'setValue'])
      ->getMock();

    $this->field->expects($this->any())->method('getFieldDefinition')
      ->willReturn($fieldDefinition);

    $this->field->expects($this->any())->method('getValue')
      ->will($this->returnCallback(
        function() {
          return $this->testValue;
        }
      ));

    $this->field->expects($this->any())->method('setValue')
      ->will($this->returnCallback(
        function($value) {
          $this->testValue = $value;
        }
      ));

    // Create a test entity with our test field.
    $this->entity = $this->getMock('\Drupal\Core\Entity\ContentEntityInterface');
    $this->entity->expects($this->any())->method('getFields')
      ->willReturn([$this->field]);
  }

  /**
   * Test the encrypt_entity() function.
   */
  public function testEncryptEntity() {
    $this->service->encrypt_entity($this->entity);

    $this->assertEquals('encrypted value', $this->field->getValue()[0]['value']);
  }

  /**
   * Test the decrypt_entity() function.
   */
  public function testDecryptEntity() {
    $this->service->decrypt_entity($this->entity);

    $this->assertEquals('decrypted value', $this->field->getValue()[0]['value']);
  }

}
