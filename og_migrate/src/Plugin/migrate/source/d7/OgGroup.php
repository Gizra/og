<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Migrate source plugin for {group_group} field.
 *
 * @MigrateSource(
 *   id = "d7_og_group",
 *   source_module = "og_migrate"
 * )
 *
 * @internal
 */
class OgGroup extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('field_config_instance', 'fci')
      ->fields('fci', ['entity_type', 'bundle'])
      ->condition('fci.field_name', 'group_group');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
        'max_length' => 32,
        'is_ascii' => TRUE,
      ],
      'bundle' => [
        'type' => 'string',
        'max_length' => 128,
        'is_ascii' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_type' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
    ];
  }

}
