#!/usr/bin/env php
<?php
// $Id: generate-d6-content.sh,v 1.3 2010/09/11 00:39:49 webchick Exp $

/**
 * Generate content for a Drupal 6 database to test the upgrade process.
 *
 * Run this script at the root of an existing Drupal 6 installation.
 * Steps to use this generation script:
 * - Install drupal 6.
 * - Run this script from your Drupal ROOT directory.
 * - Use the dump-database-d6.sh to generate the D7 file
 *   modules/simpletest/tests/upgrade/database.filled.php
 *
 * This scripts produces the following scenario:
 * - Nid 1: Group without posts.
 * - Nid 2: Group with 3 posts (Nid 3 - 5).
 * - Nid 6: Orphan group content (i.e. no attached to a group).
 * - Nid 7, 8: Groups that share a group content (Nid 9).
 * - Nid 10: Group with members:
 *   - Uid 3: Group manager.
 *   - Uid 4: Pending member.
 *   - Uid 5: active member.
 *   - Uid 6: Pending admin member.
 *   - Uid 7: Active admin member.
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
$modules_to_enable          = array('og', 'user', 'node');

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

// Create new content types.
module_load_include('inc','node','content_types');
$form_state = array( 'values' => array() );
$form_state['values']['name'] = 'test-group';
$form_state['values']['type'] = 'test_group';
$form_state['values']['og_content_type_usage'] = 'group';
drupal_execute('node_type_form',$form_state);

$form_state = array(  'values'=>array() );
$form_state['values']['name'] = 'test-post-group';
$form_state['values']['type'] = 'test_post_group';
$form_state['values']['og_content_type_usage'] = 'group_post_standard';
drupal_execute('node_type_form',$form_state);

// variable_set('og_content_type_usage_test_group', 'group');
// variable_set('og_content_type_usage_test_post_group', 'group_post_standard');

// Create users with ID 3 to 7.
$user_ids=array();
foreach (range(3,7) as $i) {
  $user_values = array();
  $user_values['name'] = 'og_test_user' . $i;
  $user_values['mail'] = 'og_test_user' . $i . '@example.com';
  $user_values['pass'] = user_password(5);
  $user_values['status'] = 1;
  $user=user_save(NULL,$user_values);
  $user_ids[ $i ] = $user->uid;
}

/**
 * Organic groups content generation interface.
 */
interface ogContent {
  //returns a list of groups configuration arrays
  public function groupList($user_ids);
  //returns a list of posts configuation arrays
  //groups nids generated in groupList are provided in groups parameter
  public function postList($user_ids,$groups);
  //performs actions of the generated groups and posts
  public function groupActions($user_ids,$groups,$posts);
}

/**
 * A group without posts.
 */
class ogGroupNoPosts implements ogContent {
  public function groupList($user_ids) {
    $list = array();
    $list[] = array(
      'title' => 'group-without-posts',
      'uid' => $user_ids[3],
      'body' => 'group without posts'
    );
    return $list;
  }

  public function postList($user_ids,$groups) {
    return array();
  }

  public function groupActions($user_ids,$groups,$posts) {}
}

/**
 * A group with three posts.
 */
class ogGroupThreePosts implements ogContent {

  public function groupList($user_ids) {
    $list = array();
    $list[] = array(
      'title' => 'group-with-3-posts',
      'uid' => $user_ids[3],
      'body' => 'group with 3 posts'
    );
    return $list;
  }

  public function postList($user_ids,$groups) {
    $gid = $groups[0];
    $list = array();
    foreach ( array(1,2,3) as $itr){
      $list[] = array(
        'title' => 'group-posts-' . $itr,
        'uid' => $user_ids[3],
        'body' => 'group posts ' . $itr,
        'og_groups' => array($gid)
      );
    }
    return $list;
  }

  public function groupActions($user_ids,$groups,$posts){}
}

/**
 * A group post not associated to any other group.
 */
class ogGroupOrphanPost implements ogContent {

  public function groupList($user_ids) {
    return array();
  }

  public function postList($user_ids,$groups) {
    $list = array();
    $list[] = array(
      'title' => 'group-posts-orphan',
      'uid' => $user_ids[3],
      'body' => 'group posts orphan',
      'og_groups' => array()
    );
    return $list;
  }

  public function groupActions($user_ids,$groups,$posts){}
}

/**
 * A group post associated with two groups.
 */
class ogGroupPostMultipleGroups implements ogContent {

  public function groupList($user_ids) {
    $list = array();
    $list['alpha'] = array(
      'title' => 'group-alpha',
      'uid' => $user_ids[3],
      'body' => 'group alpha'
    );
    $list['beta'] =array(
      'title' => 'group-beta',
      'uid' => $user_ids[3],
      'body' => 'group beta'
    );
    return $list;
  }

  public function postList($user_ids,$groups) {
    $list = array();

    $gid_b = $groups['beta'];
    $gid_a = $groups['alpha'];

    $list[] = array(
      'title' => 'group-posts-ab',
      'uid' => $user_ids[3],
      'body' => 'group posts ab',
      'og_groups' => array($gid_a,$gid_b)
    );
    return $list;
  }
  public function groupActions($user_ids,$groups,$posts){}
}


/**
 * A group with multiple members.
 */
class ogGroupUserAction implements ogContent {
  public function groupList($user_ids) {
    $list = array();
    $list[] = array(
      'title' => 'group-with-user-action',
      'uid' => $user_ids[3],
      'body' => 'group with user action'
    );
    return $list;
  }

  public function postList($user_ids, $groups) {
    return array();
  }

  public function groupActions($user_ids, $groups,$posts){
    $gid = $groups[0];
    // - user ID 4 as pending member.
    og_save_subscription( $gid , $user_ids[4] , array('is_active' => 0));
    // - user ID 5 as active member.
    og_save_subscription( $gid , $user_ids[5] , array('is_active' => 1));
    // - user ID 6 as pending admin member.
    og_save_subscription( $gid , $user_ids[6] , array('is_active' => 0 , 'is_admin' => 1));
    // - user ID 7 as active admin member.
    og_save_subscription( $gid , $user_ids[7] , array('is_active' => 1 , 'is_admin' => 1));
  }
}

// Start content generation.
$og_content_config = array();
$og_content_config[] = new ogGroupNoPosts();
$og_content_config[] = new ogGroupThreePosts();
$og_content_config[] = new ogGroupOrphanPost();
$og_content_config[] = new ogGroupPostMultipleGroups();
$og_content_config[] = new ogGroupUserAction();

foreach ($og_content_config as $content_config){
  $groups = array_map('og_group_node' , $content_config->groupList($user_ids) );
  $posts = array_map('og_post_node', $content_config->postList($user_ids, $groups));
  $content_config->groupActions($user_ids, $groups, $posts);
}

/**
 * Create a group node.
 */
function og_group_node($values = array()) {
  $node=(object)$values;
  $node->type = 'test_group';
  node_save($node);
  return $node->nid;
}

/**
 * Create a group post.
 */
function og_post_node($values = array()){
  $node=(object)$values;
  $node->type = 'test_post_group';
  node_save($node);
  return $node->nid;
}
