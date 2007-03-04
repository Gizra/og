// $Id$

Drupal.ogAttach = function() {
/*  Disable the public checkbox if no groups are selected in in Audience*/
  $('.og-audience').click(function() {
    // Audience can be select or checkboxes
    if ($('input.og-audience .form-checkbox')) {
      var cnt = $('input.og-audience:checked').size();  
    }
    else {
      var cnt = $('input.og-audience:selected').size();      
    }
    
      
    if (cnt > 0) {
      $('#edit-og-public').removeAttr("disabled");
    }
    else {
      $('#edit-og-public').attr("disabled", "disabled");
    }
  })
  $('.og-audience').click();
}

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.ogAttach);
}
