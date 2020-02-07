<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\og\Entity\OgRole;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests subscribe and un-subscribe formatter.
 *
 * @group og
 */
class GroupSubscribeFormatterTest extends BrowserTestBase {

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
  protected $group;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * A non-author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * A non-author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create bundle.
    $this->groupBundle = mb_strtolower($this->randomMachineName());

    // Create a node type.
    $node_type = NodeType::create(['type' => $this->groupBundle, 'name' => $this->groupBundle]);
    $node_type->save();

    // Define the bundles as groups.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle);

    // Create node author user.
    $user = $this->createUser();

    // Create groups.
    $this->group = Node::create([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group->save();

    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::getRole('node', $this->groupBundle, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe without approval')
      ->save();

    $this->user1 = $this->drupalCreateUser();
    $this->user2 = $this->drupalCreateUser();
  }

  /**
   * Tests the formatter changes by user and membership.
   */
  public function testFormatter() {
    $this->drupalLogin($this->user1);

    // Subscribe to group.
    $this->drupalGet('node/' . $this->group->id());
    $this->clickLink('Subscribe to group');
    $this->click('#edit-submit');

    $this->drupalGet('node/' . $this->group->id());
    $this->assertSession()->linkExists('Unsubscribe from group');

    // Validate another user sees the correct formatter link.
    $this->drupalLogin($this->user2);
    $this->drupalGet('node/' . $this->group->id());
    $this->clickLink('Subscribe to group');
  }

}
