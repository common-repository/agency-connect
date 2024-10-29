<?php
class MySettingsPage
{
	/**
	* Holds the values to be used in the fields callbacks
	*/
	private $options;

	/**
	* Start up
	*/
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	* Add options page
	*/
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'Agency Connect Settings',		//pagetitle 
			'Agency Connect',			//menutitle 
			'manage_options',			//capability 
			'agency_connect_settings',		//menu slug. I am using this to refer to this menu at other places
			array( $this, 'create_admin_page' )
		);
	}

	/**
	* Options page callback
	*/
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option( 'agency_connect_options' );
		?>
		<div class="wrap">
			<?php screen_icon(); //I think this it the small settings-icon ?>
			<h2>My Settings</h2>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
					settings_fields( 'agency_connect_options' );   
					do_settings_sections( 'agency_connect_settings' );
					submit_button(); 
				?>
			</form>
		</div>
		<?php
	}

	/**
	* Register and add settings
	*/
	public function page_init()
	{        
		register_setting(
			'agency_connect_options', // Option group
			'agency_connect_options' // Option name
			//array( $this, 'sanitize' ) // Sanitize
		);

		//TODO: SANTIZATION!!

		add_settings_section(
			'general', // ID
			__('General'), // Title
			array( $this, 'callback_general_section' ), // Callback
			'agency_connect_settings' // Page
		);

		add_settings_section(
			'widget', // ID
			__('Dashboard Widget'), // Title
			array( $this, 'callback_widget_section' ), // Callback
			'agency_connect_settings' // Page
		);

		add_settings_section(
			'helprequest', // ID
			__('Helprequest Menu'), // Title
			array( $this, 'callback_helprequest_section' ), // Callback
			'agency_connect_settings' // Page
		);

		add_settings_field(
			'email', // ID
			__('Email Adress'), // Title 
			array( $this, 'email_callback' ), // Callback
			'agency_connect_settings', // Page
			'general' // Section           
		);

		add_settings_field(
			'agency_name', // ID
			__('Agency'), // Title 
			array( $this, 'agency_callback' ), // Callback
			'agency_connect_settings', // Page
			'general' // Section           
		);

		add_settings_field(
			'phone', // ID
			__('Phone Number (Optional)'), // Title 
			array( $this, 'phone_callback' ), // Callback
			'agency_connect_settings', // Page
			'general' // Section           
		);

		add_settings_field(
			'widget_title', // ID
			__('Widget Title'), // Title 
			array( $this, 'widget_title_callback' ), // Callback
			'agency_connect_settings', // Page
			'widget' // Section           
		);

		add_settings_field(
			'widget_helptext', // ID
			__('Message For The Client'), // Title 
			array( $this, 'widget_helptext_callback' ), // Callback
			'agency_connect_settings', // Page
			'widget' // Section           
		);

		add_settings_field(
			'helprequest_helptext', // ID
			__('Message For The Client'), // Title 
			array( $this, 'helprequest_callback' ), // Callback
			'agency_connect_settings', // Page
			'helprequest' // Section           
		);
	}

	/* Callbacks for the input fields and sections. Mostly only echoing stuff */

	/* Section-Callbacks */
		public function callback_general_section()
		{
			_e('Set you general settings here. Agency Connect needs your email-adress so that you can reveive the help calls from your clients.');
		}

		public function callback_widget_section()
		{
			_e('The settings for the Agency Connect dashboard widget go here. Do not forget to mention that the message-field is optional for sending the help-request if you want so. That will give clients the possibility to just call for help without giving further details.');
		}

		public function callback_helprequest_section()
		{
			_e('The settings for the "Agency Connect Helpcall" in the wordpress main menu go here. Do not forget to mention that the message-field is optional for the help-request if you want so. That will give clients the possibility to just call for help without giving further details.');
		}

	/* Field-Callbacks */

		public function email_callback()
		{
			printf( '<input type="text" id="email" placeholder="e.g. support@jan.do" name="agency_connect_options[email]" value="%s" />',
			isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : '');
		}		

		public function phone_callback()
		{
			printf( '<input type="text" id="phone" placeholder="e.g. +49 551 99690041" name="agency_connect_options[phone]" value="%s" />',
			isset( $this->options['phone'] ) ? esc_attr( $this->options['phone']) : '');
		}

		public function agency_callback()
		{
			printf( '<input type="text" id="agency_name" placeholder="e.g. jan.do" name="agency_connect_options[agency_name]" value="%s" />',
			isset( $this->options['agency_name'] ) ? esc_attr( $this->options['agency_name']) : '');
		}

		public function widget_title_callback()
		{
			printf( '<input type="text" id="widget_title" placeholder="e.g. Do you need help?" name="agency_connect_options[widget_title]" value="%s" />',
			isset( $this->options['widget_title'] ) ? esc_attr( $this->options['widget_title']) : '');
		}

		public function widget_helptext_callback()
		{
			printf( '<textarea id="widget_helptext" placeholder="e.g. Please feel free to fill out the form or to call us" name="agency_connect_options[widget_helptext]" rows="5" cols="50">%s</textarea>',
			isset( $this->options['widget_helptext'] ) ? esc_attr( $this->options['widget_helptext']) : '');
		}

		public function helprequest_callback()
		{
			printf( '<textarea id="helprequest_helptext" placeholder="e.g. Do you need help? Please feel free to fill out the form or to call us" name="agency_connect_options[helprequest_helptext]" rows="5" cols="50">%s</textarea>',
			isset( $this->options['helprequest_helptext'] ) ? esc_attr( $this->options['helprequest_helptext']) : '');
		}

	/**
	* Sanitize each setting field as needed
	*
	* @param array $input Contains all settings fields as array keys
	*/
	public function sanitize( $input )
	{
		$new_input = array();
		if( isset( $input['id_number'] ) )
		$new_input['id_number'] = absint( $input['id_number'] );

		if( isset( $input['title'] ) )
		$new_input['title'] = sanitize_text_field( $input['title'] );

		return $new_input;
	}
}
if(is_admin())//current_user_can('edit_plugins'))
$my_settings_page = new MySettingsPage();
