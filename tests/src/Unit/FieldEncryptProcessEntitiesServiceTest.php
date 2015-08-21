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
        ['field_name', 'test_field'],
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
      // We use a function here so that the value is calculated at runtime.
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
    $this->entity = $this->getMockBuilder('\Drupal\Core\Entity\ContentEntityInterface')
      ->getMock();

    $this->entity->expects($this->any())->method('getFields')
      ->willReturn([$this->field]);

    $this->entity->expects($this->any())->method('get')
      ->willReturn($this->field);

    $this->entity->expects($this->any())->method('save')
      ->willReturn(NULL);

    /**
     * Now we will create our service and pass it mocks of the necessary dependencies.
     */

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

    // Query mock.
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->getMock();

    $query->expects($this->any())->method('execute')
      ->willReturn([1 => 1]);

    // Query Factory mock.
    $query_factory = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $query_factory->expects($this->any())->method('get')
      ->willReturn($query);

    // Entity storage mock.
    // We are using a node here, but others should work as well.
    $entity_storage = $this->getMockBuilder('\Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_storage->expects($this->any())->method('loadRevision')
      ->willReturn($this->entity);

    // Entity Manager mock.
    $entity_manager = $this->getMockBuilder('\Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_manager->expects($this->any())->method('getStorage')
      ->willReturn($entity_storage);

    // An Encryption service mock.
    $encrypt_service = $this->getMockBuilder('stdClass')
      ->setMethods(['encrypt', 'decrypt'])
      ->getMock();

    $encrypt_service->expects($this->any())->method('encrypt')
      ->willReturn('encrypted value');

    $encrypt_service->expects($this->any())->method('decrypt')
      ->willReturn('decrypted value');

    // Service Container mock that will return our single mock service.
    $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
      ->getMock();

    $container->expects($this->any())->method('get')
      ->willReturn($encrypt_service);

    // Store an instance of our service.
    $this->service = new FieldEncryptProcessEntities($plugin_manager, $query_factory, $entity_manager, $container);
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

  /**
   * Test the encrypt_stored_field() function.
   */
  public function testEncryptStoredField() {
    $this->service->encrypt_stored_field('node', 'test_field');

    $this->assertEquals('encrypted value', $this->field->getValue()[0]['value']);
  }

  /**
   * Test the decrypt_stored_field() function.
   */
  public function testDecryptStoredField() {
    $this->service->decrypt_stored_field('node', 'test_field');

    $this->assertEquals('decrypted value', $this->field->getValue()[0]['value']);
  }

}
