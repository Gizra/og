<?php

/**
 * @file
 * Contains \Drupal\og_ui\Form\DeleteMultiple.
 */

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Form;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og_ui\OgUi;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OgUiAddPeopleForm extends FormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The og_ui storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param EntityTypeManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('og_membership');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_ui_add_people';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return [
      'username' => [
        '#title' => $this->t('Username'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#required' => TRUE,
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Submit')
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#attributes' => ['class' => ['button']],
          '#url' => $this->getCancelUrl(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $group = OgUi::getEntity();

    /** @var AccountInterface $account */
    $account = \Drupal::entityTypeManager()->getStorage('user')->load($form_state->getValue('username'));

    foreach (Og::getUserMemberships($account) as $membership) {
      if ($membership->getGroup()->getEntityTypeId() == $group->getEntityTypeId() && $membership->getGroup()->id() == $group->id()) {
        $form_state->setError($form['username'], $this->t('The user is already a member of the group.'));
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    OgMembership::create([
      'type' => OgMembership::TYPE_DEFAULT,
      'uid' => $form_state->getValue('username'),
      'entity_type' => OgUi::getEntity()->getEntityTypeId(),
      'entity_id' => OgUi::getEntity()->id(),
    ])->save();

    $form_state->setRedirect($this->getCancelUrl());
  }

  /**
   * Get the cancel URL.
   *
   * @return Url
   */
  protected function getCancelUrl() {
    return Url::fromUri($this->tempStoreFactory->get('og_ui')->get('people_url'));
  }

}
