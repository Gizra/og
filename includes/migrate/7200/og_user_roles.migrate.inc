<?php

/**
 * @file
 * Update the group ID in user roles.
 *
 * Class should be included only if this is an upgrade from branch 7.x-1.x
 * to branch 7.x-2.x
 */

if (db_field_exists('og_membership', 'group_type') && db_table_exists('og') && !db_table_exists('d6_og')) {

class OgMigrateUserRoles extends OgEntityMigration {

  public $tableName = 'og_users_roles';

  protected $dependencies = array('OgMigrateRoles');

  protected $defaultRoles;

  protected $sourceKey = array(
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
    ),
    'rid' => array(
      'type' => 'int',
      'not null' => TRUE,
    ),
    'gid' => array(
      'type' => 'int',
      'not null' => TRUE,
    ),
  );

  public function __construct() {
    $this->description = t('Update the group ID in user roles.');

    $this->query = db_select('og_users_roles', 'ogur')
      ->condition('group_type', '', '=')
      ->fields('ogur');

    parent::__construct();

    $this->addFieldMapping('uid', 'uid');
    $this->addFieldMapping('rid', 'rid');
    $this->addFieldMapping('gid', 'gid');
    $this->addFieldMapping('group_type');
  }

  /**
   * Override Migration::preImport().
   */
  protected function preImport() {
    parent::preImport();
    $this->defaultRoles = og_roles('', '');
  }

  public function prepare($entity, stdClass $row) {
    list($group_type, $gid) = $this->getNewGroupId($row->gid);

    // Some installations might have missing entities, so we don't assume
    // they exist.
    if (!$gid || !$group = entity_load_single($group_type, $gid)) {
      return FALSE;
    }
    // Get the bundle from the entity.
    list(,, $bundle) = entity_extract_ids($group_type, $group);

    // Get the per-bundle roles, and replace the role ID, with the
    // per-bundle role ID.
    $og_roles = og_roles($group_type, $bundle, $gid);
    if (!empty($this->defaultRoles[$row->rid]) && $rid = array_search($this->defaultRoles[$row->rid], $og_roles)) {
      // Assign the new role ID.
      $entity->rid = $rid;
    }
    $entity->group_type = $group_type;
    $entity->gid = $gid;
  }

  /**
   * Override Migration::postImport().
   *
   * Remove the global roles. We didn't do so it in OgMigrateRoles as
   * we need it in this class, when calling og_roles().
   */
  protected function postImport() {
    if (!$this->isComplete() || !$this->defaultRoles) {
      return;
    }
    parent::postImport();

    foreach (array_keys($this->defaultRoles) as $rid) {
      og_role_delete($rid);
    }
  }
}

}
