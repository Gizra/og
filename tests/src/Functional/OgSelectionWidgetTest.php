<?php

namespace Drupal\Tests\og\Functional;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\node\Entity\Node;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the widget for the OG selection handler.
 *
 * @group og
 */
class OgSelectionWidgetTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content', 'node', 'og', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    EntityTestBundle::create([
      'id' => 'group_type',
      'label' => 'group_type',
    ])->save();

    Og::groupTypeManager()->addGroup('entity_test_with_bundle', 'group_type');

    // Add a group audience field to the "post" node type, turning it into a
    // group content type.
    $this->createContentType(['type' => 'post']);
    $settings = [
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'entity_test_with_bundle',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', 'post', $settings);
  }

  /**
   * Tests adding groups, and node access.
   */
  public function testFields() {
    $user = $this->drupalCreateUser([
      'administer group',
      'access content',
      'create post content',
    ]);

    $group = EntityTestWithBundle::create([
      'type' => 'group_type',
      'name' => $this->randomMachineName(),
    ]);
    $group->save();

    // Log in as administrator.
    $this->drupalLogin($user);

    // Create a new post in the group by using the given field in the UI.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'og_audience[]' => $group->id(),
    ];

    $this->drupalGet('node/add/post');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Retrieve the post that was created from the database.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity_type.manager')->getStorage('node')->getQuery();
    $result = $query
      ->condition('type', 'post')
      ->range(0, 1)
      ->sort('nid', 'DESC')
      ->execute();
    $post_nid = reset($result);

    /** @var \Drupal\node\NodeInterface $post */
    $post = Node::load($post_nid);

    // Check that the post references the group correctly.
    $reference_list = $post->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    $this->assertEquals(1, $reference_list->count(), "There is 1 reference after adding a group to the audience field.");
    $this->assertEquals($group->id(), $reference_list->first()->getValue()['target_id'], "The audience field references the correct group.");
  }

}
