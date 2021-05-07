<?php

declare(strict_types = 1);

declare(strict_types = 1);
namespace Drupal\og\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;

/**
 * Provides an 'OG Membership' block visibility condition.
 *
 * @Condition(
 *   id = "og_group_membership",
 *   label = @Translation("Group Membership"),
 *   context_definitions = {
 *     "og" = @ContextDefinition("entity", label = @Translation("Group")),
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User")),
 *   }
 * )
 */
class GroupMembership extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['og_membership'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User has OG membership?'),
      '#description' => $this->t('The current user has a membership to the entity from OG context.'),
      '#default_value' => $this->configuration['og_membership'],
    ];

    $roles = [];
    foreach (OgRole::loadMultiple() as $role) {
      /** @var \Drupal\og\Entity\OgRole $role */
      $roles[$role->id()] = $role->getLabel();
    }

    $form['og_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('User has OG roles within membership'),
      '#options' => $roles,
      '#size' => 3,
      '#multiple' => TRUE,
      '#description' => $this->t('Optionally require that the user has the selected role in the membership.'),
      '#default_value' => $this->configuration['og_roles'],
      '#states' => [
        'visible' => [
          ':input[name="visibility[og_group_membership][og_membership]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['og_roles'] = array_filter($form_state->getValue('og_roles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Checks if the current user has OG membership');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Grab the required values from context
    // and attempt to load a membership.
    $group = $this->getContextValue('og');
    $user = $this->getContextValue('user');
    $membership = Og::getMembership($group, $user);

    // There is no membership.
    if (empty($membership)) {
      return FALSE;
    }

    // The membership status is not active.
    if ($membership->getState() !== OgMembership::STATE_ACTIVE) {
      return FALSE;
    }

    // There are no role requirements of the membership.
    if (empty($this->configuration['og_roles'])) {
      return TRUE;
    }

    // Validate that the membership contains one of
    // the roles defined by the condition.
    if (!empty($this->configuration['og_roles'])) {
      foreach ($this->configuration['og_roles'] as $role_id => $role) {
        if ($membership->hasRole($role_id)) {
          return TRUE;
        }
      }
    }

    // None of the conditions passed.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'og_membership' => FALSE,
      'og_roles' => [],
    ] + parent::defaultConfiguration();
  }

}
