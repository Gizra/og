<?php

namespace Drupal\Tests\og\Kernel\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\OgContextInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Prophecy\Promise\CallbackPromise;

/**
 * Tests the member count block.
 *
 * @group og
 */
class MemberCountBlockTest extends KernelTestBase {

  use OgMembershipCreationTrait;
  use StringTranslationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_test',
    'field',
    'og',
    'system',
    'user',
  ];

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The block storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $blockStorage;

  /**
   * The block view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $blockViewBuilder;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The render cache.
   *
   * @var \Drupal\Core\Render\PlaceholderingRenderCache
   */
  protected $renderCache;

  /**
   * Test groups.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $groups;

  /**
   * A test block. This is the system under test.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected $block;

  /**
   * The group that is considered to be "currently active" in the test.
   *
   * This group will be returned by the mocked OgContext service.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $activeGroup;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installEntitySchema('og_membership');
    $this->installConfig(['system', 'block', 'og']);
    $this->installSchema('system', ['sequences']);

    $this->groupTypeManager = $this->container->get('og.group_type_manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->blockStorage = $this->entityTypeManager->getStorage('block');
    $this->blockViewBuilder = $this->entityTypeManager->getViewBuilder('block');
    $this->cacheTagsInvalidator = $this->container->get('cache_tags.invalidator');
    $this->renderer = $this->container->get('renderer');
    $this->renderCache = $this->container->get('render_cache');

    // The block being tested shows the member count of the currently active
    // group, which it gets from OgContext. Mock OgContext using a callback to
    // set the active group that will be used during the test.
    $og_context = $this->prophesize(OgContextInterface::class);
    $og_context->getGroup()->will(new CallbackPromise([$this, 'getActiveGroup']));
    $this->container->set('og.context', $og_context->reveal());

    // Create a group type.
    $this->groupTypeManager->addGroup('entity_test', 'group');

    // Create two test groups.
    for ($i = 0; $i < 2; $i++) {
      $this->groups[$i] = EntityTest::create([
        'type' => 'group',
        'name' => $this->randomString(),
      ]);
      $this->groups[$i]->save();
    }

    // Create a test block.
    $this->block = $this->blockStorage->create([
      'plugin' => 'og_member_count',
      'region' => 'sidebar_first',
      'id' => 'group_member_count',
      'theme' => $this->config('system.theme')->get('default'),
      'label' => 'Group member count',
      'visibility' => [],
      'weight' => 0,
    ]);
    $this->block->save();
  }

  /**
   * Tests the member count block.
   */
  public function testMemberCountBlock() {
    // Before the blocks are rendered for the first time, no cache entries
    // should exist for them. We have two groups, so let's test both blocks.
    $this->assertNotCached(0);
    $this->assertNotCached(1);

    // After rendering the first block, only this block should be cached, the
    // other should be unaffected.
    $this->renderBlock(0);
    $this->assertCached(0);
    $this->assertNotCached(1);

    $this->renderBlock(1);
    $this->assertCached(1);

    // Initially the blocks should have 0 members.
    $this->assertMemberCount(0, 0);
    $this->assertMemberCount(1, 0);

    // In the default configuration the block should only count active users,
    // and ignore blocked and pending users. Also check that making changes to
    // the members of the group only invalidates the cache of the related block.
    $this->addMember(0, OgMembershipInterface::STATE_BLOCKED);
    $this->addMember(0, OgMembershipInterface::STATE_PENDING);
    $this->assertNotCached(0);
    $this->assertCached(1);
    $this->assertMemberCount(0, 0);

    // However adding an active user increases the member count.
    $active_membership = $this->addMember(0, OgMembershipInterface::STATE_ACTIVE);
    $this->assertMemberCount(0, 1);

    // If we block the active user, the member count should be updated
    // accordingly.
    $active_membership->setState(OgMembershipInterface::STATE_BLOCKED)->save();
    $this->assertMemberCount(0, 0);

    // Check that both blocks are invalidated when the block settings are
    // changed.
    $this->updateBlockSetting('count_pending_users', TRUE);
    $this->assertNotCached(0);
    $this->assertNotCached(1);

    // The block has now been configured to also count pending members. Check if
    // the count is updated accordingly.
    $this->assertMemberCount(0, 1);
    $this->assertMemberCount(1, 0);

    // Turn on the counting of blocked members and check the resulting value.
    $this->updateBlockSetting('count_blocked_users', TRUE);
    $this->assertMemberCount(0, 3);
    $this->assertMemberCount(1, 0);

    // Now delete one of the memberships of the first group. This should
    // decrease the counter.
    $active_membership->delete();
    $this->assertMemberCount(0, 2);

    // Since the deletion of the user only affected the first group, the block
    // of the second group should still be unchanged and happily cached.
    $this->assertCached(1);
    $this->assertMemberCount(1, 0);

    // For good measure, try to add a user to the second group and check that
    // all is in order.
    $this->addMember(1, OgMembershipInterface::STATE_ACTIVE);
    $this->assertMemberCount(1, 1);

    // The block from the first group should not be affected by this.
    $this->assertCached(0);
    $this->assertMemberCount(0, 2);
  }

  /**
   * Renders the block using the passed in group as the currently active group.
   *
   * @param int $group_key
   *   The key of the group to set as active group.
   *
   * @return string
   *   The content of the block rendered as HTML.
   */
  protected function renderBlock($group_key) {
    // Clear the static caches of the cache tags invalidators. The invalidators
    // will only invalidate cache tags once per request to improve performance.
    // Unfortunately they can not distinguish between an actual Drupal page
    // request and a PHPUnit test that simulates visiting multiple pages.
    // We are pretending that every time this method is called a new page has
    // been requested, and the static caches are empty.
    $this->cacheTagsInvalidator->resetChecksums();

    $this->activeGroup = $this->groups[$group_key];
    $render_array = $this->blockViewBuilder->view($this->block);
    $html = $this->renderer->renderRoot($render_array);

    // At all times, after a block is rendered, it should be cached.
    $this->assertCached($group_key);

    return $html;
  }

  /**
   * Checks that the block shows the correct member count for the given group.
   *
   * @param int $group_key
   *   The key of the group for which to check the block.
   * @param int $expected_count
   *   The number of members that are expected to be shown in the block.
   */
  protected function assertMemberCount($group_key, $expected_count) {
    $expected_string = (string) $this->formatPlural($expected_count, '@label has 1 member.', '@label has @count members', ['@label' => $this->groups[$group_key]->label()]);
    $this->assertContains($expected_string, (string) $this->renderBlock($group_key));
  }

  /**
   * Adds a member with the given membership state to the given group.
   *
   * @param int $group_key
   *   The key of the group to which a member should be added.
   * @param string $state
   *   The membership state to assign to the newly added member.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The membership entity for the newly added member.
   */
  protected function addMember($group_key, $state) {
    $user = $this->createUser();
    return $this->createOgMembership($this->groups[$group_key], $user, NULL, $state);
  }

  /**
   * Updates the given setting in the block with the given value.
   *
   * @param string $setting
   *   The setting to update.
   * @param mixed $value
   *   The value to set.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the updated block cannot be saved.
   */
  protected function updateBlockSetting($setting, $value) {
    $settings = $this->block->get('settings');
    $settings[$setting] = $value;
    $this->block->set('settings', $settings)->save();
  }

  /**
   * Checks that the block is cached for the given group.
   *
   * @param int $group_key
   *   The key of the group for which to check the block cache status.
   */
  protected function assertCached($group_key) {
    $this->doAssertCached('assertNotEmpty', $group_key);
  }

  /**
   * Checks that the block is not cached for the given group.
   *
   * @param int $group_key
   *   The key of the group for which to check the block cache status.
   */
  protected function assertNotCached($group_key) {
    $this->doAssertCached('assertEmpty', $group_key);
  }

  /**
   * Checks the cache status of the block for the given group.
   *
   * @param string $assert_method
   *   The method to use for asserting that the block is cached or not cached.
   * @param int $group_key
   *   The key of the group for which to check the block cache status.
   */
  protected function doAssertCached($assert_method, $group_key) {
    // We will switch the currently active context so that the right cache
    // contexts are available for the render cache. Keep track of the currently
    // active group so we can restore it after checking the cache status.
    $original_active_group = $this->activeGroup;
    $this->activeGroup = $this->groups[$group_key];

    // Retrieve the block to render, and apply the required cache contexts that
    // are also applied when RendererInterface::renderRoot() is executed. This
    // ensures that we pass the same cache information to the render cache as is
    // done when actually rendering the HTML root.
    $render_array = $this->blockViewBuilder->view($this->block);
    $render_array['#cache']['contexts'] = Cache::mergeContexts($render_array['#cache']['contexts'], $this->container->getParameter('renderer.config')['required_cache_contexts']);

    // Retrieve the cached data and perform the assertion.
    $cached_data = $this->renderCache->get($render_array);
    $this->$assert_method($cached_data);

    // Restore the active group.
    $this->activeGroup = $original_active_group;
  }

  /**
   * Callback providing the active group to be returned by the mocked OgContext.
   *
   * @return \Drupal\entity_test\Entity\EntityTest
   *   The active group.
   */
  public function getActiveGroup() {
    return $this->activeGroup;
  }

}
