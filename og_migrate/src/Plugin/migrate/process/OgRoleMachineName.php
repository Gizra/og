<?php

namespace Drupal\og_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Ensures that the migrated role names do not contain spaces.
 *
 * It was possible to allow role names with spaces similar to Drupal core role
 * names. These need to be transformed to match the expectations of this module
 * in Drupal 8.
 *
 * @MigrateProcessPlugin(
 *   id = "og_role_machine_name"
 * )
 */
class OgRoleMachineName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $name = str_replace(' ', '-', $value);
    return str_replace('administrator-member', 'administrator', $name);
  }

}
