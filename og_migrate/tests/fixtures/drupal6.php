<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('og', [
  'fields' => [
    'nid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    // 0, 1, 2, 3
    'og_selective' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'og_description' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ],
    'og_theme' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ],
    'og_register' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'og_directory' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'og_language' => [
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ],
    'og_private' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
  ],
  'primary key' => ['nid'],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('og')
->fields([
  'nid',
  'og_selective',
  'og_description',
  'og_theme',
  'og_register',
  'og_directory',
  'og_language',
  'og_private',
])
->values([
  '14',
  '0',
  'United Federation of Planets',
  'garland',
  '0',
  '1',
  '',
  '0',
])
->values([
  '15',
  '1',
  'Klingon Empire',
  '',
  '0',
  '1',
  '',
  '1',
])
->values([
  '16',
  '2',
  'Romulan Empire',
  '',
  '0',
  '1',
  '',
  '1',
])
->values([
  '17',
  '3',
  'Ferengi Commerce Authority',
  '',
  '0',
  '0',
  '',
  '0',
])
->execute();

$connection->schema()->createTable('og_uid', [
  'fields' => [
    'nid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'og_role' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'is_active' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'is_admin' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 0,
    ],
    'uid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'created' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => FALSE,
      'default' => 0,
    ],
    'changed' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => FALSE,
      'default' => 0,
    ],
  ],
  'primary key' => ['nid', 'uid'],
  'indexes' => [
    'uid' => ['uid'],
  ],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('og_uid')
->fields([
  'nid',
  'og_role',
  'is_active',
  'is_admin',
  'uid',
  'created',
  'changed',
])
->values([
  '14',
  '0',
  '1',
  '1',
  '2',
  '1391152253',
  '1391152253',
])
->values([
  '15',
  '0',
  '1',
  '1',
  '8',
  '1391152254',
  '1391152254',
])
->values([
  '16',
  '0',
  '1',
  '1',
  '15',
  '1391152255',
  '1391152255',
])
->values([
  '17',
  '0',
  '1',
  '1',
  '16',
  '1391152255',
  '1391152255',
])
->values([
  '14',
  '0',
  '1',
  '0',
  '17',
  '1390151054',
  '1390162255',
])
->values([
  '15',
  '0',
  '0',
  '0',
  '17',
  '1390151054',
  '1390162255',
])
->values([
  '16',
  '0',
  '1',
  '0',
  '16',
  '1390151054',
  '1390162255',
])
->execute();

$connection->schema()->createTable('og_ancestry', [
  'fields' => [
    'nid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'group_nid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['nid', 'group_nid'],
  'indexes' => [
    'group_nid' => ['group_nid'],
  ],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('og_ancestry')
->fields(['nid', 'group_nid'])
->values(['3', '14'])
->values(['7', '14'])
->values(['8', '14'])
->values(['9', '14'])
->values(['4', '15'])
->values(['8', '15'])
->values(['5', '16'])
->values(['7', '16'])
->values(['6', '17'])
->execute();

$connection->schema()->createTable('og_access_post', [
  'fields' => [
    'nid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'og_public' => [
      'type' => 'int',
      'size' => 'tiny',
      'default' => 1,
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['nid'],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('og_access_post')
->fields(['nid', 'og_public'])
->values(['3', '1'])
->values(['7', '1'])
->values(['8', '1'])
->values(['9', '0'])
->values(['4', '1'])
->values(['5', '0'])
->values(['6', '0'])
->execute();

$connection->schema()->createTable('og_notifications', [
  'fields' => [
    'uid' => [
      'type' => 'int',
      'size' => 'normal',
      'not null' => TRUE,
    ],
    'autosubscribe' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => -1,
    ],
  ],
]);

$connection->insert('og_notifications')
->fields(['uid', 'autosubscribe'])
->values(['2', '-1'])
->values(['8', '-1'])
->values(['15', '1'])
->values(['16', '1'])
->values(['17', '0'])
->execute();
