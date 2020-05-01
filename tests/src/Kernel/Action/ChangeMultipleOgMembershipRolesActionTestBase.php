<?php

namespace Drupal\Tests\og\Kernel\Action;

/**
 * Base class for tests for plugins that change multiple roles at once.
 */
class ChangeMultipleOgMembershipRolesActionTestBase extends ChangeOgMembershipActionTestBase {

  /**
   * The factory for private temporary storage objects.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStorageFactory;

  /**
   * A test user that is logged in during the test session.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('system', ['key_value_expire']);

    $this->tempStorageFactory = $this->container->get('tempstore.private');

    // Set up the group administrator as the user that will be logged in during
    // the tests.
    $this->testUser = $this->users['group_administrator'];
  }

  /**
   * Checks if the action can be performed correctly.
   *
   * @param array $membership_ids
   *   The memberships on which to perform the action.
   *
   * @covers ::executeMultiple
   * @dataProvider executeMultipleProvider
   */
  public function testExecuteMultiple(array $membership_ids) {
    // Log in as a group administrator. The memberships to change will be stored
    // in the private temporary storage of the logged in user. We do not need to
    // check any permissions on this user since it is already handled by the
    // access test.
    // @see \Drupal\Tests\og\Kernel\Action\ActionTestBase::testAccess()
    $this->setCurrentUser($this->testUser);

    $memberships = $this->memberships;
    $memberships = array_filter($memberships, function ($membership_id) use ($membership_ids) {
      return in_array($membership_id, $membership_ids);
    }, ARRAY_FILTER_USE_KEY);

    /** @var \Drupal\og\Plugin\Action\AddMultipleOgMembershipRoles $plugin */
    $plugin = $this->getPlugin();
    $plugin->executeMultiple($memberships);

    // The plugin's only responsibility is to store the memberships in temporary
    // storage. The actual processing of the memberships will be handled in the
    // confirmation form.
    $this->assertStoredMemberships($membership_ids);
  }

  /**
   * Data provider for testExecuteMultiple().
   */
  public function executeMultipleProvider() {
    // Test a few combinations of different memberships.
    return [
      [
        [
          'pending',
          'member',
        ],
      ],
      [
        [
          'blocked',
          'member',
          'group_moderator',
        ],
      ],
      [
        [
          'blocked',
          'pending',
          'member',
          'group_administrator',
          'group_moderator',
        ],
      ],
    ];
  }

  /**
   * Checks if the action can be performed correctly.
   *
   * @param string $membership
   *   The membership on which to perform the action.
   *
   * @covers ::execute
   * @dataProvider executeProvider
   */
  public function testExecute($membership) {
    // Log in as a group administrator. The memberships to change will be stored
    // in the private temporary storage of the logged in user. We do not need to
    // check any permissions on this user since it is already handled by the
    // access test.
    // @see \Drupal\Tests\og\Kernel\Action\ActionTestBase::testAccess()
    $this->setCurrentUser($this->testUser);

    /** @var \Drupal\og\Plugin\Action\AddMultipleOgMembershipRoles $plugin */
    $plugin = $this->getPlugin();
    $plugin->execute($this->memberships[$membership]);

    // The plugin's only responsibility is to store the memberships in temporary
    // storage. The actual processing of the memberships will be handled in the
    // confirmation form.
    $this->assertStoredMemberships([$membership]);
  }

  /**
   * Data provider for testExecute().
   */
  public function executeProvider() {
    // Test each membership.
    return [
      ['member'],
      ['pending'],
      ['blocked'],
      ['group_administrator'],
      ['group_moderator'],
    ];
  }

  /**
   * Checks that the memberships in temporary storage match the expected ones.
   *
   * @param array $membership_ids
   *   An array of membership IDs that are expected to be present in the private
   *   temporary storage of the logged in user.
   */
  protected function assertStoredMemberships(array $membership_ids) {
    $private_tempstore = $this->tempStorageFactory->get($this->pluginId);
    $actual_membership_ids = $private_tempstore->get('membership_ids');

    // Get the actual entity IDs of the memberships as stored in the database.
    $memberships = $this->memberships;
    $expected_membership_ids = array_map(function ($membership_id) use ($memberships) {
      return $memberships[$membership_id]->id();
    }, $membership_ids);

    sort($expected_membership_ids);
    sort($actual_membership_ids);

    $this->assertEquals($expected_membership_ids, $actual_membership_ids);
  }

}
