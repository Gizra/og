<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\og\Entity\OgMembershipType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;

/**
 * Tests subscribe and un-subscribe to groups.
 *
 * @group og
 */
class GroupSubscribeTest extends BrowserTestBase {

  use OgMembershipCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $groupB1No1;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $groupB2No1;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $groupB1No2Unpublished;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nonGroup;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $groupB3No1;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $groupBundle1;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $groupBundle2;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $groupBundle3;

  /**
   * A membership type bundle name.
   *
   * @var string
   */
  protected $membershipTypeBundle;

  /**
   * A non-group bundle name.
   *
   * @var string
   */
  protected $nonGroupBundle;

  /**
   * Test normal user with no connection to the organic group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create bundles.
    $this->groupBundle1 = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->groupBundle1])->save();
    $this->groupBundle2 = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->groupBundle2])->save();
    $this->groupBundle3 = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->groupBundle3])->save();
    $this->nonGroupBundle = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->nonGroupBundle])->save();
    $this->membershipTypeBundle = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->membershipTypeBundle])->save();

    // Define the entities as groups.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle1);
    Og::groupTypeManager()->addGroup('node', $this->groupBundle2);
    Og::groupTypeManager()->addGroup('node', $this->groupBundle3);

    // Create node author user.
    $user = $this->createUser();

    // Create test groups. The first group bundle has the
    // 'subscribe without approval' permission.
    $this->groupB1No1 = Node::create([
      'type' => $this->groupBundle1,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->groupB1No1->save();

    // An unpublished group.
    $this->groupB1No2Unpublished = Node::create([
      'type' => $this->groupBundle1,
      'title' => $this->randomString(),
      'uid' => $user->id(),
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $this->groupB1No2Unpublished->save();

    // A group which is using default permissions; it grants the 'subscribe'
    // permission to non-members.
    $this->groupB2No1 = Node::create([
      'type' => $this->groupBundle2,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->groupB2No1->save();

    // Create non-group.
    $this->nonGroup = Node::create([
      'type' => $this->nonGroupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->nonGroup->save();

    // A group which is closed for subscription. It grants neither 'subscribe'
    // nor 'subscribe without approval'.
    $this->groupB3No1 = Node::create([
      'type' => $this->groupBundle3,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->groupB3No1->save();

    // Grant the permission to 'subscribe without approval' to the first group
    // type.
    OgRole::getRole('node', $this->groupBundle1, OgRoleInterface::ANONYMOUS)
      ->grantPermission('subscribe without approval')
      ->save();

    // Revoke the permission to subscribe from the third group type.
    OgRole::getRole('node', $this->groupBundle3, OgRoleInterface::ANONYMOUS)
      ->revokePermission('subscribe')
      ->save();

    // Create a new membership type.
    $membership_type = OgMembershipType::create([
      'type' => $this->membershipTypeBundle,
      'name' => $this->randomString(),
    ]);
    $membership_type->save();

    $this->normalUser = $this->drupalCreateUser();
  }

  /**
   * Tests access to subscribe page.
   */
  public function testSubscribeAccess() {

    // We don't use a provider function, as it makes the test run much slower.
    $scenarios = [
      // Group with active membership.
      [
        'entity' => $this->groupB1No1,
        // Don't set the membership type. "Default" will be used.
        'membership_type' => '',
        'code' => 200,
        'skip_approval' => TRUE,
        'private' => FALSE,
      ],
      [
        'entity' => $this->groupB1No1,
        // Explicitly set the membership type.
        'membership_type' => OgMembershipInterface::TYPE_DEFAULT,
        'code' => 200,
        'skip_approval' => TRUE,
        'private' => FALSE,
      ],
      [
        'entity' => $this->groupB1No1,
        // Set invalid membership type.
        'membership_type' => mb_strtolower($this->randomMachineName()),
        'code' => 404,
      ],
      // Group with pending membership.
      [
        'entity' => $this->groupB2No1,
        'code' => 200,
        'skip_approval' => FALSE,
        'private' => FALSE,
      ],

      // Entity is un-accessible to the user, but we still allow to subscribe to
      // it. Since it's "private" the default membership will be pending,
      // even though the permission is "subscribe without approval".
      [
        'entity' => $this->groupB1No2Unpublished,
        'code' => 200,
        'skip_approval' => FALSE,
        'private' => TRUE,
      ],

      // A non-group entity.
      [
        'entity' => $this->nonGroup,
        'code' => 403,
      ],

      // A group which doesn't allow new subscriptions.
      [
        'entity' => $this->groupB3No1,
        'code' => 403,
      ],

      // A non existing entity type.
      [
        'entity_type_id' => mb_strtolower($this->randomMachineName()),
        'entity_id' => 1,
        // @todo This currently returns a 500 error due to a bug in core. Change
        //   this to a 403 or 404 when the bug is fixed.
        // @see https://www.drupal.org/node/2786897
        'code' => version_compare(\Drupal::VERSION, '9.1.4', '>=') ? 404 : 500,
      ],

      // A non existing entity ID.
      [
        'entity_type_id' => 'node',
        'entity_id' => rand(1000, 2000),
        'code' => 404,
      ],
    ];

    foreach ($scenarios as $scenario) {
      // Use a different user for each scenario so they have no existing
      // memberships.
      $new_user = $this->drupalCreateUser();
      $this->drupalLogin($new_user);
      $entity = NULL;
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (!empty($scenario['entity'])) {
        $entity = $scenario['entity'];
        $entity_type_id = $entity->getEntityTypeId();
        $entity_id = $entity->id();
      }
      else {
        $entity_type_id = $scenario['entity_type_id'];
        $entity_id = $scenario['entity_id'];
      }

      $path = "group/$entity_type_id/$entity_id/subscribe";

      if (!empty($scenario['membership_type'])) {
        // Add the membership type.
        $path .= '/' . $scenario['membership_type'];
      }

      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals($scenario['code']);

      if ($scenario['code'] !== 200) {
        continue;
      }

      if ($scenario['skip_approval']) {
        $this->assertSession()->elementNotExists('css', '#edit-og-membership-request-0-value');
        $text = 'Are you sure you want to join the group';
        $expected_state = [OgMembershipInterface::STATE_ACTIVE];
      }
      else {
        // The text area to explain the request to join.
        $this->assertSession()->elementExists('css', '#edit-og-membership-request-0-value');
        $text = 'Are you sure you want to request a subscription to the group';
        $expected_state = [OgMembershipInterface::STATE_PENDING];
      }
      $this->assertSession()->pageTextContains($text);
      // The user should not be able to change their role or state, due to
      // field permissions in
      // \Drupal\og\OgMembershipAccessControlHandler::checkFieldAccess().
      $this->assertSession()->elementNotExists('css', '#edit-roles');
      $this->assertSession()->elementNotExists('css', '#edit-state');
      $this->click('#edit-submit');
      $this->assertSession()->statusCodeEquals(200);
      /** @var \Drupal\og\MembershipManager $membership_manager */
      $membership_manager = $this->container->get('og.membership_manager');
      $this->assertTrue($membership_manager->isMember($entity, $new_user->id(), $expected_state));

      // Assert title appears only for accessible groups.
      if ($scenario['private']) {
        // Group's title shouldn't appear anywhere.
        $this->assertSession()->responseNotContains($entity->label());
      }
      else {
        $this->assertSession()->pageTextContains($entity->label());
      }
    }
  }

  /**
   * Tests access to un-subscribe page.
   */
  public function testUnSubscribeAccess() {
    $this->createOgMembership($this->groupB1No1, $this->normalUser);

    $this->drupalLogin($this->normalUser);

    $scenarios = [
      $this->groupB1No1->id() => 200,
      $this->groupB2No1->id() => 403,
    ];

    foreach ($scenarios as $entity_id => $code) {
      $path = "group/node/$entity_id/unsubscribe";

      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals($code);
    }
  }

}
