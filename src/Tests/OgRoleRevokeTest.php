<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgRoleRevokeTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the revocation of group roles.
 *
 * @group og
 */
class OgRoleRevokeTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  public function testOgRoleRevoke()  {
    // Create a user.
    $user1 = $this->drupalCreateUser();

    // Add OG group field to the entity_test's "main" bundle.
    og_create_field(OG_GROUP_FIELD, 'entity_test', 'main');

    // Create two groups entity1 and entity2.
    $entity1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $entity2 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity2);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    // Create a role named 'role1'.
    $role1 = og_role_create('role1', 'entity_test', 0, 'main');
    og_role_save($role1);

    // Create a role named 'role2'.
    $role2 = og_role_create('role2', 'entity_test', 0, 'main');
    og_role_save($role2);

    // Grant 'role1' to user1 at entity1 and 'role2' to user1 at entity2
    og_role_grant('entity_test', $entity1->pid, $user1->uid, $role1->rid);
    og_role_grant('entity_test', $entity2->pid, $user1->uid, $role2->rid);

    // Unsubscribe user1 from entity1.
    og_ungroup('entity_test', $entity1->pid, 'user', $user1->uid);

    $this->assertFalse(og_get_user_roles('entity_test', $entity1->pid, $user1->uid, FALSE), t('User is unsubscribed from group, so role was revoked'));
    $this->assertTrue(og_get_user_roles('entity_test', $entity2->pid, $user1->uid, FALSE), t('User is still subscribed to group, so return value is not empty'));

    $uid = $user1->uid;
    // Delete user1.
    user_delete($user1->uid);

    $result = db_query('SELECT * FROM {og_users_roles} WHERE uid = :uid', array(':uid' => $uid));
    $this->assertFalse($result->rowCount(), t('User is removed, so all roles of this user were revoked'));
  }

}
