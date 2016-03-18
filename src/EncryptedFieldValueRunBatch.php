<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\EncryptedFieldValueRunBatch.
 */

namespace Drupal\field_encrypt;

/**
 * Runs a field update batch.
 * @TODO: make service
 *   - process entities
 *   - config
 *   - translation
 */
class EncryptedFieldValueRunBatch {

  /**
   * Processes batch updating of encryption fields.
   *
   * @param array $entity_ids
   *   The entity IDs to update with the new field encryption settings.
   * @param string $field_name
   *   The name of the field that his its encryption settings changed.
   * @param string $field_entity_type
   *   The entity type whose field storage settings have been changed.
   * @param array $original_encryption_settings
   *   The original field encryption settings, before they where changed.
   * @param array $context
   *   The batch API context.
   */
  public static function processBatch($entity_ids, $field_name, $field_entity_type, $original_encryption_settings, &$context) {
    $process_entities = \Drupal::service('field_encrypt.process_entities');
    if (empty($context['sandbox'])) {
      $context['sandbox']['entity_ids'] = $entity_ids;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($entity_ids);
    }

    $config = \Drupal::config('field_encrypt_admin_settings');
    // Process entities in groups. Default batch size is 5.
    // @TODO: fix!!!!
    $batch_size = 5; //$config->get('batch_size');
    $count = min($batch_size, count($context['sandbox']['entity_ids']));
    for ($i = 1; $i <= $count; $i++) {
      $entity_id = array_shift($context['sandbox']['entity_ids']);
      /* @var $entity_storage \Drupal\Core\Entity\EntityStorageInterface */
      $process_entities->updateStoredField($field_name, $field_entity_type, $original_encryption_settings, $entity_id);
      $context['results'][] = $entity_id;
      // Update our progress information.
      $context['sandbox']['progress']++;
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finish batch encryption updates of fields.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One field updated.', '@count fields updated.');
    }
    else {
      $message = 'Finished with an error.';
      //$message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
