<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d6;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Migrate source plugin for {og_uid} table.
 *
 * @MigrateSource(
 *   id = "d6_og_uid",
 *   source_module = "og_migrate"
 * )
 */
class OgUid extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_uid', 'ogu');
    $query->innerJoin('node', 'n', 'n.nid = ogu.nid');
    $query->innerJoin('og', 'og', 'og.nid = ogu.nid');
    $query
      ->fields('og', ['og_language'])
      ->fields('n', ['type'])
      ->fields('ogu', [
        'nid',
        'uid',
        'is_active',
        'is_admin',
        'created',
      ]);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'ogu',
      ],
      'nid' => [
        'type' => 'integer',
        'alias' => 'ogu',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Content Type'),
      'nid' => $this->t('Node ID'),
      'uid' => $this->t('User ID'),
      'is_admin' => $this->t('Group admin'),
      'is_active' => $this->t('Membership state'),
      'created' => $this->t('Created date'),
      'og_language' => $this->t('Language'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $roles = [];

    if ($row->getSourceProperty('is_admin')) {
      $roles[] = 'node-' . $row->getSourceProperty('type') . '-administrator';
    }
    $row->setSourceProperty('roles', $roles);

    return parent::prepareRow($row);
  }

}
