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

  protected $groupBundle;
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
    $this->groupBundle = Unicode::strtolower($this->randomMachineName());
    $this->nonGroupBundle = Unicode::strtolower($this->randomMachineName());

    // Define the entities as groups.
    Og::groupManager()->addGroup('node', $this->groupBundle);

    // Create node author user.
    $user = $this->createUser();

    // Create group.
    $this->group1 = Node::create([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group1->save();

    // Create non-group.
    $this->group2 = Node::create([
      'type' => $this->nonGroupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group2->save();

    // Create an unpublished node.
    $this->group3 = Node::create([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
      'status' => NODE_NOT_PUBLISHED,
    ]);
    $this->group3->save();

    /** @var OgRole $role */
    $role = Og::getRole('node', $this->groupBundle, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe without approval')
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
      $this->group1->id() => [
        'code' => 200,
        'skip_approval' => TRUE,
      ],
      $this->group2->id() => ['code' => 403],

      // Entity is un-accessible to the user, but we still allow to subscribe to
      // it. Since it's "private" the default membership will be pending,
      // even though the permission is "subscribe without approval".
      $this->group3->id() => [
        'code' => 200,
        'skip_approval' => FALSE,
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
    }
  }

}
