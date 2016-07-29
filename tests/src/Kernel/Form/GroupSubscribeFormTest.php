<?php

namespace Drupal\Tests\og\Kernel\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
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
  protected $groupBundle;

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
   * Tests subscribe confirmation related text.
   */
  public function testSubscribeByState() {
    $entity_type_id = 'og_membership';
    $display = entity_get_form_display($entity_type_id, $entity_type_id, 'default');

    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    $membership_stub = OgMembership::create();
    $membership_stub
      ->setGroup($this->group1)
      ->setUser($user);

    /** @var GroupSubscribeForm $form */
    $form = \Drupal::entityManager()->getFormObject($entity_type_id, 'subscribe');

    // Set permissions for group.
    $form->setEntity($membership_stub);
    $actual = $form->getConfirmText();
    $expected = 'Join';
    $this->assertEquals($actual, $expected);

  }

}
