DESCRIPTION
--------------------------
Enable users to create and manage their own 'groups'. Each group can have members, and maintains a group page where members can post into. Posts may be placed into multiple groups (i.e. cross-posting) and individual posts may be shared with non-members or not. Membership to groups may be open, closed, moderated, or invitation only. Add-on modules are available for group image galleries, group calendars, group vocabulary, group stores, and so on.

Groups may choose their own theme and language. Groups have RSS feeds and email notifications and so on. Group admins may customize the layout and contents of their group home page and add additional custom pages (requires the included OG Panels module).

INSTALLATION
---------------
- Enable the Organic Groups module. If you want to protect some posts so that only certain users may view them, enable the 'Organic Groups access control' module as well. Please make sure og is working well on its own before enabling other OG related modules.
- Visit the admin/og/og page.
- On admin/og/og, see the content types table at the top. Click edit beside each type to set its 'usage'. You usually want to create a new node type via admin/content/types page and then designate that node type as a group from here. See the first item in NOTES below. 
- Set other preferences on admin/og/og as desired. It may take some experimenting before you arrive at a configuration well suited to your needs.
- On the admin/content/types page, disable comments and attachments for node types which are designated as groups. Click the edit link beside those types.
- On the admin/build/themes/settings pages, in 'Display post information on' section, uncheck each node type which has been designated as a group.
- On the admin/build/block page, enable the 'Group details' with a low 'weight' value. Optionally enable the other 'Group' blocks.
- Grant permissions as needed on the admin/user/access page 
- Begin creating groups (visit the node/add page), joining those groups, and posting into those groups. The join link appears in the Group details block, for non invite-only groups.
- Consider enabling the following modules which work well with OG: Pathauto, Locale, Job queue. After your install is working nicely, consider enabling og add-on modules like og_mandatory_group, og_vocab, and og_panels. Those are know to work well with OG. Beware of the helper modules listed at at http://drupal.org/project/Modules/category/90. Many of them actually break OG in subtle ways.

NOTES
----------------
- This module supports designating any type of node to be a group. This node type should be defined by a custom module or via the admin/content/types page. When defining your type, you usually want the title label to be 'Group name' and the body label to be 'Welcome message'. Since all nodes of this type are treated as groups, you will usually not want to designate the standard page, story, or book node types as groups. The capacity to make custom node types groups means that you can have custom fields for your groups and even several different kinds of groups. 
- There are a few handy tabs at the path 'group'. You might want to add a link in your navigation to that url. Each tab also provides a useful RSS feed.
- 'Administer nodes' permission is required for changing the Manager of a group (do so by changing the posts' Author.)
- 'Administer nodes' permission enables viewing of all nodes regardless of private/public status.
- All membership management happens on the 'membership list' page which is linked from the group Block (while viewing a group page). This includes approving membership requests (for selective groups), adding/removing users and promoting users into group admins.
- If you decide to stop using this module, just disable it as usual. If you ever decide to re-enable, all your prior group access control information will be restored. If you want to really start fresh, uninstall og and og_access modules.

DEVELOPERS & SITE BUILDERS
------------------
- You may craft your own URLs which produce useful behavior. For example, user/register?gids[]=4 will add a checked checkbox for to the user's registration page for subscribing to group nid=4. This feature overrides the usual preference for groups to always appear during registration.
- You alter the links in the group details block using hook_og_link_alter($links, $group_node). See bottom of og_og_block_details(). Or you may completely take over this block by defining a og_block_details for each of your custom group node types. See og_block_details().

THEMES
------------------
You may wish to stylize nodes which have properties assigned by this module.
--- public vs. private posts are denoted by $node->og_public
--- group assignments (if any) are to be found in $node->og_groups_both.
--- you may wish to omit the node author and time if we are showing a group home page. also consider not showing node links for these nodes since they just add clutter.
--- provided in this package are two template files for the phptemplate engine. One stylizes group nodes as suggested above and the other stylizes all other nodes as suggested above. These can be starting points for your customization of look and feel of the central area of the group home page. Or you might use the included og_panels module to achieve custom group homepages (and other group pages) that group admins can design themselves.

INTEGRATION
---------------------
- This module exposes an API for retrieving and managing membership via direct PHP functions [og_save_subscription()] and via XMLRPC.
- Enabling og_notifications provides subscription support for groups and group
posts. The README in the og_notifications directory provides more information.

UNIT TESTING
----------------------
This module comes with a small suite of unit tests. Please help update and build more of them. See http://drupal.org/simpletest

TODO/BUGS/FEATURE REQUESTS
----------------
- see http://drupal.org/project/issues/og

UPGRADING FROM 4.7 TO 5.0
-----------------
- In order to support the new 'as many node access modules as desired' feature of core, much has changed. A full update path to the new database configuration has been provided. However, not every scenario has been tested. Please backup your database before upgrading. Also, please report successes or failures with the update via http://drupal.org/project/issues/og or send email to Moshe Weitzman (see bottom of this file).
- The og_basic module has been deprecated. You should delete it.
- When you perform an update, og will create try to create a custom node type for you. This code sometimes fails though (help wanted). You may manage it afterwards at admin/content/types.
- Views.module is now required (and views_rss).

CREDITS
----------------------------
Authored and maintained by Moshe Weitzman <weitzman AT tejasa DOT com>
Contributors: Gerhard Killesreiter, Angie Byron, Derek Wright, Thomas Ilsche, Ted Serbinski, damien_vancouver
Sponsored by Bryght - http://www.bryght.com
Sponsored by Broadband Mechanics - http://www.broadbandmechanics.com/
Sponsored by Finnish Broadcasting Company - http://www.yle.fi/fbc/
Sponsored by Post Carbon Institute - http://www.postcarbon.org/
