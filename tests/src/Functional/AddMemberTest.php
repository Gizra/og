<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgRoleInterface;
use Drupal\og\Entity\OgRole;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the new "Add member" form.
 *
 * @group og
 */
class AddMemberTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * Member management depends on views.
   */
  public static $modules = ['node', 'views', 'og'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * A group admin test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * A group member test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupMember;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonMember;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create bundle.
    $this->groupBundle = mb_strtolower($this->randomMachineName());
    $bundle = NodeType::create(['type' => $this->groupBundle, 'name' => $this->groupBundle]);
    $bundle->save();

    // Define the bundles as groups.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle);

    // Create node author user.
    $user = $this->createUser();

    // Create a group.
    $this->group = $this->drupalCreateNode([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group->save();

    // Create a test user who's an admin of the group.
    $this->groupAdmin = $this->drupalCreateUser();
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ADMINISTRATOR);
    $membership = $this->container->get('og.membership_manager')->createMembership($this->group, $this->groupAdmin);
    $membership->addRole($role);
    $membership->save();

    // Create a normal member test user subscribed to the group.
    $this->groupMember = $this->drupalCreateUser();
    $membership = $this->container->get('og.membership_manager')->createMembership($this->group, $this->groupMember);
    $membership->save();

    // Create a test non-group-member user to subscribe to the group.
    $this->nonMember = $this->drupalCreateUser();
  }

  /**
   * Tests the add members page as an admin.
   */
  public function testAddMembersByAdmin() : void {
    // Login as the admin of the group.
    $this->drupalLogin($this->groupAdmin);
    // Admins should have access to form.
    $this->assertUserSubscribeFormAndAddNonMember();
  }

  /**
   * Test the add members page as a normal member.
   */
  public function testAddMembersByMembers() : void {
    // Login as the member of the group.
    $this->drupalLogin($this->groupMember);
    // Go to add members page.
    $this->drupalGet($this->getAddMembersUrl());
    // Normal users should not get access straight out of the box.
    $this->assertSession()->statusCodeEquals(403);
    // Grant them the access to administer the group.
    $member_role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::AUTHENTICATED);
    $member_role->grantPermission(OgAccess::ADMINISTER_GROUP_PERMISSION);
    $member_role->save();
    // Assert we can add members now.
    $this->assertUserSubscribeFormAndAddNonMember();
  }

  /**
   * Generates a URL object to the add members page of the test group.
   *
   * @return \Drupal\Core\Url
   *   The generated URL object.
   */
  protected function getAddMembersUrl() : Url {
    return Url::fromRoute("entity.node.og_admin_routes.add_member")
      ->setRouteParameter($this->group->getEntityTypeId(), $this->group->id());
  }

  /**
   * Helper to reduce code duplication.
   *
   * Opens the Add members page, fills in the form to add the Non-member, and
   * checks the membership is created.
   */
  protected function assertUserSubscribeFormAndAddNonMember() : void {
    // Go to add members page.
    $this->drupalGet($this->getAddMembersUrl());
    // We should see the user subscribe form.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#og-user-subscribe-form');
    // Fill in the user subscribe form and subscribe the non member.
    $this->getSession()->getPage()->fillField('uid', "{$this->nonMember->getDisplayName()} ({$this->nonMember->id()})");
    $this->click('#edit-submit');
    // We should get a confirmation message.
    $this->assertSession()->pageTextContainsOnce("{$this->nonMember->getDisplayName()} added to {$this->group->label()}");
    // And a new membership should be created.
    $membership = $this->container->get('og.membership_manager')->getMembership($this->group, $this->nonMember->id());
    $this->assertNotEmpty($membership);
  }

}
