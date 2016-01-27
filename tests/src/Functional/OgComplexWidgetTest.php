<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Functional\OgComplexWidgetTest.
 */

namespace Drupal\Tests\og\Functional;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItemList;
use Drupal\simpletest\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

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
  public static $modules = ['node', 'og'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Create a "group" node type and turn it into a group type.
    $this->createContentType(['type' => 'group']);
    Og::groupManager()->addGroup('node', 'group');

    // Add a group audience field to the "post" node type, turning it into a
    // group content type.
    $this->createContentType(['type' => 'post']);
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'post');

    // Make the group audience field visible in the default form display.
    // @todo Remove this once issue #144 is in.
    // @see https://github.com/amitaibu/og/issues/144
    /** @var EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.post.default');
    $widget = $form_display->getComponent('og_group_ref');
    $widget['type'] = 'og_complex';
    $widget['settings'] = [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => '',
    ];
    $form_display->setComponent('og_group_ref', $widget);
    $form_display->save();
  }

  /**
   * Tests the "Other Groups" field.
   */
  function testOtherGroups() {
    $admin_user = $this->drupalCreateUser(['administer group', 'access content', 'create post content']);
    $group_owner = $this->drupalCreateUser(['access content', 'create post content']);

    // Create a group content type owned by the group owner.
    $settings = [
      'type' => 'group',
      'uid' => $group_owner->id(),
    ];
    $group = $this->createNode($settings);

    // Log in as administrator.
    $this->drupalLogin($admin_user);

    // Create a new post in the group by using the "Other Groups" field in the
    // UI.
    $edit = [
      'title[0][value]' => "Group owner's post.",
      'other_groups[0][target_id]' => "group ({$group->id()})",
    ];
    $this->drupalGet('node/add/post');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Retrieve the post that was created from the database.
    /** @var QueryInterface $query */
    $query = $this->container->get('entity.query')->get('node');
    $result = $query
      ->condition('type', 'post')
      ->range(0, 1)
      ->sort('nid', 'DESC')
      ->execute();
    $post_nid = reset($result);

    /** @var NodeInterface $post */
    $post = Node::load($post_nid);

    // Check that the post references the group correctly.
    /** @var OgMembershipReferenceItemList $reference_list */
    $reference_list = $post->get(OgGroupAudienceHelper::DEFAULT_FIELD);
    $this->assertEquals(1, $reference_list->count(), 'There is 1 reference after adding a group to the "Other Groups" field.');
    $this->assertEquals($group->id(), $reference_list->first()->getValue()['target_id'], 'The "Other Groups" field references the correct group.');
  }

}
