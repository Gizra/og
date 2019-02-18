<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Migrate source plugin for Drupal 7 {og_membership}.
 *
 * @MigrateSource(
 *   id = "d7_og_membership",
 *   source_module = "og_migrate"
 * )
 */
class OgMembership extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $op = '=';
    $entity_type = isset($this->configuration['entity_type']) ? $this->configuration['entity_type'] : 'any';
    if ($entity_type === 'any') {
      $op = '<>';
      $entity_type = 'user';
    }

    $query = $this->select('og_membership', 'ogm');
    $query
      ->fields('ogm', [
        'id',
        'type',
        'etid',
        'entity_type',
        'gid',
        'group_type',
        'state',
        'created',
        'field_name',
        'language',
      ])
      ->condition('ogm.entity_type', $entity_type, $op);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'ogm',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Primary Identifier'),
      'type' => $this->t('Membership Type'),
      'etid' => $this->t('Entity ID'),
      'entity_type' => $this->t('Entity Type'),
      'gid' => $this->t('Group Entity ID'),
      'group_type' => $this->t('Group Entity Type'),
      'state' => $this->t('Membership State'),
      'created' => $this->t('Joined Date'),
      'field_name' => $this->t('Group Membership Field'),
      'language' => $this->t('Language Code'),
    ];

    if ($this->isUserMembershipMigration()) {
      $fields += [
        'roles' => $this->t('Roles'),
      ];
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($this->isUserMembershipMigration()) {
      $rids = [];
      $results = $this->select('og_users_roles', 'ogur')
        ->fields('ogur', ['rid'])
        ->condition('ogur.uid', $row->getSourceProperty('etid'))
        ->condition('ogur.gid', $row->getSourceProperty('gid'))
        ->condition('ogur.group_type', $row->getSourceProperty('group_type'))
        ->execute()
        ->fetchAllKeyed(0, 0);

      if (!empty($results)) {
        $rids = array_values($results);
      }
      $row->setSourceProperty('roles', $rids);
    }

    return parent::prepareRow($row);
  }

  /**
   * Determines if this source is configured for users or other entities.
   *
   * @return bool
   *   TRUE if the migration is for users.
   */
  protected function isUserMembershipMigration() {
    $entity_type = isset($this->configuration['entity_type']) ? $this->configuration['entity_type'] : 'any';
    return $entity_type === 'user';
  }

}
