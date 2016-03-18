<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\EventSubscriber\ConfigSubscriber.
 */

namespace Drupal\field_encrypt\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_encrypt\EncryptedFieldValueRunBatch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates existing data when field encryption settings are updated.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a new ConfigSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager, QueryFactory $entity_query) {
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 0);
    $events[ConfigEvents::IMPORT][] = array('onConfigImport', 0);
    return $events;
  }

  /**
   * React on the configuration save event.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if (substr($config->getName(), 0, 14) == 'field.storage.' && $event->isChanged('third_party_settings.field_encrypt')) {
      // Get both the newly saved and original field_encrypt configuration.
      $new_config = $config->get('third_party_settings.field_encrypt');
      $original_config = $config->getOriginal('third_party_settings.field_encrypt');

      // Get the entity type and field from the changed config key.
      $storage_name = substr($config->getName(), 14);
      list($entity_type, $field_name) = explode('.', $storage_name, 2);

      // Load the FieldStorageConfig entity that was updated.
      $field_storage_config = FieldStorageConfig::loadByName($entity_type, $field_name);
      if ($field_storage_config) {
        if ($field_storage_config->hasData()) {
          // Get entities that need updating, because they contain the field that has
          // its field encryption settings updated.
          $query = $this->entityQuery->get($entity_type);
          // Check if the field is present.
          $query->exists($field_name);
          // Make sure to get all revisions for revisionable entities.
          if ($this->entityManager->getDefinition($entity_type)->hasKey('revision')) {
            $query->allRevisions();
          }
          $entity_ids = $query->execute();

          // Configure the batch operation to be called, with the appropriate
          // parameters to process the loaded entity IDs that need updating.
          $batch = [
            'title' => t('Updating field encryption'),
            'operations' => [
              [
                [EncryptedFieldValueRunBatch::class, 'processBatch'],
                [array_keys($entity_ids), $field_name, $entity_type, $original_config]
              ]
            ],
            'finished' => [EncryptedFieldValueRunBatch::class, 'finishBatch'],
            'progressive' => TRUE,
          ];
          batch_set($batch);
          //return batch_process();
        }
      }
    }
  }

  /**
   * React on the configuration import event.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigImport(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if (substr($config->getName(), 0, 14) == 'field.storage.' && $event->isChanged('third_party_settings.field_encrypt')) {
      // Get both the newly saved and original field_encrypt configuration.
      $new_config = $config->get('third_party_settings.field_encrypt');
      $original_config = $config->getOriginal('third_party_settings.field_encrypt');
    }
  }

}
