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
$modules_to_enable          = array('og','user');

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

// why not test node_type_form ?
// this has changed in og7 , new hook are introduced
	module_load_include('inc','node','content_types');
	$form_state=array( 'values'=>array() );
	$form_state['values']['name']='test-group';
	$form_state['values']['type']='test_group';
	$form_state['values']['og_content_type_usage']='group';
	drupal_execute('node_type_form',$form_state);

	$form_state=array( 'values'=>array() );
	$form_state['values']['name']='test-post-group';
	$form_state['values']['type']='test_post_group';
	$form_state['values']['og_content_type_usage']='group_post_standard';
	drupal_execute('node_type_form',$form_state);

// Create six users 
// identified by range(3,7)
$user_ids=array();
foreach ( range(3,7) as $i ) {
	$user_values=array();
	$user_values['name']='og_test_user'.$i;
	$user_values['mail']='og_test_user'.$i.'@example.com';
	$user_values['pass']=user_password(5);
	$user_values['status']=1;
	$user=user_save(NULL,$user_values);
	$user_ids[ $i ]=$user->uid;
}

// 1) Create group by user ID 3 with no group posts.
	$node=new stdClass();
	$node->type='test_group';
	$node->title='group-without-posts';
	$node->uid=$user_ids[3];
	$node->body='group without posts';
	node_save($node);
	
// 2) Create group by user ID 3 with 3 group posts.
	$node=new stdClass();
	$node->type='test_group';
	$node->title='group-with-3-posts';
	$node->uid=$user_ids[3];
	$node->body='group with 3 posts';
	node_save($node);
	$nids[]=$node->nid;
	$gid=$node->nid;
		/*3 posts*/	
		foreach( array(1,2,3) as $itr){
			$node=new stdClass();
			$node->type='test_post_group';
			$node->title='group-posts-'.$itr;
			$node->uid=$user_ids[3];
			$node->body='group posts '.$itr;
			$node->og_groups=array($gid);
			node_save($node);
			$nids[]=$node->nid;
		}
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
