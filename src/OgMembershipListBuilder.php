<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a list of og memberships.
 */
class OgMembershipListBuilder extends EntityListBuilder {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->setCurrentUser($container->get('current_user'));

    return $instance;
  }

  /**
   * Set the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Group name');
    $header['roles'] = $this->t('Roles');
    $header['state'] = $this->t('Membership state');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    /** @var \Drupal\og\OgMembershipInterface $entity */
    $operations = parent::getOperations($entity);
    $url = Url::fromRoute('og.unsubscribe', [
      'entity_type_id' => $entity->getGroup()->getEntityTypeId(),
      'group' => $entity->getGroup()->id(),
    ]);
    $operations['unsubscribe'] = [
      'title' => $this->t('Leave group'),
      'url' => $this->ensureDestination($url),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\og\OgMembershipInterface $entity */
    $row['label'] = Link::fromTextAndUrl($entity->getGroup()->label(), $entity->getGroup()->toUrl());
    $callback = function (OgRoleInterface $role) {
      return $role->getLabel();
    };
    $row['roles'] = implode(', ', array_map($callback, $entity->getRoles()));
    $row['state'] = $entity->getState();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no memberships yet.');
    uasort($build['table']['#rows'], [$this, 'sort']);

    return $build;
  }

  /**
   * Sort results by group name.
   *
   * @param array $a
   *   Group a.
   * @param array $b
   *   Group b.
   *
   * @return int
   *   Returns the string comparison between the two labels of the groups.
   */
  protected function sort(array $a, array $b) {
    /** @var \Drupal\Core\Link $a_label */
    $a_label = (is_array($a) && isset($a['label'])) ? $a['label'] : '';
    /** @var \Drupal\Core\Link $b_label */
    $b_label = (is_array($b) && isset($b['label'])) ? $b['label'] : '';

    return strnatcasecmp($a_label->getText(), $b_label->getText());
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()
      ->getQuery()
      ->condition('uid', $this->currentUser->id());

    return $query->execute();
  }

}
