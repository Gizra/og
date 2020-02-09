<?php

declare(strict_types = 1);

namespace Drupal\og\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;

/**
 * Defines a cache context service for the OG roles of the current user.
 *
 * This cache context allows to cache render elements that vary by the role of
 * the user within the available group(s). This is useful for elements that for
 * example display information that is only intended for group members or
 * administrators.
 *
 * A user might have multiple roles in a group, this is also taken into account
 * when calculating the cache context key.
 *
 * Since the user might be a member of a large number of groups this cache
 * context key is presented as a hashed value.
 *
 * Cache context ID: 'og_role'
 */
class OgRoleCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * The string to return when no context is found.
   */
  const NO_CONTEXT = 'none';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * An array of cached cache context key hashes.
   *
   * @var string[]
   */
  protected $hashes = [];

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('OG role');
  }

  /**
   * Constructs a new UserCacheContextBase class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The membership manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\PrivateKey $privateKey
   *   The private key service.
   */
  public function __construct(AccountInterface $user, EntityTypeManagerInterface $entityTypeManager, MembershipManagerInterface $membershipManager, Connection $database, PrivateKey $privateKey) {
    parent::__construct($user);

    $this->entityTypeManager = $entityTypeManager;
    $this->membershipManager = $membershipManager;
    $this->database = $database;
    $this->privateKey = $privateKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Due to cacheability metadata bubbling this can be called often. Only
    // compute the hash once.
    if (empty($this->hashes[$this->user->id()])) {
      // If the memberships are stored in a SQL database, use a fast SELECT
      // query to retrieve the membership data. If not, fall back to loading
      // the full membership entities.
      $storage = $this->entityTypeManager->getStorage('og_membership');
      $memberships = $storage instanceof SqlContentEntityStorage ? $this->getMembershipsFromDatabase() : $this->getMembershipsFromEntities();

      // Sort the memberships, so that the same key can be generated, even if
      // the memberships were defined in a different order.
      ksort($memberships);
      foreach ($memberships as &$groups) {
        ksort($groups);
        foreach ($groups as &$role_names) {
          sort($role_names);
        }
      }

      // If the user is not a member of any groups, return a unique key.
      $this->hashes[$this->user->id()] = empty($memberships) ? self::NO_CONTEXT : $this->hash(serialize($memberships));
    }

    return $this->hashes[$this->user->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

  /**
   * Returns membership information by performing a database query.
   *
   * This method retrieves the membership data by doing a direct SELECT query on
   * the membership database. This is very fast but can only be done on SQL
   * databases since the query requires a JOIN between two tables.
   *
   * @return array[][]
   *   An array containing membership information for the current user. The data
   *   is in the format [$entity_type_id][$entity_id][$role_name].
   */
  protected function getMembershipsFromDatabase(): array {
    $storage = $this->entityTypeManager->getStorage('og_membership');
    if (!$storage instanceof SqlContentEntityStorage) {
      throw new \LogicException('Can only retrieve memberships directly from SQL databases.');
    }

    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping();
    $base_table = $table_mapping->getBaseTable();
    $role_table = $table_mapping->getFieldTableName('roles');
    $query = $this->database->select($base_table, 'm');
    $query->leftJoin($role_table, 'r', 'm.id = r.entity_id');
    $query->fields('m', ['entity_type', 'entity_bundle', 'entity_id']);
    $query->fields('r', ['roles_target_id']);
    $query->condition('m.uid', $this->user->id());
    $query->condition('m.state', OgMembershipInterface::STATE_ACTIVE);

    $memberships = [];
    foreach ($query->execute() as $row) {
      $entity_type_id = $row->entity_type;
      $entity_bundle_id = $row->entity_bundle;
      $entity_id = $row->entity_id;
      $role_name = $row->roles_target_id;

      // If the role name is empty this is a regular authenticated user. If it
      // is set we can derive the role name from the role ID.
      if (empty($role_name)) {
        $role_name = OgRoleInterface::AUTHENTICATED;
      }
      else {
        $pattern = preg_quote("$entity_type_id-$entity_bundle_id-");
        preg_match("/$pattern(.+)/", $row->roles_target_id, $matches);
        $role_name = $matches[1];
      }

      $memberships[$entity_type_id][$entity_id][] = $role_name;
    }

    return $memberships;
  }

  /**
   * Returns membership information by iterating over membership entities.
   *
   * This method uses pure Entity API methods to retrieve the data. This is slow
   * but also works with NoSQL databases.
   *
   * @return array[][]
   *   An array containing membership information for the current user. The data
   *   is in the format [$entity_type_id][$entity_id][$role_name].
   */
  protected function getMembershipsFromEntities(): array {
    $memberships = [];
    foreach ($this->membershipManager->getMemberships($this->user->id()) as $membership) {
      // Derive the role names from the role IDs. This is faster than loading
      // the OgRole object from the membership.
      $role_names = array_map(function (string $role_id) use ($membership): string {
        $pattern = preg_quote("{$membership->getGroupEntityType()}-{$membership->getGroupBundle()}-");
        preg_match("/$pattern(.+)/", $role_id, $matches);
        return $matches[1];
      }, $membership->getRolesIds());
      if ($role_names) {
        $memberships[$membership->getGroupEntityType()][$membership->getGroupId()] = $role_names;
      }
    }
    return $memberships;
  }

}
