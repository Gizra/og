<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\og\OgAccess;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Form\GroupSubscribeForm;
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

  function testStates() {
    $entity_type_id = 'og_membership';
    $display = entity_get_form_display($entity_type_id, $entity_type_id, 'default');

    $user1 = User::create(['name' => $this->randomString()]);
    $user1->save();

    $user2 = User::create(['name' => $this->randomString()]);
    $user2->save();

    $membership_active = OgMembership::create();
    $membership_active
      ->setGroup($this->group1)
      ->setUser($user1);

    $membership_pending = OgMembership::create();
    $membership_pending
      ->setGroup($this->group1)
      ->setUser($user1);

    /** @var GroupSubscribeForm  $form */
    $form = \Drupal::entityManager()->getFormObject($entity_type_id, 'subscribe');
    $form->setEntity($membership_active);

    $actual = $form->getConfirmText();
    $expected = 'Join';
    $this->assertEquals($actual, $expected);

  }
}
