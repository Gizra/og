<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
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
  protected $group1;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group2;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group3;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group4;

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
  protected function setUp() {
    parent::setUp();

    // Create bundles.
    $this->groupBundle1 = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->groupBundle1])->save();
    $this->groupBundle2 = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->groupBundle2])->save();
    $this->nonGroupBundle = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->nonGroupBundle])->save();
    $this->membershipTypeBundle = mb_strtolower($this->randomMachineName());
    NodeType::create(['type' => $this->membershipTypeBundle])->save();

    // Define the entities as groups.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle1);
    Og::groupTypeManager()->addGroup('node', $this->groupBundle2);

    // Create node author user.
    $user = $this->createUser();

    // Create groups.
    $this->group1 = Node::create([
      'type' => $this->groupBundle1,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'type' => $this->groupBundle2,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group2->save();

    // Create an unpublished node.
    $this->group3 = Node::create([
      'type' => $this->groupBundle1,
      'title' => $this->randomString(),
      'uid' => $user->id(),
      'status' => NODE_NOT_PUBLISHED,
    ]);
    $this->group3->save();

    // Create non-group.
    $this->group4 = Node::create([
      'type' => $this->nonGroupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group4->save();

    $role = OgRole::getRole('node', $this->groupBundle1, OgRoleInterface::ANONYMOUS);

    $role
      ->grantPermission('subscribe without approval')
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
    $this->drupalLogin($this->normalUser);

    // We don't use a provider function, as it makes the test run much slower.
    $scenarios = [
      // Group with active membership.
      [
        'entity' => $this->group1,
        // Don't set the membership type. "Default" will be used.
        'membership_type' => '',
        'code' => 200,
        'skip_approval' => TRUE,
        'private' => FALSE,
      ],
      [
        'entity' => $this->group1,
        // Explicitly set the membership type.
        'membership_type' => OgMembershipInterface::TYPE_DEFAULT,
        'code' => 200,
        'skip_approval' => TRUE,
        'private' => FALSE,
      ],
      [
        'entity' => $this->group1,
        // Set invalid membership type.
        'membership_type' => mb_strtolower($this->randomMachineName()),
        'code' => 404,
      ],
      // Group with pending membership.
      [
        'entity' => $this->group2,
        'code' => 200,
        'skip_approval' => FALSE,
        'private' => FALSE,
      ],

      // Entity is un-accessible to the user, but we still allow to subscribe to
      // it. Since it's "private" the default membership will be pending,
      // even though the permission is "subscribe without approval".
      [
        'entity' => $this->group3,
        'code' => 200,
        'skip_approval' => FALSE,
        'private' => TRUE,
      ],

      // A non-group entity.
      [
        'entity' => $this->group4,
        'code' => 403,
      ],
      // A non existing entity type.
      [
        'entity_type_id' => mb_strtolower($this->randomMachineName()),
        'entity_id' => 1,
        // @todo This currently returns a 500 error due to a bug in core. Change
        //   this to a 403 or 404 when the bug is fixed.
        // @see https://www.drupal.org/node/2786897
        'code' => 500,
      ],

      // A non existing entity ID.
      [
        'entity_type_id' => 'node',
        'entity_id' => rand(1000, 2000),
        'code' => 404,
      ],
    ];

    foreach ($scenarios as $scenario) {
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

      if ($scenario['code'] != 200) {
        continue;
      }

      // Assert request membership field.
      if ($scenario['skip_approval']) {
        $this->assertSession()->elementNotExists('xpath', '//*[@id="edit-og-membership-request-0-value"]');
      }
      else {
        $this->assertSession()->elementExists('xpath', '//*[@id="edit-og-membership-request-0-value"]');
      }

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
    $this->createOgMembership($this->group1, $this->normalUser);

    $this->drupalLogin($this->normalUser);

    $scenarios = [
      $this->group1->id() => 200,
      $this->group2->id() => 403,
    ];

    foreach ($scenarios as $entity_id => $code) {
      $path = "group/node/$entity_id/unsubscribe";

      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals($code);
    }
  }

}
