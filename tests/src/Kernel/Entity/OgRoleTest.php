<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
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
    $this->groupTypeManager = $this->container->get('og.group_type_manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create two test group types.
    foreach (['node_type', 'entity_test_bundle'] as $entity_type_id) {
      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $values = [
        $definition->getKey('id') => 'group',
        $definition->getKey('label') => 'Group',
      ];
      $group_type = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
      $group_type->save();
      $this->groupTypes[$entity_type_id] = $group_type;
    }
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
    $saved_role = $this->loadUnchangedOgRole('node-group-content_editor');
    $this->assertNotEmpty($saved_role, 'The role was created with the expected ID.');
    $this->assertEquals($og_role->id(), $saved_role->id());

    // Checking creation of the role.
    $this->assertEquals($og_role->getPermissions(), ['administer group']);

    // Check if the role is correctly recognized as a non-default role.
    $this->assertFalse($og_role->isRequired());

    // When a role is created the two accompanying actions to add or remove this
    // role to a membership should also be created.
    $action_ids = [
      'og_membership_add_single_role_action.content_editor',
      'og_membership_remove_single_role_action.content_editor',
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
      ->setGroupType('entity_test_with_bundle')
      ->setGroupBundle('group')
      ->save();

    $this->assertEquals('entity_test_with_bundle-group-content_editor', $og_role->id());

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
        ->setGroupType('entity_test_with_bundle')
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
      'id' => 'entity_test_with_bundle-group-configurator',
      'label' => 'Configurator',
      'group_type' => 'entity_test_with_bundle',
      'group_bundle' => 'group',
    ]);
    $og_role->save();

    $this->assertNotEmpty($this->loadUnchangedOgRole('entity_test_with_bundle-group-configurator'));

    // Check that we can retrieve the role name correctly. This was not
    // explicitly saved but it should be possible to derive this from the ID.
    $this->assertEquals('configurator', $og_role->getName());

    // When a role is saved with an ID that does not matches the pattern
    // 'entity type-bundle-role name' then an exception should be thrown.
    try {
      $og_role = OgRole::create();
      $og_role
        ->setId('entity_test_with_bundle-group-wrong_id')
        ->setName('content_editor')
        ->setLabel('Content editor')
        ->setGroupType('entity_test_with_bundle')
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
    $this->groupTypes['node_type']->delete();

    $role = OgRole::getRole('node', 'group', 'content_editor');
    $this->assertEmpty($role);

    foreach ($action_ids as $action_id) {
      $action = $this->actionStorage->loadUnchanged($action_id);
      $this->assertEquals($action_id, $action->id());
    }

    // Delete the last role that references the content editor. Now the two
    // actions should also be deleted.
    OgRole::getRole('entity_test_with_bundle', 'group', 'content_editor')->delete();

    foreach ($action_ids as $action_id) {
      $action = $this->actionStorage->loadUnchanged($action_id);
      $this->assertEmpty($action);
    }
  }

  /**
   * Tests the creation and deletion of required roles.
   */
  public function testRequiredRoles() {
    // Check that the required roles are created when a new group type is
    // declared.
    foreach (['node', 'entity_test_with_bundle'] as $entity_type_id) {
      $this->groupTypeManager->addGroup($entity_type_id, 'group');
    }

    $required_roles = [];
    foreach ([OgRole::ANONYMOUS, OgRole::AUTHENTICATED] as $role_name) {
      foreach (['node', 'entity_test_with_bundle'] as $group_type) {
        $role_id = "$group_type-group-$role_name";
        $required_role = OgRole::load($role_id);

        // Check that the role is actually a required role.
        $this->assertTrue($required_role->isRequired());

        // Check that the other data is correct.
        $this->assertEquals($group_type, $required_role->getGroupType());
        $this->assertEquals('group', $required_role->getGroupBundle());
        $this->assertEquals($role_name, $required_role->getName());

        // Keep track of the role so we can later test if they can be deleted.
        $required_roles[] = $required_role;
      }
    }

    // Required roles cannot be deleted, so an exception should be thrown when
    // trying to delete them when the group type still exists.
    foreach ($required_roles as $required_role) {
      try {
        $required_role->delete();
        $this->fail('A default role cannot be deleted.');
      }
      catch (OgRoleException $e) {
      }
    }

    // Delete the group types.
    foreach ($this->groupTypes as $group_type) {
      $group_type->delete();
    }
    // The required roles are dependent on the group types so this action should
    // result in the deletion of the roles.
    foreach ($required_roles as $required_role) {
      $this->assertEmpty($this->loadUnchangedOgRole($required_role->id()));
    }
  }

  /**
   * Loads the unchanged OgRole directly from the database.
   *
   * @param string $id
   *   The ID of the role to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The role, or NULL if there is no such role.
   */
  protected function loadUnchangedOgRole($id) {
    return $this->entityTypeManager->getStorage('og_role')->loadUnchanged($id);
  }

}
