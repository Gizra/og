<?php

namespace Drupal\og\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\user\UserInterface;

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
        $member = $result->_entity;
        if ($member instanceof OgMembershipInterface) {
          $form[$this->options['id']][$row_index]['#title'] = $this->t('Update the user %name', array('%name' => $member->label()));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No users selected.');
  }

}
