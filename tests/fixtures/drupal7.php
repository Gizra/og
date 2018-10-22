<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('og_role', [
  'fields' => [
    'rid' => [
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
    ],
    'gid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'group_type' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ],
    'group_bundle' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ],
    'name' => [
      'type' => 'varchar',
      'length' => 64,
      'not null' => TRUE,
      'default' => '',
    ],
  ],
  'primary key' => ['rid'],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('og_role')
->fields(['rid', 'gid', 'name', 'group_type', 'group_bundle'])
->values([
  'rid' => 1,
  'gid' => 0,
  'name' => 'non-member',
  'group_type' => 'node',
  'group_bundle' => 'test_content_type',
])
->values([
  'rid' => 2,
  'gid' => 0,
  'name' => 'member',
  'group_type' => 'node',
  'group_bundle' => 'test_content_type',
])
->values([
  'rid' => 3,
  'gid' => 0,
  'name' => 'administrator member',
  'group_type' => 'node',
  'group_bundle' => 'test_content_type',
])
->values([
  'rid' => 4,
  'gid' => 0,
  'name' => 'non-member',
  'group_type' => 'taxonomy_term',
  'group_bundle' => 'test_vocabulary',
])
->values([
  'rid' => 5,
  'gid' => 0,
  'name' => 'member',
  'group_type' => 'taxonomy_term',
  'group_bundle' => 'test_vocabulary',
])
->values([
  'rid' => 6,
  'gid' => 0,
  'name' => 'administrator member',
  'group_type' => 'taxonomy_term',
  'group_bundle' => 'test_vocabulary',
])
->values([
  'rid' => 7,
  'gid' => 0,
  'name' => 'content creator',
  'group_type' => 'node',
  'group_bundle' => 'test_content_type',
])
->execute();

$connection->schema()->createTable('og_role_permission', [
  'fields' => [
    'rid' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'permission' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'module' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ],
  ],
  'primary key' => ['rid', 'permission'],
  'indexes' => ['permission' => ['permission']],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('og_role_permission')
->fields(['rid', 'permission', 'module'])
->values([
  'rid' => 3,
  'permission' => 'add user',
  'module' => 'og_ui',
])
->values([
  'rid' => 3,
  'permission' => 'administer group',
  'module' => 'og',
])
->values([
  'rid' => 3,
  'permission' => 'approve and deny subscription',
  'module' => 'og_ui',
])
->values([
  'rid' => 3,
  'permission' => 'manage members',
  'module' => 'og_ui',
])
->values([
  'rid' => 3,
  'permission' => 'manage permissions',
  'module' => 'og_ui',
])
->values([
  'rid' => 3,
  'permission' => 'manage roles',
  'module' => 'og_ui',
])
->values([
  'rid' => 3,
  'permission' => 'update group',
  'module' => 'og',
])
->values([
  'rid' => 7,
  'permission' => 'create article content',
  'module' => 'og',
])
->values([
  'rid' => 7,
  'permission' => 'create page content',
  'module' => 'og',
])
->values([
  'rid' => 6,
  'permission' => 'add user',
  'module' => 'og_ui',
])
->values([
  'rid' => 6,
  'permission' => 'administer group',
  'module' => 'og',
])
->values([
  'rid' => 6,
  'permission' => 'approve and deny subscription',
  'module' => 'og_ui',
])
->values([
  'rid' => 6,
  'permission' => 'manage members',
  'module' => 'og_ui',
])
->values([
  'rid' => 6,
  'permission' => 'manage permissions',
  'module' => 'og_ui',
])
->values([
  'rid' => 6,
  'permission' => 'manage roles',
  'module' => 'og_ui',
])
->values([
  'rid' => 6,
  'permission' => 'update group',
  'module' => 'og',
])
->execute();

$connection->schema()->createTable('og_users_roles', [
  'fields' => [
    'uid' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'rid' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'gid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'group_type' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ],
  ],
  'primary key' => ['uid', 'rid', 'gid'],
  'indexes' => ['rid' => ['rid']],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('og_users_roles')
->fields(['uid', 'rid', 'gid', 'group_type'])
->values([
  'uid' => 2,
  'rid' => 3,
  'gid' => 1,
  'group_type' => 'node',
])
->values([
  'uid' => 2,
  'rid' => 7,
  'gid' => 1,
  'group_type' => 'node',
])
->values([
  'uid' => 3,
  'rid' => 3,
  'gid' => 3,
  'group_type' => 'taxonomy_term',
])
->execute();

$connection->schema()->createTable('og_membership_type', [
  'fields' => [
    'id' => [
      'type' => 'serial',
      'not null' => TRUE,
    ],
    'name' => [
      'type' => 'varchar',
      // Avoids MySQL UTF-8 multi-byte length issue by setting length to 100 instead of 255.
      'length' => 100,
      'not null' => TRUE,
    ],
    'description' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ],
    'status' => [
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0x01,
      'size' => 'tiny',
    ],
    'module' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ],
  ],
  'primary key' => ['id'],
  'unique keys' => ['name' => ['name']],
]);

$connection->insert('og_membership_type')
->fields(['id', 'name', 'description', 'status', 'module', 'language'])
->values([
  'id' => 1,
  'name' => 'og_membership_type_default',
  'description' => 'Custom default description.',
  'status' => 2,
  'module' => 'og',
  'language' => 'en',
])
->execute();

$connection->schema()->createTable('og_membership', [
  'fields' => [
    'id' => [
      'type' => 'serial',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'type' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ],
    'etid' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_type' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'gid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'group_type' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'state' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
      'default' => '',
    ],
    'created' => [
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ],
    'field_name' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ],
  ],
  'primary key' => ['id'],
  'indexes' => [
    'entity' => ['etid', 'entity_type'],
    'group' => ['gid', 'group_type'],
    'group_type' => ['group_type'],
  ],
]);

$connection->insert('og_membership')
->fields([
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
->values([
  1,
  'og_membership_type_default',
  2,
  'user',
  1,
  'node',
  1,
  1308605919,
  'og_user_node',
  'en',
])
->values([
  2,
  'og_membership_type_default',
  3,
  'user',
  1,
  'node',
  2,
  1308610519,
  'og_user_node',
  'en',
])
->values([
  3,
  'og_membership_type_default',
  1,
  'user',
  1,
  'node',
  1,
  1308610519,
  'og_user_node',
  'en',
])
->values([
  4,
  'og_membership_type_default',
  2,
  'node',
  1,
  'node',
  1,
  1308610519,
  'og_group_ref',
  'en',
])
->values([
  5,
  'og_membership_type_default',
  6,
  'node',
  1,
  'node',
  1,
  1308610519,
  'og_group_ref',
  'en',
])
->values([
  6,
  'og_membership_type_default',
  3,
  'user',
  2,
  'taxonomy_term',
  1,
  1308610519,
  'og_user_node',
  'en',
])
->values([
  7,
  'og_membership_type_default',
  2,
  'user',
  2,
  'taxonomy_term',
  3,
  1308610519,
  'og_user_node',
  'en',
])
->values([
  8,
  'og_membership_type_default',
  5,
  'node',
  2,
  'taxonomy_term',
  1,
  1308610519,
  'og_group_ref',
  'en',
])
->values([
  10,
  'og_membership_type_default',
  7,
  'node',
  2,
  'taxonomy_term',
  1,
  1308610519,
  'og_group_ref',
  'en',
])
->execute();

$connection->schema()->createTable('field_data_og_user_node', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'og_user_node_target_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
    'target_id' => ['og_user_node_target_id'],
  ],
]);

$connection->schema()->createTable('field_revision_og_user_node', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'og_user_node_target_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'revision_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
    'target_id' => ['og_user_node_target_id'],
  ],
]);

$connection->schema()->createTable('field_data_og_group_ref', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'og_group_ref_target_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
    'target_id' => ['og_group_ref_target_id'],
  ],
]);

$connection->schema()->createTable('field_revision_og_group_ref', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'og_group_ref_target_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'revision_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
    'target_id' => ['og_group_ref_target_id'],
  ],
]);

$connection->schema()->createTable('field_data_group_group', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'group_group_value' => [
      'type' => 'int',
      'size' => 'normal',
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
  ],
]);

$connection->insert('field_data_group_group')
->fields([
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'group_group_value',
])
->values([
  'node',
  'test_content_type',
  0,
  1,
  1,
  'und',
  0,
  1,
])
->values([
  'taxonomy_term',
  'test_vocabulary',
  0,
  2,
  2,
  'und',
  0,
  1,
])
->execute();

$connection->schema()->createTable('field_revision_group_group', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'group_group_value' => [
      'type' => 'int',
      'size' => 'normal',
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'revision_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
  ],
]);

$connection->insert('field_revision_group_group')
->fields([
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'group_group_value',
])
->values([
  'node',
  'test_content_type',
  0,
  1,
  1,
  'und',
  0,
  1,
])
->values([
  'taxonomy_term',
  'test_vocabulary',
  0,
  2,
  2,
  'und',
  0,
  1,
])
->execute();

$connection->schema()->createTable('field_data_group_access', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'group_access_value' => [
      'type' => 'int',
      'size' => 'tiny',
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
  ],
]);
$connection->insert('field_data_group_access')
->fields([
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'group_access_value',
])
->values([
  'node',
  'test_content_type',
  0,
  1,
  1,
  'und',
  0,
  1,
])
->execute();

$connection->schema()->createTable('field_revision_group_access', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'entity_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
      'unsigned' => TRUE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 32,
      'not null' => TRUE,
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'size' => 'normal',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'group_access_value' => [
      'type' => 'int',
      'size' => 'tiny',
    ],
  ],
  'primary key' => ['entity_type', 'deleted', 'entity_id', 'revision_id', 'language', 'delta'],
  'indexes' => [
    'entity_type' => ['entity_type'],
    'bundle' => ['bundle'],
    'deleted' => ['deleted'],
    'entity_id' => ['entity_id'],
    'revision_id' => ['revision_id'],
    'language' => ['language'],
  ],
]);
$connection->insert('field_revision_group_access')
->fields([
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'group_access_value',
])
->values([
  'node',
  'test_content_type',
  0,
  1,
  1,
  'und',
  0,
  1,
])
->execute();

// Insert into field_config for the above fields.
$connection->insert('field_config')
->fields([
  'id',
  'field_name',
  'type',
  'module',
  'active',
  'storage_type',
  'storage_module',
  'storage_active',
  'locked',
  'data',
  'cardinality',
  'translatable',
  'deleted',
])
->values([
  'id' => 900,
  'field_name' => 'group_group',
  'type' => 'list_boolean',
  'module' => 'list',
  'active' => 1,
  'storage_type' => 'field_sql_storage',
  'storage_module' => 'field_sql_storage',
  'storage_active' => 1,
  'locked' => 0,
  'data' => 'a:8:{s:2:"id";i:900;s:12:"entity_types";a:0:{}s:7:"indexes";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:5:"no_ui";i:1;s:8:"settings";a:2:{s:14:"allowed_values";a:2:{i:0;s:16:"Not a group type";i:1;s:10:"Group type";}s:23:"allowed_values_function";s:0:"";}s:12:"translatable";i:0;s:12:"foreign keys";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:22:"field_data_group_group";a:1:{s:5:"value";s:17:"group_group_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:26:"field_revision_group_group";a:1:{s:5:"value";s:17:"group_group_value";}}}}}}',
  'cardinality' => 1,
  'translatable' => 0,
  'deleted' => 0,
])
->values([
  'id' => 901,
  'field_name' => 'og_group_ref',
  'type' => 'entityreference',
  'module' => 'entityreference',
  'active' => 1,
  'storage_type' => 'field_sql_storage',
  'storage_module' => 'field_sql_storage',
  'storage_active' => 1,
  'locked' => 0,
  'data' => 'a:9:{s:2:"id";i:901;s:12:"entity_types";a:0:{}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:5:"no_ui";i:1;s:8:"settings";a:4:{s:7:"handler";s:2:"og";s:16:"handler_settings";a:4:{s:9:"behaviors";a:2:{s:11:"og_behavior";a:1:{s:6:"status";i:1;}s:17:"views-select-list";a:1:{s:6:"status";i:0;}}s:15:"membership_type";s:26:"og_membership_type_default";s:4:"sort";a:1:{s:4:"type";s:4:"none";}s:14:"target_bundles";a:0:{}}s:14:"handler submit";s:14:"Change handler";s:11:"target_type";s:4:"node";}s:11:"target_type";s:0:"";s:12:"translatable";i:0;s:12:"foreign keys";a:1:{s:4:"node";a:2:{s:5:"table";s:4:"node";s:7:"columns";a:1:{s:9:"target_id";s:3:"nid";}}}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}}',
  'cardinality' => -1,
  'translatable' => 0,
  'deleted' => 0,
])
->values([
  'id' => 902,
  'field_name' => 'og_user_node',
  'type' => 'entityreference',
  'module' => 'entityreference',
  'active' => 1,
  'storage_type' => 'field_sql_storage',
  'storage_module' => 'field_sql_storage',
  'storage_active' => 1,
  'locked' => 0,
  'data' => 'a:9:{s:2:"id";i:902;s:12:"entity_types";a:0:{}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:5:"no_ui";i:1;s:8:"settings";a:4:{s:7:"handler";s:2:"og";s:16:"handler_settings";a:4:{s:9:"behaviors";a:2:{s:11:"og_behavior";a:1:{s:6:"status";i:1;}s:17:"views-select-list";a:1:{s:6:"status";i:0;}}s:15:"membership_type";s:26:"og_membership_type_default";s:4:"sort";a:1:{s:4:"type";s:4:"none";}s:14:"target_bundles";a:0:{}}s:14:"handler submit";s:14:"Change handler";s:11:"target_type";s:4:"node";}s:11:"target_type";s:0:"";s:12:"translatable";i:0;s:12:"foreign keys";a:1:{s:4:"node";a:2:{s:5:"table";s:4:"node";s:7:"columns";a:1:{s:9:"target_id";s:3:"nid";}}}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}}',
  'cardinality' => -1,
  'translatable' => 0,
  'deleted' => 0,
])
->values([
  'id' => 903,
  'field_name' => 'group_access',
  'type' => 'list_boolean',
  'module' => 'list',
  'active' => 1,
  'storage_type' => 'field_sql_storage',
  'storage_module' => 'field_sql_storage',
  'storage_active' => 1,
  'locked' => 0,
  'data' => 'a:7:{s:12:"entity_types";a:0:{}s:12:"foreign_keys";a:0:{}s:7:"indexes";a:1:{s:5:"value";a:1:{i:0;a:1:{i:0;s:5:"value";}}}s:5:"no_ui";b:1;s:8:"settings";a:2:{s:14:"allowed_values";a:2:{i:0;s:36:"Public- accessible to all site users";i:1;s:42:"Private - accessible only to group members";}s:23:"allowed_values_function";s:0:"";}s:12:"translatable";i:0;s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}}',
  'cardinality' => 1,
  'translatable' => 0,
  'deleted' => 0,
])
->execute();

$connection->insert('field_config_instance')
->fields([
  'id',
  'field_id',
  'field_name',
  'entity_type',
  'bundle',
  'data',
  'deleted',
])
->values([
  'id' => 900,
  'field_id' => 900,
  'field_name' => 'group_group',
  'entity_type' => 'node',
  'bundle' => 'test_content_type',
  'data' => 'a:9:{s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";i:1;}}s:11:"description";s:0:"";s:7:"display";a:2:{s:7:"default";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:2;}s:6:"teaser";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:0;}}s:5:"label";s:10:"Group type";s:8:"required";i:1;s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:10:"view modes";a:2:{s:4:"full";a:3:{s:15:"custom settings";b:0;s:5:"label";s:4:"Full";s:4:"type";s:18:"og_group_subscribe";}s:6:"teaser";a:3:{s:15:"custom settings";b:0;s:5:"label";s:6:"Teaser";s:4:"type";s:18:"og_group_subscribe";}}s:6:"widget";a:5:{s:6:"active";i:1;s:6:"module";s:7:"options";s:8:"settings";a:0:{}s:4:"type";s:15:"options_buttons";s:6:"weight";i:3;}s:11:"widget_type";s:14:"options_select";}',
  'deleted' => 0,
])
->values([
  'id' => 901,
  'field_id' => 900,
  'field_name' => 'group_group',
  'entity_type' => 'taxonomy_term',
  'bundle' => 'test_vocabulary',
  'data' => 'a:9:{s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";i:1;}}s:11:"description";s:0:"";s:7:"display";a:2:{s:7:"default";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:2;}s:6:"teaser";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:0;}}s:5:"label";s:10:"Group type";s:8:"required";i:1;s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:10:"view modes";a:2:{s:4:"full";a:3:{s:15:"custom settings";b:0;s:5:"label";s:4:"Full";s:4:"type";s:18:"og_group_subscribe";}s:6:"teaser";a:3:{s:15:"custom settings";b:0;s:5:"label";s:6:"Teaser";s:4:"type";s:18:"og_group_subscribe";}}s:6:"widget";a:5:{s:6:"active";i:1;s:6:"module";s:7:"options";s:8:"settings";a:0:{}s:4:"type";s:15:"options_buttons";s:6:"weight";i:3;}s:11:"widget_type";s:14:"options_select";}',
  'deleted' => 0,
])
->values([
  'id' => 902,
  'field_id' => 901,
  'field_name' => 'og_group_ref',
  'entity_type' => 'node',
  'bundle' => 'article',
  'data' => 'a:8:{s:13:"default_value";N;s:11:"description";s:0:"";s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:6:"module";s:5:"og_ui";s:8:"settings";a:0:{}s:4:"type";s:15:"og_list_default";s:6:"weight";i:0;}}s:5:"label";s:15:"Groups audience";s:8:"required";b:0;s:8:"settings";a:2:{s:9:"behaviors";a:1:{s:9:"og_widget";a:3:{s:5:"admin";a:1:{s:11:"widget_type";s:28:"entityreference_autocomplete";}s:7:"default";a:1:{s:11:"widget_type";s:14:"options_select";}s:6:"status";i:1;}}s:18:"user_register_form";b:0;}s:10:"view modes";a:1:{s:4:"full";a:3:{s:15:"custom settings";b:0;s:5:"label";s:4:"Full";s:4:"type";s:15:"og_list_default";}}s:6:"widget";a:4:{s:6:"module";s:2:"og";s:8:"settings";a:0:{}s:4:"type";s:10:"og_complex";s:6:"weight";i:0;}}',
  'deleted' => 0,
])
->values([
  'id' => 903,
  'field_id' => 901,
  'field_name' => 'og_group_ref',
  'entity_type' => 'node',
  'bundle' => 'forum',
  'data' => 'a:8:{s:13:"default_value";N;s:11:"description";s:0:"";s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:6:"module";s:5:"og_ui";s:8:"settings";a:0:{}s:4:"type";s:15:"og_list_default";s:6:"weight";i:0;}}s:5:"label";s:15:"Groups audience";s:8:"required";b:0;s:8:"settings";a:2:{s:9:"behaviors";a:1:{s:9:"og_widget";a:3:{s:5:"admin";a:1:{s:11:"widget_type";s:28:"entityreference_autocomplete";}s:7:"default";a:1:{s:11:"widget_type";s:14:"options_select";}s:6:"status";i:1;}}s:18:"user_register_form";b:0;}s:10:"view modes";a:1:{s:4:"full";a:3:{s:15:"custom settings";b:0;s:5:"label";s:4:"Full";s:4:"type";s:15:"og_list_default";}}s:6:"widget";a:4:{s:6:"module";s:2:"og";s:8:"settings";a:0:{}s:4:"type";s:10:"og_complex";s:6:"weight";i:0;}}',
  'deleted' => 0,
])
->values([
  'id' => 904,
  'field_id' => 902,
  'field_name' => 'og_user_node',
  'entity_type' => 'user',
  'bundle' => 'user',
  'data' => 'a:8:{s:13:"default_value";N;s:11:"description";s:0:"";s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:6:"module";s:5:"og_ui";s:8:"settings";a:0:{}s:4:"type";s:15:"og_list_default";s:6:"weight";i:0;}}s:5:"label";s:17:"Groups membership";s:6:"widget";a:4:{s:6:"module";s:2:"og";s:8:"settings";a:0:{}s:4:"type";s:10:"og_complex";s:6:"weight";i:0;}s:8:"required";b:0;s:8:"settings";a:2:{s:9:"behaviors";a:1:{s:9:"og_widget";a:3:{s:5:"admin";a:1:{s:11:"widget_type";s:28:"entityreference_autocomplete";}s:7:"default";a:1:{s:11:"widget_type";s:14:"options_select";}s:6:"status";i:1;}}s:18:"user_register_form";b:0;}s:10:"view modes";a:1:{s:4:"full";a:3:{s:15:"custom settings";b:0;s:5:"label";s:4:"Full";s:4:"type";s:15:"og_list_default";}}}',
  'deleted' => 0,
])
->values([
  'id' => 905,
  'field_id' => 903,
  'field_name' => 'group_access',
  'entity_type' => 'node',
  'bundle' => 'test_content_type',
  'data' => 'a:9:{s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";i:0;}}s:11:"description";s:0:"";s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"Above";s:6:"module";s:4:"list";s:8:"settings";a:0:{}s:4:"type";s:12:"list_default";s:6:"weight";i:0;}}s:5:"label";s:16:"Group visibility";s:8:"required";i:1;s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:10:"view modes";a:1:{s:4:"full";a:2:{s:5:"label";s:5:"above";s:4:"type";s:13:"options_onoff";}}s:6:"widget";a:4:{s:6:"module";s:7:"options";s:8:"settings";a:0:{}s:4:"type";s:15:"options_buttons";s:6:"weight";i:0;}s:11:"widget_type";s:14:"options_select";}',
  'deleted' => 0,
])
->execute();

