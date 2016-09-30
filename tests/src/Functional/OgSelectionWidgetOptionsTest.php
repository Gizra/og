<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\Tests\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the various OG handler options.
 *
 * @group og
 */
class OgSelectionWidgetOptionsTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

  /**
   * A group object.
   *
   * @var Node
   */
  protected $group1;

  /**
   * A group object.
   *
   * @var Node
   */
  protected $group2;

  /**
   * Demo user.
   *
   * @var User
   */
  protected $demoUser;

  /**
   * Group owner.
   *
   * @var User
   */
  protected $groupOwner;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Setting content types.
    NodeType::create(['type' => 'group'])->save();
    NodeType::create(['type' => 'group_content'])->save();

    // Setting up groups and group content relations.
    $settings = [
      'form_display' => [
        'type' => 'options_buttons',
      ],
    ];
    Og::addGroup('node', 'group');
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Creating users.
    $this->demoUser = $this->drupalCreateUser([
      'create group_content content',
    ]);

    $this->groupOwner = $this->drupalCreateUser([
      'administer group',
      'create group content',
      'create group_content content',
    ]);

    // Create groups.
    $this->group1 = Node::create([
      'type' => 'group',
      'title' => $this->randomString(),
      'uid' => $this->groupOwner->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'type' => 'group',
      'title' => $this->randomString(),
      'uid' => $this->groupOwner->id(),
    ]);
    $this->group2->save();
  }

  /**
   * Tests adding groups, and node access.
   */
  public function testFields() {
    $this->drupalLogin($this->demoUser);
  }

}
