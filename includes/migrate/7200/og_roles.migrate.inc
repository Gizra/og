<?php

/**
 * @file
 * Add per-bundle OG roles.
 *
 * Class should be included only if this is an upgrade from branch 7.x-1.x
 * to branch 7.x-2.x
 */


if (db_field_exists('og_membership', 'group_type') && db_table_exists('og') && !db_table_exists('d6_og')) {

class OgMigrateRoles extends OgEntityMigration {

  public $tableName = 'og_role';

  public $keyName = 'rid';

  protected $dependencies = array('OgMigrateGroupAudience');

  /**
   * Indicate we are updating existing data.
   */
  protected $systemOfRecord = Migration::DESTINATION;

  public function __construct() {
    $this->description = t('Add per-bundle OG roles.');

    $this->query = db_select('og_role', 'ogr')
      ->fields('ogr', array('rid', 'gid', 'group_bundle'))
      ->condition('gid', 0, '>')
      ->condition('group_type', '', '=')
      ->condition('group_bundle', '', '=')
      ->orderBy('rid');

    parent::__construct();

    $this->addFieldMapping('rid', 'rid');
    $this->addFieldMapping('gid', 'gid');
    $this->addFieldMapping('group_type', 'group_type');
    $this->addFieldMapping('group_bundle', 'group_bundle');
  }

  /**
   * Override Migration::preImport().
   */
  protected function preImport() {
    parent::preImport();
    foreach (og_get_all_group_bundle() as $group_type => $bundles) {
      foreach ($bundles as $bundle => $label) {
        og_roles_override($group_type, $bundle, 0);
      }
    }
  }

  public function prepareRow($row) {
    list($group_type, $gid) = $this->getNewGroupId($row->gid);
    // Some installations might have missing entities, so we don't assume
    // they exist.
    if (!$gid || !$group = entity_load_single($group_type, $gid)) {
      return FALSE;
    }
    list(,,$bundle) = entity_extract_ids($group_type, $group);

    $row->group_type = $group_type;
    $row->gid = $gid;
    $row->group_bundle = $bundle;
    parent::prepareRow($row);
  }
}

}