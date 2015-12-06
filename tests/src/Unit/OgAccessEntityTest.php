<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessEntity.
 */


namespace Drupal\Tests\og\Unit;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\og\OgAccess;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessEntityTest extends OgAccessTestBase  {

  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    if (!defined('OG_STATE_ACTIVE')) {
      define('OG_STATE_ACTIVE', 1);
    }

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getType()->willReturn('og_membership_reference');
    $field_definition->getFieldStorageDefinition()
      ->willReturn($this->prophesize(FieldStorageDefinitionInterface::class)->reveal());
    $field_definition->getSetting("handler_settings")->willReturn([]);
    $field_definition->getName()->willReturn($this->randomMachineName());

    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $entity_id = mt_rand(20, 30);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getListCacheTags()->willReturn([]);
    $entity_type->id()->willReturn($entity_type_id);

    $this->entity = $this->prophesize(EntityInterface::class);
    $this->entity->id()->willReturn($entity_id);
    $this->entity->bundle()->willReturn($bundle);
    $this->entity->isNew()->willReturn(FALSE);
    $this->entity->getEntityType()->willReturn($entity_type->reveal());
    $this->entity->getEntityTypeId()->willReturn($entity_type_id);

    $this->groupManager->isGroup($entity_type_id, $bundle)->willReturn(FALSE);

    $entity_manager = $this->prophesize(EntityManagerInterface::class);
    $entity_manager->getFieldDefinitions($entity_type_id, $bundle)->willReturn([$field_definition->reveal()]);
    \Drupal::getContainer()->set('entity.manager', $entity_manager->reveal());

    $r = new \ReflectionClass('Drupal\og\Og');
    $reflection_property = $r->getProperty('entityGroupCache');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue(["$entity_type_id:$entity_id:1:" => [[$this->groupEntity()->reveal()]]]);
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testDefaultForbidden($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(FALSE);
    $user_access = OgAccess::userAccessEntity($operation, $this->entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_access->isForbidden());
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testEntityNew($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(TRUE);
    $user_access = OgAccess::userAccessEntity($operation, $group_entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testGetEntityGroups($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_entity_access = OgAccess::userAccessEntity($operation, $this->entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_entity_access->isAllowed());
  }

}
