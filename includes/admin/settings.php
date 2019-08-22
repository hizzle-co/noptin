<?php

defined( 'ABSPATH'  ) || exit;

class Noptin_Settings {

    //Class constructor
    private function __construct() {}

    //Inits hooks
    public static function init_hooks() {
        add_action( 'noptin_render_select_settings_field', 'Noptin_Settings::render_select', 10, 2 );
        add_action( 'noptin_render_input_settings_field', 'Noptin_Settings::render_input', 10, 2 );
        add_action( 'noptin_render_checkbox_settings_field', 'Noptin_Settings::render_checkbox', 10, 2 );
    }

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

    public static function render_field( $id, $args ) {

        //abort early if no element is specified
        if( empty( $args['el'] ) ) {
            return;
        }

        $el            = trim( sanitize_text_field( $args['el'] ) );
        $args['value'] = get_noptin_option( $id );

        do_action( "noptin_render_{$el}_settings_field", $id, $args );

    }

    /**
     * Renders a select field
     */
    public static function render_select( $id,  $args ) {

        //set options
        if( !empty( $args['data'] ) ) {
            $data = trim( $args['data'] );

            if( function_exists("noptin_get_$data") ) {
                $args['options'] = call_user_func( "noptin_get_$data", $id, $args );
            }
        }

        //Abort early if there are no options
        if( empty( $args['options'] ) ) {
            return;
        }
        $id           = esc_attr( $id );
        $value        = isset( $args['value'] ) ? esc_attr( $args['value'] ) : '';
        $class        = empty( $args['class'] ) ? "regular-select" : esc_attr( $args['class'] ) . " regular-$type";
        $description  = isset( $args['description'] ) ? "<p class='description'>{$args['description']}</p>" : '';
        echo "<label for='$id'><select class='$class' id='$id' name='$id'>";

        foreach ( $args['options'] as $key => $label ) {

            if( !is_scalar($key) || !is_scalar($label) ) {
                continue;
            }
            $key        = esc_attr( $key );
            $label      = esc_html( $label );
            $selected   = selected( $key, $value, false );
            echo "<option value='$key' $selected>$label</option> ";
        }

        echo "</select>$description</label>";
    }

    /**
     * Renders a checkbox field
     */
    public static function render_checkbox( $id,  $args ) {

        $id           = esc_attr( $id );
        $value        = isset( $args['value'] ) ? esc_attr( $args['value'] ) : '0';
        $checked      = checked( $value, '1', false );
        $class        = empty( $args['class'] ) ? "regular-checkbox" : esc_attr( $args['class'] ) . " regular-checkbox";
        $description  = isset( $args['description'] ) ? $args['description'] : '';
        echo "<label for='$id'><input class='$class' id='$id' name='$id' value='1' type='checkbox' $checked />$description</label>";

    }

    /**
     * Renders an input field
     */
    public static function render_input( $id,  $args ) {

        $id           = esc_attr( $id );
		$value        = isset( $args['value'] ) ? esc_attr( $args['value'] ) : '';
		$placeholder  = isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '';
        $type         = empty( $args['type'] )  ? 'text' : esc_attr( $args['type'] );
        $class        = empty( $args['class'] ) ? "regular-$type" : esc_attr( $args['class'] ) . " regular-$type";
        $description  = isset( $args['description'] ) ? "<p class='description'>{$args['description']}</p>" : '';
        echo "<label for='$id'><input placeholder='$placeholder' class='$class' id='$id' name='$id' value='$value' type='$type' />$description</label>";

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
				'description'     => __( 'You can use the tags [[title]], [[excerpt]], [[first_name]], [[last_name]] or any other field name that you collect.', 'noptin' ) ,
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

Noptin_Settings::init_hooks();
