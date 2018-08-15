<?php

/**
 * @file
 * Contains \OgSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\og\OgDeleteOrphansInterface;

/**
 * Behat step definitions and hook implementations for Organic Groups.
 */
class OgSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Cleans up orphaned group content after each scenario.
   *
   * By default orphaned group content is cleaned up in a shutdown function that
   * fires at the end of the Drupal page request. When Drupal is being
   * bootstrapped to serve as a backend for a Behat test suite then this "page
   * request" will only end after all scenarios are completed and the Behat test
   * runner is deconstructed, which might be after several hours. If a large
   * number of orphans have been accumulated in the meantime the cleaning up of
   * all these orphans might cause database lock timeouts.
   *
   * By cleaning up the orphans after each scenario we can reduce the risk of
   * timeouts.
   *
   * @see \Drupal\og\Plugin\OgDeleteOrphans\Simple::register()
   *
   * @AfterScenario
   */
  public function cleanOrphans() {
    $callbacks = &drupal_register_shutdown_function();
    foreach ($callbacks as $key => $callback) {
      $callable = $callback['callback'];
      if (is_array($callable) && $callable[0] instanceof OgDeleteOrphansInterface && $callable[1] === 'process') {
        call_user_func_array($callback['callback'], $callback['arguments']);
        unset($callbacks[$key]);
      }
    }
  }

}
