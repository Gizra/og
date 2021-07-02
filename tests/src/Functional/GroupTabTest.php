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
  public static $modules = ['node', 'og', 'views'];

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
   * Test non-group entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nonGroup;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $bundle1;

  /**
   * The group author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * A administrative user.
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
    $this->authorUser = $this->createUser();

    // Create nodes.
    $this->group = Node::create([
      'type' => $this->bundle1,
      'title' => $this->randomString(),
      'uid' => $this->authorUser->id(),
    ]);
    $this->group->save();

    $this->nonGroup = Node::create([
      'type' => $this->bundle2,
      'title' => $this->randomString(),
      'uid' => $this->authorUser->id(),
    ]);
    $this->nonGroup->save();

    $this->user1 = $this->drupalCreateUser(['administer organic groups']);
    $this->user2 = $this->drupalCreateUser();
  }

  /**
   * Tests the formatter changes by user and membership.
   */
  public function testGroupTab() {
    foreach ($this->groupTabScenarios() as $scenario) {
      [$account, $code] = $scenario;
      $this->drupalLogin($account);
      $this->drupalGet('group/node/' . $this->group->id() . '/admin');
      $this->assertSession()->statusCodeEquals($code);

      // This page is a view.
      $this->drupalGet('group/node/' . $this->group->id() . '/admin/members');
      $this->assertSession()->statusCodeEquals($code);

      $this->drupalGet('group/node/' . $this->group->id() . '/admin/members/add');
      $this->assertSession()->statusCodeEquals($code);

      $this->drupalGet('group/node/' . $this->nonGroup->id() . '/admin');
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  protected function groupTabScenarios(): array {
    return [
      [$this->authorUser, 200],
      [$this->user1, 200],
      [$this->user2, 403],
    ];
  }

}
