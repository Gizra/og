<?php

namespace Drupal\og\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\views\Plugin\views\field\BulkForm;

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
    parent::viewsForm($form, $form_state);

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row_index => $result) {
        $membership = $result->_entity;
        if ($membership instanceof OgMembershipInterface) {
          $form[$this->options['id']][$row_index]['#title'] = $this->t('Update the member @name', ['@name' => $membership->getOwner()->getAccountName()]);
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
