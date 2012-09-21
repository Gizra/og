<?php

/**
 * @file
 * Re-populate group ID and group entity, deprecating the OG group entity.
 *
 * Class should be included only if this is an upgrade from branch 7.x-1.x
 * to branch 7.x-2.x
 */

if (db_field_exists('og_membership', 'group_type') && db_table_exists('og') && !db_table_exists('d6_og')) {

class OgMigrateMembership extends OgEntityMigration {

  /**
   * The name of the table migrate will query.
   */
  public $tableName = 'og_membership';

  /**
   * Indicate we are updating existing data.
   */
  protected $systemOfRecord = Migration::DESTINATION;

  public function __construct() {
    $this->description = t('Re-populate group ID and group entity, deprecating the OG group entity.');

    $this->query = db_select('og_membership', 'ogm')
      ->fields('ogm')
      ->condition('group_type', '', '=')
      ->orderBy('id');

    parent::__construct();

    // TODO: Is there a better way?
    $fields = array(
      'id',
      'group_type',
      'gid',
      'entity_type',
      'etid',
      'field_name',
    );

    foreach ($fields as $field) {
      $this->addFieldMapping($field, $field);
    }

  }

  public function prepareRow($row) {
    list($group_type, $gid) = $this->getNewGroupId($row->gid);
    if (empty($gid)) {
      // TODO: The OG membership was somehow not deleted when the group was deleted,
      // so do it now.
      // $row->delete();
      return;
    }

    // Re-populate the group ID and add the group type.
    $row->group_type = $group_type;
    $row->gid = $gid;

    $entity_type = $row->entity_type;

    if (!$entity = entity_load_single($entity_type, $row->etid)) {
      // TODO: The OG membership was somehow not deleted when the entity
      // was deleted, so do it now.
      // $row->delete();
      return;
    }

    if (!$group = entity_load_single($group_type, $gid)) {
      return;
    }

    list(,, $group_bundle) = entity_extract_ids($group_type, $group);
    if (!$field_name = og_get_best_group_audience_field($entity_type, $entity, $group_type, $group_bundle)) {
      // Create a new field. Pick an unused name.
      $field_name = substr("og_$group_type", 0, 32);
      $i = 1;
      while (field_info_field($field_name)) {
        $field_name = substr("og_$group_type", 0, 32 - strlen($i)) . $i;
        ++$i;
      }
    }
    $row->field_name = $field_name;

    parent::prepareRow($row);
  }
}

}