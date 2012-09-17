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

    $this->softDependencies = array('OgMigrateGroupAudience');

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

    $entity = entity_load_single($entity_type, $row->etid);
    if (!$entity) {
      // TODO: The OG membership was somehow not deleted when the entity
      // was deleted, so do it now.
      // $row->delete();
      return;
    }
    list(,, $bundle) = entity_extract_ids($row->entity_type, $entity);
    $row->field_name = OG_AUDIENCE_FIELD . '_' . $entity_type . '_' . $bundle;

    parent::prepareRow($row);
  }
}

}