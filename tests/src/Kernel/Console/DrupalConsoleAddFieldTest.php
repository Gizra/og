<?php

namespace Drupal\Tests\og\Kernel\Console;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\Command\OgAddFieldCommand;
use Drupal\og\Helper\OgDrupalConsoleHelperTrait;
use Drupal\og\OgGroupAudienceHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests create membership helper function.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class DrupalConsoleAddFieldTest extends KernelTestBase {

  use OgDrupalConsoleHelperTrait;

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installSchema('system', 'sequences');

    NodeType::create([
      'name' => $this->randomString(),
      'type' => 'article',
    ])->save();
  }

  /**
   * Tests creating membership for an un-saved group.
   */
  public function testNewGroup() {
    $command = new OgAddFieldCommand($this->getHelperSet());
    $commandTester = new CommandTester($command);
    $command->setHelperSet($this->getHelperSet());

    $commandTester->execute(
      [
        '--field_id' => 'og_audience',
        '--entity_type' => 'node',
        '--bundle' => 'article',
        '--target_entity' => 'node',
      ]
    );

    $field_names = OgGroupAudienceHelper::getAllGroupAudienceFields('node', 'article');
    $this->assertEquals(['og_audience'], array_keys($field_names));
  }

}
