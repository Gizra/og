Access control for groups and group content
===========================================

Controlling access to groups and group content is one of the most important
aspects of building a group based project. Group based access is more complex
than the standard role and permission based access system built into Drupal core
so Organic Groups has extended the core functionality to make it more flexible
and, indeed, _organic_ for developers to design their access control systems.

Group level permissions
-----------------------

The first line of defense is the group level access control provided by OG.
Group level permissions apply to the group as a whole, and OG defines a number
of permissions that control basic group and membership management tasks such as:
- Administration permissions such as `update group`, `delete group`, `manage
  members` and `approve and deny subscription`.
- Membership permissions such as `subscribe` and `subscribe without approval`
  which can be granted to non-members.

Developers can define their own group level permissions by implementing an event
listener that subscribes to the `og.permission` event and instantiating
`GroupPermission` objects with the properties of the permission.
 
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
        'description' => $this->t('Users can only join a private group when invited.'),
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
  
The list of group level permissions that are provided out of the box by Organic
Groups can be found in
`\Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultOgPermissions()`.


Group content entity operation permissions
------------------------------------------

In addition to group level permissions we also need to control access to CRUD
operations on group content entities. Depending on the group requirements the
creation, updating and deletion of group content is restricted to certain roles
within the group.

In Drupal core the entity CRUD operations are defined as simple strings (e.g.
an for an 'article' node type we would have the permission `delete own article
content`). 

OG not only supports nodes but all kinds of entities, and the simple string
based permissions from Drupal core have proved to be difficult to use for
operations that need to be applicable across multiple entity types. Some typical
use cases are that a group administrator should be able to edit and delete all
group content, regardless of entity type. Also a normal member of a group might
have the permission to edit their own content, but not the content of other
members.

The permission system from Drupal core does not lend itself well to these use
cases since they are simple unstructured strings and we cannot reliably derive
the entity type and operation from them. For example the permission `delete own
article content` gives a user the permission to delete nodes of type 'article'
which were created by themselves. This permission contains the words 'delete'
and 'own' so it would be possible to come up with an algorithm that infers the
operation and the scope, but the bundle and entity type can not be derived
easily. In fact the entity type 'node' doesn't even appear in the string!

In OG this is solved by defining group content entity operation (CRUD)
permissions using a structured object: `GroupContentOperationPermission`. The
above permission would be defined as follows, which encodes all the data we need
to determine the entity type, bundle, operation, ownership (whether or not a
user is performing the operation on their own entity), etc:

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
an event listener for the `og.permissions` event. Here are some examples how OG
sets these permissions:

- `OgEventSubscriber::getDefaultEntityOperationPermissions()`: creates a generic
  set of permissions for every group content type. These can be overridden by
  custom modules if needed.
- `OgEventSubscriber::provideDefaultNodePermissions()`: an example of how the
  generic permissions can be overridden. This ensures that the same permission
  names are used for the Node entity type as used by core.


Granting permissions to users
-----------------------------

Similar to Drupal core, permissions are not directly assigned to users in OG,
but they are assigned to roles, and a user can have one or more roles in a
group.

The role data is stored in a config entity type named `OgRole`, and whenever a
new group type is created, a number of roles will automatically be created:

- `member` and `non-member`: these two roles are required for every group, they
  indicate whether or not a user is a member.
- `administrator`: this role is not strictly required but is created by default
  by OG because it is considered to be generally useful for most groups.
- Developers can choose to provide additional default roles by listening to the
  `og.default_role` event.

Whenever a default role is created, it will automatically inherit the
permissions that are assigned to the role in their permission declaration (see
the `default role` property above which takes an array of roles to which these
permissions should be applied).

To manually assign a permission to a role for a certain group, it can be done as
follows:

```php
$admin_role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ADMINISTRATOR);
$admin_role->grantPermission('my permission')->save();
````

Similarly, an existing permission can be removed from a role by calling
`$admin_role->revokePermission('my permission')` and saving the role entity.

For more information on how `OgRole` objects are handled, check the following
methods:
- `\Drupal\og\GroupTypeManager::addGroup()`: this code is responsible for
  creating a new group type and will create the roles as part of it.
- `\Drupal\og\OgRoleManager::getDefaultRoles()`: creates the default roles and
  fires the event listener that modules can hook into the provide additional
  default roles.
- `\Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultRoles()`: OG's
  own implementation of the event listener, which provides the `administrator`
  role.

