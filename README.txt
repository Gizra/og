DESCRIPTION
--------------------------
Enable users to create and manage their own 'groups'. Each group can have subscribers, and maintains a a group page where subscribers can post into. groups may be selective or not. Selective groups require approval in order to become a member.

Support for group photo albums is available. Groups may choose their own theme. groups have RSS feeds, and so on.

INSTALLATION
---------------
- Activate the module as usual
- Install the .sql table as usual
- Visit the admin/settings/og page and 'initialize access control'. Set other preferences as desired. Submit the page. This initialization fundamentally changes your drupal site, so don't do this just for fun.
- On the administer/content/configure/defaults workflow page, disable commenting for nodes of type 'group'
- On the admin/settings page, set 'Default 403 (access denied) page' to 'og/access_denied'
- On the admin/block page, enable the 'Group details' with a low 'weight' value. Optionally enable the 'Group subscribers' and 'Group albums' block. If you use the 'group albums' block, you must have folksonomy, and the new image.module enabled and configured. See for http://cvs.drupal.org/viewcvs/contrib/contributions/sandbox/walkah/image/ for image.module
- Grant permissions as needed on the admin/user/configure/permision page
- If you are using album.module to provide personal image albums, then you must omit the 'image' node type on your settings page. Note that group photo albums will be group specific regardless of this setting.
- Begin creating groups, subscribing to those groups, and posting into those groups. Subscribe from the Group Block.

NOTES
----------------
- 'Administer nodes' permission is required for changing the Manager of a group
- 'Administer nodes' permission enables viewing of all nodes regardless of private/public status
- All subscriber management happens on the 'user list' page which is linked from the group Block (while viewing a group page). This includes approving subscription requests (for selective groups), unsubscribing users and promoting users into group admins.
- If you decide to stop using this module, you might want to remove all records from the node_access table which reference the og_group and og_uid realms. You must insert the inital record which grants universal read access to all nodes. This lives in the node_access table as well.


THEMES
------------------
You may wish to stylize nodes which have properties assigned by this module.
--- public vs. private posts are denoted by $node->og_public
--- group assignments (if any) are to be found in $node->og_groups. this is an array of nids. the group names are in $node->og_groups_names. 
--- you may wish to omit the node author and time if $node->type == 'og'. also consider not showing node links for these nodes since they slightly clutter the group home page.

TODO
----------------
- optional 'permanent deny' for posts in groups and subscriber requests
- move 'initialize access control' to its own form with proper instructions
