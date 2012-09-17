<?php

/**
 * @file
 * Add new group-audience fields to group content bundles.
 *
 * Class should be included only if this is an upgrade from branch 7.x-1.x
 * to branch 7.x-2.x
 */

if (db_field_exists('og_membership', 'group_type') && db_table_exists('og') && !db_table_exists('d6_og')) {


class OgMigrateGroupAudience extends MigrationBase {

  public function __construct() {
    parent::__construct();
    $this->description = t('Add new group-audience fields to group content bundles.');
  }

  public function isComplete() {
    return !variable_get('og_migrate_7200_field_instances', array());
  }

  public function import() {
    if (!$instances = variable_get('og_migrate_7200_field_instances', array())) {
      return MigrationBase::RESULT_COMPLETED;
    }

    foreach ($instances as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        // Add a group-audience field to the bundle.
        $og_field = og_fields_info(OG_AUDIENCE_FIELD);
        $field_name = OG_AUDIENCE_FIELD . '_' . $entity_type . '_' . $bundle;

        og_create_field($field_name, $entity_type, $bundle, $og_field);
      }
    }

    variable_del('og_migrate_7200_field_instances');

    // Must return one of the MigrationBase RESULT constants.
    return MigrationBase::RESULT_COMPLETED;
  }
}

}