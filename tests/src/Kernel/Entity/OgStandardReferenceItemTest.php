<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\OgStandardReferenceItemTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Tests OgStandardReferenceItem class.
 *
 * @group Entity
 * @coversDefaultClass \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem
 */
class OgStandardReferenceItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'entity_test', 'field', 'og', 'system'];

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
    for ($i = 0; $i <= 2; $i++) {
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

    Og::CreateField(OgGroupAudienceHelper::DEFAULT_FIELD, 'entity_test', $this->bundles[2], ['field_name' => $this->fieldName]);
  }

  /**
   * Testing referencing of non-user entity to groups.
   */
  public function testStandardReference() {
    $groups_query = function($gid) {
      return $this->container->get('entity.query')->get('entity_test')
        ->condition($this->fieldName, $gid)
        ->execute();
    };

    $entity = EntityTest::create([
      'type' => $this->bundles[2],
      'name' => $this->randomString(),
    ]);
    $entity->save();

    $this->assertEmpty($groups_query($this->groups[0]->id()));

    $entity = EntityTest::create([
      'type' => $this->bundles[2],
      'name' => $this->randomString(),
      $this->fieldName => [['target_id' => $this->groups[1]->id()]],
    ]);
    $entity->save();

    $this->assertEmpty($groups_query($this->groups[0]->id()));
    $this->assertEquals(array_keys($groups_query($this->groups[1]->id())), [$entity->id()]);
  }

}
