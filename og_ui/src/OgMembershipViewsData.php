<?php

namespace Drupal\og_ui;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the OG membership entity type.
 */
class OgMembershipViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['og_membership']['membership_delete_action'] = array(
      'title' => $this->t('Bulk delete'),
      'help' => $this->t('Add a form element that lets you run operations on multiple memberships.'),
      'field' => array(
        'id' => 'membership_delete_action',
      ),
    );
    
    return $data;
  }

}
