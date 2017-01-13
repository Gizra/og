<?php

namespace Drupal\og\Command;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\og\Og;
use Drupal\og\OgFieldsPluginManager;
use Drupal\og\Plugin\OgFields\AudienceField;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class OgAddFieldCommand.
 *
 * @package Drupal\og
 */
class OgAddFieldCommand extends Command {

  /**
   * OG plugin manager.
   *
   * @var \Drupal\og\OgFieldsPluginManager
   */
  protected $ogFieldsPluginManager;

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
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The type repository service.
   * @param \Drupal\og\OgFieldsPluginManager $og_field_plugin_manager
   *   The OG plugin manager.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info, EntityTypeRepositoryInterface $entity_type_repository, OgFieldsPluginManager $og_field_plugin_manager) {
    parent::__construct();

    $this->bundleInfo = $bundle_info;
    $this->entityTypeRepository = $entity_type_repository;
    $this->ogFieldsPluginManager = $og_field_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('og:add_field')
      ->setDescription('Attach OG field to entities')
      ->addOption('field_id', '', InputArgument::OPTIONAL, 'Field ID')
      ->addOption('field_name', '', InputArgument::OPTIONAL, 'Field name')
      ->addOption('entity_type', '', InputArgument::OPTIONAL, 'The entity type ID (for example node, user, taxonomy_term)')
      ->addOption('bundle', '', InputArgument::OPTIONAL, 'The bundle name')
      ->addOption('target_entity', '', InputArgument::OPTIONAL, 'The entity type ID (for example node, user, taxonomy_term)');
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    if (!$field_id = $input->getOption('field_id')) {
      $field_id = $io->choiceNoList(
        $this->getDefinition()->getOption('field_id')->getDescription(),
        $this->getOgFields()
      );
      $input->setOption('field_id', $field_id);
    }

    if (!$field_name = $input->getOption('field_name')) {
      $field_name = $io->ask($this->getDefinition()->getOption('field_name')->getDescription());
      $input->setOption('field_name', $field_name);
    }

    if (!$entity_type = $input->getOption('entity_type')) {
      $entity_type = $io->choiceNoList(
        $this->getDefinition()->getOption('entity_type')->getDescription(),
        $this->getEntityTypes()
      );
      $input->setOption('entity_type', $entity_type);
    }

    if (!$input->getOption('bundle')) {
      $bundles = $this->bundleInfo->getBundleInfo($entity_type);
      $input->setOption('bundle', $io->choiceNoList(
        $this->getDefinition()->getOption('bundle')->getDescription(),
        array_keys($bundles)
      ));
    }

    if ($this->fieldIsAudienceField($field_id) && !$input->getOption('target_entity')) {
      $input->setOption('target_entity', $io->choiceNoList(
        $this->getDefinition()->getOption('target_entity')->getDescription(),
        $this->getEntityTypes()
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $settings = [
      'field_name' => $input->getOption('field_name'),
    ];

    if ($this->fieldIsAudienceField($input->getOption('field_id'))) {
      $settings['field_storage_config']['settings'] = [
        'target_type' => $input->getOption('target_entity'),
      ];
    }

    Og::createField($input->getOption('field_id'), $input->getOption('entity_type'), $input->getOption('bundle'), $settings);
    $io->info('The field attached successfully.');
  }

  /**
   * Get OG fields.
   *
   * @return array
   *   List of OG fields ID.
   */
  protected function getOgFields() {
    return array_map(function ($item) {
      return $item['id'];
    },
    $this->ogFieldsPluginManager->getDefinitions());
  }

  /**
   * Get all the entity types.
   *
   * @return array
   *   List of entity IDs.
   */
  protected function getEntityTypes() {
    $groups = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
    return array_map(function ($item) {
      return $item->render();
    }, $groups['Content']);
  }

  /**
   * Check if the field is an audience field or not.
   *
   * @param string $field_id
   *   The field ID.
   *
   * @return bool
   *   True or false if the field is an audience or not.
   */
  protected function fieldIsAudienceField($field_id) {
    return $this->ogFieldsPluginManager->createInstance($field_id) instanceof AudienceField;
  }

}
