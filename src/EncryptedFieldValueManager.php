<?php

/**
 * @file
 * Contains \Drupal\field_encrypt\EncryptedFieldValueManager.
 */

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field_encrypt\Entity\EncryptedFieldValue;

/**
 * Manager containing common functions to manage EncryptedFieldValue entities.
 */
class EncryptedFieldValueManager implements EncryptedFieldValueManagerInterface {

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
   * Construct the CommentManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, QueryFactory $entity_query) {
    $this->entityManager = $entity_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public function saveEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property, $encrypted_value) {
    $langcode = $entity->language()->getId();
    if ($encrypted_field_value = $this->getExistingEntity($entity, $field_name, $delta, $property)) {
      $translation = $encrypted_field_value->hasTranslation($langcode) ? $encrypted_field_value->getTranslation($langcode) : $encrypted_field_value->addTranslation($langcode);
      $translation->setEncryptedValue($encrypted_value);
    }
    else {
      $encrypted_field_value = EncryptedFieldValue::create([
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'entity_revision_id' => $this->getEntityRevisionId($entity),
        'field_name' => $field_name,
        'field_property' => $property,
        'field_delta' => $delta,
        'encrypted_value' => $encrypted_value,
        'langcode' => $langcode,
      ]);
    }
    $encrypted_field_value->save();
  }


  /**
   * {@inheritdoc}
   */
  public function getEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property) {
    $field_value_entity = $this->getExistingEntity($entity, $field_name, $delta, $property);
    if ($field_value_entity) {
      $langcode = $entity->language()->getId();
      if ($field_value_entity->hasTranslation($langcode)) {
        $field_value_entity = $field_value_entity->getTranslation($langcode);
      }
      return $field_value_entity->getEncryptedValue();
    }
    return FALSE;
  }

  /**
   * Loads an existing EncryptedFieldValue entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param string $field_name
   *   The field name to check.
   * @param int $delta
   *   The field delta to check.
   * @param string $property
   *   The field property to check.
   *
   * @return bool|\Drupal\field_encrypt\Entity\EncryptedFieldValue
   *   The existing EncryptedFieldValue entity.
   */
  protected function getExistingEntity(ContentEntityInterface $entity, $field_name, $delta, $property) {
    $query = $this->entityQuery->get('encrypted_field_value')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('entity_revision_id', $this->getEntityRevisionId($entity))
      ->condition('field_name', $field_name)
      ->condition('field_delta', $delta)
      ->condition('field_property', $property);
    $values = $query->execute();

    if (!empty($values)) {
      $id = array_shift($values);
      return EncryptedFieldValue::load($id);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntityEncryptedFieldValues(ContentEntityInterface $entity) {
    $field_values = $this->entityManager->getStorage('encrypted_field_value')->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
    if ($field_values) {
      $this->entityManager->getStorage('encrypted_field_value')->delete($field_values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntityEncryptedFieldValuesForField(ContentEntityInterface $entity, $field_name) {
    $field_values = $this->entityManager->getStorage('encrypted_field_value')->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'field_name' => $field_name,
      'entity_revision_id' => $this->getEntityRevisionId($entity),
    ]);
    if ($field_values) {
      $this->entityManager->getStorage('encrypted_field_value')->delete($field_values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEncryptedFieldValuesForField($entity_type, $field_name) {
    $field_values = $this->entityManager->getStorage('encrypted_field_value')->loadByProperties([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
    ]);
    if ($field_values) {
      $this->entityManager->getStorage('encrypted_field_value')->delete($field_values);
    }
  }

  /**
   * Get the revision ID to store for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return int
   *   The revision ID.
   */
  protected function getEntityRevisionId(ContentEntityInterface $entity) {
    if ($entity->getEntityType()->hasKey('revision')) {
      $revision_id = $entity->getRevisionId();
    }
    else {
      $revision_id = $entity->id();
    }
    return $revision_id;
  }

}
