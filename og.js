// $Id$

Drupal.ogAttach = function() {
  
  /* Node authoring form for group content -Disable the public checkbox if no groups are selected in in Audience */
  $('.og-audience').click(function() {
    // Audience can be select or checkboxes
    var cnt;
    if ( $('.og-audience .form-checkbox').size() > 0) {
      cnt = $('input.og-audience:checked').size();  
    }
    else {
      cnt = $('.og-audience option:selected').size();      
    }
    if (cnt > 0) {
      $('#edit-og-public').removeAttr("disabled");
    }
    else {
      $('#edit-og-public').attr("disabled", "disabled");
    }
  });
  
  if ( $('.og-audience .form-checkbox').size() > 0 ) {
    if ( $('input.og-audience:checked').size() < 1) {
        $('#edit-og-public').attr("disabled", "disabled");
    }    
  }
  else {
    if ( $('.og-audience option:selected').size() < 1) {
        $('#edit-og-public').attr("disabled", "disabled");
    }        
  }

  /* Node authoring form for group homepages - Don't allow "private group" and "Open subscription" at the same time 
   * This is just for improved UI. You may change it if you need this combination.
   */
  $("#edit-og-private").click(function() { 
    if ($("#edit-og-private:checked").val()) {
      $("input[@Name='og_selective']:nth(0)").removeAttr('checked').attr('disabled','disabled');
    }
    else {
      $("input[@Name='og_selective']:nth(0)").removeAttr('disabled');
    }
  });
  
  $("input[@Name='og_selective']").click(function() {
      // if Open is selected
      if ($("input[@Name='og_selective']:checked").val() == 0) {
        $("#edit-og-private").removeAttr("checked").attr('disabled','disabled');
      }
      else {
        $("#edit-og-private").removeAttr("disabled");
      }
  });
  
  if ($("#edit-og-private:checked").val()) {
      $("input[@Name='og_selective']:nth(0)").removeAttr('checked').attr('disabled','disabled');
  }
  
  
  /* Node authoring form for group homepages - Don't allow "private group" and "list in groups directory" at the same time 
   * This is just for improved UI. You may change it if you need this combination.
   */
  $("#edit-og-private").click(function() { 
    if ($("#edit-og-private:checked").val()) {
      $("#edit-og-directory").removeAttr("checked").attr('disabled','disabled');
    }
    else {
      $("#edit-og-directory").removeAttr('disabled');
    }
  });
  
  $("#edit-og-directory").click(function() {
    if ($("#edit-og-directory:checked").val()) {
      $("#edit-og-private").attr('disabled','disabled');
    }
    else {
      $("#edit-og-private").removeAttr('disabled');
    }
  });
  if ($("#edit-og-directory:checked").val() && !$("#edit-og-private:checked").val()) {
      $("#edit-og-private").attr('disabled','disabled');
  }
  if ($("#edit-og-private:checked").val() && !$("#edit-og-directory:checked").val()) {
      $("#edit-og-directory").attr('disabled','disabled');
  } 

};

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.ogAttach);
}
