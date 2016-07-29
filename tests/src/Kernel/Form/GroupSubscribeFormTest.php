<?php

namespace Drupal\Tests\og\Kernel\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Form\GroupSubscribeForm;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;

/**
 * Tests access to the create entity form through the user interface.
 *
 * @group og
 */
class GroupSubscribeFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'og',
    'entity_test',
  ];

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user1;


  /**
   * A group entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group1;

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle1;

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

    $this->groupBundle1 = Unicode::strtolower($this->randomMachineName());
    $this->groupBundle2 = Unicode::strtolower($this->randomMachineName());

    // Define the entity as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle1);
    Og::groupManager()->addGroup('entity_test', $this->groupBundle2);

    // Create users.
    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    // Create groups.
    $this->group1 = EntityTest::create([
      'type' => $this->groupBundle1,
      'name' => $this->randomString(),
      'user_id' => $user->id(),
    ]);
    $this->group1->save();

    $this->group2 = EntityTest::create([
      'type' => $this->groupBundle2,
      'name' => $this->randomString(),
      'user_id' => $user->id(),
    ]);
    $this->group2->save();

    // Change the permissions of group to "subscribe".
    /** @var OgRole $role_pending */
    $role_pending = Og::getRole('entity_test', $this->groupBundle1, OgRoleInterface::ANONYMOUS);
    $role_pending
      ->grantPermission('subscribe')
      ->save();

    // Change the permissions of group to allow "subscribe without approval".
    /** @var OgRole $role_active */
    $role_active = Og::getRole('entity_test', $this->groupBundle2, OgRoleInterface::ANONYMOUS);
    $role_active
      ->grantPermission('subscribe without approval')
      ->save();
  }

  /**
   * Tests subscribe confirmation related text.
   */
  public function testSubscribeByState() {
    $entity_type_id = 'og_membership';

    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    $membership_pending = OgMembership::create();
    $membership_pending
      ->setGroup($this->group1)
      ->setUser($user);

    $membership_active = OgMembership::create();
    $membership_active
      ->setGroup($this->group2)
      ->setUser($user);

    /** @var GroupSubscribeForm $form */
    $form = \Drupal::entityManager()->getFormObject($entity_type_id, 'subscribe');

    $form->setEntity($membership_pending);
    $this->assertEquals('Request membership', $form->getConfirmText());

    $form->setEntity($membership_active);
    $this->assertEquals('Join', $form->getConfirmText());
  }

}
