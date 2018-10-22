<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d6;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Finds node types that are group content.
 *
 * @MigrateSource(
 *   id = "d6_og_audience",
 *   source_module = "og_migrate"
 * )
 */
class OgAudience extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_ancestry', 'oga');
    $query->innerJoin('node', 'n', 'oga.nid = n.nid');
    $query
      ->fields('n', ['type'])
      ->distinct(TRUE);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Content Type'),
    ];
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

}
