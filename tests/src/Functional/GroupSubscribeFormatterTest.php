<?php

namespace Drupal\Tests\og\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
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
   * A non-author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;


  /**
   * The node author (and group manager) user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create bundles.
    $this->groupBundle1 = Unicode::strtolower($this->randomMachineName());
    $this->groupBundle2 = Unicode::strtolower($this->randomMachineName());

    // Define the bundles as groups.
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

    /** @var \Drupal\og\Entity\OgRole $role */
    $role = Og::getRole('node', $this->groupBundle1, OgRoleInterface::ANONYMOUS);
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
    $this->drupalGet('node/' . $this->group1->id());
    $this->clickLink('Subscribe to group');
    $this->click('#edit-submit');

    $this->drupalGet('node/' . $this->group1->id());
    $this->assertSession()->linkExists('Unsubscribe from group');

    // Validate another user sees the correct formatter link.
    $this->drupalLogin($this->user2);
    $this->drupalGet('node/' . $this->group1->id());
    $this->clickLink('Subscribe to group');

  }

}
