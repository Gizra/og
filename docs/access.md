Access control for groups and group content
===========================================

Controlling access to groups and group content is one of the most important
aspects of building a group based project. Having separate access rules for
different groups and the content in them is more complex than the standard role
and permission based access system built into Drupal core so Organic Groups (OG)
has extended the core functionality to make it more flexible and, indeed,
_organic_ for developers to design their access control systems.

Group level permissions
-----------------------

The first line of defense is group level access control. Group level permissions
apply to the group as a whole. OG ships with a number of permissions that
control basic group and membership management tasks such as:
- Administration permissions such as `update group`, `delete group`, `manage
  members` and `approve and deny subscription`.
- Membership permissions such as `subscribe` and `subscribe without approval`
  which can be granted to non-members.

Developers can define their own group level permissions by implementing an event
listener that subscribes to the `PermissionEventInterface::EVENT_NAME` event and
instantiating `GroupPermission` objects with the properties of the permission.

As an example let's define a permission that would allow an administrator to
make a group private or public - this could be used in a project that has
private groups that would only accept new members that are invited by existing
members:

```php
<?php

namespace Drupal\my_module\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OgEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideGroupLevelPermissions']],
    ];
  }

  public function provideGroupLevelPermissions(PermissionEventInterface $event): void {
    $event->setPermission(
      new GroupPermission([
        // The unique machine name of the permission, similar to a Drupal core
        // permission.
        'name' => 'set group privacy',
        // The human readable permission title, to show to site builders in the UI.
        'title' => $this->t('Set group privacy'),
        // An optional description providing extra information.
        'description' => $this->t('Users can only join a private group when invited by an existing member.'),
        // The roles to which this permission applies by default when a new
        // group is created.
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        // Whether or not this permission has security implications and should
        // be restricted to trusted users only.
        'restrict access' => TRUE,
      ])
    );
  }

}
```

The list of group level permissions that are provided out of the box by OG can
be found in
`\Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultOgPermissions()`.


Group content entity operation permissions
------------------------------------------

In addition to group level permissions we also need to control access to CRUD
operations on group content entities. Depending on the group requirements the
creation, updating and deletion of group content is restricted to certain roles
within the group.

Drupal core defines the entity CRUD operations as simple strings (e.g. for an
'article' node type we would have the permission `delete own article content`).

OG not only supports nodes but all kinds of entities, and the simple string
based permissions from Drupal core have proved to be difficult to use for
operations that need to be applicable across multiple entity types. Some typical
use cases are that a group administrator should be able to edit and delete all
group content, regardless of entity type. Also a normal member of a group might
have the permission to edit their own content, but not the content of other
members.

Drupal core's permission system doesn't lend itself well to these use cases
since we cannot reliably derive the entity type and operation from these simple
string based permissions. For example the permission `delete own article
content` gives a user the permission to delete nodes of type 'article' which
were created by themselves. This permission string contains the words 'delete'
and 'own' so it would be possible to come up with an algorithm that infers the
operation and the scope, but the bundle and entity type can not be derived
easily. In fact the entity type 'node' doesn't even appear in the string!

OG solves this by defining group content entity operation (CRUD) permissions
using a structured object: `GroupContentOperationPermission`. The above
permission would be defined as follows, which encodes all the data we need to
determine the entity type, bundle, operation, ownership (whether or not a user
is performing the operation on their own entity), etc:

```php
$permission = new \Drupal\og\GroupContentOperationPermission([
  'entity_type' => 'node',
  'bundle' => 'article',
  'name' => "delete own article content",
  'title' => $this->t('%type_name: Delete own content', ['type_name' => 'article']),
  'operation' => 'delete',
  'owner' => TRUE,
]);
```

These permissions are defined in the same way as the group level permissions, in
an event listener for the `og.permissions` event. Here are some examples how
OG sets these permissions:

- `OgEventSubscriber::getDefaultEntityOperationPermissions()`: creates a generic
  set of permissions for every group content type. These can be overridden by
  custom modules as shown below.
- `OgEventSubscriber::provideDefaultNodePermissions()`: an example of how the
  generic permissions can be overridden. This ensures that we can use the same
  permission names for the Node entity type in our group content as the ones
  used by core.


Granting permissions to users
-----------------------------

Similar to Drupal core, permissions are not directly assigned to users in
OG, but they are assigned to roles, and a user can have one or more roles in a
group.

The role data is stored in a config entity type named `OgRole`, and whenever a
new group type is created, the following roles will automatically be created:

- `member` and `non-member`: these two roles are required for every group, they
  indicate whether or not a user is a member.
- `administrator`: this role is not strictly required but is created by default
  by OG because it is considered to be generally useful for most groups.
- Developers can choose to provide additional default roles by listening to the
  `og.default_role` event.

Whenever a default role is created, it will automatically inherit the
permissions that are assigned to the role in their permission declaration (see
the `default roles` property above which takes an array of roles to which these
permissions should be applied).

To manually assign a permission to a role for a certain group, it can be done as
follows:

```php
// OgRole::loadByGroupAndName() is the easiest way to load a specific role for
// a given group.
$admin_role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ADMINISTRATOR);
$admin_role->grantPermission('my permission')->save();
````

Similarly, an existing permission can be removed from a role by calling
`$admin_role->revokePermission('my permission')` and saving the `OgRole` entity.

For more information on how `OgRole` objects are handled, check the following
methods:
- `\Drupal\og\GroupTypeManager::addGroup()`: this code is responsible for
  creating a new group type and will create the roles as part of it.
- `\Drupal\og\OgRoleManager::getDefaultRoles()`: creates the default roles and
  fires the event listener that modules can hook into the provide additional
  default roles.
- `\Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultRoles()`: OG's
  own implementation of the event listener, which provides the default
  `administrator` role.


Checking if a user has a certain permission in a group
------------------------------------------------------

It would be natural to think that checking a permission would be a simple matter
of loading the `OgRole` entity and calling `$role->hasPermission()` on it, but
this is not sufficient. There are a number of additional things to consider:

- The super user (user ID 1) has full permissions.
- OG has a configuration option to allow full access to group owners.
- Users that have the global permission `administer organic groups` have all
  permissions in all groups.
- The role can have the `is_admin` flag set which will grant all permissions.
- Modules can alter permissions depending on their own requirements.

OG provides a service that will perform these checks. To determine whether the
currently logged in user has for example the `manage members` permission on a
given group entity:

```php
// Load some custom group.
$group = \Drupal::entityTypeManager()->getStorage('my_group_type')->load($some_id);

/** @var \Drupal\og\OgAccessInterface $og_access */
$og_access = \Drupal::service('og.access');
$access_result = $og_access->userAccess($group, 'manage members');

// An AccessResult object is returned including full cacheability metadata. In
// order to get the access as a simple boolean value, call `::isAllowed()`.
if ($access_result->isAllowed()) {
  // The user has permission.
}
```

For projects that have a large number of group types and group content types
there is also a convenient method `::userAccessEntity()` to discover if a user
has a group level permission on any given entity, being a group, group content,
or even something that is not related to any group:

```php
// Load some entity, which might be a group, group content, or something not
// related to any group.
$entity = \Drupal::entityTypeManager()->getStorage('my_entity_type')->load($some_id);

/** @var \Drupal\og\OgAccessInterface $og_access */
$og_access = \Drupal::service('og.access');
$access_result = $og_access->userAccessEntity('manage members', $entity);

// An AccessResult object is returned including full cacheability metadata. In
// order to get the access as a simple boolean value, call `::isAllowed()`.
if ($access_result->isAllowed()) {
  // The user has permission.
}
```

**Caution**: The above example will do a discovery to find out if the passed in
entity is a group or group content, and will loop over all associated groups to
determine access. While this is very convenient this also comes with a
performance impact, so it is recommended to use it only in cases where the
faster `::userAccess()` is not applicable.


Checking if a user can perform an entity operation on group content
-------------------------------------------------------------------

OG extends Drupal core's entity access control system so checking access on an
entity operation is as simple as this:

```php
// Check if the given user can edit the entity, which is a group content entity.
$access_result = $group_content_entity->access('update', $user);
```

Behind the scenes, OG implements `hook_access()` and delegates the access check
to the `OgAccess` service, so within the context of group content this is
equivalent to calling the following:

```php
/** @var \Drupal\og\OgAccessInterface $og_access */
$og_access = \Drupal::service('og.access');
$access_result = $og_access->userAccessEntityOperation('update', $group_content_entity, $user);
```

There is also a faster way to get the same result, in case you know beforehand
to which group the group content entity belongs. The following example is more
efficient since it doesn't need to do an expensive discovery of the groups to
which the entity belongs:

```php
// In case we know the group entity we can use the faster method:
$group_entity = \Drupal::entityTypeManager()->getStorage('my_group_type')->load($some_id);

/** @var \Drupal\og\OgAccessInterface $og_access */
$og_access = \Drupal::service('og.access');
$access_result = $og_access->userAccessGroupContentEntityOperation('update', $group_entity, $group_content_entity, $user);
```


Altering permissions
--------------------

There are many use cases where permissions should be altered under some
circumstances to fulfill business requirements. OG offers ways for modules to
hook into the permission system and alter the access result.


### Alter group permissions

Modules can implement `hook_og_user_access_alter()` to alter group level
permissions. Here is an example that implements a use case where groups can only
be deleted if they are unpublished. This functionality can be toggled off by
site administrators in the site configuration, so the example also demonstrates
how to alter the cacheability metadata to include the config setting. The access
result is different if this option is turned on or off, so this needs to be
included in the cache metadata.

```php
function mymodule_og_user_access_alter(array &$permissions, CacheableMetadata $cacheable_metadata, array $context): void {
  // Retrieve the module configuration.
  $config = \Drupal::config('mymodule.settings');

  // Check if the site is configured to allow deletion of published groups.
  $published_groups_can_be_deleted = $config->get('delete_published_groups');

  // If deletion is not allowed and the group is published, revoke the
  // permission.
  $group = $context['group'];
  if ($group instanceof EntityPublishedInterface && !$group->isPublished() && !$published_groups_can_be_deleted) {
    $key = array_search(OgAccess::DELETE_GROUP_PERMISSION, $permissions);
    if ($key !== FALSE) {
      unset($permissions[$key]);
    }
  }

  // Since our access result depends on our custom module configuration, we need
  // to add it to the cache metadata.
  $cacheable_metadata->addCacheableDependency($config);
}
```


### Alter group content permissions

In addition to altering group level permissions, OG also allows to alter access
to group content entity operations, this time using an event listener.

```php
<?php

namespace Drupal\my_module\EventSubscriber;

use Drupal\og\Event\GroupContentEntityOperationAccessEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyModuleEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      GroupContentEntityOperationAccessEventInterface::EVENT_NAME => [['moderatorsCanManageComments']],
    ];
  }

  public function moderatorsCanManageComments(GroupContentEntityOperationAccessEventInterface $event): void {
    $is_comment = $event->getGroupContent()->getEntityTypeId() === 'comment';
    $user_can_moderate_comments = $event->getUser()->hasPermission('edit and delete comments in all groups');

    if ($is_comment && $user_can_moderate_comments) {
      $event->grantAccess();
    }
  }

}
```

Please note that this follows the same principles of the Drupal core entity
access handlers. Access will be granted only if at least one of the subscribers
or other properties grants access (like having the `administer organic groups`
permission). If any event listener __denies__ access, then this will be
considered as a hard deny, and cannot be overruled. This might have some
unexpected consequences; for example if group content is published in multiple
groups, and a user has access to a permission in all groups, except one in which
access is forbidden, then `OgAccess::userAccessEntityOperation()` will return
access denied.

In most cases this can be solved by only granting access to a permission when
required, and remaining neutral if not. Alternatively check access to
individual groups using `OgAccess::userAccessGroupContentEntityOperation()` -
this will only return access denied for the specific group where access has been
forbidden, while still allowing access for all others.
