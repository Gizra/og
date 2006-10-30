DESCRIPTION
--------------------------
Enable users to create and manage their own 'groups'. Each group can have subscribers, and maintains a group page where subscribers can post into. Posts may be placed into multiple groups (i.e. cross-posting) and individual posts may be shared with non-subscribers or not. Membership to groups may be open, closed, moderated, or invitation only. Add-on modules are available for group image galleries, group calendars, group vocabulary, group stores, and so on.

Groups may choose their own theme and language. Groups have RSS feeds, and so on.

INSTALLATION
---------------
- If not already active, enable the Views and Views_RSS modules. These are now prerequisites. 
- Enable the og module. 
- Visit the admin/settings/og page. If you want to protect posts within private groups, click the button to enable access control.
- On admin/settings/og, see the 'Group home page node types' field at bottom. You usually want to create a new node type via admin/content/types page and then select that node type here. See the first item in NOTES below. 
- Set other preferences on admin/settings/og as desired. It may take some experimenting before you arrive at a configuration weil suited to your needs.
- On the admin/settings/content-types/og page, disable commenting and attachments for nodes of type 'group'
- On the admin/build/themes/settings pages, in 'Display post information on' section, uncheck each node type which has been designated as a group.
- On the admin/block page, enable the 'Group details' with a low 'weight' value. Optionally enable the 'Group authors', 'Group notifications', 'New groups, 'My groups' blocks.
- Grant permissions as needed on the admin/access page 
- Begin creating groups, subscribing to those groups, and posting into those groups. The subscribe link appears in the Group details block, for non invite-only groups.
- Consider enabling the following modules which work well with OG: Pathauto, Locale. After your install is working nicely, consider enabling og add-on modules like og_mandatory_group, og_vocab, etc.

NOTES
----------------
- This module supports designating any type of node to be a group. This node type should be defined by a custom module or via the admin/content/types page. When defining your type, you usually want the title label to be 'Group name' and the body label to be 'Welcome message'. Since all nodes of this type are treated as groups, you will usually not want to designate the standard page, story, or book node types as groups. The capacity to make custom node types groups means that you can have custom fields for your groups and even several different kinds of groups. Specify the group types at bottom of admin/settings/og and also remember to disable attachments and comments for each type.
- 'Administer nodes' permission is required for changing the Manager of a group
- 'Administer nodes' permission enables viewing of all nodes regardless of private/public status
- All subscriber management happens on the 'subscriber list' page which is linked from the group Block (while viewing a group page). This includes approving subscription requests (for selective groups), subscribing/unsubscribing users and promoting users into group admins.
- If you decide to stop using this module, just disable it as usual. If you ever decide to re-enable, all your prior group information will be restored.

UPGRADING FROM 4.7 TO 5.0
-----------------
- In order to support the new 'as many node access modules as desired' feature of core, much has changed. A full update path to the new database configuration has been provided. However, not every scenario has been tested. Please backup your database before upgrading. Also, please report successes or failures with the update via http://drupal.org/project/issues/og or send email to Moshe Weitzman (see bottom of this file).
- The og_basic module has been deprecated. You may should delete it, or move it from your og directory.
- When you perform an update, og will create a custom node type for you. You may manage it afterwards at admin/content/types.
- Views.module is now required.

THEMES
------------------
You may wish to stylize nodes which have properties assigned by this module.
--- public vs. private posts are denoted by $node->og_public
--- group assignments (if any) are to be found in $node->og_groups. this is an array of nids. the group names are in $node->og_groups_names.
--- you may wish to omit the node author and time if we are showing a group home page. also consider not showing node links for these nodes since they just add clutter.
--- provided in this package are two template files for the phptemplate engine. One stylizes group nodes as suggested above and the other stylizes all other nodes as suggested above. These can be starting points for your customization of look and feel of the central area of the group home page. you may also investigate themeing of Views for more techniques.

INTEGRATION
---------------------
- This module exposes an API for retrieving and managing subscriptions via direct PHP functions [og_save_subscription()] and via XMLRPC.

UNIT TESTING
----------------------
This module comes with a suite of unit tests. Please help build more of them. See http://drupal.org/simpletest

TODO/BUGS/FEATURE REQUESTS
----------------
- see http://drupal.org/project/issues/og

CREDITS
----------------------------
Authored and maintained by Moshe Weitzman <weitzman AT tejasa DOT com>
Contributors: Mir Nazim, Gerhard Killesreiter, Angie Byron, Derek Wright, Thomas Ilsche, Ted Serbinski
Sponsored by Bryght - http://www.bryght.com
Sponsored by Broadband Mechanics - http://www.broadbandmechanics.com/
Sponsored by Finnish Broadcasting Company - http://www.yle.fi/fbc/
