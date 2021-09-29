<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Test creation and deletion of group types.
 *
 * @group og
 */
class GroupTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'node', 'og', 'options', 'system', 'user'];

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['og']);
    $this->groupTypeManager = $this->container->get('og.group_type_manager');
  }

  /**
   * Test creation and deletion of a group type.
   */
  public function testGroupType() {
    // Even if we set the og_membership into the config it should not
    // be recognized as a group type.
    $editable = $this->config('og.settings');
    $groups = $editable->get('groups');
    $groups['og_membership'][] = 'default';
    $editable->set('groups', $groups);
    $editable->save();
    // Create a content type.
    /** @var \Drupal\node\NodeTypeInterface $group_type */
    $group_type = NodeType::create(['type' => 'group', 'name' => 'Group']);
    $group_type->save();

    // Initially the node type should not be a group.
    $this->assertFalse($this->groupTypeManager->isGroup('node', 'group'));

    // Turn it into a group.
    $this->groupTypeManager->addGroup('node', 'group');
    $this->assertTrue($this->groupTypeManager->isGroup('node', 'group'));

    // The membership entity should not be a group despite being in config.
    $this->assertFalse($this->groupTypeManager->isGroup('og_membership', 'default'));

    // Verify that the config still contains og_membership.
    $editable = $this->config('og.settings');
    $groups = $editable->get('groups');
    $this->assertFalse(empty($groups['og_membership']));
    $this->assertFalse(empty($groups['node']));

    // Delete the content type. It should no longer be a group.
    $group_type->delete();
    $this->assertFalse($this->groupTypeManager->isGroup('node', 'group'));

    // Adding og_membership as a group type is not possible.
    $this->expectException(\InvalidArgumentException::class);
    $this->groupTypeManager->addGroup('og_membership', 'default');
  }

}
