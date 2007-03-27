// $Id$

Drupal.ogAttach = function() {
/*  Disable the public checkbox if no groups are selected in in Audience*/
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
}

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.ogAttach);
}