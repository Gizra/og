<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\EntityReference\Views;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests OG standard reference relationship data.
 *
 * @group og
 *
 * @see core_field_views_data()
 */
class OgStandardReferenceRelationshipTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_og_standard_reference_entity_test_view',
    'test_og_standard_reference_reverse_entity_test_view',
    'test_og_standard_reference_entity_test_mul_view',
    'test_og_standard_reference_reverse_entity_test_mul_view',
  ];

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'field',
    'entity_test',
    'views',
    'og_standard_reference_test_views',
    'og',
    'options',
  ];

  /**
   * The entity_test entities used by the test.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');

    // Create reference from entity_test to entity_test_mul.
    $settings = [
      'field_name' => 'field_test_data',
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'entity_test_mul',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', 'entity_test', $settings);

    // Create reference from entity_test_mul to entity_test.
    $settings = [
      'field_name' => 'field_data_test',
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'entity_test',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test_mul', 'entity_test_mul', $settings);

    ViewTestData::createTestViews(get_class($this), ['og_standard_reference_test_views']);
  }

  /**
   * Tests using the views relationship.
   */
  public function testNoDataTableRelationship() {

    // Create some test entities which link each other.
    $referenced_entity = EntityTestMul::create();
    $referenced_entity->save();

    $entity = EntityTest::create();
    $entity->field_test_data->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_test_data[0]->entity->id());
    $this->entities[] = $entity;

    $entity = EntityTest::create();
    $entity->field_test_data->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_test_data[0]->entity->id());
    $this->entities[] = $entity;

    Views::viewsData()->clear();

    // Check the generated views data.
    $views_data = Views::viewsData()->get('entity_test__field_test_data');
    $this->assertEquals('standard', $views_data['field_test_data']['relationship']['id']);
    $this->assertEquals('entity_test_mul_property_data', $views_data['field_test_data']['relationship']['base']);
    $this->assertEquals('id', $views_data['field_test_data']['relationship']['base field']);
    $this->assertEquals('field_test_data_target_id', $views_data['field_test_data']['relationship']['relationship field']);
    $this->assertEquals('entity_test_mul', $views_data['field_test_data']['relationship']['entity type']);

    // Check the backwards reference.
    $views_data = Views::viewsData()->get('entity_test_mul_property_data');
    $this->assertEquals('entity_reverse', $views_data['reverse__entity_test__field_test_data']['relationship']['id']);
    $this->assertEquals('entity_test', $views_data['reverse__entity_test__field_test_data']['relationship']['base']);
    $this->assertEquals('id', $views_data['reverse__entity_test__field_test_data']['relationship']['base field']);
    $this->assertEquals('entity_test__field_test_data', $views_data['reverse__entity_test__field_test_data']['relationship']['field table']);
    $this->assertEquals('field_test_data_target_id', $views_data['reverse__entity_test__field_test_data']['relationship']['field field']);
    $this->assertEquals('field_test_data', $views_data['reverse__entity_test__field_test_data']['relationship']['field_name']);
    $this->assertEquals('entity_test', $views_data['reverse__entity_test__field_test_data']['relationship']['entity_type']);

    $values = ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE];
    $this->assertEquals($values, $views_data['reverse__entity_test__field_test_data']['relationship']['join_extra'][0]);

    // Check an actual test view.
    $view = Views::getView('test_og_standard_reference_entity_test_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      // Check that the actual ID of the entity is the expected one.
      $this->assertEquals($this->entities[$index]->id(), $row->id);

      // Also check that we have the correct result entity.
      $this->assertEquals($this->entities[$index]->id(), $row->_entity->id());

      // Test the forward relationship.
      $this->assertEquals(1, $row->entity_test_mul_property_data_entity_test__field_test_data_i);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals(1, $row->_relationship_entities['field_test_data']->id());
      $this->assertEquals('entity_test_mul', $row->_relationship_entities['field_test_data']->bundle());
    }

    // Check the backwards reference view.
    $view = Views::getView('test_og_standard_reference_reverse_entity_test_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      $this->assertEquals($row->id, 1);
      $this->assertEquals($row->_entity->id(), 1);

      // Test the backwards relationship.
      $this->assertEquals($row->field_test_data_entity_test_mul_property_data_id, $this->entities[$index]->id());

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['reverse__entity_test__field_test_data']->id(), $this->entities[$index]->id());
      $this->assertEquals($row->_relationship_entities['reverse__entity_test__field_test_data']->bundle(), 'entity_test');
    }
  }

  /**
   * Tests views data generated for relationship.
   *
   * @see entity_reference_field_views_data()
   */
  public function testDataTableRelationship() {

    // Create some test entities which link each other.
    $referenced_entity = EntityTest::create();
    $referenced_entity->save();

    $entity = EntityTestMul::create();
    $entity->field_data_test->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_data_test[0]->entity->id());
    $this->entities[] = $entity;

    $entity = EntityTestMul::create();
    $entity->field_data_test->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_data_test[0]->entity->id());
    $this->entities[] = $entity;

    Views::viewsData()->clear();

    // Check the generated views data.
    $views_data = Views::viewsData()->get('entity_test_mul__field_data_test');
    $this->assertEquals('standard', $views_data['field_data_test']['relationship']['id']);
    $this->assertEquals('entity_test', $views_data['field_data_test']['relationship']['base']);
    $this->assertEquals('id', $views_data['field_data_test']['relationship']['base field']);
    $this->assertEquals('field_data_test_target_id', $views_data['field_data_test']['relationship']['relationship field']);
    $this->assertEquals('entity_test', $views_data['field_data_test']['relationship']['entity type']);

    // Check the backwards reference.
    $views_data = Views::viewsData()->get('entity_test');
    $this->assertEquals('entity_reverse', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['id']);
    $this->assertEquals('entity_test_mul_property_data', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['base']);
    $this->assertEquals('id', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['base field']);
    $this->assertEquals('entity_test_mul__field_data_test', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['field table']);
    $this->assertEquals('field_data_test_target_id', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['field field']);
    $this->assertEquals('field_data_test', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['field_name']);
    $this->assertEquals('entity_test_mul', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['entity_type']);

    $values = ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE];
    $this->assertEquals($values, $views_data['reverse__entity_test_mul__field_data_test']['relationship']['join_extra'][0]);

    // Check an actual test view.
    $view = Views::getView('test_og_standard_reference_entity_test_mul_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      // Check that the actual ID of the entity is the expected one.
      $this->assertEquals($this->entities[$index]->id(), $row->id);

      // Also check that we have the correct result entity.
      $this->assertEquals($this->entities[$index]->id(), $row->_entity->id());

      // Test the forward relationship.
      $this->assertEquals(1, $row->entity_test_entity_test_mul__field_data_test_id);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals(1, $row->_relationship_entities['field_data_test']->id());
      $this->assertEquals('entity_test', $row->_relationship_entities['field_data_test']->bundle());

    }

    // Check the backwards reference view.
    $view = Views::getView('test_og_standard_reference_reverse_entity_test_mul_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      $this->assertEquals(1, $row->id);
      $this->assertEquals(1, $row->_entity->id());

      // Test the backwards relationship.
      $this->assertEquals($this->entities[$index]->id(), $row->field_data_test_entity_test_id);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($this->entities[$index]->id(), $row->_relationship_entities['reverse__entity_test_mul__field_data_test']->id());
      $this->assertEquals('entity_test_mul', $row->_relationship_entities['reverse__entity_test_mul__field_data_test']->bundle());
    }
  }

}
