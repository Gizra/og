Drupal.og_accessAttach = function() {

  /* admin og settings form, "Group details - Private Groups"
   * Disable "always public" if Node authoring visibility set to "Visible only within the targeted groups"
   * Disable "always private" if Node authoring visibility set to "Visible within the targeted groups and on other pages"
   */
  $("input[@Name='og_visibility']").click(function() {
    if ($("input[@Name='og_visibility']:checked").val() == 0) {
        $("input[@name='og_private_groups']:nth(0)").attr('disabled','disabled');
        $("input[@name='og_private_groups']:nth(1)").removeAttr('disabled');
      }
      else if ($("input[@Name='og_visibility']:checked").val() == 1) {
        $("input[@name='og_private_groups']:nth(0)").removeAttr('disabled');
        $("input[@name='og_private_groups']:nth(1)").attr('disabled','disabled');
      } 
      else {
        $("input[@name='og_private_groups']:nth(0)").removeAttr('disabled');
        $("input[@name='og_private_groups']:nth(1)").removeAttr('disabled');
      }
    }
  );

  if ($("input[@Name='og_visibility']:checked").val() == 0) {
      $("input[@name='og_private_groups']:nth(0)").attr('disabled','disabled');
      $("input[@name='og_private_groups']:nth(1)").removeAttr('disabled');
  }
  else if ($("input[@Name='og_visibility']:checked").val() == 1) {
      $("input[@name='og_private_groups']:nth(0)").removeAttr('disabled');
      $("input[@name='og_private_groups']:nth(1)").attr('disabled','disabled');     
  }
    
  /* admin og settings form, "Node Authoring Form - Visibilty of Posts"
   * Disable "Visible within the targeted groups and on other pages" if private groups set to "always private"
   * Disable "Visible only within the targeted groups" if private groups set to "always public"
   */
  $("input[@Name='og_private_groups']").click(function() {
      if ( $("input[@Name='og_private_groups']:checked").val() == 1 ) {
        $("input[@name='og_visibility']:nth(0)").removeAttr('disabled');
        $("input[@name='og_visibility']:nth(1)").attr('disabled','disabled');
      }
      else if ( $("input[@Name='og_private_groups']:checked").val() == 0 ) {
        $("input[@name='og_visibility']:nth(0)").attr('disabled','disabled');
        $("input[@name='og_visibility']:nth(1)").removeAttr('disabled');  
      }
      else { 
        $("input[@name='og_visibility']:nth(0)").removeAttr('disabled');  
        $("input[@name='og_visibility']:nth(1)").removeAttr('disabled');  
      }
    }
  );

  if ( $("input[@Name='og_private_groups']:checked").val() == 1 ) {
    $("input[@name='og_visibility']:nth(0)").removeAttr('disabled');
    $("input[@name='og_visibility']:nth(1)").attr('disabled','disabled');
  }
  else if ( $("input[@Name='og_private_groups']:checked").val() == 0 ) {
      $("input[@name='og_visibility']:nth(0)").attr('disabled','disabled');
      $("input[@name='og_visibility']:nth(1)").removeAttr('disabled');  
  }

}

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.og_accessAttach);
}
