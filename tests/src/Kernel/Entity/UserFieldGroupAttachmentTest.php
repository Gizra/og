<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\og\Og;
use Drupal\user\Entity\User;

/**
 * Tests that a group reference field attached to the user entity upon group
 * type creation.
 *
 * @group og
 */
class UserFieldGroupAttachmentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'field', 'og', 'entity_test', 'og_test'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installSchema('system', 'sequences');

    $group_bundle = Unicode::strtolower($this->randomMachineName());

    // Create users.
    $this->user = User::create(['name' => $this->randomString()]);
    $this->user->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $group_bundle);

    // Create a group and associate with user 1.
    $this->group = EntityTest::create([
      'type' => $group_bundle,
      'name' => $this->randomString(),
      'user_id' => $this->user->id(),
    ]);
    $this->group->save();
  }

  /**
   * Test field creation for user upon group creation.
   */
  public function testFieldCreationValidation() {
    $fields = array_keys(\Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('user', 'user'));

    // Verify the field exists.
    $this->assertTrue(in_array('og_user_entity_test', $fields));

    // Get another bundle of the user entity.
    $fields = array_keys(\Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('user', 'og_user_bundle'));
    $this->assertTrue(in_array('og_user_entity_test', $fields));
  }

}
