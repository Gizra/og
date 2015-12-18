<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\OgMembershipReferenceItemTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;

/**
 * Tests OgMembershipReferenceItem and OgMembershipReferenceItemList classes.
 *
 * @group Entity
 * @coversDefaultClass \Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItemList
 */
class OgMembershipReferenceItemListTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'user', 'field', 'entity_reference', 'og', 'system'];

  /**
   * Array with the bundle IDs.
   *
   * @var array
   */
  protected $bundles;
  protected $fieldName;
  protected $groups;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');

    // Create several bundles.
    for ($i = 0; $i <= 4; $i++) {
      $bundle = EntityTest::create([
        'type' => Unicode::strtolower($this->randomMachineName()),
        'name' => $this->randomString(),
      ]);

      $bundle->save();
      $this->bundles[] = $bundle->id();
    }
    for ($i = 0 ; $i < 2; $i++) {
      $bundle = $this->bundles[$i];
      Og::groupManager()->addGroup('entity_test', $bundle);
      $group = EntityTest::create(['type' => $bundle]);
      $group->save();
      $this->groups[] = $group;
    }
    $this->fieldName = strtolower($this->randomMachineName());

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'og_membership_reference',
      'entity_type' => 'entity_test',
      'cardinality' => -1,
      'settings' => [
        'target_type' => 'entity_test',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'bundle' => $this->bundles[2],
      'label' => $this->randomString(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [$this->bundles[0], $this->bundles[1]],
        ],
      ],
    ])->save();
  }

  /**
   * Test creating and saving og membership reference field items.
   */
  public function testMembershipSave() {
    $run_query = function ($id) {
      return $this->container->get('entity.query')->get('og_membership')
        ->condition('field_name', $this->fieldName)
        ->condition('member_entity_type', 'entity_test')
        ->condition('member_entity_id', $id)
        ->condition('group_entity_type', 'entity_test')
        ->condition('state', OgMembershipInterface::STATE_ACTIVE)
        ->execute();
    };
    $entity = EntityTest::create([
      'type' => $this->bundles[2],
    ]);
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $entity->save();
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $this->assertSame($run_query($entity->id()), []);
    $entity = EntityTest::create([
      'type' => $this->bundles[2],
      $this->fieldName => [['target_id' => $this->groups[0]->id()]],
    ]);
    $this->assertSame(count($entity->{$this->fieldName}), 1);
    $entity->save();
    $this->assertSame(count($entity->{$this->fieldName}), 1);
    $this->assertSame(count($run_query($entity->id())), 1);
    $entity = EntityTest::create([
      'type' => $this->bundles[2],
      $this->fieldName => [
        ['target_id' => $this->groups[0]->id()],
        ['target_id' => $this->groups[1]->id()],
      ],
    ]);
    $this->assertSame(count($entity->{$this->fieldName}), 2);
    $entity->save();
    $this->assertSame(count($entity->{$this->fieldName}), 2);
    $this->assertSame(count($run_query($entity->id())), 2);
  }

  /**
   * Test loading og membership reference field items.
   */
  public function testMembershipLoad() {
    $reload = function (EntityInterface &$entity) {
      $entity = \Drupal::entityTypeManager()->getStorage('entity_test')->loadUnchanged($entity->id());
    };
    $entity = EntityTest::create([
      'type' => $this->bundles[2],
    ]);
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $entity->save();
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $membership = OgMembership::create([
      'type' => $this->bundles[0],
      'field_name' => $this->fieldName,
      'member_entity_type' => 'entity_test',
      'member_entity_id' => $entity->id(),
      'group_entity_type' => 'entity_test',
      'group_entity_id' => $this->groups[0]->id(),
    ]);
    $membership->save();
    $reload($entity);
    $this->assertSame(count($entity->{$this->fieldName}), 1);
  }

}
