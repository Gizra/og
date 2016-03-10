<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base implementation for OgDeleteOrphans plugins.
 */
abstract class OgDeleteOrphansBase implements OgDeleteOrphansInterface {

  /**
   * {@inheritdoc}
   */
  public function register(EntityInterface $entity) {
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    throw new \Exception(__METHOD__ . ' is not implemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormValidate($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit($form, FormStateInterface $form_state) {
  }

}
