<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\UserFieldGroupAttachmentTest.php.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Tests getting the memberships of an entity.
 *
 * @group og
 */
class UserFieldGroupAttachmentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'field', 'og', 'entity_test', 'node'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group;

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The machine name of the group content node type.
   *
   * @var string
   */
  protected $groupContentBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test');
    $this->installSchema('system', 'sequences');

    $this->groupBundle = Unicode::strtolower($this->randomMachineName());
    $this->groupContentBundle = Unicode::strtolower($this->randomMachineName());

    // Create users.
    $this->user= User::create(['name' => $this->randomString()]);
    $this->user->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with user 1.
    $this->group = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->user->id(),
    ]);
    $this->group->save();

    // Creating a node type and define it as a group.
    NodeType::create([
      'type' => 'post',
      'name' => $this->randomString(),
    ]);
    Og::groupManager()->addGroup('node', 'post');
  }

  /**
   * Test field creation for user upon group creation.
   */
  public function testFieldCreationValidation() {
    $fields = array_keys(\Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('user', 'user'));

    // Verify the field exists.
    $this->assertTrue(in_array('og_user_entity_test', $fields) && in_array('og_user_node', $fields));

    $field_config = FieldConfig::loadByName('user', 'user', 'og_user_entity_test');

    $this->container->get('account_switcher')->switchTo($this->user);
    $referenceable_entities = Og::getSelectionHandler($field_config)->getReferenceableEntities();

    $this->assertEquals(array_keys($referenceable_entities[$this->group->bundle()]), [$this->group->id()]);
  }

}
