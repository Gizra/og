<?php

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a node.
 */
class OgRoleDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->getEntity();

    return Url::fromRoute('og_ui.roles_overview', [
      'entity_type_id' => $entity->group_type,
      'bundle_id' => $entity->group_bundle,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

}
