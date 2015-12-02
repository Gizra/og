<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\CheckFieldCardinalityTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\OgHelper;

/**
 * Tests the OgHelper::checkFieldCardinality method.
 *
 * @group og
 *
 * @coversDefaultClass \Drupal\og\OgHelper
 */
class CheckFieldCardinalityTest extends UnitTestCase {

  /**
   * @covers ::checkFieldCardinality
   *
   * @expectedException \Drupal\Core\Field\FieldException
   */
  public function testFieldCardinalityNoDefinition() {
    $field_name = 'test_field_no_definition';
    $entity_prophecy = $this->prophesize(ContentEntityInterface::class);

    $entity_prophecy->getFieldDefinition($field_name)
      ->willReturn(NULL);
    // The bundle() and getEntityTypeId() methods will be called for the
    // exception string.
    $entity_prophecy->bundle()
      ->shouldBeCalled();
    $entity_prophecy->getEntityTypeId()
      ->shouldBeCalled();

    OgHelper::checkFieldCardinality($entity_prophecy->reveal(), $field_name);
  }

  /**
   * @covers ::checkFieldCardinality
   *
   * @dataProvider providerTestFieldCardinality
   */
  public function testFieldCardinality($field_count, $cardinality, $expected) {
    $field_name = 'test_field';

    $field_storage_definition_prophecy = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition_prophecy->getCardinality()
      ->willReturn($cardinality)
      ->shouldBeCalled();

    $field_definition_prophecy = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition_prophecy->getFieldStorageDefinition()
      ->willReturn($field_storage_definition_prophecy->reveal())
      ->shouldBeCalled();

    $entity_prophecy = $this->prophesize(ContentEntityInterface::class);

    $entity_prophecy->getFieldDefinition($field_name)
      ->willReturn($field_definition_prophecy->reveal());

    // If the cardinality is unlimited getting a cound of the field items is
    // never expected, so just check it's not called.
    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $entity_prophecy->get($field_name)
        ->shouldNotBeCalled();
    }
    // Otherwise, there will be a count retrieved from the field item.
    else {
      $field_item_prophecy = $this->prophesize(FieldItemListInterface::class);
      $field_item_prophecy->count()
        ->willReturn($field_count)
        ->shouldBeCalled();

      $entity_prophecy->get($field_name)
        ->willReturn($field_item_prophecy->reveal())
        ->shouldBeCalled();
    }

    $this->assertSame(OgHelper::checkFieldCardinality($entity_prophecy->reveal(), $field_name), $expected);
  }

  /**
   * Data provider for testFieldCardinality.
   *
   * @return array
   */
  public function providerTestFieldCardinality() {
    return [
      [0, 1, TRUE],
      [1, 1, FALSE],
      [2, 1, FALSE],
      [1, 2, TRUE],
      [2, 2, FALSE],
      [3, 2, FALSE],
      [0, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, TRUE],
      [1, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, TRUE],
      [10, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, TRUE],
    ];
  }

}
