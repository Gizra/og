<?php

namespace Drupal\og\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a user operations bulk form element.
 *
 * @ViewsField("og_membership_bulk_form")
 */
class OgMembershipBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   *
   * Provide a more useful title to improve the accessibility.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Remove all actions related to (non-)member role.
    foreach ($this->actions as $key => $action) {
      if (strstr($key, '-member') !== FALSE) {
        unset($this->actions[$key]);
      }
    }
    parent::viewsForm($form, $form_state);

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row_index => $result) {
        $member = $result->_entity;
        if ($member instanceof OgMembershipInterface) {
          $form[$this->options['id']][$row_index]['#title'] = $this->t('Update the member @name', array('@name' => $member->getUser()->getAccountName()));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No members selected.');
  }

}