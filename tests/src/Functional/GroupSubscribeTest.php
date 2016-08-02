<?php

namespace Drupal\Tests\og\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

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
      $this->group1->id() => 200,
      $this->group2->id() => 403,
      $this->group3->id() => 403,
    ];

    foreach ($scenarios as $entity_id => $code) {
      $path = "group/$entity_type_id/$entity_id/subscribe";
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals($code);
    }
  }

}
