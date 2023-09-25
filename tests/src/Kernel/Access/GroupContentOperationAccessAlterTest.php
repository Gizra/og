<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\user\Entity\User;

/**
 * Test that access to group content operations can be altered.
 *
 * @coversDefaultClass \Drupal\og\OgAccess
 * @group og
 */
class GroupContentOperationAccessAlterTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'entity_test',
    'field',
    'og',
    'og_test',
    'system',
    'user',
    'options',
  ];

  /**
   * The OG access service. This is the system under test.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A test group.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group;

  /**
   * A test group content entity.
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $groupContent;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->ogAccess = $this->container->get('og.access');

    // Create a dummy user which will get UID 1. We cannot use this for testing
    // since this user becomes the super administrator and is not suitable for
    // testing access control.
    User::create(['name' => $this->randomString()])->save();

    // Create a test user with the 'moderator' role which has global permission
    // to moderate comments in all groups, even ones they are not a member of.
    $this->user = $this->createUser(['edit and delete comments in all groups']);

    // Create the test group along with a user that serves as the group owner.
    $group_bundle = mb_strtolower($this->randomMachineName());
    $this->group = EntityTest::create([
      'type' => $group_bundle,
      'name' => $this->randomString(),
      'user_id' => $this->createUser()->id(),
    ]);
    $this->group->save();

    // Declare that the test entity type is a group type.
    Og::groupTypeManager()->addGroup('entity_test', $group_bundle);

    // Create a group content type.
    CommentType::create([
      'id' => 'comment',
      'label' => 'Comment subscription',
      'target_entity_type_id' => 'entity_test',
    ])->save();
    $settings = [
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'entity_test',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'comment', 'comment', $settings);

    // Create a group content entity.
    $values = [
      'subject' => 'subscribe',
      'comment_type' => 'comment',
      'entity_id' => $this->group->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'an_imaginary_field',
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => [['target_id' => $this->group->id()]],
    ];
    $this->groupContent = Comment::create($values);
    $this->groupContent->save();
  }

  /**
   * Tests that modules can alter group content entity operation access.
   *
   * This mimicks a use case where a moderator has access to edit and delete
   * comments in all groups.
   *
   * @see \og_test_og_user_access_entity_operation_alter()
   *
   * @dataProvider groupContentEntityOperationAccessAlterHookTestProvider
   */
  public function testGroupContentEntityOperationAccessAlterHook(string $operation): void {
    // Check that our test user doesn't have access to edit or delete comments
    // in the group.
    // This is the default behavior for users that are not a group member.
    $this->assertFalse($this->userHasAccess($operation));

    // Now enable our hook which will alter the group content entity operation
    // access rules to allow moderators to edit and delete comments in all
    // groups. Since our user is a moderator they should now have access.
    \Drupal::state()->set('og_test_group_content_entity_operation_access_alter', TRUE);
    $this->assertTrue($this->userHasAccess($operation));
  }

  /**
   * Checks whether the test user has access to perform the entity operation.
   *
   * @param string $operation
   *   The entity operation to check.
   *
   * @return bool
   *   TRUE if the user has access, FALSE otherwise.
   */
  protected function userHasAccess(string $operation): bool {
    return $this->ogAccess->userAccessGroupContentEntityOperation($operation, $this->group, $this->groupContent, $this->user)->isAllowed();
  }

  /**
   * Provides test data for ::testGroupContentEntityOperationAccessAlterHook().
   *
   * @return string[][]
   *   Test cases for the 'update' and 'delete' entity operations.
   */
  public function groupContentEntityOperationAccessAlterHookTestProvider(): array {
    return [
      ['update'],
      ['delete'],
    ];
  }

}
