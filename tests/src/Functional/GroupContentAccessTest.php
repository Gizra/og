<?php

namespace Drupal\Tests\og\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\simpletest\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Tests the access to content that belongs to a group.
 *
 * @group og
 */
class GroupContentAccessTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

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
   * A node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * An user object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create bundle.
    $this->groupBundle = Unicode::strtolower($this->randomMachineName());

    // Create a node type.
    $node_type = NodeType::create(['type' => $this->groupBundle, 'name' => $this->groupBundle]);
    $node_type->save();

    // Define the bundles as groups.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle);

    // Create node author user.
    $this->user = $this->createUser();

    // Create groups.
    $this->group = Node::create([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $this->user->id(),
    ]);
    $this->group->save();

    // Add a group audience field to the "post" node type, turning it into a
    // group content type.
    $this->createContentType(['type' => 'post']);
    $settings = [
      'field_audience' => [
        'settings' => [
          'target_type' => 'node',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', 'post', $settings);

    $this->node = Node::create([
     'type' => 'post',
     'title' => $this->randomString(),
     'uid' => $this->user->id,
    ]);
  }

  /**
   * Tests the access of content that does not belong to a group.
   */
  public function testGroupContentAccess() {
    $this->drupalLogin($this->user1);
    $this->drupalGet('node/' . $this->node->nid . '/edit');
    $this->assertSession()->statusCodeEquals(200);
  }

}
