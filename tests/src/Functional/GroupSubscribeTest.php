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

    // Create non-group.
    $this->group3 = Node::create([
      'type' => $this->nonGroupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group3->save();

    // Create an unpublished node.
    $this->group4 = Node::create([
      'type' => $this->groupBundle1,
      'title' => $this->randomString(),
      'uid' => $user->id(),
      'status' => NODE_NOT_PUBLISHED,
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
    $entity_type_id = $this->group1->getEntityTypeId();
    $this->drupalLogin($this->normalUser);

    $scenarios = [
      // Group with active membership.
      $this->group1->id() => [
        'code' => 200,
        'skip_approval' => TRUE,
        'label' => $this->group1->label(),
        'private' => FALSE,
      ],
      // Group with pending membership.
      $this->group2->id() => [
        'code' => 200,
        'skip_approval' => FALSE,
        'label' => $this->group1->label(),
        'private' => FALSE,
      ],
      // A non-group entity.
      $this->group3->id() => ['code' => 403],

      // Entity is un-accessible to the user, but we still allow to subscribe to
      // it. Since it's "private" the default membership will be pending,
      // even though the permission is "subscribe without approval".
      $this->group4->id() => [
        'code' => 200,
        'skip_approval' => FALSE,
        'label' => $this->group1->label(),
        'private' => TRUE,
      ],
    ];

    foreach ($scenarios as $entity_id => $options) {
      $path = "group/$entity_type_id/$entity_id/subscribe";
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals($options['code']);

      if ($options['code'] != 200) {
        continue;
      }

      // Assert request membership field.
      if ($options['skip_approval']) {
        $this->assertSession()->elementNotExists('xpath', '//*[@id="edit-og-membership-request-0-value"]');
      }
      else {
        $this->assertSession()->elementExists('xpath', '//*[@id="edit-og-membership-request-0-value"]');
      }

      // Assert title appears only for accessible groups.
      if ($options['private']) {
        // Group's title shouldn't appear anywhere.
        $this->assertSession()->responseNotContains($options['label']);
      }
      else {
        $this->assertSession()->pageTextContains($options['label']);
      }


    }
  }

}
