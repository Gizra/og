<?php

namespace Drupal\Tests\og\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the OG admin Members view.
 *
 * @group og
 */
class OgAdminMembersViewTest extends ViewsKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'node',
    'og',
    'options',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['og_members_overview'];

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $configProperties = [
    'disabled',
    'name',
    'description',
    'tag',
    'base_table',
    'label',
    'core',
    'display',
  ];

  /**
   * Properties that should be stored in the executable.
   *
   * @var array
   */
  protected $executableProperties = [
    'storage',
    'built',
    'executed',
    'args',
    'build_info',
    'result',
    'attachment_before',
    'attachment_after',
    'exposed_data',
    'exposed_raw_input',
    'old_view',
    'parent_views',
  ];

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The group entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create a group entity type.
    $group_bundle = mb_strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::groupTypeManager()->addGroup('node', $group_bundle);

    // Create group admin user.
    $this->user = $this->createUser(['access user profiles'], FALSE, TRUE);

    // Create a group.
    $this->group = Node::create([
      'title' => $this->randomString(),
      'type' => $group_bundle,
      'uid' => $this->user->id(),
    ]);
    $this->group->save();

    parent::setUpFixtures();
  }

  /**
   * Tests the Members table.
   */
  public function testMembersTable() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $view = Views::getView('og_members_overview');
    $preview = $view->preview('default', ['node', $this->group->id()]);

    $this->setRawContent($renderer->renderRoot($preview));

    $map = [
      // Validate header.
      'Name' => '//*[@id="view-name-table-column"]/a/text()',
      'Member since' => '//*[@id="view-created-table-column"]',
      'State' => '//*[@id="view-state-table-column"]',

      // Validate the user appears.
      $this->user->label() => '//*/tbody/tr/td[2]/span/text()',

      // Validate that the user has the bulk operation checkbox.
      'Update the member' => '//td[contains(@class, \'views-field-og-membership-bulk-form\')]/div/label',
    ];

    foreach ($map as $value => $xpath) {
      $result = $this->xpath($xpath);
      $this->assertTrue(strpos(trim((string) $result[0]), $value) === 0);
    }
  }

}
