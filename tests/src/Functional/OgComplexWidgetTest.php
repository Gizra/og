<?php

namespace Drupal\Tests\og\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\node\Entity\Node;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the complex widget.
 *
 * @group og
 */
class OgComplexWidgetTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content', 'node', 'og'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a "group" bundle on the Custom Block entity type and turn it into
    // a group. Note we're not using the Entity Test entity for this since it
    // does not have real support for multiple bundles.
    BlockContentType::create(['id' => 'group'])->save();
    Og::groupTypeManager()->addGroup('block_content', 'group');

    // Add a group audience field to the "post" node type, turning it into a
    // group content type.
    $this->createContentType(['type' => 'post']);
    $settings = [
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'block_content',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', 'post', $settings);
  }

  /**
   * Tests adding groups with the "Groups audience" and "Other Groups" fields.
   *
   * @dataProvider ogComplexFieldsProvider
   */
  public function testFields($field, $field_name) {
    $admin_user = $this->drupalCreateUser([
      'administer group',
      'access content',
      'create post content',
    ]);
    $group_owner = $this->drupalCreateUser([
      'access content',
      'create post content',
    ]);

    // Create a group content type owned by the group owner.
    $values = [
      'type' => 'group',
      'uid' => $group_owner->id(),
    ];
    $group = BlockContent::create($values);
    $group->save();

    // Log in as administrator.
    $this->drupalLogin($admin_user);

    // Create a new post in the group by using the given field in the UI.
    $edit = [
      'title[0][value]' => "Group owner's post.",
      $field_name => "group ({$group->id()})",
    ];
    $this->drupalGet('node/add/post');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Retrieve the post that was created from the database.
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
    /** @var \Drupal\og\OgMembershipReferenceItemList $reference_list */
    $reference_list = $post->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    $this->assertEquals(1, $reference_list->count(), "There is 1 reference after adding a group to the '$field' field.");
    $this->assertEquals($group->id(), $reference_list->first()->getValue()['target_id'], "The '$field' field references the correct group.");
  }

  /**
   * Data provider for ::testFields()
   *
   * @return array
   *   Array with the field human readable name, and the field's HTML name.
   */
  public function ogComplexFieldsProvider() {
    return [
      ['Groups audience', 'og_audience[0][target_id]'],
      ['Other groups', 'other_groups[0][target_id]'],
    ];
  }

}
