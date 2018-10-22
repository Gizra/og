<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d6;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Finds unique is_admin property in {og_uid} table.
 *
 * @MigrateSource(
 *   id = "d6_og_admin_role",
 *   source_module = "og_migrate"
 * )
 */
class OgAdminRole extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_uid', 'ogu');
    $query->innerJoin('node', 'n', 'n.nid = ogu.nid');
    $query
      ->fields('n', ['type'])
      ->fields('ogu', ['is_admin'])
      ->condition('ogu.is_admin', 1)
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
      'is_admin' => $this->t('Group admin'),
      'id' => $this->t('Role ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($row->getSourceProperty('is_admin')) {
      $id = 'node-' . $row->getSourceProperty('type') . '-administrator-member';
      $row->setSourceProperty('id', $id);
    }
    return parent::prepareRow($row);
  }

}
