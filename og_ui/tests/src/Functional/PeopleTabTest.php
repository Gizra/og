<?php

/**
 * @file
 * Contains \Drupal\og_ui\Tests\PeopleTabTest.
 */

namespace Drupal\og_ui\Tests;

use Drupal\KernelTests\AssertLegacyTrait;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\simpletest\AssertContentTrait;
use Drupal\simpletest\BrowserTestBase;

/**
 * Test making a bundle a group and a group content.
 *
 * @group og
 */
class PeopleTabTest extends BrowserTestBase {

  use AssertContentTrait;
  use AssertLegacyTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og', 'og_ui', 'user'];

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $standardUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var OgRole
   */
  protected $ogRole;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    // Log in as an administrator that can manage blocks and content types.
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer group',
    ]);

    // Keep a standard user to verify the access logic.
    $this->standardUser = $this->drupalCreateUser(['access content']);

    $this->drupalLogin($this->adminUser);

    // Set up a group.
    $node_type = $this->drupalCreateContentType();
    $this->group = $this->createNode(['type' => $node_type->id(), 'uid' => $this->adminUser->id()]);
    Og::groupManager()->addGroup('node', $this->group->bundle());

    // Create a role and assign the appropriate permission to access to the
    // group tab.
    /** @var OgRole $og_permission */
    $this->ogRole = OgRole::create();
    $this->ogRole
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group->getEntityTypeId())
      ->setGroupBundle($this->group->bundle())
      // Associate an arbitrary permission with the role.
      ->grantPermission('administer group')
      ->save();

    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->adminUser->id())
      ->setEntityId($this->adminUser->id())
      ->setGroupEntityType($this->group->getEntityTypeId())
      ->addRole($this->ogRole->id())
      ->save();
  }

  /**
   * Verifying the people page exists.
   */
  public function testPeopleTab() {
    $this->drupalGet('node/' . $this->group->id());
    $this->assertText('Group');
  }

}
