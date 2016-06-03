jQuery(function() {
    jQuery( "#nt_note_cont" ).draggable();
  });

jQuery(document).ready( function() {
  var dt = new Date();
var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();

  var userSubmitButton = document.getElementById('nt-note-submit');

  var adminAjaxRequest = function( formData, action) {
    jQuery.ajax({
      type: 'POST',
      dataType: 'text',
      url: nt_ajax_call.adminAjax,
      data: {
        action: action,
        data: formData,
        submission: document.getElementById('xyz').value,
        security: nt_ajax_call.security
      },
      success: function(response) {
					if ( true === response.responseText ) {

            jQuery('#apf-response').append('Success!!!');
					} else {

            jQuery('#apf-response').append('Success: ' + time);
            setTimeout(function() {
              jQuery('#apf-response').remove();
            }, 4000);
					}
				}
    });
  };

  userSubmitButton.addEventListener( 'click', function(event) {
			event.preventDefault();
			var formData = {
				'title' : document.getElementById( 'nt-note-title').value,
				'body' : document.getElementById( 'nt-note-body').value,
        'userId' : document.getElementById( 'nt-note-user-id').value,
        'currentLessonId' : document.getElementById( 'nt-note-current-lessson-id').value,
        'currentPostType' : document.getElementById( 'nt-note-current-post-type').value
			};
			adminAjaxRequest( formData, 'process_course_note' );
		} );
});
