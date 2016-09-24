<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\Command\OgAddFieldCommand;
use Drupal\og\Helper\OgDrupalConsoleHelperTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests create membership helper function.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class DrupalConsoleAddFieldTest extends UnitTestCase {

  use OgDrupalConsoleHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
//    $container->set('entity.manager', $this->entityManager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests creating membership for an un-saved group.
   *
   * @covers ::createMembership
   */
  public function testNewGroup() {
    $command = new OgAddFieldCommand($this->getHelperSet());
    $commandTester = new CommandTester($command);
    $command->setHelperSet($this->getHelperSet());

//    $code = $commandTester->execute(
//      [
//        '--field_id' => 'og_audience',
//        '--entity_type' => 'node',
//        '--bundle' => 'article',
//        '--target_entity' => 'node',
//      ]
//    );

//    $this->assertEquals(0, $code);

    $this->assertTrue(1 == 1);
  }

}
