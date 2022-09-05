<?php

declare(strict_types = 1);

namespace Drupal\Tests\og_ui\Functional;

use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\Og;

/**
 * Test making a bundle a group and a group content.
 *
 * @group og
 */
class BundleEntityFormAlterAjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'node', 'og_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected GroupTypeManagerInterface $groupTypeManager;

  /**
   * A test group content type.
   *
   * @var \Drupal\Core\Entity\RevisionableEntityBundleInterface
   */
  protected RevisionableEntityBundleInterface $groupContentType;

  /**
   * A test group type.
   *
   * @var \Drupal\Core\Entity\RevisionableEntityBundleInterface
   */
  protected RevisionableEntityBundleInterface $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->groupTypeManager = $this->container->get('og.group_type_manager');

    // Create two group bundles of different entity types.
    $this->groupType = NodeType::create([
      'name' => 'group node',
      'type' => 'group',
    ]);
    $this->groupType->save();
    Og::groupTypeManager()->addGroup('node', 'group');
    Og::groupTypeManager()->addGroup('entity_test', 'entity_test');

    $this->groupContentType = NodeType::create([
      'name' => 'group content',
      'type' => 'group_content',
    ]);
    $this->groupContentType->save();

    // Log in as an administrator that can manage blocks and content types.
    $user = $this->drupalCreateUser([
      'administer content types',
      'bypass node access',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests AJAX behavior for selecting group content entity types and bundles.
   */
  public function testGroupContentAjax() {
    $this->drupalGet($this->groupContentType->toUrl('edit-form'));
    $page = $this->getSession()->getPage();

    // Open our vertical tab and check that the group content information is
    // present.
    $page->clickLink('Organic group');
    $this->assertSession()->pageTextContains('Every "group content" is a group which can contain entities and can have members.');
    $page->checkField('Group content');
    $this->assertSession()->selectExists('Target type');
    $this->assertSession()->pageTextContains('The entity type that can be referenced through this field.');
    $this->assertSession()->selectExists('Target bundles');
    $this->assertSession()->pageTextContains('The bundles of the entity type that can be referenced. Optional, leave empty for all bundles.');

    // Since the available group types are ordered alphabetically, the shown
    // options should initially match "Content" (aka "node").
    $this->assertSession()->optionExists('Target bundles', 'group node');
    $this->assertSession()->optionNotExists('Target bundles', 'Entity Test Bundle');

    // Switch to the test entity, this should update the options.
    $page->selectFieldOption('Target type', 'Test entity');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->optionExists('Target bundles', 'Entity Test Bundle');
    $this->assertSession()->optionNotExists('Target bundles', 'group node');

    // Select the test entity bundle and save the form.
    $page->selectFieldOption('Target bundles', 'Entity Test Bundle');
    $page->pressButton('Save content type');

    // Check that the target bundles are set to the test entity bundle.
    $this->groupTypeManager->reset();
    $group_content_bundles = $this->groupTypeManager->getGroupContentBundleIdsByGroupBundle('entity_test', 'entity_test');
    $this->assertArrayHasKey('node', $group_content_bundles, 'The test entity group type references the correct group content entity type.');
    $this->assertArrayHasKey('group_content', $group_content_bundles['node'], 'The test entity group type references the correct group content entity bundle.');
  }

}
