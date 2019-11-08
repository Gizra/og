<?php

namespace Drupal\og_migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\og\Og;

/**
 * Creates og_audience field on entity bundle.
 *
 * @MigrateDestination(
 *   id = "og_field"
 * )
 *
 * @internal
 */
class OgField extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    try {
      $field = Og::createField(
        $row->getDestinationProperty('plugin_id'),
        $row->getDestinationProperty('entity_type'),
        $row->getDestinationProperty('entity_bundle')
        // @todo worth it to migrate field settings?
        // $row->getDestinationProperty('settings')
      );
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return ['id' => $field->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'plugin_id' => $this->t('Og Plugin ID'),
      'entity_type' => $this->t('Entity type'),
      'entity_bundle' => $this->t('Entity bundle'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'string',
        'length' => 255,
        'is_ascii' => TRUE,
      ],
    ];
  }

}
