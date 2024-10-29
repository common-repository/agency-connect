<?php
/*
	Plugin Name: Agency Connect
	Plugin URI: http://jan.do/agency-connect/
	Description: Agency Connect empower your customers to contact you and to ask you for help - directly and at any time.
	Version: 0.6.1.2
	Author: jan.do
	Author URI: http://jan.do
	License: GPLv2 and later
	Text Domain: agency-connect-domain
*/

add_action('wp_ajax_send_helprequest_messsage', array('Agency_Connect','send_helprequest_messsage'));

class Agency_Connect {

	/**
	* The id of this widget.
	*/
	const wid = 'agency_connect';
	public static $configuration;
	public static $plugin_dir;

	public static function init()
	{
		add_option('agency_connect_options');
		self::$plugin_dir = basename(dirname(__FILE__));
		self::$configuration = get_option( 'agency_connect_options' );

		load_plugin_textdomain( 'agency-connect-domain', false, self::$plugin_dir );

		add_action('wp_dashboard_setup', array('Agency_Connect','init_widget'));
		add_action('admin_menu', array('Agency_Connect','register_request_help_menu_option') );
		add_action('admin_enqueue_scripts', array('Agency_Connect','register_scripts'));
	}

	public static function register_scripts()
	{
		wp_register_style('agency_connect_styles', plugins_url('css/agency-connect.css', __FILE__) );
		wp_enqueue_style('agency_connect_styles');

		//replace the menu link to the helprequest page by a dialog
		
		wp_enqueue_script('jquery-ui-dialog');		
		add_action('wp_enqueue_scripts', 'my_scripts_method');

		wp_enqueue_style('jquery-ui', plugins_url('lib/jqueryui/jquery-ui.css', __FILE__));

		//makes my function be called when the jquery executes the ajax request to admin-ajax.php (best way to use ajax within wordpress)
		add_action('wp_ajax_send_helprequest_messsage', array('Agency_Connect','send_helprequest_messsage'));

		//my script which makes the menu link open a dialog with the form instead of linking somewhere and then makes the ajax to send the message
		wp_enqueue_script('dialog', plugins_url('scripts/dialog.js', __FILE__));
		//so that the dialog script can use ajax_object.ajax_url as a referer to admin-ajax.php for the jQuery.post ajax request
		wp_localize_script( 'dialog', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );

	}

	public static function init_widget() {
		$widget_title = ( isset(self::$configuration['widget_title']) ) ? esc_attr(self::$configuration['widget_title']) : __("Do you need help?", "agency-connect-domain");
		wp_add_dashboard_widget(
			self::wid,				//A unique slug/ID
			$widget_title,				//Visible name for the widget
			array('Agency_Connect','widget')	//Callback for the main widget content
		);

		//try to move the widget to the top:
		self::move_widget_to_the_top(self::wid);
		//add_filter('admin_bar_menu', array('Agency_Connect','add_help_call_to_menu'), 51);
	}

	public static function widget()
	{
		if(isset($_POST['agency_connector_message']))
		{
			self::send_helprequest_messsage();
		}else
		{
			self::echo_helprequest_form('dashboard_widget');
		}
	}


	public static function register_request_help_menu_option()
	{
		$page_title = __('Agency Connect - Get Help');
		$menu_title = __('Request Help');
		$menu_slug = 'agency-connect';
		$function = array('Agency_Connect', 'display_helprequest_page' );
		$icon_url = plugins_url('media/agencyconnect-help.png', __FILE__);
		$position = '2.1';
		add_menu_page($page_title, $menu_title, 'manage_options', $menu_slug, $function, $icon_url, $position);

		//if javascript is activated, the scripts will be loaded and the register_help_request page will be replaced
		//if you click on the menu option a modal jquery dialog will be opened where the help request can be put
		add_action('admin_footer', array('Agency_Connect', 'dialogize_helprequest_page'));
	}

	public static function display_helprequest_page()
	{
		//context variable needed to use different ways for sending the mail (in dashboard and on menu page, normal php, dialog works with js)
		if(isset($_POST['agency_connector_message']))
		{
			self::send_helprequest_messsage();
		}else
		{
			self::echo_helprequest_form('menupage');
		}
	}

	public static function dialogize_helprequest_page()
	{
		//context variable needed to use different ways for sending the mail (in dashboard and on menu page, normal php, dialog works with js)
		$context = 'dialog';
		//add the hidden div to the page. dialog.js uses this div to make a dialog out of it
		//dialog.js is enqueud on every site in register_scripts function
		echo '<div id="agency_connect_dialog" title="'.__("Request Help").'" style="display: none;">';

		self::echo_helprequest_form('dialog');

		echo '</div>';


		/*TODO: find a way nicer way to do this. when the content of the dialog changes (showing error/success messages) and you reopen the dialog,
		the dialog has to get the original form again, so it doesn't still show the error message from last time. so it gets the old data from this div and copies it back to the dialog div just created */
		echo '<div id="backup_content_agency_connect_dialog" title="'.__("Request Help").'" style="display: none;">';

		self::echo_helprequest_form('dialog');

		echo '</div>';

		//needs to be done so that I can access the image from javascript. I can't read out the right url in js.
		#echo '<script type="application/javascript"> var ajax_loader_image_url = "'.plugins_url('media/ajax-loader.gif', __FILE__).'"; var ajax_failure_error_message = "'._e("A Problem occured when trying to oreach the mail server. Please it with the dashboard widget or contact your agency directly").'"</script>';
	}

	public static function move_widget_to_the_top($widgetID)
	{
	 	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	 
	 	global $wp_meta_boxes;
	 	
	 	// Get the regular dashboard widgets array 
	 	// (which has our new widget already but at the end)
	 
	 	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
	 	
	 	// Backup and delete our new dashboard widget from the end of the array
	 
	 	$widget_backup = array( $widgetID => $normal_dashboard[$widgetID] );
	 	unset( $normal_dashboard[$widgetID] );
	 	// Merge the two arrays together so our widget is at the beginning
	 
	 	$sorted_dashboard = array_merge( $widget_backup, $normal_dashboard );
	 
	 	// Save the sorted array back into the original metaboxes 
	 
	 	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}

	public static function link_to_settings_section()
	{
		return '<a href="options-general.php?page=agency-connect.php"></a>';
	}

	public static function send_helprequest_messsage()
	{
			if(empty($_POST['agency_connector_message']) || is_string($_POST['agency_connector_message']))
			{
				$message = !empty($_POST['agency_connector_message']) ? sanitize_text_field($_POST['agency_connector_message']) : __("The customer didn't enter any message");
				$email_to = sanitize_email(self::$configuration['email']);
				if (wp_mail($email_to, __("Agency Connect: A Client Needs Help", "agency-connect-domain"),$message.' From: '.site_url()))
				{
					echo '<p class="success">';
					printf(__('Your help request has been send.', "agency-connect-domain"), $email_to);
					echo '</p>';
				}
				else
				{
					echo '<p class="error">';
					echo "sending to ".$email_to." the message is: ".$message." and it failed <br>";
					printf(__('An error occured while sending the help request. Please contact your agency directly at %s', 'agency-connect-domain'), $email_to);
					echo '</p>';
				}			
			}else
			{
				echo '<p class="error">'.__('The message you entered is not valid.').'</p>';
			}

		//needed, because elseways the ajax-request returns the message with a '0' at the end. some studip wp problem if I understood right
		if (defined('DOING_AJAX') && DOING_AJAX)
			die();
	}


	public static function echo_helprequest_form($context)
	{
		//context is 'dashboard_widget', 'menupage' or 'dialog' depending on for what the form is rendered
		switch ($context)
		{
			case "dashboard_widget":
				$helptext = ( isset(self::$configuration['widget_helptext']) ) ? esc_attr(self::$configuration['widget_helptext']) : 'not configured';
				break;			
			default:				
				$helptext = ( isset( self::$configuration['helprequest_helptext'])) ? esc_attr(self::$configuration['helprequest_helptext']) : 'not configured';
		}		

		//this is the form which is used by the dashboard widget and the dialog (or the mainmenu-page if js is disabled)
		//if a helptext is set, the form will be rendered
		if($helptext !== 'not configured'): ?>
			<p><?php echo $helptext; ?></p>

			<?php if($context == 'dialog'): 
			/*we need to check if the form is loaded for the dialog or a widget. widget already sends the message via php 
			and only if the dialog is rendering form (context==dialog) we have to add another id to handle sending the message with jquery */ ?>
				<form autocomplete="on" method="POST" helprequest_file="<?php echo plugins_url('send_helprequest_message.php', __FILE__); ?>" id="helprequest_dialog">
			<?php else: ?>
				<form autocomplete="on" method="POST" id="helprequest">
			<?php endif; ?>

				<textarea rows='4' name='agency_connector_message' id='optional_help_message' placeholder="Your message (optional)"></textarea>
				<div id="widget-footer-holder">	 
					<?php if(isset(self::$configuration['phone']) && !empty(self::$configuration['phone'])): ?>
						<span id='or_call_agency'>
							<?php _e("Call "); echo esc_attr(self::$configuration['phone']); ?> or 
						</span>
					<?php endif; ?>

					<input type='submit' class='button-primary' value="<?php _e("Request Help", "agency-connect-domain"); ?>">
				</div>
				</form> 
			
			<p id="powered-by">Powered by <a href="http://jan.do/?utm_source=Tools&utm_medium=Plugin&utm_campaign=Agency+Connect" target="_blank">jan.do</a></p>
		<?php else: //if no helptext is set the please configure the plugin message is shown ?>
			<?php //if(current_user_can('edit_plugins')): ?>
				<p><?php printf( __( 'Please configure the plugin <a href="options-general.php?page=agency_connect_settings">here</a>', 'agency-connect-domain' ), self::link_to_settings_section()); 					?>				</p>
			<?php //else: ?>
				<p><?php //_e("You have to configure this widget before it can be used. Please log in as an administrator and configure this widget", "agency-connect-domain") ?></p>
			<?php //endif; ?>
		<?php endif;
	}

}

require_once('agency_connect_settings.php');
add_action( 'plugins_loaded', array('Agency_Connect', 'init'));
//add_action( 'admin_init', array('Agency_Connect', 'register_settings'));
