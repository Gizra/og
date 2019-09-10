<?php

namespace Drupal\Tests\og\Kernel\EntityReference;

use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
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
    'entity_test',
    'og',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installSchema('system', 'sequences');

    EntityTestBundle::create([
      'id' => 'non_group',
      'label' => 'non_group',
    ])->save();

    EntityTestBundle::create([
      'id' => 'group_type1',
      'label' => 'group_type1',
    ])->save();

    EntityTestBundle::create([
      'id' => 'group_type2',
      'label' => 'group_type2',
    ])->save();

    EntityTestBundle::create([
      'id' => 'group_content',
      'label' => 'group_content',
    ])->save();

    Og::addGroup('entity_test_with_bundle', 'group_type1');
    Og::addGroup('entity_test_with_bundle', 'group_type2');

    $settings = [
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'entity_test_with_bundle',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'entity_test_with_bundle', 'group_content', $settings);
  }

  /**
   * Test if a group that uses a string as ID can be referenced.
   *
   * @covers ::buildConfigurationForm
   */
  public function testConfigurationForm() {
    $form_object = \Drupal::entityTypeManager()->getFormObject('field_config', 'edit');

    $entity = FieldConfig::loadByName('entity_test_with_bundle', 'group_content', OgGroupAudienceHelper::DEFAULT_FIELD);
    $form_object->setEntity($entity);

    $form_state = new FormState();

    $form = \Drupal::formBuilder()->buildForm($form_object, $form_state);

    $options = array_keys($form['settings']['handler']['handler_settings']['target_bundles']['#options']);
    sort($options);

    $this->assertEquals(['group_type1', 'group_type2'], $options);
  }

}
