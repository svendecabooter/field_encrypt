<?php

/**
 * @file
 * Contains Drupal\field_encrypt\Tests\FieldEncryptCacheTest.
 */

namespace Drupal\field_encrypt\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;

/**
 * Tests field encryption caching.
 *
 * @group field_encrypt
 */
class FieldEncryptCacheTest extends FieldEncryptTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = ['dynamic_page_cache'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up fields for encryption.
    $this->setFieldStorageSettings(TRUE);

    // Create a test entity.
    $this->createTestNode();
  }

  /**
   * Test dynamic page cache.
   */
  public function testDynamicPageCache() {
    // Page should be uncacheable due to max-age = 0.
    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertEqual('UNCACHEABLE', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Page with encrypted fields is uncacheable.');

    // Set encrypted field as cacheable.
    $this->setFieldStorageSettings(TRUE, FALSE, FALSE);

    // Page is cacheable, but currently not cached.
    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertEqual('MISS', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Dynamic Page Cache MISS.');

    // Page is cacheable, and should be cached.
    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Dynamic Page Cache HIT.');
  }

  /**
   * Test caching of rendered entity.
   */
  public function testEntityRender() {
    // Check for max-age = 0 on entity with encrypted field.
    $build = $this->drupalBuildEntityView($this->testNode);
    // @TODO: this probably doesn't work because hook_entity_view isn't called.
    // Find out if there's a better way then hook_entity_view to set cache tags.
    $this->assertEqual(0, $build['#cache']['max-age'], 'Cache max-age is set correctly.');

    // Set encrypted field as cacheable.
    $this->setFieldStorageSettings(TRUE, FALSE, FALSE);
    $build = $this->drupalBuildEntityView($this->testNode);
    $this->assertEqual(Cache::PERMANENT, $build['#cache']['max-age'], 'Cache max-age is set correctly.');
  }

}
