<?php

namespace Drupal\Tests\og\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests subscribe to group.
 *
 * @group og
 */
class GroupSubscribeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

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
    $this->groupBundle1 = Unicode::strtolower($this->randomMachineName());
    $this->groupBundle2 = Unicode::strtolower($this->randomMachineName());

    $this->nonGroupBundle = Unicode::strtolower($this->randomMachineName());

    // Define the entities as groups.
    Og::groupManager()->addGroup('node', $this->groupBundle1);
    Og::groupManager()->addGroup('node', $this->groupBundle2);

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

    /** @var OgRole $role */
    $role = Og::getRole('node', $this->groupBundle1, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe without approval')
      ->save();

    $role = Og::getRole('node', $this->groupBundle2, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe')
      ->save();

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
        'code' => 200,
        'skip_approval' => TRUE,
        'private' => FALSE,
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

    ];

    foreach ($scenarios as $scenario) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $scenario['entity'];
      $entity_type_id = $entity->getEntityTypeId();
      $entity_id = $entity->id();

      $path = "group/$entity_type_id/$entity_id/subscribe";
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

}
