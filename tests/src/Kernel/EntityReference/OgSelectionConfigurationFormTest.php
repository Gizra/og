<?php


namespace Drupal\Tests\og\Kernel\EntityReference;


use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Tests the field settings configuration form for the OG audience field.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\EntityReferenceSelection\OgSelection
 */
class OgSelectionConfigurationFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'field_ui',
    'og',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add node types.
    NodeType::create([
      'type' => 'non_group',
      'name' => 'non_group',
    ])->save();

    NodeType::create([
      'type' => 'group_type1',
      'name' => 'group_type1',
    ])->save();

    NodeType::create([
      'type' => 'group_type2',
      'name' => 'group_type2',
    ])->save();

    NodeType::create([
      'type' => 'group_content',
      'name' => 'group_content',
    ])->save();

    Og::addGroup('node', 'group_type1');
    Og::addGroup('node', 'group_type2');

    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content');
  }

  /**
   * Test if a group that uses a string as ID can be referenced.
   *
   * @covers ::buildConfigurationForm
   */
  public function testConfigurationForm() {
    $entity_type_id = 'field_config';
    $operation = 'edit';
    $form_object = \Drupal::entityManager()->getFormObject($entity_type_id, $operation);

    $entity = FieldConfig::loadByName('node', 'group_content', 'og_audience');
    $form_object->setEntity($entity);

    $form_state = new FormState();

    $form = \Drupal::formBuilder()->buildForm($form_object, $form_state);
    print_r($form['settings']['handler']['handler_settings']['target_bundles']['#options']);

    $options = array_keys($form['settings']['handler']['handler_settings']['target_bundles']['#options']);
    sort($options);


    $this->assertEquals(['group_type1', 'group_type2'], $options);
  }

}
