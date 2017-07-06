<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgRole;
use Drupal\og\Exception\OgRoleException;

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
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of test group types.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
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
