DESCRIPTION
--------------------------
Enable users to create and manage their own 'groups'. Each group can have subscribers, and maintains a
a group page where subscribers can post into. groups may be selective or not. Selective groups
require approval in order to become a member.

INSTALLATION
---------------
- Activate the module as usual
- Visit the admin/settings/og page and 'initialize access control'. Set other preferences as desired. Submit the page. This initialization fundamentally changes your drupal site,
so don't do this just for fun
- On the administer/content/configure/defaults workflow page, disable commenting for nodes of type 'group'
- On the admin/settings page, set 'Default 403 (access denied) page' to 'og/access_denied'
- On the admin/block page, enable the 'Group details' with a low 'weight' value
- Grant permissions as needed on the admin/user/configure/permision page
- Begin creating groups, subscribing to those groups, and posting into those groups

NOTES
----------------
- 'Administer nodes' permission is required for changing the Manager of a group
- 'Administer nodes' enables viewing of all nodes regardless of private/public status
- All subscriber management happens on the 'user list' page which is linked from the group Block (while viewing a group page).
This includes approving subscription requests (for selective groups), unsubscribing users and promoting
users into group admins.


THEMES
------------------
You may wish to stylize nodes which have properties assigned by this module.
--- public vs. private posts are denoted by $node->og_public
--- group assignments (if any) are to be found in $node->og_groups. this is an array of nids. the group names are in $node->og_groups_names. 
--- you may wish to omit the node author and time if $node->type == 'og'. also consider not showing the node links
these items slightly clutter the group page.

TODO
----------------
- optional 'permanent deny' for posts in groups and subscriber requests
- email notifications for pending group subscriptions, request approved, request denied
- move 'initialize access control' to its own form with proper instructions
