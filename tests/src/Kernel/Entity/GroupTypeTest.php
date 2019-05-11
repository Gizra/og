<?php

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
  public static $modules = ['field', 'node', 'og', 'system', 'user'];

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->groupTypeManager = $this->container->get('og.group_type_manager');
  }

  /**
   * Test creation and deletion of a group type.
   */
  public function testGroupType() {
    // Create a content type.
    /** @var \Drupal\node\NodeTypeInterface $group_type */
    $group_type = NodeType::create(['type' => 'group', 'name' => 'Group']);
    $group_type->save();

    // Initially it should not be a group.
    $this->assertFalse($this->groupTypeManager->isGroup('node', 'group'));

    // Turn it into a group.
    $this->groupTypeManager->addGroup('node', 'group');
    $this->assertTrue($this->groupTypeManager->isGroup('node', 'group'));

    // Delete the content type. It should no longer be a group.
    $group_type->delete();
    $this->assertFalse($this->groupTypeManager->isGroup('node', 'group'));
  }

}
