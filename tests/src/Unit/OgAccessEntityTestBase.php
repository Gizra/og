<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessEntityTestBase.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Prophecy\Argument;

class OgAccessEntityTestBase extends OgAccessTestBase {

  protected $entity;

  public function setup() {
    parent::setUp();

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getType()->willReturn('og_standard_reference');
    $field_definition->getFieldStorageDefinition()
      ->willReturn($this->prophesize(FieldStorageDefinitionInterface::class)->reveal());
    $field_definition->getSetting("handler_settings")->willReturn([]);
    $field_definition->getName()->willReturn($this->randomMachineName());

    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $entity_id = mt_rand(20, 30);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getListCacheTags()->willReturn([]);
    $entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type->id()->willReturn($entity_type_id);

    $this->entity = $this->prophesize(ContentEntityInterface::class);
    $this->entity->id()->willReturn($entity_id);
    $this->entity->bundle()->willReturn($bundle);
    $this->entity->isNew()->willReturn(FALSE);
    $this->entity->getEntityType()->willReturn($entity_type->reveal());
    $this->entity->getEntityTypeId()->willReturn($entity_type_id);

    $this->groupManager->isGroup($entity_type_id, $bundle)->willReturn(FALSE);

    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle)->willReturn([$field_definition->reveal()]);

    $group = $this->groupEntity()->reveal();
    $group_type_id = $group->getEntityTypeId();

    $storage = $this->prophesize(EntityStorageInterface::class);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($entity_type_id)->willReturn($entity_type->reveal());
    $entity_type_manager->getStorage($group_type_id)->willReturn($storage->reveal());


    $container = \Drupal::getContainer();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('entity_field.manager', $entity_field_manager->reveal());

    // Mock the results of Og::getGroupIds().
    $r = new \ReflectionClass('Drupal\og\Og');
    $reflection_property = $r->getProperty('cache');
    $reflection_property->setAccessible(TRUE);

    $identifier = [
      'Drupal\og\Og::getGroupIds',
      $entity_id,
      NULL,
      NULL,
    ];

    $identifier = implode(':', $identifier);

    $group_ids = [$group_type_id => [$group->id()]];
    $reflection_property->setValue([$identifier => $group_ids]);

    // Mock the results of Og::getGroups().
    $storage->loadMultiple(Argument::type('array'))->willReturn([$group]);
  }

}
