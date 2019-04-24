<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d7;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
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
class OgMembership extends SqlBase implements ConfigurableInterface {

  /**
   * Returns a select query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query interface.
   *
   * @throws \Drupal\migrate\Plugin\Exception\BadPluginDefinitionException
   */
  public function query() {
    if (!isset($this->configuration['entity_type'])) {
      throw new BadPluginDefinitionException('d7_og_membership', 'entity_type');
    }
    if (!isset($this->configuration['group_type'])) {
      throw new BadPluginDefinitionException('d7_og_membership', 'group_type');
    }

    $entity_type = $this->configuration['entity_type'];
    $group_type = $this->configuration['group_type'];
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
      ->condition('ogm.entity_type', $entity_type)
      ->condition('ogm.group_type', $group_type);
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
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Hopefully nobody depends on this returning something anything other than
    // an empty array.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->configuration, $configuration);
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

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
