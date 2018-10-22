<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d6;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Finds node memberships in Drupal 6.
 *
 * @MigrateSource(
 *   id = "d6_og_ancestry",
 *   source_module = "og_migrate"
 * )
 */
class OgAncestry extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_ancestry', 'oga');
    $query->innerJoin('og', 'og', 'oga.group_nid = og.nid');
    $query
      ->fields('oga', ['nid', 'group_nid'])
      ->fields('og', ['og_language']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Node ID'),
      'group_nid' => $this->t('Group ID'),
      'og_language' => $this->t('Group Language'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'oga',
      ],
      'group_nid' => [
        'type' => 'integer',
        'alias' => 'oga',
      ],
    ];
  }

}
