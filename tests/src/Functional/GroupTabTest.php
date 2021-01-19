<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;

/**
 * Tests the "Group" tab.
 *
 * @group og
 */
class GroupTabTest extends BrowserTestBase {

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
  protected $bundle1;

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
  protected function setUp(): void {
    parent::setUp();

    // Create bundles.
    $this->bundle1 = mb_strtolower($this->randomMachineName());
    $this->bundle2 = mb_strtolower($this->randomMachineName());

    // Create node types.
    $node_type1 = NodeType::create([
      'type' => $this->bundle1,
      'name' => $this->bundle1,
    ]);
    $node_type1->save();

    $node_type2 = NodeType::create([
      'type' => $this->bundle2,
      'name' => $this->bundle2,
    ]);
    $node_type2->save();

    // Define the first bundle as group.
    Og::groupTypeManager()->addGroup('node', $this->bundle1);

    // Create node author user.
    $user = $this->createUser();

    // Create nodes.
    $this->group = Node::create([
      'type' => $this->bundle1,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group->save();

    $this->nonGroup = Node::create([
      'type' => $this->bundle2,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->nonGroup->save();

    $this->user1 = $this->drupalCreateUser(['administer organic groups']);
  }

  /**
   * Tests the formatter changes by user and membership.
   */
  public function testGroupTab() {
    $this->drupalLogin($this->user1);
    $this->drupalGet('group/node/' . $this->group->id() . '/admin');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('group/node/' . $this->nonGroup->id() . '/admin');
    $this->assertSession()->statusCodeEquals(403);
  }

}
