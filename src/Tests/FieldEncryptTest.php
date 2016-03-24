<?php

/**
 * @file
 * Contains Drupal\field_encrypt\Tests\FieldEncryptTest.
 */

namespace Drupal\field_encrypt\Tests;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_encrypt\Entity\EncryptedFieldValue;
use Drupal\key\Entity\Key;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;


/**
 * Tests field encryption.
 *
 * @group field_encrypt
 */
class FieldEncryptTest extends WebTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'field',
    'field_ui',
    'text',
    'locale',
    'content_translation',
    'key',
    'encrypt',
    'encrypt_test',
    'field_encrypt',
  ];

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;


  /**
   * A list of test keys.
   *
   * @var \Drupal\key\Entity\Key[]
   */
  protected $keys;

  /**
   * A list of test encryption profiles.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile[]
   */
  protected $encryptionProfiles;

  /**
   * The page node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeType;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->container->get('entity.manager');

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer encrypt',
      'administer keys',
      'administer field encryption',
    ], NULL, TRUE);
    $this->drupalLogin($this->adminUser);

    // Create test keys for encryption.
    $key_128 = Key::create([
      'id' => 'key_128',
      'label' => 'Test Key 128 bit',
      'key_type' => "encryption",
      'key_type_settings[key_size]' => '128',
      'key_provider' => 'config',
      'key_input_settings[key_value]' => 'mustbesixteenbit',
    ]);
    $key_128->save();
    $this->keys['key_128'] = $key_128;

    $key_256 = Key::create([
      'id' => 'key_256',
      'label' => 'Test Key 256 bit',
      'key_type' => "encryption",
      'key_type_settings[key_size]' => '256',
      'key_provider' => 'config',
      'key_input_settings[key_value]' => 'mustbesixteenbitmustbesixteenbit',
    ]);
    $key_256->save();
    $this->keys['key_256'] = $key_256;

    // Create test encryption profiles.
    $encryption_profile_1 = EncryptionProfile::create([
      'id' => 'encryption_profile_1',
      'label' => 'Encryption profile 1',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->keys['key_128']->id(),
    ]);
    $encryption_profile_1->save();
    $this->encryptionProfiles['encryption_profile_1'] = $encryption_profile_1;

    $encryption_profile_2 = EncryptionProfile::create([
      'id' => 'encryption_profile_2',
      'label' => 'Encryption profile 2',
      'encryption_method' => 'config_test_encryption_method',
      'encryption_method_configuration[mode]' => 'CFB',
      'encryption_key' => $this->keys['key_256']->id(),
    ]);
    $encryption_profile_2->save();
    $this->encryptionProfiles['encryption_profile_2'] = $encryption_profile_2;

    // Create content type to test.
    $this->nodeType = $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create test fields.
    $single_field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_test_single',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
      'cardinality' => 1,
    ));
    $single_field_storage->save();
    $single_field = FieldConfig::create([
      'field_storage' => $single_field_storage,
      'bundle' => 'page',
      'label' => 'Single field',
    ]);
    $single_field->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent('field_test_single')
      ->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent('field_test_single', array(
        'type' => 'text_default',
      ))
      ->save();

    $multi_field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_test_multi',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 3,
    ));
    $multi_field_storage->save();
    $multi_field = FieldConfig::create([
      'field_storage' => $multi_field_storage,
      'bundle' => 'page',
      'label' => 'Multi field',
    ]);
    $multi_field->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent('field_test_multi')
      ->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent('field_test_multi', array(
        'type' => 'string',
      ))
      ->save();
  }

  /**
   * Test encrypting fields.
   *
   * This test also covers changing field encryption settings when existing
   * data already exists, as well as making fields unencrypted again with
   * data unencryption support.
   */
  public function testEncryptField() {
    $this->setFieldStorageSettings(TRUE);

    // Save test entity.
    $test_node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_test_single' => [
        [
          'value' => "Lorem ipsum dolor sit amet.",
          'summary' => "Lorem ipsum",
          'format' => filter_default_format(),
        ],
      ],
      'field_test_multi' => [
        ['value' => "one"],
        ['value' => "two"],
        ['value' => "three"],
      ],
    ]);
    $test_node->enforceIsNew(TRUE);
    $test_node->save();

    $fields = $test_node->getFields();
    // Check field_test_single settings.
    $single_field = $fields['field_test_single'];
    $definition = $single_field->getFieldDefinition();
    $this->assertTrue($definition instanceof FieldDefinitionInterface);
    $storage = $definition->get('fieldStorage');
    $this->assertEqual(TRUE, $storage->getThirdPartySetting('field_encrypt', 'encrypt', FALSE));
    $this->assertEqual(['value' => 'value', 'summary' => 'summary'], array_filter($storage->getThirdPartySetting('field_encrypt', 'properties', [])));
    $this->assertEqual('encryption_profile_1', $storage->getThirdPartySetting('field_encrypt', 'encryption_profile', ''));

    // Check field_test_multi settings.
    $single_field = $fields['field_test_multi'];
    $definition = $single_field->getFieldDefinition();
    $this->assertTrue($definition instanceof FieldDefinitionInterface);
    $storage = $definition->get('fieldStorage');
    $this->assertEqual(TRUE, $storage->getThirdPartySetting('field_encrypt', 'encrypt', FALSE));
    $this->assertEqual(['value' => 'value'], array_filter($storage->getThirdPartySetting('field_encrypt', 'properties', [])));
    $this->assertEqual('encryption_profile_2', $storage->getThirdPartySetting('field_encrypt', 'encryption_profile', ''));

    // Check existence of EncryptedFieldValue entities.
    $encrypted_field_values = EncryptedFieldValue::loadMultiple();
    $this->assertEqual(5, count($encrypted_field_values));

    // Check if text is displayed unencrypted.
    $this->drupalGet('node/' . $test_node->id());
    $this->assertText("Lorem ipsum dolor sit amet.");
    $this->assertText("one");
    $this->assertText("two");
    $this->assertText("three");

    $result = \Drupal::database()->query("SELECT field_test_single_value FROM {node__field_test_single} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchField();
    $this->assertEqual("[ENCRYPTED]", $result);

    $result = \Drupal::database()->query("SELECT field_test_multi_value FROM {node__field_test_multi} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchAll();
    foreach ($result as $record) {
      $this->assertEqual("[ENCRYPTED]", $record->field_test_multi_value);
    }

    // Test updating entities with alternative encryption settings.
    $this->setFieldStorageSettings(TRUE, TRUE);
    // Update existing data with new field encryption settings.
    $this->assertLinkByHref('admin/config/system/field-encrypt/field-update');
    $this->drupalGet('admin/config/system/field-encrypt/field-update');
    $this->assertText('There are 2 fields queued for encryption updates.');
    $this->cronRun();
    $this->drupalGet('admin/config/system/field-encrypt/field-update');
    $this->assertText('There are 0 fields queued for encryption updates.');

    // Check existence of EncryptedFieldValue entities.
    $encrypted_field_values = EncryptedFieldValue::loadMultiple();
    $this->assertEqual(5, count($encrypted_field_values));

    // Check if text is displayed unencrypted.
    $this->drupalGet('node/' . $test_node->id());
    $this->assertText("Lorem ipsum dolor sit amet.");
    $this->assertText("one");
    $this->assertText("two");
    $this->assertText("three");

    // Check values saved in the database.
    $result = \Drupal::database()->query("SELECT field_test_single_value FROM {node__field_test_single} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchField();
    $this->assertEqual("[ENCRYPTED]", $result);

    $result = \Drupal::database()->query("SELECT field_test_multi_value FROM {node__field_test_multi} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchAll();
    foreach ($result as $record) {
      $this->assertEqual("[ENCRYPTED]", $record->field_test_multi_value);
    }

    // Test updating entities to remove field encryption.
    $this->setFieldStorageSettings(FALSE);
    // Update existing data with new field encryption settings.
    $this->assertLinkByHref('admin/config/system/field-encrypt/field-update');
    $this->drupalGet('admin/config/system/field-encrypt/field-update');
    $this->assertText('There are 2 fields queued for encryption updates.');
    $this->cronRun();
    $this->drupalGet('admin/config/system/field-encrypt/field-update');
    $this->assertText('There are 0 fields queued for encryption updates.');

    // Check removal of EncryptedFieldValue entities.
    $encrypted_field_values = EncryptedFieldValue::loadMultiple();
    $this->assertEqual(0, count($encrypted_field_values));

    // Check if text is displayed unencrypted.
    $this->drupalGet('node/' . $test_node->id());
    $this->assertText("Lorem ipsum dolor sit amet.");
    $this->assertText("one");
    $this->assertText("two");
    $this->assertText("three");

    $result = \Drupal::database()->query("SELECT field_test_single_value FROM {node__field_test_single} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchField();
    $this->assertEqual("Lorem ipsum dolor sit amet.", $result);

    $result = \Drupal::database()->query("SELECT field_test_multi_value FROM {node__field_test_multi} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchAll();
    $valid_values = ["one", "two", "three"];
    foreach ($result as $record) {
      $this->assertTrue(in_array($record->field_test_multi_value, $valid_values));
    }
  }

  /**
   * Test encrypting fields with revisions.
   *
   * This test also covers deletion of an encrypted field with existing data.
   */
  public function testEncryptFieldRevision() {
    $this->setFieldStorageSettings(TRUE);

    // Save test entity.
    $test_node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_test_single' => [
        [
          'value' => "Lorem ipsum dolor sit amet.",
          'summary' => "Lorem ipsum",
          'format' => filter_default_format(),
        ],
      ],
      'field_test_multi' => [
        ['value' => "one"],
        ['value' => "two"],
        ['value' => "three"],
      ],
    ]);
    $test_node->enforceIsNew(TRUE);
    $test_node->save();

    // Create a new revision for the entity.
    $old_revision_id = $test_node->getRevisionId();
    $test_node->setNewRevision(TRUE);
    $test_node->field_test_single->value = "Lorem ipsum dolor sit amet revisioned.";
    $test_node->field_test_single->summary = "Lorem ipsum revisioned.";
    $multi_field = $test_node->get('field_test_multi');
    $multi_field_value = $multi_field->getValue();
    $multi_field_value[0]['value'] = "four";
    $multi_field_value[1]['value'] = "five";
    $multi_field_value[2]['value'] = "six";
    $multi_field->setValue($multi_field_value);
    $test_node->save();

    // Ensure that the node revision has been created.
    $this->entityManager->getStorage('node')->resetCache(array($test_node->id()));
    $this->assertNotIdentical($test_node->getRevisionId(), $old_revision_id, 'A new revision has been created.');

    // Check existence of EncryptedFieldValue entities.
    $encrypted_field_values = EncryptedFieldValue::loadMultiple();
    $this->assertEqual(10, count($encrypted_field_values));

    // Check if revisioned text is displayed unencrypted.
    $this->drupalGet('node/' . $test_node->id());
    $this->assertText("Lorem ipsum dolor sit amet revisioned.");
    $this->assertText("four");
    $this->assertText("five");
    $this->assertText("six");

    // Check if original text is displayed unencrypted.
    $this->drupalGet('node/' . $test_node->id() . '/revisions/' . $old_revision_id . '/view');
    $this->assertText("Lorem ipsum dolor sit amet.");
    $this->assertText("one");
    $this->assertText("two");
    $this->assertText("three");

    // Check values saved in the database.
    $result = \Drupal::database()->query("SELECT field_test_single_value FROM {node_revision__field_test_single} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchField();
    $this->assertEqual("[ENCRYPTED]", $result);

    $result = \Drupal::database()->query("SELECT field_test_multi_value FROM {node_revision__field_test_multi} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchAll();
    foreach ($result as $record) {
      $this->assertEqual("[ENCRYPTED]", $record->field_test_multi_value);
    }

    $edit = [
      'confirm' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_test_multi/delete', $edit, t('Delete'));

    // Test if EncryptedFieldValue entities got deleted.
    $encrypted_field_values = EncryptedFieldValue::loadMultiple();
    $this->assertEqual(4, count($encrypted_field_values));
  }

  /**
   * Test encrypting fields with translations.
   */
  public function testEncryptFieldTranslation() {
    $this->setTranslationSettings();
    $this->setFieldStorageSettings(TRUE);

    // Save test entity.
    $test_node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_test_single' => [
        [
          'value' => "This is some english text.",
          'summary' => "English text",
          'format' => filter_default_format(),
        ],
      ],
      'field_test_multi' => [
        ['value' => "one"],
        ['value' => "two"],
        ['value' => "three"],
      ],
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
    ]);
    $test_node->enforceIsNew(TRUE);
    $test_node->save();

    // Reload node after saving.
    $controller = $this->entityManager->getStorage($test_node->getEntityTypeId());
    $controller->resetCache(array($test_node->id()));
    $test_node = $controller->load($test_node->id());

    // Add translated values.
    $translated_values = [
      'title' => $this->randomMachineName(8),
      'field_test_single' => [
        [
          'value' => "Ceci est un text francais.",
          'summary' => "Text francais",
          'format' => filter_default_format(),
        ],
      ],
      'field_test_multi' => [
        ['value' => "un"],
        ['value' => "deux"],
        ['value' => "trois"],
      ],
    ];
    $test_node->addTranslation('fr', $translated_values);
    $test_node->save();

    // Check existence of EncryptedFieldValue entities.
    $encrypted_field_values = EncryptedFieldValue::loadMultiple();
    $this->assertEqual(5, count($encrypted_field_values));

    // Check if English text is displayed unencrypted.
    $this->drupalGet('node/' . $test_node->id());
    $this->assertText("This is some english text.");
    $this->assertText("one");
    $this->assertText("two");
    $this->assertText("three");

    // Check if English text is displayed unencrypted.
    $this->drupalGet('fr/node/' . $test_node->id());
    $this->assertText("Ceci est un text francais.");
    $this->assertText("un");
    $this->assertText("deux");
    $this->assertText("trois");

    // Check values saved in the database.
    $result = \Drupal::database()->query("SELECT field_test_single_value FROM {node__field_test_single} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchAll();
    foreach ($result as $record) {
      $this->assertEqual("[ENCRYPTED]", $record->field_test_single_value);
    }

    $result = \Drupal::database()->query("SELECT field_test_multi_value FROM {node__field_test_multi} WHERE entity_id = :entity_id", array(':entity_id' => $test_node->id()))->fetchAll();
    foreach ($result as $record) {
      $this->assertEqual("[ENCRYPTED]", $record->field_test_multi_value);
    }
  }

  /**
   * Set up storage settings for test fields.
   */
  protected function setFieldStorageSettings($encryption = TRUE, $alternate = FALSE) {
    // Set up storage settings for first field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_test_single/storage');
    $this->assertFieldByName('field_encrypt[encrypt]', NULL, 'Encrypt field found.');
    $this->assertFieldByName('field_encrypt[encryption_profile]', NULL, 'Encryption profile field found.');

    $profile_id = ($alternate == TRUE) ? 'encryption_profile_2' : 'encryption_profile_1';
    $edit = [
      'field_encrypt[encrypt]' => $encryption,
      'field_encrypt[properties][value]' => 'value',
      'field_encrypt[properties][summary]' => 'summary',
      'field_encrypt[encryption_profile]' => $profile_id,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_test_single/storage');

    // Set up storage settings for second field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_test_multi/storage');
    $this->assertFieldByName('field_encrypt[encrypt]', NULL, 'Encrypt field found.');
    $this->assertFieldByName('field_encrypt[encryption_profile]', NULL, 'Encryption profile field found.');

    $profile_id = ($alternate == TRUE) ? 'encryption_profile_1' : 'encryption_profile_2';
    $edit = [
      'field_encrypt[encrypt]' => $encryption,
      'field_encrypt[properties][value]' => 'value',
      'field_encrypt[encryption_profile]' => $profile_id,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
  }

  /**
   * Set up translation settings for content translation test.
   */
  protected function setTranslationSettings() {
    // Set up extra language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')
      ->setEnabled('node', 'page', TRUE);
    drupal_static_reset();
    $this->entityManager->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
    $this->rebuildContainer();
  }

}
