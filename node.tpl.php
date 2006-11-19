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
          for ($ind=0; $ind < count($node->og_groups); $ind++) {
            $og_links['og_'. $node->og_groups[$ind]] = array('title' => $node->og_groups_names[$ind], 'href' => 'node/'. $node->og_groups[$ind]);
          }
          $og_links = theme('links', $og_links);
          print '<div class="groups">'. t('Groups'). ': ';
					print '<div class="links"'.  $og_links. '</div></div>';
   } ?>

	<?php if ($links) { ?><div class="links">&raquo; <?php print $links?></div><?php }; ?>
</div>