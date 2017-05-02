<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Exception\OgRoleException;
use Drupal\system\Entity\Action;

/**
 * Test OG role creation.
 *
 * @group og
 */
class OgRoleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

  /**
   * The entity storage handler for Action entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * The entity storage handler for OgRole entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * Test group types.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityBundleBase[]
   */
  protected $groupTypes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Installing needed schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');

    $this->actionStorage = $this->container->get('entity_type.manager')->getStorage('action');
    $this->roleStorage = $this->container->get('entity_type.manager')->getStorage('og_role');

    // Create two test group types.
    $values = ['type' => 'group', 'name' => 'Group'];
    $this->groupTypes['node'] = NodeType::create($values);
    $this->groupTypes['node']->save();
    $this->groupTypes['entity_test'] = EntityTest::create($values);
    $this->groupTypes['entity_test']->save();
  }

  /**
   * Testing OG role creation.
   */
  public function testRoleCreate() {
    /** @var \Drupal\og\Entity\OgRole $og_role */
    $og_role = OgRole::create();
    $og_role
      ->setName('content_editor')
      ->setLabel('Content editor')
      ->grantPermission('administer group');

    try {
      $og_role->save();
      $this->fail('Creating OG role without group type/bundle is not allowed.');
    }
    catch (ConfigValueException $e) {
      $this->assertTrue(TRUE, 'OG role without bundle/group was not saved.');
    }

    $og_role
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->save();

    /** @var \Drupal\og\Entity\OgRole $saved_role */
    $saved_role = $this->roleStorage->loadUnchanged('node-group-content_editor');
    $this->assertNotEmpty($saved_role, 'The role was created with the expected ID.');
    $this->assertEquals($og_role->id(), $saved_role->id());

    // Checking creation of the role.
    $this->assertEquals($og_role->getPermissions(), ['administer group']);

    // When a role is created the two accompanying actions to add or remove this
    // role to a membership should also be created.
    $action_ids = [
      'og_membership_add_role_action.content_editor',
      'og_membership_remove_role_action.content_editor',
    ];
    /** @var \Drupal\Core\Action\ActionInterface[] $actions */
    $actions = Action::loadMultiple($action_ids);
    foreach ($action_ids as $action_id) {
      $this->assertTrue(array_key_exists($action_id, $actions));
      $this->assertEquals($action_id, $actions[$action_id]->id());
    }

    // Try to create the same role again.
    try {
      $og_role = OgRole::create();
      $og_role
        ->setName('content_editor')
        ->setLabel('Content editor')
        ->setGroupType('node')
        ->setGroupBundle('group')
        ->grantPermission('administer group')
        ->save();

      $this->fail('OG role with the same ID can be saved.');
    }
    catch (EntityStorageException $e) {
      $this->assertTrue(TRUE, "OG role with the same ID can not be saved.");
    }

    // Create a role assigned to a group type.
    $og_role = OgRole::create();
    $og_role
      ->setName('content_editor')
      ->setLabel('Content editor')
      ->setGroupType('entity_test')
      ->setGroupBundle('group')
      ->save();

    $this->assertEquals('entity_test-group-content_editor', $og_role->id());

    // Confirm role can be re-saved.
    $og_role->save();

    // Confirm a role's ID cannot be changed.
    try {
      $og_role->setId($og_role->id() . 'foo');
      $this->fail('Existing OG role ID can change.');
    }
    catch (OgRoleException $e) {
    }

    // Try to create the same role again.
    try {
      $og_role = OgRole::create();
      $og_role
        ->setName('content_editor')
        ->setLabel('Content editor')
        ->setGroupType('entity_test')
        ->setGroupBundle('group')
        ->save();

      $this->fail('OG role with the same ID on the same group can be saved.');
    }
    catch (EntityStorageException $e) {
      $this->assertTrue(TRUE, "OG role with the same ID on the same group can not be saved.");
    }

    // Try to save a role with an ID instead of a name. This is how the Config
    // system will create a role from data stored in a YAML file.
    $og_role = OgRole::create([
      'id' => 'entity_test-group-configurator',
      'label' => 'Configurator',
      'group_type' => 'entity_test',
      'group_bundle' => 'group',
    ]);
    $og_role->save();

    $this->assertNotEmpty($this->roleStorage->loadUnchanged('entity_test-group-configurator'));

    // Check that we can retrieve the role name correctly. This was not
    // explicitly saved but it should be possible to derive this from the ID.
    $this->assertEquals('configurator', $og_role->getName());

    // When a role is saved with an ID that does not matches the pattern
    // 'entity type-bundle-role name' then an exception should be thrown.
    try {
      $og_role = OgRole::create();
      $og_role
        ->setId('entity_test-group-wrong_id')
        ->setName('content_editor')
        ->setLabel('Content editor')
        ->setGroupType('entity_test')
        ->setGroupBundle('group')
        ->save();

      $this->fail('OG role with a non-matching ID can be saved.');
    }
    catch (ConfigValueException $e) {
      $this->assertTrue(TRUE, "OG role with a non-matching ID can not be saved.");
    }

    // Delete the first group type. Doing this should automatically delete the
    // role that depends on the group type. The actions should still be present
    // since there still is one role left that references this role name.
    $this->groupTypes['node']->delete();

    $role = OgRole::getRole('node', 'group', 'content_editor');
    $this->assertEmpty($role);

    foreach ($action_ids as $action_id) {
      $action = $this->actionStorage->loadUnchanged($action_id);
      $this->assertEquals($action_id, $action->id());
    }

    // Delete the last role that references the content editor. Now the two
    // actions should also be deleted.
    OgRole::getRole('entity_test', 'group', 'content_editor')->delete();

    foreach ($action_ids as $action_id) {
      $action = $this->actionStorage->loadUnchanged($action_id);
      $this->assertEmpty($action);
    }
  }

}
