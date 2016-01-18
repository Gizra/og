<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessEntityTestBase.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class OgAccessEntityTestBase extends OgAccessTestBase {

  protected $entity;

  public function setup() {
    parent::setUp();

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

    $this->entity = $this->prophesize(ContentEntityInterface::class);
    $this->entity->id()->willReturn($entity_id);
    $this->entity->bundle()->willReturn($bundle);
    $this->entity->isNew()->willReturn(FALSE);
    $this->entity->getEntityType()->willReturn($entity_type->reveal());
    $this->entity->getEntityTypeId()->willReturn($entity_type_id);

    $this->groupManager->isGroup($entity_type_id, $bundle)->willReturn(FALSE);

    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle)->willReturn([$field_definition->reveal()]);
    \Drupal::getContainer()->set('entity.manager', $entity_field_manager->reveal());

    // Mock the results of Og::getEntityGroups().
    $r = new \ReflectionClass('Drupal\og\Og');
    $reflection_property = $r->getProperty('entityGroupCache');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue(["$entity_type_id:$entity_id:1:" => [[$this->groupEntity()->reveal()]]]);

  }
}
