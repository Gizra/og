<?php

namespace Drupal\og_ui\Tests;

use Drupal\Core\Form\FormState;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og_ui\BundleFormAlter;
use Drupal\Tests\BrowserTestBase;

/**
 * Test making a bundle a group and a group content.
 *
 * @group og
 */
class BundleFormAlterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content', 'entity_test', 'node', 'og_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    // Log in as an administrator that can manage blocks and content types.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'administer content types',
      'bypass node access',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that group and group content bundles can be created through the UI.
   */
  public function testCreate() {
    // Create a custom block and define it as a group type. We make sure the
    // group and group content are of different entity types so we can test that
    // the correct entity type is referenced.
    $edit = [
      'label' => 'school',
      'id' => 'school',
      'og_is_group' => 1,
    ];
    $this->drupalGet('admin/structure/block/block-content/types/add');
    $this->submitForm($edit, t('Save'));

    $edit = [
      'name' => 'class',
      'type' => 'class',
      'og_group_content_bundle' => 1,
      'og_target_type' => 'block_content',
      'og_target_bundles[]' => ['school'],
    ];
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm($edit, t('Save content type'));
    $this->content = $this->drupalGet('admin/structure/types/manage/class');
    $this->assertOptionSelected('edit-og-target-bundles', 'school');
    $this->assertTargetType('block_content', 'The target type is set to the "Custom Block" entity type.');
    $this->assertTargetBundles(['school' => 'school'], 'The target bundles are set to the "school" bundle.');

    // Test that if the target bundles are unselected, the value for the target
    // bundles becomes NULL rather than an empty array. The entity reference
    // selection plugin considers the value NULL to mean 'all bundles', while an
    // empty array means 'no bundles are allowed'.
    // @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection::buildEntityQuery()
    $edit = [
      'name' => 'class',
      'og_group_content_bundle' => 1,
      'og_target_type' => 'block_content',
      'og_target_bundles[]' => [],
    ];
    $this->drupalGet('admin/structure/types/manage/class');
    $this->submitForm($edit, t('Save content type'));
    $this->assertTargetBundles(NULL, 'When the target bundle field is cleared from all values, it takes on the value NULL.');
  }

  /**
   * Tests AJAX behavior for selecting group content entity types and bundles.
   */
  public function testGroupContentAjax() {
    // Create two group bundles of different entity types.
    NodeType::create(['name' => 'group node', 'type' => 'group'])->save();
    Og::groupTypeManager()->addGroup('node', 'group');
    Og::groupTypeManager()->addGroup('entity_test', 'entity_test');

    // BrowserTestBase doesn't support JavaScript yet. Replace the following
    // unit test with a functional test once JavaScript support is added.
    // @see https://www.drupal.org/node/2469713
    $form = [];
    $form_state = new FormState();
    // Set the form state as if the 'entity_test' option was chosen with AJAX.
    $form_state->setValue('og_target_type', 'entity_test');
    $entity = $this->entityTypeManager->getStorage('node_type')->create([]);
    (new BundleFormAlter($entity))->formAlter($form, $form_state);

    // Check that the target bundles are set to the test entity bundle.
    $this->assertEquals(['entity_test' => 'Entity Test Bundle'], $form['og']['og_target_bundles']['#options']);
  }

  /**
   * Checks whether the target bundles in the group content are as expected.
   *
   * @param array|null $expected
   *   The expected value for the target bundles.
   * @param string $message
   *   The message to display with the assertion.
   */
  protected function assertTargetBundles($expected, $message) {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');
    $entity_field_manager->clearCachedFieldDefinitions();
    $field_definitions = $entity_field_manager->getFieldDefinitions('node', 'class');
    $settings = $field_definitions['og_audience']->getSetting('handler_settings');
    $this->assertEquals($expected, $settings['target_bundles'], $message);
  }

  /**
   * Checks whether the target entity type in the group content is as expected.
   *
   * @param string $expected
   *   The expected target entity type.
   * @param string $message
   *   The message to display with the assertion.
   */
  protected function assertTargetType($expected, $message) {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');
    $entity_field_manager->clearCachedFieldDefinitions();
    $field_definitions = $entity_field_manager->getFieldStorageDefinitions('node');
    $setting = $field_definitions['og_audience']->getSetting('target_type');
    $this->assertEquals($expected, $setting, $message);
  }

}
