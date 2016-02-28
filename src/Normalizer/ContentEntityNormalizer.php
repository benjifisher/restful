<?php

/**
 * @file
 * Contains \Drupal\restful\Normalizer\ContentEntityNormalizer.
 */

namespace Drupal\restful\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\hal\Normalizer\ContentEntityNormalizer as HalContentEntityNormalizer;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 */
class ContentEntityNormalizer extends HalContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $context += array(
      'account' => NULL,
      'included_fields' => NULL,
    );

    // Create the array of normalized fields, starting with the URI.
    /* @var $entity \Drupal\Core\Entity\ContentEntityInterface */
    $normalized = array(
      '_links' => array(
        'self' => array(
          'href' => $this->getEntityUri($entity),
        ),
        'type' => array(
          'href' => $this->linkManager->getTypeUri($entity->getEntityTypeId(), $entity->bundle(), $context),
        ),
      ),
    );

    // If the fields to use were specified, only output those field values.
    // Otherwise, output all field values except internal ID.
    if (isset($context['included_fields'])) {
      $fields = array();
      foreach ($context['included_fields'] as $field_name) {
        $fields[] = $entity->get($field_name);
      }
    }
    else {
      $fields = $entity->getFields();
    }
    // Ignore the entity ID and revision ID.
    $exclude = array($entity->getEntityType()->getKey('id'), $entity->getEntityType()->getKey('revision'));
    $field_names = explode(',', \Drupal::request()->query->get('fields', array_keys($fields)));
    $exclude = array_merge($exclude, array_diff(array_keys($fields), $field_names));
    foreach ($fields as $field) {
      // Continue if this is an excluded field or the current user does not have
      // access to view it.
      if (in_array($field->getFieldDefinition()->getName(), $exclude) || !$field->access('view', $context['account'])) {
        continue;
      }

      $normalized_property = $this->serializer->normalize($field, $format, $context);
      $normalized = NestedArray::mergeDeep($normalized, $normalized_property);
    }

    return $normalized;
  }

}