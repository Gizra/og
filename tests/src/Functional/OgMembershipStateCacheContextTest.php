<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Functional;

use Drupal\Core\Url;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the og_membership_state cache context.
 *
 * @group og
 */
class OgMembershipStateCacheContextTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'og',
    'og_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that group is resolved correctly from cache contexts.
   *
   * When a node's render cache contexts includes og_membership_state, then a
   * 500 error is thrown when viewing the node's revision page. This test is for
   * verifying that regression has not happened.
   */
  public function testResolvedGroupEntity() : void {
    // Create a node group type.
    $group_type = 'group';
    $user = $this->createUser();
    NodeType::create([
      'name' => $this->randomString(),
      'type' => $group_type,
    ])->save();
    $this->container->get('og.group_type_manager')->addGroup('node', $group_type);

    // Create a group content type.
    $group_content_type = 'group_content';
    NodeType::create([
      'name' => $this->randomString(),
      'type' => $group_content_type,
    ])->save();
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $group_content_type);

    // Create the group node.
    $group = $this->createNode([
      'type' => $group_type,
      'uid' => $user->id(),
    ]);
    $group->setPublished();
    $group->save();
    // Create a new revision for the group node.
    $group->setNewRevision(TRUE);
    $group->setRevisionLogMessage('Created a new revision for testing.');
    $group->setRevisionCreationTime($this->container->get('datetime.time')->getRequestTime());
    $group->setRevisionUserId($user->id());
    $group->save();

    // Create the group content node.
    $group_content = $this->createNode([
      'type' => $group_content_type,
      'uid' => $user->id(),
    ]);
    $group_content->setPublished();
    $group_content->save();
    // Create a new revision for the group content node.
    $group_content->setNewRevision(TRUE);
    $group_content->setRevisionLogMessage('Created a new revision for testing.');
    $group_content->setRevisionCreationTime($this->container->get('datetime.time')->getRequestTime());
    $group_content->setRevisionUserId($user->id());
    $group_content->save();

    // Create a new user who can view revisions and login.
    $tester = $this->createUser(['view all revisions']);
    $this->drupalLogin($tester);

    $assert_session = $this->assertSession();

    // Visit the group node's new revision page and verify we're getting a 200
    // response and not 500.
    $url = Url::fromRoute('entity.node.revision', [
      'node' => $group->id(),
      'node_revision' => $group->getLoadedRevisionId(),
    ]);
    $this->drupalGet($url);
    $assert_session->statusCodeEquals(200);

    // Visit the group content node's new revision page and verify we're getting
    // a 200 response and not 500.
    $url = Url::fromRoute('entity.node.revision', [
      'node' => $group_content->id(),
      'node_revision' => $group_content->getLoadedRevisionId(),
    ]);
    $this->drupalGet($url);
    $assert_session->statusCodeEquals(200);
  }

}
