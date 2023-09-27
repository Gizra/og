<?php

declare(strict_types = 1);

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
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = $this->getEntity();

    return Url::fromRoute('entity.og_role.collection', [
      'entity_type_id' => $role->getGroupType(),
      'bundle_id' => $role->getGroupBundle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

}
