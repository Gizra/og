<?php

namespace Drupal\og_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Migration source plugin for {og_membership_type}.
 *
 * @MigrateSource(
 *   id = "d7_og_membership_type",
 *   source_module = "og_migrate"
 * )
 */
class OgMembershipType extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('og_membership_type', 'ogt')
      ->fields('ogt', [
        'id',
        'name',
        'description',
        'status',
        'module',
        'language',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'max_length' => 255,
        'is_ascii' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Primary Identifier'),
      'name' => $this->t('Machine name'),
      'description' => $this->t('Description'),
      'status' => $this->t('Status'),
      'module' => $this->t('Module owner'),
      'language' => $this->t('Language code'),
    ];
  }

}
