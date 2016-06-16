<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestStringId;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\Entity\OgMembership;
use Drupal\user\Entity\User;

/**
 * Checks that groups with string IDs can be referenced.
 *
 * @group og
 */
class ReferenceStringIdTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'entity_test', 'field', 'og', 'system'];

  /**
   * Array of test bundles. The first is a group, the second group content.
   *
   * @var EntityInterface[]
   */
  protected $bundles;

  /**
   * The name of the group audience field used for the group content type.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The group entity type.
   *
   * @var EntityInterface
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test_string_id');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    // Create two bundles, one will serve as group, the other as group content.
    for ($i = 0; $i < 2; $i++) {
      $bundle = EntityTestStringId::create([
        'type' => Unicode::strtolower($this->randomMachineName()),
        'name' => $this->randomString(),
        'id' => $this->randomMachineName(),
      ]);
      $bundle->save();
      $this->bundles[] = $bundle->id();
    }

    // Create a group with a string as an ID.
    $group = EntityTestStringId::create([
      'type' => $this->bundles[0],
      'id' => $this->randomMachineName(),
    ]);
    $group->save();
    $this->group = $group;

    // Let OG mark the group entity type as a group.
    Og::groupManager()->addGroup('entity_test_string_id', $this->bundles[0]);

    // Add a group audience field to the second bundle, this will turn it into a
    // group content type.
    $this->fieldName = strtolower($this->randomMachineName());
    Og::CreateField(OgGroupAudienceHelper::DEFAULT_FIELD, 'entity_test_string_id', $this->bundles[1], [
      'field_name' => $this->fieldName,
    ]);

    // Add a group audience field to the User entity, so that we can test if
    // users can become members of the test group.
    Og::CreateField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', 'user', [
      'field_name' => $this->fieldName,
    ]);
  }

  /**
   * Test if a group that uses a string as ID can be referenced.
   */
  public function testReferencingStringIds() {
    // Create a group content entity that references the group.
    $entity = EntityTestStringId::create([
      'type' => $this->bundles[1],
      'name' => $this->randomString(),
      'id' => $this->randomMachineName(),
      $this->fieldName => [['target_id' => $this->group->id()]],
    ]);
    $entity->save();

    // Check that the group content entity is referenced.
    $references = $this->container->get('entity.query')->get('entity_test_string_id')
      ->condition($this->fieldName, $this->group->id())
      ->execute();
    $this->assertEquals([$entity->id()], array_keys($references), 'The correct group is referenced.');

    // Create a user and make it a member of the group.
    $user = User::create(['name' => $this->randomString()]);
    $user->save();
    $membership = OgMembership::create([
      'uid' => $user->id(),
      'type' => 'user',
      'entity_type' => 'entity_test_string_id',
      'entity_id' => $this->group->id(),
      'field_name' => $this->fieldName,
    ]);
    $membership->save();

    // Reload the user and check that its group audience field correctly
    // references the entity.
    $user = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($user->id());
    $this->assertEquals($this->group->id(), $user->{$this->fieldName}->getValue()[0]['target_id']);
  }

}
