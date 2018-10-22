<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides source mapping for Drupal 7 organic groups roles.
 *
 * @MigrateSource(
 *   id = "d7_og_role",
 *   source_module = "og_migrate"
 * )
 */
class OgRole extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_role', 'ogr');
    $query->fields('ogr', [
      'rid',
      'name',
      'gid',
      'group_type',
      'group_bundle',
    ]);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'rid' => [
        'type' => 'integer',
        'alias' => 'ogr',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'rid' => $this->t('Role ID'),
      'name' => $this->t('Machine name'),
      'gid' => $this->t('Entity ID'),
      'group_type' => $this->t('Entity type'),
      'group_bundle' => $this->t('Bundle'),
      'permissions' => $this->t('Permissions'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $permissions = [];
    $results = $this->select('og_role_permission', 'ogrp')
      ->fields('ogrp', ['permission'])
      ->condition('ogrp.rid', $row->getSourceProperty('rid'))
      ->execute()
      ->fetchAllKeyed(0, 0);

    if (!empty($results)) {
      $permissions = array_values($results);
    }

    if ($row->getSourceProperty('name') === 'non-member') {
      // Adds subscribe to the default role for anonymous users, which
      // does not exist in drupal 7. The "subscribe without approval"
      // permission has access implications.
      $permissions[] = 'subscribe';
    }

    $row->setSourceProperty('permissions', $permissions);

    return parent::prepareRow($row);
  }

}
