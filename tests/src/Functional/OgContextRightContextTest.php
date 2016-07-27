<?php

namespace Drupal\Tests\og\Functional;

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\OgContextTest.
 */

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgContextHandlerInterface;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Test OG context plugins.
 *
 * @see og_entity_create_access().
 *
 * @group og
 */
class OgContextRightContextTest extends BrowserTestBase {

  use UserCreationTrait;

  /**
   * A group.
   *
   * @var Node
   */
  protected $group1;

  /**
   * Another group.
   *
   * @var Node
   */
  protected $group2;

  /**
   * A user instance.
   *
   * @var User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og_test', 'og', 'system', 'user', 'field', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    Og::contextHandler()->updateConfigStorage();
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', 'user');

    $group_type = NodeType::create([
      'type' => 'group',
      'name' => $this->randomString(),
    ]);
    $group_type->save();
    Og::groupManager()->addGroup('node', $group_type->id());

    // Create the user and populate the field.
    $this->user = $this->drupalCreateUser(['access content']);

    // Create the groups.
    $this->group1 = Node::create([
      'title' => $this->randomString(),
      'type' => $group_type->id(),
      'uid' => $this->user->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'title' => $this->randomString(),
      'type' => $group_type->id(),
      'uid' => $this->user->id(),
    ]);
    $this->group2->save();

    Og::contextHandler()->updatePlugin('entity', ['status' => 1, 'weight' => 0]);
    Og::contextHandler()->updatePlugin('user_group_reference', ['status' => 1, 'weight' => 1]);
  }

  /**
   * Verify the context handler will return the correct OG context.
   */
  public function testOgContextPluginsList() {
    $this->drupalGet('node/' . $this->group1->id());
  }

}
