<?php // $Id$
?>

<div class="node<?php print ($sticky) ? " sticky" : ""; ?>">
  <?php if ($page == 0): ?>
    <h2><a href="<?php print $node_url ?>" title="<?php print $title ?>"><?php print $title ?></a></h2>
  <?php endif; ?>
  <?php print $picture ?>

  <div class="info"><?php print $submitted ?></div>
  <div class="content">
    <?php print $content ?>
  </div>

  <?php if ($node->og_groups) {
          for ($ind=0; $ind < count($node->og_groups); $ind++) {
            $og_links[] = l($node->og_groups_names[$ind], 'node/', $node->og_groups[$ind]);
          }
          $og_links = theme('links', $og_links);
          print '<div class="groups">';
          print t('groups'). ': '.  $og_links. '</div>';
   } ?>
  <?php if ($terms) {
          print '<div class="terms">';
          print t('categories'). ': '. $terms.  '</div>';
        }

        if ($links): ?>

    <?php if ($picture): ?>
      <br class='clear' />
    <?php endif; ?>
    <div class="links"><?php print $links ?></div>
<?php endif; ?>
</div>
