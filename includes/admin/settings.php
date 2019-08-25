<?php

defined( 'ABSPATH'  ) || exit;

class Noptin_Settings {

    //Class constructor
    private function __construct() {}

    //Renders the settings page
    public static function output() {

        //Maybe save the settings
        Noptin_Settings::maybe_save_settings();

        //Render settings
        include( 'templates/settings.php' );
    }

    //Saves the settings page
    public static function maybe_save_settings() {
        global $noptin_options;

        //Maybe abort early
        if( empty( $_POST['_wpnonce'] ) || !wp_verify_nonce( $_POST['_wpnonce'] ) ) {
            return;
        }

        //Prepare the settings
        $registered_settings = self::get_settings();
        $posted_settings     = $_POST;
        unset( $posted_settings['_wpnonce'] );
        unset( $posted_settings['_wp_http_referer'] );

        //Sanitize the settings
        $options = self::sanitize_settings( $registered_settings, $posted_settings );

        //Then save them
        $noptin_options = $options;
        update_option( 'noptin_options', $options );
    }

    /**
     * Sanitizes settings fields
     */
    public static function sanitize_settings( $registered_settings, $posted_settings ) {

        foreach( $registered_settings as $id=>$args ) {

            //Deal with checkboxes(unchecked ones are never posted)
            if( 'checkbox' == $args['el'] ) {
                $posted_settings[$id] = isset( $posted_settings[$id] ) ? '1' : '0';
            }
        }
        return apply_filters( 'noptin_sanitize_settings', $posted_settings );
    }

	/**
     * Returns all settings sections
     */
	public static function get_sections() {
		$sections = wp_list_pluck( self::get_settings(), 'section');
		$modified = array();

		foreach( $sections as $section ) {
			$modified[$section] = ucwords( str_replace('-',' ',$section) );
		}
		return $modified;

	}

	/**
     * Returns a section conditional
     */
	public static function get_section_conditional( $args ) {

		if( empty( $args['section'] ) ) {
			return '';
		}

		return "v-show=\"currentTab=='{$args['section']}'\"";

	}

	/**
     * Returns the default state
     */
	public static function get_state() {

		$settings = array_keys( self::get_settings() );
		$state    = array();

		foreach( $settings as $setting ) {
			$state[$setting] = get_noptin_option( $setting, '' );
		}

		$state['currentTab'] = 'general';
		$state['saved'] = __( 'Your settings have been saved', 'noptin' );
		$state['error'] = __( 'Your settings could not be saved.', 'noptin' );
		return $state;

	}

    /**
     * Returns all settings fields
     */
    public static function get_settings() {

        $settings = array(

			'notify_new_post'       => array(
				'el'              => 'input',
				'type'            => 'checkbox_alt',
				'section'		  => 'general',
				'label'           => __( 'New Post Notifications', 'noptin' ),
				'description'     => __( 'Notify your active subscribers every time you publish a new post.', 'noptin' ) ,
			),

			'new_post_subject'    => array(
				'el'              => 'input',
				'type'            => 'text',
				'restrict'        => 'notify_new_post',
				'section'		  => 'general',
				'label'           => __( 'Email Subject', 'noptin' ),
				'placeholder'     => '[[title]]',
				'description'     => __( 'You can use the tags [[title]], [[first_name]], [[last_name]] or any other field name that you collect.', 'noptin' ) ,
			),

			'new_post_preview_text'    => array(
				'el'              => 'input',
				'type'            => 'text',
				'restrict'        => 'notify_new_post',
				'section'		  => 'general',
				'label'           => __( 'Email Preview Text', 'noptin' ),
				'placeholder'     => __( 'We just published a new blog post. Hope you like it.', 'noptin'),
				'description'     => __( 'You can use the tags [[title]], [[first_name]], [[last_name]] or any other field name that you collect.', 'noptin' ) ,
			),

			'new_post_content'    => array(
				'el'              => 'textarea',
				'restrict'        => 'notify_new_post',
				'section'		  => 'general',
				'label'           => __( 'Email Content', 'noptin' ),
				'placeholder'     => "Hello [[first_name]], \nI just published a new post on [[blog_name]]. \n[[excerpt]]",
				'description'     => __( 'You can use the tags [[title]], [[excerpt]],[[post_content]], [[first_name]], [[last_name]] or any other field name that you collect.', 'noptin' ) ,
			),

			'comment_form'        => array(
				'el'              => 'input',
				'type'            => 'checkbox_alt',
				'section'		  => 'general',
				'label'           => __( 'Subscribe Commentors', 'noptin' ),
				'description'     => __( 'Ask commentors to subscribe to the newsletter.', 'noptin' ) ,
			),

			'comment_form_msg'    => array(
				'el'              => 'input',
				'type'            => 'text',
				'restrict'        => 'comment_form',
				'section'		  => 'general',
				'label'           => __( 'Checkbox label', 'noptin' ),
				'placeholder'     => __( 'Subscribe To Our Newsletter', 'noptin' ),
			),

			'register_form'       => array(
				'el'              => 'input',
				'type'            => 'checkbox_alt',
				'section'		  => 'general',
				'label'           => __( 'Subscribe New Users', 'noptin' ),
				'description'     => __( 'Ask new users to subscribe to the newsletter.', 'noptin' ) ,
			),

			'register_form_msg'    => array(
				'el'              => 'input',
				'type'            => 'text',
				'restrict'        => 'register_form',
				'section'		  => 'general',
				'label'           => __( 'Checkbox label', 'noptin' ),
				'placeholder'     => __( 'Subscribe To Our Newsletter', 'noptin' ),
			),

			'hide_from_subscribers'       => array(
				'el'              => 'input',
				'type'            => 'checkbox_alt',
				'section'		  => 'general',
				'label'           => __( 'Hide From Subscribers', 'noptin' ),
				'description'     => __( 'Hide opt-in forms and methods from existing subscribers.', 'noptin' ) ,
			),

			'from_email'       => array(
				'el'              => 'input',
				'section'		  => 'sender',
				'type'            => 'email',
                'label'           => __( 'From Email', 'noptin' ),
                'class'           => 'regular-text',
                'placeholder'     => get_option('admin_email'),
                'description'     =>  __( 'Set this to a valid email address. If emails are not being delivered, leave this field blank.', 'noptin' ),
			),

			'from_name'       => array(
				'el'              => 'input',
				'section'		  => 'sender',
                'label'           => __( 'From Name', 'noptin' ),
				'class'           => 'regular-text',
				'restrict'		  => 'from_email',
                'placeholder'     => get_option('blogname'),
			),

            'company'   => array(
				'el'              => 'input',
				'section'		  => 'sender',
                'label'           => __( 'Company', 'noptin' ),
				'class'           => 'regular-text',
				'placeholder'     => get_option('blogname'),
			),

			'company_logo'   => array(
				'el'              => 'input',
				'type'			  => 'image',
				'section'		  => 'sender',
				'label'           => __( 'Logo', 'noptin' ),
				'description'     =>  __( "Appears on top of emails. Leave blank to use your website's logo or the default image", 'noptin' ),
            ),

            'address'       => array(
				'el'              => 'input',
				'section'		  => 'sender',
                'label'           => __( 'Street Address', 'noptin' ),
                'class'           => 'regular-text',
                'placeholder'     => __( '31 North San Juan Ave. ', 'noptin' ),
            ),

            'city'       => array(
				'el'              => 'input',
				'section'		  => 'sender',
                'label'           => __( 'City', 'noptin' ),
                'class'           => 'regular-text',
                'placeholder'     => __( 'Santa Clara', 'noptin' ),
			),

			'state'       => array(
				'el'              => 'input',
				'section'		  => 'sender',
                'label'           => __( 'State', 'noptin' ),
                'class'           => 'regular-text',
                'placeholder'     => __( 'San Francisco', 'noptin' ),
			),

			'country'       => array(
				'el'              => 'input',
				'section'		  => 'sender',
                'label'           => __( 'Country', 'noptin' ),
                'class'           => 'regular-text',
                'placeholder'     => __( 'United States', 'noptin' ),
            ),
        );
        return apply_filters( 'noptin_get_settings', $settings );
    }

}
