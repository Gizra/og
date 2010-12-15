#!/usr/bin/env php
<?php
// $Id$

/**
 * Generate content for a Drupal 6 database to test the upgrade process.
 *
 * Run this script at the root of an existing Drupal 6 installation.
 * Steps to use this generation script:
 * - Install drupal 6.
 * - Run this script from your Drupal ROOT directory.
 * - Use the dump-database-d6.sh to generate the D7 file
 *   modules/simpletest/tests/upgrade/database.filled.php
 */

// Define settings.
$cmd = 'index.php';
$_SERVER['HTTP_HOST']       = 'default';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['SERVER_SOFTWARE'] = NULL;
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['QUERY_STRING']    = '';
$_SERVER['PHP_SELF']        = $_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_USER_AGENT'] = 'console';
$modules_to_enable          = array('og');

// Bootstrap Drupal.
include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Enable requested modules
include_once './modules/system/system.admin.inc';
$form = system_modules();
foreach ($modules_to_enable as $module) {
  $form_state['values']['status'][$module] = TRUE;
}
$form_state['values']['disabled_modules'] = $form['disabled_modules'];
system_modules_submit(NULL, $form_state);
unset($form_state);

// Run cron after installing
drupal_cron_run();

// Make Page content type a group, and Story content type a group post.
variable_set('og_content_type_usage_page', 'group');
variable_set('og_content_type_usage_story', 'group_post_standard');


// Create six users
for ($i = 0; $i < 6; $i++) {
  $name = "test user $i";
  $pass = md5("test PassW0rd $i !(.)");
  $mail = "test$i@example.com";
  $now = mktime(0, 0, 0, 1, $i + 1, 2010);
  db_query("INSERT INTO {users} (name, pass, mail, status, created, access) VALUES ('%s', '%s', '%s', %d, %d, %d)", $name, $pass, $mail, 1, $now, $now);
}

// 1) Create group by user ID 3 with no group posts.
//
// 2) Create group by user ID 3 with 3 group posts.
//
// 3) Create group bu user ID 3 with:
// - user ID 4 as pending member.
// - user ID 5 as active member.
// - user ID 6 as pending admin member.
// - user ID 7 as active admin member.
//
// 4) Create group post not associated to any other group.
//
// 5) Create group posts associated to group node ID 1, 2.

$node_id = 0;
$revision_id = 0;
module_load_include('inc', 'node', 'node.pages');


$uid = 3;

for ($i = 0; $i < 2; $i++) {
  $node = new stdClass;
  $node->uid = $uid;
  $node->type = 'page';
  $node->sticky = 0;
  ++$node_id;
  ++$revision_id;
  $node->title = "group node title $node_id rev $revision_id (i=$i)";
  $node->description = "description for group node title $node_id rev $revision_id (i=$i)";

  $node->status = intval($i / 4) % 2;
  $node->language = '';
  $node->revision = $i < 12;
  $node->promote = $i % 2;
  $node->created = $now + $i * 86400;
  $node->log = "added $i node";

  node_save($node);
}
