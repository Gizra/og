DESCRIPTION
--------------------------
Enable users to create and manage their own 'groups'. Each group can have subscribers, and maintains a group page where subscribers can post into. groups may be selective or not. Selective groups require approval in order to become a member.

Support for group photo albums is available. Groups may choose their own theme. groups have RSS feeds, and so on.

INSTALLATION
---------------
- Activate the module as usual
- Install the .sql table as usual
- Visit the admin/settings/og page and click the button to enable access control.
- Set other preferences on admin/settings/og as desired.
- On the admin/node/configure/types page, disable commenting and attachments for nodes of type 'group'
- On the admin/settings page, set 'Default 403 (access denied) page' to 'og/access_denied'
- On the admin/block page, enable the 'Group details' with a low 'weight' value. Optionally enable the 'Group subscribers' and 'Group albums' block. If you use the 'group albums' block, you must have folksonomy, and image.module enabled and configured.
- If you want each group to optionally show its own image in the Group Details block, you must have image.module enabled. See http://drupal.org/project/image
- Grant permissions as needed on the admin/access page
- If you are using album.module to provide personal image albums, then you should omit the 'image' node type on your admin/settings/og page. Note that group photo albums will be group specific regardless of this setting.
- Begin creating groups, subscribing to those groups, and posting into those groups. The subscribe link appears in the Group block, for non invite-only groups.

NOTES
----------------
- 'Administer nodes' permission is required for changing the Manager of a group
- 'Administer nodes' permission enables viewing of all nodes regardless of private/public status
- All subscriber management happens on the 'user list' page which is linked from the group Block (while viewing a group page). This includes approving subscription requests (for selective groups), subscribing/unsubscribing users and promoting users into group admins.
- If you decide to stop using this module, click the 'disable' button on the admin/settings/og page. If you ever decide to re-enable, all your prior subscriptions and group settings are preserved.


THEMES
------------------
You may wish to stylize nodes which have properties assigned by this module.
--- public vs. private posts are denoted by $node->og_public
--- group assignments (if any) are to be found in $node->og_groups. this is an array of nids. the group names are in $node->og_groups_names.
--- you may wish to omit the node author and time if $node->type == 'og'. also consider not showing node links for these nodes since they slightly clutter the group home page.

TODO
----------------
- see http://drupal.org/project/issues/og
- optional 'permanent deny' subscriber requests
