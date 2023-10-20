<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d6;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Migrate source plugin for Drupal 6 organic group node types.
 *
 * @MigrateSource(
 *   id = "d6_og_group_type",
 *   source_module = "og_migrate"
 * )
 *
 * @internal
 */
class OgGroupType extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og', 'og');
    $query->innerJoin('node', 'n', 'n.nid = og.nid');
    $query
      ->fields('n', ['type'])
      ->distinct(TRUE);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'type' => [
        'type' => 'string',
        'length' => 32,
        'is_ascii' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Content Type'),
    ];
  }

}
