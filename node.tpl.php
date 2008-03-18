<?php // $Id$
?>

<div class="node<?php if ($sticky) { print " sticky"; } ?><?php if (!$status) { print " node-unpublished"; } ?>">
  <?php if ($picture) {
    print $picture;
  }?>
  <?php if ($page == 0) { ?><h2 class="title"><a href="<?php print $node_url?>"><?php print $title?></a></h2><?php }; ?>
  <span class="submitted"><?php print $submitted?></span>
  <span class="taxonomy"><?php print $terms?></span>
  <div class="content"><?php print $content?></div>
  <?php if ($node->og_groups && $page) {
          $current_groups = og_node_groups_distinguish($node->og_groups_both);
          foreach ($node->og_groups_both as $gid => $title) {
            global $user;
            // User may only see a group if she is a member or it is accessible.
            if (isset($user->og_groups[$gid]) || isset($current_groups['accessible'][$gid])) {
              $og_links['og_'. $gid] = array('title' => $title, 'href' => "node/$gid");
            }
          }
          $og_links = theme('links', $og_links);
          print '<div class="groups">'. t('Groups'). ': ';
					print '<div class="links"'.  $og_links. '</div></div>';
   } ?>

	<?php if ($links) { ?><div class="links">&raquo; <?php print $links?></div><?php }; ?>
</div>