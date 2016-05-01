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
  public static $modules = ['system', 'user', 'field', 'og', 'entity_test'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user1;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user3;

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group1;

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group2;

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
    $this->installEntitySchema('entity_test');
    $this->installSchema('system', 'sequences');

    $this->groupBundle = Unicode::strtolower($this->randomMachineName());
    $this->groupContentBundle = Unicode::strtolower($this->randomMachineName());

    // Create users.
    $this->user1 = User::create(['name' => $this->randomString()]);
    $this->user1->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with user 1.
    $this->group1 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->user1->id(),
    ]);
    $this->group1->save();
  }

  /**
   * Test field creation for user upon group creation.
   */
  public function testFieldCreationValidation() {
    $fields = array_keys(\Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('user', 'user'));

    // Verify the field exists.
    $this->assertTrue(in_array('og_user_entity_test', $fields));

    $field_config = FieldConfig::loadByName('user', 'user', 'og_user_entity_test');

    $this->container->get('account_switcher')->switchTo($this->user1);
    $referenceable_entities = Og::getSelectionHandler($field_config);
    $referenceable_entities->getReferenceableEntities();

    $this->assertTrue(1,1);
  }

}
