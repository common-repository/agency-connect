jQuery(document).ready(function($)
{
	jQuery('a.toplevel_page_agency-connect').removeAttr('href');		//remove destionation where the main-menu link should point to, so that we can set a javascript handler instead
	jQuery('div#agency_connect_dialog').dialog({
		autoOpen: false,
		modal: true,
		closeOnEscape: true,
		close: function(event, ui){ jQuery('div#agency_connect_dialog').html( jQuery('div#backup_content_agency_connect_dialog').html() ); } //on closing the dialog it gets backup content and sets it back to original. more about that in dialogize_helprequest php function
	});
	jQuery('a.toplevel_page_agency-connect').click(function(){
		
		jQuery('div#agency_connect_dialog').dialog('open');
	});

	jQuery('form#helprequest_dialog').submit(function(e){
		e.preventDefault();		//stop the page from refreshing after the form is submitted		
		//jQuery('div#agency_connect_dialog').html('<img src="' + ajax_loader_image_url + '" alt="Please wait.." id="ajax-loader">');	//ajax_loader_image_url is defined in the dialogize_helprequest php function
		helpmessage = jQuery('textarea#optional_help_message').val();
		//the ajax_object is localized for this file in the register_script function. the action post data sets which action the admin-ajax.php will trigger
		//an function is bound to wp_ajax_send_helprequest_message which will be executed when this ajax call is made
		jQuery.post( ajax_object.ajax_url, { agency_connector_message: helpmessage, action: "send_helprequest_messsage"}, function( data ) {
			jQuery('div#agency_connect_dialog').html(data);
		}, 'html')
		.fail(function(){
			jQuery('div#agency_connect_dialog').html(ajax_failure_error_message);				//is defined in dialogize_helprequest, because I need to use the integrated wordpress translation functions for this
		});
	});
});
