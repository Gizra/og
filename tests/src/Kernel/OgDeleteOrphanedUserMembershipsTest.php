<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Tests deletion of orphaned user memberships.
 *
 * @group og
 */
class OgDeleteOrphanedUserMembershipsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'field', 'entity_reference', 'node', 'og'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A test group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installSchema('system', ['queue', 'sequences']);

    $this->entityTypeManager = \Drupal::entityTypeManager();

    // Create a group entity type.
    $group_bundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::groupManager()->addGroup('node', $group_bundle);

    // Create a group content entity type.
    $group_content_bundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_content_bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', $group_content_bundle);

    // Create a group.
    $this->group = Node::create([
      'title' => $this->randomString(),
      'type' => $group_bundle,
    ]);
    $this->group->save();

    // Create a user and subscribe it to the group.
    $this->user = User::create(['name' => $this->randomString()]);
    $this->user->save();
    $membership = OgMembership::create(Og::membershipDefault());
    $membership
      ->setFieldName(OgGroupAudienceHelper::DEFAULT_FIELD)
      ->setUser($this->user->id())
      ->setEntityType($this->group->getEntityTypeId())
      ->setEntityId($this->group->id())
      ->setState(OgMembershipInterface::STATE_ACTIVE)
      ->save();
  }

  /**
   * Tests that orphaned user memberships are deleted when the group is deleted.
   */
  public function testDeleteOrphanedUserMemberships() {
    $entity_storage = $this->entityTypeManager->getStorage('og_membership');

    // Retrieve the group memberships. There should be 2 members: the group
    // owner and our test user.
    $memberships = $entity_storage->loadByProperties([
      'entity_type' => $this->group->getEntityTypeId(),
      'entity_id' => $this->group->id(),
    ]);
    $this->assertCount(2, $memberships);

    // Delete the group.
    $this->group->delete();

    // Check that the memberships referring to the group have been deleted.
    $entity_storage->resetCache(array_keys($memberships));
    $memberships = $entity_storage->loadByProperties([
      'entity_type' => $this->group->getEntityTypeId(),
      'entity_id' => $this->group->id(),
    ]);
    $this->assertEmpty($memberships);
  }

}
