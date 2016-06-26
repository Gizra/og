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
use Drupal\Core\Url;
use Drupal\og\Entity\OgMembership;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a og_ui deletion confirmation form.
 */
class DeleteMultiple extends FormBase {

  /**
   * The array of og_uis to delete.
   *
   * @var array
   */
  protected $og_uis = [];

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
    return 'og_ui_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \Drupal::translation()->formatPlural(count($this->og_uis), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Not working. Port badly from Message.
    $this->og_uis = $this->tempStoreFactory->get('og_membership_multiple_delete_confirm')->get(\Drupal::currentUser()->id());

    if (empty($this->og_uis)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['og_uis'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function (OgMembership $membership) {
        $params = [
          '@id' => $membership->id(),
          '@name' => $membership->getUser()->label(),
        ];
        return t('Delete membership ID @id for @name', $params);
      }, $this->og_uis),
    ];
    $form = parent::buildForm($form, $form_state);

    $form['actions']['cancel']['#url'] = $this->getCancelUrl();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->og_uis)) {
      $this->storage->delete($this->og_uis);
      $this->tempStoreFactory->get('og_ui_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
      $count = count($this->og_uis);
      $this->logger('og_ui')->notice('Deleted @count posts.', ['@count' => $count]);
      drupal_set_message(\Drupal::translation()->formatPlural($count, 'Deleted 1 membership.', 'Deleted @count memberships.'));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function _getCancelUrl() {
    return Url::fromUri($this->tempStoreFactory->get('og_ui')->get('people_url'));
  }

}
