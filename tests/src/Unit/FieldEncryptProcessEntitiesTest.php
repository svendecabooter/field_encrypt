<?php

/**
 * @file
 * Contains \Drupal\Tests\field_encrypt\Unit\FieldEncryptProcessEntitiesTest.
 */

namespace Drupal\Tests\field_encrypt\Unit;

use Drupal\field_encrypt\FieldEncryptProcessEntities;
use Drupal\Tests\UnitTestCase;

/**
 * Unit Tests for the FieldEncryptProcessEntities service.
 *
 * @ingroup field_encrypt
 *
 * @group field_encrypt
 *
 * @coversDefaultClass \Drupal\field_encrypt\FieldEncryptProcessEntities
 */
class FieldEncryptProcessEntitiesTest extends UnitTestCase {

  /**
   * A mock entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * A mock field.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $field;

  /**
   * A mock query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * A mock entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * A mock encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryptService;

  /**
   * A mock encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * A mock EncryptionProfile.
   *
   * @var \Drupal\encrypt\EncryptionProfileInterface
   */
  protected $encryptionProfile;

  /**
   * A mock EncryptedFieldValue entity manager.
   *
   * @var \Drupal\field_encrypt\EncryptedFieldValueManagerInterface
   */
  protected $encryptedFieldValueManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set up a mock entity.
    $this->entity = $this->getMockBuilder('\Drupal\Core\Entity\ContentEntityInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up language object.
    $language = $this->getMockBuilder('\Drupal\Core\Language\LanguageInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for language.
    $language->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));

    // Set up expectations for entity.
    $this->entity->expects($this->any())
      ->method('getTranslationLanguages')
      ->will($this->returnValue([$language]));
    $this->entity->expects($this->any())
      ->method('getTranslation')
      ->will($this->returnSelf());

    // Set up a mock field.
    $this->field = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock QueryFactory.
    $this->queryFactory = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock EntityTypeManager.
    $this->entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock EncryptService.
    $this->encryptService = $this->getMockBuilder('\Drupal\encrypt\EncryptServiceInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for EncryptService.
    $this->encryptService->expects($this->any())
      ->method('encrypt')
      ->will($this->returnValue('encrypted text'));
    $this->encryptService->expects($this->any())
      ->method('decrypt')
      ->will($this->returnValue('decrypted text'));

    // Set up a mock EncryptionProfileManager.
    $this->encryptionProfileManager = $this->getMockBuilder('\Drupal\encrypt\EncryptionProfileManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->encryptionProfile = $this->getMockBuilder('\Drupal\encrypt\EncryptionProfileInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for EncryptionProfileManager.
    $this->encryptionProfileManager->expects($this->any())
      ->method('getEncryptionProfile')
      ->will($this->returnValue($this->encryptionProfile));

    // Set up a mock EncryptedFieldValueManager.
    $this->encryptedFieldValueManager = $this->getMockBuilder('\Drupal\field_encrypt\EncryptedFieldValueManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Test method entityHasEncryptedFields().
   *
   * @covers ::__construct
   * @covers ::entityHasEncryptedFields
   * @covers ::checkField
   *
   * @dataProvider entityHasEncryptedFieldsDataProvider
   */
  public function testEntityHasEncryptedFields($encrypted, $expected) {
    $definition = $this->getMockBuilder('\Drupal\Core\Field\BaseFieldDefinition')
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this->getMockBuilder('\Drupal\Core\Field\FieldConfigStorageBase')
      ->setMethods(['getThirdPartySetting'])
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for storage.
    $storage->expects($this->once())
      ->method('getThirdPartySetting')
      ->will($this->returnValue($encrypted));

    // Set up expectations for definition.
    $definition->expects($this->once())
      ->method('get')
      ->will($this->returnValue($storage));

    // Set up expectations for field.
    $this->field->expects($this->once())
      ->method('getFieldDefinition')
      ->will($this->returnValue($definition));

    // Set up expectations for entity.
    $this->entity->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue([$this->field]));

    $service = new FieldEncryptProcessEntities(
      $this->queryFactory,
      $this->entityManager,
      $this->encryptService,
      $this->encryptionProfileManager,
      $this->encryptedFieldValueManager
    );
    $return = $service->entityHasEncryptedFields($this->entity);
    $this->assertEquals($expected, $return);
  }

  /**
   * Data provider for testEntityHasEncryptedFields method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function entityHasEncryptedFieldsDataProvider() {
    return [
      'encrypted_fields' => [TRUE, TRUE],
      'no_encrypted_fields' => [FALSE, FALSE],
    ];
  }

  /**
   * Tests the encryptEntity / decryptEntity methods.
   *
   * @covers ::__construct
   * @covers ::encryptEntity
   * @covers ::decryptEntity
   * @covers ::processEntity
   * @covers ::processField
   * @covers ::processValue
   *
   * @dataProvider encyptDecryptEntityDataProvider
   */
  public function testEncyptDecryptEntity($encrypted) {
    // Set up field definition.
    $definition = $this->getMockBuilder('\Drupal\Core\Field\BaseFieldDefinition')
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMock();

    // Set up field storage.
    $storage = $this->getMockBuilder('\Drupal\Core\Field\FieldConfigStorageBase')
      ->setMethods(['getThirdPartySetting'])
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for storage.
    $storage_map = [
      ['field_encrypt', 'encrypt', FALSE, $encrypted],
      ['field_encrypt', 'encryption_profile', [], 'test_encryption_profile'],
      ['field_encrypt', 'properties', [], ['value' => 'value']],
    ];
    $storage->expects($this->any())
      ->method('getThirdPartySetting')
      ->will($this->returnValueMap($storage_map));

    // Set up expectations for definition.
    $definition->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['field_name', 'test_field'],
        ['fieldStorage', $storage],
      ]);

    // Set up expectations for field.
    $this->field->expects($this->once())
      ->method('getFieldDefinition')
      ->will($this->returnValue($definition));
    $field_value = [
      ['value' => 'unencrypted text'],
    ];

    if ($encrypted) {
      $this->field->expects($this->once())
        ->method('getValue')
        ->will($this->returnValue($field_value));
      $this->field->expects($this->once())
        ->method('setValue')
        ->with([['value' => '[ENCRYPTED]']]);
    }
    else {
      $this->field->expects($this->never())
        ->method('getValue');
      $this->field->expects($this->never())
        ->method('setValue');
    }

    // Set expectations for entity.
    $this->entity->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue([$this->field]));

    // Set up a mock for the EncryptionProfile class to mock some methods.
    $service = $this->getMockBuilder('\Drupal\field_encrypt\FieldEncryptProcessEntities')
      ->setMethods(['checkField'])
      ->setConstructorArgs(array(
        $this->queryFactory,
        $this->entityManager,
        $this->encryptService,
        $this->encryptionProfileManager,
        $this->encryptedFieldValueManager,
      ))
      ->getMock();

    // Mock some methods on FieldEncryptProcessEntities, since they are out of
    // scope of this specific unit test.
    $service->expects($this->once())
      ->method('checkField')
      ->will($this->returnValue(TRUE));

    $service->encryptEntity($this->entity);
  }

  /**
   * Data provider for testEncyptDecryptEntity method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function encyptDecryptEntityDataProvider() {
    return [
      'encrypted' => [TRUE],
      'not_encrypted' => [FALSE],
    ];
  }

  /**
   * Tests the updateStoredField method.
   *
   * @covers ::__construct
   * @covers ::updateStoredField
   *
   * @dataProvider updateStoredFieldDataProvider
   */
  public function testUpdateStoredField($field_name, $field_entity_type, $original_encryption_settings, $entity_id) {
    // Set up entity storage mock.
    $entity_storage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock entity type.
    $entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for entity type.
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->will($this->returnValue(TRUE));

    // Set up expectations for entity storage.
    $entity_storage->expects($this->any())
      ->method('loadRevision')
      ->will($this->returnValue($this->entity));
    $entity_storage->expects($this->never())
      ->method('load');

    // Set up expectations for entity manager.
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->will($this->returnValue($entity_storage));
    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->will($this->returnValue($entity_type));

    // Set up expectations for entity.
    $this->entity->expects($this->once())
      ->method('get')
      ->with($field_name)
      ->will($this->returnValue($this->field));
    $this->entity->expects($this->once())
      ->method('save');

    // Set up a mock for the EncryptionProfile class to mock some methods.
    $service = $this->getMockBuilder('\Drupal\field_encrypt\FieldEncryptProcessEntities')
      ->setMethods(['checkField', 'processField'])
      ->setConstructorArgs(array(
        $this->queryFactory,
        $this->entityManager,
        $this->encryptService,
        $this->encryptionProfileManager,
        $this->encryptedFieldValueManager,
      ))
      ->getMock();

    if (!empty($original_encryption_settings)) {
      $service->expects($this->once())
        ->method('processField');
    }
    else {
      $service->expects($this->never())
        ->method('processField');
    }

    $service->updateStoredField($field_name, $field_entity_type, $original_encryption_settings, $entity_id);
  }

  /**
   * Data provider for testUpdateStoredField method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function updateStoredFieldDataProvider() {
    return [
      'no_decrypt' => [
        'field_test',
        'node',
        [],
        1,
      ],
      'decrypt' => [
        'field_test',
        'node',
        [
          'field_encrypt' => TRUE,
          'properties' => ['value'],
          'encryption_profile' => 'test_encryption_profile',
        ],
        1,
      ],
    ];
  }

}
