<?php

namespace Drupal\og;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the OG Membership entity type.
 */
class OgMembershipViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['og_membership']['og_membership_bulk_form'] = [
      'title' => $this->t('Bulk update'),
      'help' => $this->t('Add a form element that lets you run operations on multiple members.'),
      'field' => [
        'id' => 'og_membership_bulk_form',
      ],
    ];

    return $data;
  }

}
