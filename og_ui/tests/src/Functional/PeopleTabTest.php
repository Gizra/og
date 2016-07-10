<?php

/**
 * @file
 * Contains \Drupal\og_ui\Tests\PeopleTabTest.
 */

namespace Drupal\og_ui\Tests;

use Drupal\KernelTests\AssertLegacyTrait;
use Drupal\og\Og;
use Drupal\simpletest\AssertContentTrait;
use Drupal\simpletest\BrowserTestBase;

/**
 * Test making a bundle a group and a group content.
 *
 * @group og
 */
class PeopleTabTest extends BrowserTestBase {

  use AssertContentTrait;
  use AssertLegacyTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og', 'og_ui', 'user'];

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $standardUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    // Log in as an administrator that can manage blocks and content types.
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
    ]);

    // Keep a standard user to verify the access logic.
    $this->standardUser = $this->drupalCreateUser(['access content']);

    $this->drupalLogin($this->adminUser);

    // Set up a group.
    $this->group = $this->createNode();
    Og::groupManager()->addGroup('node', $this->group->bundle());
  }

  /**
   * Verifying the people page exists.
   */
  public function testPeopleTab() {
    $this->drupalGet('node/' . $this->group->id());
    $this->assertText('Group');
  }

}
