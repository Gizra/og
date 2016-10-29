<?php

namespace Drupal\og\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class OgAddGroupCommand.
 *
 * @package Drupal\og
 */
class OgAddGroupCommand extends Command {

  use ContainerAwareCommandTrait;

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

    $io->info('You added the group. A good job indeed.');
  }
}
