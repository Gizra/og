DESCRIPTION
--------------------------
Enable users to create and manage their own 'groups'. Each group can have subscribers, and maintains a group page where subscribers can post into. Membership to groups may be open, moderated, or invitation only.

Groups may choose their own theme. Groups have RSS feeds, and so on.

INSTALLATION
---------------
- Activate the og and og_basic modules as usual.
- Visit the admin/settings/og page and click the button to enable access control.
- Set other preferences on admin/settings/og as desired.
- On the admin/settings/content-types/og page, disable commenting and attachments for nodes of type 'group'
- On the admin/block page, enable the 'Group details' with a low 'weight' value. Optionally enable the 'Group subscribers', 'New groups, 'My groups' blocks.
- Grant permissions as needed on the admin/access page
- Begin creating groups, subscribing to those groups, and posting into those groups. The subscribe link appears in the Group block, for non invite-only groups.

NOTES
----------------
- This module actually supports group nodes coming from custom modules, not just og_basic. That means that you can have custom fields for your groups and even several different kinds of groups. Specify the group types at admin/settings/og and also remember to disable attachments and comments for each type.
- Drupal has poor support for running more than one node_access type module at one. That means that you can't run og with
taxonomy_access, nodeperm_by_role, nodeaccess, or any other node access control module. The plan for fixing this is for og to use the na_arbitrator module (currently ikn contrin - patches welcome)
- 'Administer nodes' permission is required for changing the Manager of a group
- 'Administer nodes' permission enables viewing of all nodes regardless of private/public status
- All subscriber management happens on the 'subscriber list' page which is linked from the group Block (while viewing a group page). This includes approving subscription requests (for selective groups), subscribing/unsubscribing users and promoting users into group admins.
- If you decide to stop using this module, click the 'disable' button on the admin/settings/og page. If you ever decide to re-enable, all your prior subscriptions and group settings are preserved.


THEMES
------------------
You may wish to stylize nodes which have properties assigned by this module.
--- public vs. private posts are denoted by $node->og_public
--- group assignments (if any) are to be found in $node->og_groups. this is an array of nids. the group names are in $node->og_groups_names.
--- you may wish to omit the node author and time if $node->type == 'og'. also consider not showing node links for these nodes since they slightly clutter the group home page.
--- theme('mark', OG_ADMIN) may be customized so that admins are denoted differently
--- provided in this package are two template files for the phptemplate engine. One stylizes group nodes and suggested above and the other stylizes all other nodes as suggested above. These can be starting points for your customization of look and feel of the central area of the group home page.

TODO/BUGS/FEATURE REQUESTS
----------------
- see http://drupal.org/project/issues/og

CREDITS
----------------------------
Authored and maintained by Moshe Weitzman <weitzman AT tejasa DOT com>
Contributors: Mir Nazim, Gerhard Killesreiter, Angie Byron
Sponsored by Bryght - http://www.bryght.com
Sponsored by Broadband Mechanics - http://www.broadbandmechanics.com/
Sponsored by Finnish Broadcasting Company - http://www.yle.fi/fbc/
