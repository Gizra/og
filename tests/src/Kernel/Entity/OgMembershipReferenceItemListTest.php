<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\OgMembershipReferenceItemTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

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
  public static $modules = ['entity_test', 'user', 'field', 'og', 'system'];

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
    $this->installSchema('system', 'sequences');

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

    Og::CreateField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', 'user', ['field_name' => $this->fieldName]);
  }

  /**
   * Test creating and saving OG membership reference field items.
   */
  public function testMembershipSave() {
    $run_query = function ($id) {
      return $this->container->get('entity.query')->get('og_membership')
        ->condition('field_name', $this->fieldName)
        ->condition('uid', $id)
        ->condition('entity_type', 'user')
        ->condition('state', OgMembershipInterface::STATE_ACTIVE)
        ->execute();
    };
    $entity = User::create([
      'type' => $this->bundles[2],
      'name' => $this->randomString(),
    ]);
    // Assert no membership for a group membership with no references.
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $entity->save();
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $this->assertSame($run_query($entity->id()), []);
    $member_in_single_grpup = User::create([
      'type' => $this->bundles[2],
      'name' => $this->randomString(),
      $this->fieldName => [['target_id' => $this->groups[0]->id()]],
    ]);

    // Assert group membership is found before save.
    $this->assertSame(count($member_in_single_grpup->{$this->fieldName}), 1);
    $member_in_single_grpup->save();
    $this->assertSame(count($member_in_single_grpup->{$this->fieldName}), 1);
    $this->assertSame(count($run_query($member_in_single_grpup->id())), 1);
    $member_in_two_groups = User::create([
      'name' => $this->randomString(),
      'type' => $this->bundles[2],
      $this->fieldName => [
        ['target_id' => $this->groups[0]->id()],
        ['target_id' => $this->groups[1]->id()],
      ],
    ]);
    $this->assertSame(count($member_in_two_groups->{$this->fieldName}), 2);
    $member_in_two_groups->save();
    $this->assertSame(count($member_in_two_groups->{$this->fieldName}), 2);
    $this->assertSame(count($run_query($member_in_two_groups->id())), 2);
    // Test re-save.
    $member_in_two_groups->save();
    $this->assertSame(count($member_in_two_groups->{$this->fieldName}), 2);
    $this->assertSame(count($run_query($member_in_two_groups->id())), 2);
  }

  /**
   * Test loading og membership reference field items.
   */
  public function testMembershipLoad() {
    $reload = function (EntityInterface &$entity) {
      $entity = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($entity->id());
    };
    $entity = User::create([
      'type' => $this->bundles[2],
      'name' => $this->randomString(),
    ]);
    // Assert no membership for a group membership with no references.
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $entity->save();
    $this->assertSame(count($entity->{$this->fieldName}), 0);
    $membership = OgMembership::create([
      'type' => $this->bundles[0],
      'field_name' => $this->fieldName,
      'uid' => $entity->id(),
      'entity_type' => 'user',
      'entity_id' => $this->groups[0]->id(),
    ]);
    $membership->save();
    $reload($entity);
    // Assert membership is picked up after a load from database.
    $this->assertSame(count($entity->{$this->fieldName}->getValue()), 1);
  }

}
