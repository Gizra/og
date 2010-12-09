<?php //#!/usr/bin/env php ?>
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

module_load_include('inc', 'node', 'node.pages');
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
		}
// 3) Create group by user ID 3 with:
	$node=new stdClass();
	$node->type='test_group';
	$node->title='group-with-user-action';
	$node->uid=$user_ids[3];
	$node->body='group with user action';
	node_save($node);
	$gid=$node->nid;
// - user ID 4 as pending member.
	og_save_subscription($gid,$user_ids[4], array( 'is_active'=>0 ) );
// - user ID 5 as active member.
	og_save_subscription($gid,$user_ids[5], array( 'is_active'=>1 ) );
// - user ID 6 as pending admin member.
	og_save_subscription($gid,$user_ids[6], array( 'is_active'=>0,'is_admin'=>1 ) );
// - user ID 7 as active admin member.
	og_save_subscription($gid,$user_ids[7], array( 'is_active'=>1,'is_admin'=>1 ) );
// 4) Create group post not associated to any other group.
//
	$node=new stdClass();
	$node->type='test_post_group';
	$node->title='group-posts-orphan';
	$node->uid=$user_ids[3];
	$node->body='group posts orphan';
	$node->og_groups=array();
	node_save($node);
// 5) Create group posts associated to group node ID 1, 2.

	$node=new stdClass();
	$node->type='test_group';
	$node->title='group-alpha';
	$node->uid=$user_ids[3];
	$node->body='group alpha';
	node_save($node);
	$gid_a=$node->nid;

	$node=new stdClass();
	$node->type='test_group';
	$node->title='group-beta';
	$node->uid=$user_ids[3];
	$node->body='group beta';
	node_save($node);
	$gid_b=$node->nid;

	$node=new stdClass();
	$node->type='test_post_group';
	$node->title='group-posts-orphan';
	$node->uid=$user_ids[3];
	$node->body='group posts orphan';
	$node->og_groups=array($gid_a,$gid_b);
	node_save($node);
