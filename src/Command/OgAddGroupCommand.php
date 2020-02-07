<?php

namespace Drupal\og\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\og\GroupTypeManager;

/**
 * Class OgAddGroupCommand.
 *
 * @package Drupal\og
 */
class OgAddGroupCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * Bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * Group manager service.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The type repository service.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The OG plugin manager.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info, EntityTypeRepositoryInterface $entity_type_repository, GroupTypeManager $group_type_manager) {
    parent::__construct();

    $this->bundleInfo = $bundle_info;
    $this->entityTypeRepository = $entity_type_repository;
    $this->groupTypeManager = $group_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('og:add_group')
      ->setDescription('Define entity as a group.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $io->info('The group was added successfully.');
  }

}
