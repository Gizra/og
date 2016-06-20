<?php

namespace Drupal\og_ui;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\PluginBase;

abstract class OgUiAdminRouteAbstract extends PluginBase implements OgUiAdminRouteInterface {

  /**
   * @var ContentEntityBase
   */
  protected $group;

  /**
   * @inheritDoc
   */
  public function getGroup() {
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup(ContentEntityBase $group) {
    $this->group = $group;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPath() {
    return $this->pluginDefinition['path'];
  }

  /**
   * @inheritDoc
   */
  public function routes() {
    return [];
  }

}
