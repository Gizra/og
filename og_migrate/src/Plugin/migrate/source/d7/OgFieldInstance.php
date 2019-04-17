<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Finds og-related field instances to migrate.
 *
 * @MigrateSource(
 *   id = "d7_og_field_instance",
 *   source_module = "og_migrate"
 * )
 */
class OgFieldInstance extends SqlBase {

  const OG_FIELD_NAMES = ['og_group_ref'];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config_instance', 'fci')
      ->fields('fci', ['entity_type', 'bundle', 'field_name', 'data'])
      ->condition('fci.field_name', self::OG_FIELD_NAMES, 'IN')
      ->condition('fci.deleted', 0);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('Field name'),
      'entity_type' => $this->t('Entity type'),
      'bundle' => $this->t('Entity bundle'),
      'data' => $this->t('Field settings'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
        'length' => 32,
        'is_ascii' => TRUE,
      ],
      'bundle' => [
        'type' => 'string',
        'length' => 128,
        'is_ascii' => TRUE,
      ],
      'field_name' => [
        'type' => 'string',
        'length' => 32,
        'is_ascii' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $skip = parent::prepareRow($row);
    if (!$skip) {
      $data = unserialize($row->getSourceProperty('data'));

      if (isset($data['handler_settings']['behaviors'])) {
        unset($data['handler_settings']['behaviors']);
      }

      if (isset($data['handler_settings']['membership_type'])) {
        unset($data['handler_settings']['membership_type']);
      }

      $data['handler'] = 'og:default';

      $row->setSourceProperty('data', $data);
    }

    return $skip;
  }

}
