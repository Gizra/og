<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\og\Command\OgAddFieldCommand;
use Drupal\og\Helper\OgDrupalConsoleHelperTrait;
use Drupal\og\OgFieldBase;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityManager;
use Drupal\og\OgFieldsPluginManager;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Tests create membership helper function.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class DrupalConsoleAddFieldTest extends UnitTestCase {

  use OgDrupalConsoleHelperTrait;

  /**
   * The mocked OG field manager plugin.
   *
   * @var \Drupal\og\OgFieldsPluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $OgFieldManager;

  /**
   * The mocked OG field manager plugin.
   *
   * @var \Drupal\Core\Entity\EntityManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityManager;

  /**
   * The mocked OG field manager plugin.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The mocked OG field manager plugin.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entity;

  /**
   * The mocked OG field manager plugin.
   *
   * @var \Drupal\og\OgFieldBase|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $OgFieldBase;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->OgFieldManager = $this->prophesize(OgFieldsPluginManager::class);
    $this->OgFieldBase = $this->prophesize(OgFieldBase::class);
    $this->entityManager = $this->prophesize(EntityManager::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManager::class);
    $this->entity = $this->prophesize(EntityInterface::class);

    $this->OgFieldBase->setFieldName('og_audience')->willReturn($this->OgFieldBase);
    $this->OgFieldBase->setBundle('article')->willReturn($this->OgFieldBase);
    $this->OgFieldBase->setEntityType('node')->willReturn($this->OgFieldBase);

    $this->entityManager->getStorage('field_storage_config')->willReturn($this->entity->reveal());

    $this->OgFieldManager
      ->getDefinition('og_audience')
      ->willReturn(['foo' => 'bar']);

    $this->OgFieldManager
      ->createInstance('og_audience')
      ->willReturn($this->OgFieldBase->reveal());

    $container = new ContainerBuilder();
    $container->set('plugin.manager.og.fields', $this->OgFieldManager->reveal());
    $container->set('entity.manager', $this->entityManager->reveal());
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
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

    $code = $commandTester->execute(
      [
        '--field_id' => 'og_audience',
        '--entity_type' => 'node',
        '--bundle' => 'article',
        '--target_entity' => 'node',
      ]
    );

    $this->assertEquals(0, $code);

    $this->assertTrue(1 == 1);
  }

}
