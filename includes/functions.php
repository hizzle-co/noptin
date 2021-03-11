<?php
/**
 * Core functions
 *
 * Contains core functions.
 *
 * @since             1.0.4
 * @package           Noptin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns a reference to the main Noptin instance.
 *
 * @since 1.0.4
 * @return  Noptin An object containing a reference to Noptin.
 */
function noptin() {
	return Noptin::instance();
}

/**
 * Retrieves all default noptin options
 *
 * @return  array   options
 * @access  public
 * @since   1.0.6
 */
function get_default_noptin_options() {

	$options = array(
		'notify_admin'          => false,
		'double_optin'          => false,
		'from_email'            => noptin()->mailer->default_from_address(),
		'reply_to'              => get_option( 'admin_email' ),
		'from_name'             => get_option( 'blogname' ),
		'company'               => get_option( 'blogname' ),
		'comment_form'          => false,
		'comment_form_msg'      => __( 'Subscribe To Our Newsletter', 'newsletter-optin-box' ),
		'register_form'         => false,
		'register_form_msg'     => __( 'Subscribe To Our Newsletter', 'newsletter-optin-box' ),
		'hide_from_subscribers' => false,
		'success_message'       => __( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
		'email_template'        => 'plain',
	);
	return $options;

}

/**
 * Retrieves all noptin options
 *
 * @return  array   options
 * @access  public
 * @since   1.0.6
 */
function get_noptin_options() {
	global $noptin_options;

	if ( empty( $noptin_options ) ) {
		$noptin_options = get_option( 'noptin_options', array() );
	}

	if ( ! is_array( $noptin_options ) || empty( $noptin_options ) ) {
		$noptin_options = get_default_noptin_options();
	}
	return $noptin_options;
}

/**
 * Retrieves an option from the db
 *
 * @return  mixed|null   option or null
 * @param   string $key The option key.
 * @param   mixed  $default The default value for the option.
 * @access  public
 * @since   1.0.5
 */
function get_noptin_option( $key, $default = null ) {

	$options = get_noptin_options();
	$value   = $default;
	if ( isset( $options[ $key ] ) ) {
		$value = $options[ $key ];
	}

	if ( 'false' === $value ) {
		$value = false;
	}

	if ( 'true' === $value ) {
		$value = true;
	}

	return apply_filters( 'get_noptin_option', $value, $key );

}

/**
 * Updates noptin options
 *
 * @return  void
 * @param   array $options The updated Noptin options.
 * @access  public
 * @since   1.0.5
 */
function update_noptin_options( $options ) {
	global $noptin_options;

	$noptin_options = $options;
	update_option( 'noptin_options', $options );

}

/**
 * Updates a single option
 *
 * @return  void
 * @param   string $key The key to update.
 * @param   mixed  $value The new value.
 * @access  public
 * @since   1.0.5
 */
function update_noptin_option( $key, $value ) {

	$options         = get_noptin_options();
	$options[ $key ] = $value;
	update_noptin_options( $options );

}

/**
 * Returns the noptin action page.
 *
 * This function will always return 0 for new installs and
 * return the old actions page for existing installs.
 *
 * @return  int
 * @access  public
 * @deprecated 1.2.9 We are now using custom wp query vars.
 * @since   1.2.0
 */
function get_noptin_action_page() {
	return (int) get_option( 'noptin_actions_page', 0 );
}

/**
 * Returns the noptin action url
 *
 * @return  sting
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 * @param   string $action The action to execute.
 * @param   string $value  Optional. The value to pass to the action handler.
 * @param   bool   $empty  Optional. Whether or not to use an empty template.
 * @access  public
 * @since   1.0.6
 */
function get_noptin_action_url( $action, $value = false, $empty = false ) {
	global $wp_rewrite;

	$permalink = get_option( 'permalink_structure' );

	// Ugly urls
	if ( empty( $permalink ) ) {
		return add_query_arg(
			array(
				'noptin_newsletter'  => $action,
				'nv'  => $value,
				'nte' => $empty,
			),
			get_home_url()
		);
	}

	// Pretty permalinks.
	$path = $wp_rewrite->root . "noptin_newsletter/$action";

	return add_query_arg(
		array(
			'nv'  => $value,
			'nte' => $empty,
		),
		get_home_url( null, $path)
	);

}

/**
 * Checks if this is a noptin actions page
 *
 * @return  bool
 * @since   1.2.0
 */
function is_noptin_actions_page() {

	$matched_var = get_query_var( 'noptin_newsletter' );

	if ( ! empty( $matched_var ) ) {
		return true;
	}

	// Backwards compatibility.
	$page = get_noptin_action_page();
	return ! empty( $page ) && is_page( $page );
}

/**
 * Retrieves the URL to the forms creation page
 *
 * @return  string   The forms page url
 * @access  public
 * @since   1.0.5
 */
function get_noptin_new_form_url() {
	return admin_url( 'post-new.php?post_type=noptin-form' );
}

/**
 * Retrieves the URL to a forms edit url
 *
 * @return  string   The form edit page url
 * @access  public
 * @since   1.1.1
 */
function get_noptin_edit_form_url( $form_id ) {
	$url = admin_url( 'post.php?action=edit' );
	return add_query_arg( 'post', $form_id, $url );
}

/**
 * Retrieves the URL to the forms overview page
 *
 * @return  string   The forms page url
 * @access  public
 * @since   1.0.5
 */
function get_noptin_forms_overview_url() {
	$url = admin_url( 'edit.php?post_type=noptin-form' );
	return $url;
}

/**
 * Returns opt-in forms field types
 *
 * @return  array
 * @access  public
 * @since   1.0.8
 */
function get_noptin_optin_field_types() {
	return apply_filters( 'noptin_field_types', array() );
}

/**
 * Retrieves an optin form.
 *
 * @param int|Noptin_Form $id The id or Noptin_Form object of the optin to retrieve.
 * @since 1.0.5
 * @return Noptin_Form
 */
function noptin_get_optin_form( $id ) {
	return new Noptin_Form( $id );
}

/**
 * Retrieves the total opt-in forms count.
 *
 * @param string $type Optionally filter by opt-in type.
 * @since 1.0.6
 * @return int
 */
function noptin_count_optin_forms( $type = '' ) {
	global $wpdb;

	$sql   = "SELECT COUNT(`ID`) FROM {$wpdb->posts} as forms";
	$where = "WHERE `post_type`='noptin-form'";

	if ( ! empty( $type ) ) {
		$sql = "$sql LEFT JOIN {$wpdb->postmeta} as meta
			ON meta.post_id = forms.ID
			AND meta.meta_key = '_noptin_optin_type'
			AND meta.meta_value = %s";

		$sql    = $wpdb->prepare( $sql, $type );
		$where .= " AND meta.meta_key='_noptin_optin_type'";
	}

	return $wpdb->get_var( "$sql $where;" );
}

/**
 * Creates an optin form.
 *
 * @since 1.0.5
 */
function noptin_create_optin_form( $data = false ) {
	$form    = new Noptin_Form( $data );
	$created = $form->save();

	if ( is_wp_error( $created ) ) {
		return $created;
	}

	return $form->id;
}


/**
 * Deletes an optin form.
 *
 * @since 1.0.5
 */
function noptin_delete_optin_form( $id ) {
	return wp_delete_post( $id, true );
}

/**
 * Duplicates an optin form.
 *
 * @since 1.0.5
 * @return int
 */
function noptin_duplicate_optin_form( $id ) {
	$form = noptin_get_optin_form( $id );
	$form->duplicate();
	return $form->id;
}

/**
 * Returns all optin forms.
 *
 * @since 1.2.6
 * @return Noptin_Form[]
 */
function get_noptin_optin_forms( array $args = array() ) {
	$defaults = array(
		'numberposts' => -1,
		'post_status' => array( 'draft', 'publish' ),
	);

	$args              = wp_parse_args( $args, $defaults );
	$args['post_type'] = 'noptin-form';
	$args['fields']    = 'ids';
	$forms             = get_posts( $args );

	return array_map( 'noptin_get_optin_form', $forms );

}

/**
 * Returns post types.
 *
 * @since 1.0.4
 */
function noptin_get_post_types() {
	$return     = array();
	$args       = array(
		'public'  => true,
		'show_ui' => true,
	);
	$post_types = get_post_types( $args, 'objects' );

	foreach ( $post_types as $obj ) {
		$return[ $obj->name ] = $obj->label;
	}
	unset( $return['attachment'] );

	return $return;

}

/**
 * Checks whether subscription forms should be displayed.
 *
 * @since 1.0.7
 * @return bool
 */
function noptin_should_show_optins() {

	if ( get_noptin_option( 'hide_from_subscribers' ) && noptin_is_subscriber() ) {
		return false;
	}

	if ( ! empty( $_COOKIE['noptin_hide'] ) ) {
		return false;
	}

	return true;

}

/**
 * Returns opt-in forms stats.
 *
 * @since 1.0.7
 * @return array
 */
function noptin_get_optin_stats() {
	global $wpdb;
	$table = get_noptin_subscribers_meta_table_name();
	$sql   = "SELECT `meta_value`, COUNT( DISTINCT `noptin_subscriber_id`) AS stats FROM `$table` WHERE `meta_key`='_subscriber_via' GROUP BY `meta_value`";
	$stats = $wpdb->get_results( $sql );

	if ( ! $stats ) {
		$stats = array();
	}

	return wp_list_pluck( $stats, 'stats', 'meta_value' );

}


/**
 * Returns color themess.
 *
 * @since 1.0.7
 * @return array
 */
function noptin_get_color_themes() {
	return apply_filters(
		'noptin_form_color_themes',
		array(
			'#e51c23 #fafafa #c62828' => __( 'Red', 'newsletter-optin-box' ), // Base color, Secondary color, border color.
			'#e91e63 #fafafa #ad1457' => __( 'Pink', 'newsletter-optin-box' ),
			'#9c27b0 #fafafa #6a1b9a' => __( 'Purple', 'newsletter-optin-box' ),
			'#673ab7 #fafafa #4527a0' => __( 'Deep Purple', 'newsletter-optin-box' ),
			'#9c27b0 #fafafa #4527a0' => __( 'Purple', 'newsletter-optin-box' ),
			'#3f51b5 #fafafa #283593' => __( 'Indigo', 'newsletter-optin-box' ),
			'#2196F3 #fafafa #1565c0' => __( 'Blue', 'newsletter-optin-box' ),
			'#03a9f4 #fafafa #0277bd' => __( 'Light Blue', 'newsletter-optin-box' ),
			'#00bcd4 #fafafa #00838f' => __( 'Cyan', 'newsletter-optin-box' ),
			'#009688 #fafafa #00695c' => __( 'Teal', 'newsletter-optin-box' ),
			'#4CAF50 #fafafa #2e7d32' => __( 'Green', 'newsletter-optin-box' ),
			'#8bc34a #191919 #558b2f' => __( 'Light Green', 'newsletter-optin-box' ),
			'#cddc39 #191919 #9e9d24' => __( 'Lime', 'newsletter-optin-box' ),
			'#ffeb3b #191919 #f9a825' => __( 'Yellow', 'newsletter-optin-box' ),
			'#ffc107 #191919 #ff6f00' => __( 'Amber', 'newsletter-optin-box' ),
			'#ff9800 #fafafa #e65100' => __( 'Orange', 'newsletter-optin-box' ),
			'#ff5722 #fafafa #bf360c' => __( 'Deep Orange', 'newsletter-optin-box' ),
			'#795548 #fafafa #3e2723' => __( 'Brown', 'newsletter-optin-box' ),
			'#607d8b #fafafa #263238' => __( 'Blue Grey', 'newsletter-optin-box' ),
			'#313131 #fafafa #607d8b' => __( 'Black', 'newsletter-optin-box' ),
			'#ffffff #191919 #191919' => __( 'White', 'newsletter-optin-box' ),
			'#aaaaaa #191919 #191919' => __( 'Grey', 'newsletter-optin-box' ),
		)
	);

}

/**
 * Returns optin templates.
 *
 * @since 1.0.7
 * @return array
 */
function noptin_get_optin_templates() {
	$custom_templates  = get_option( 'noptin_templates' );
	$inbuilt_templates = include locate_noptin_template( 'optin-templates.php' );

	if ( ! is_array( $custom_templates ) ) {
		$custom_templates = array();
	}

	$templates = array_merge( $custom_templates, $inbuilt_templates );

	return apply_filters( 'noptin_form_templates', array_map( 'noptin_convert_classic_template', $templates ) );

}

/**
 * Returns opt-in form properties.
 *
 * @since 1.0.5
 * @return array
 */
function noptin_get_form_design_props() {
	return apply_filters(
		'noptin_form_design_props',
		array(
			'hideCloseButton',
			'borderSize',
			'gdprCheckbox',
			'gdprConsentText',
			'titleTypography',
			'titleAdvanced',
			'descriptionAdvanced',
			'descriptionTypography',
			'prefixTypography',
			'prefixAdvanced',
			'noteTypography',
			'noteAdvanced',
			'hideFields',
			'id',
			'imageMainPos',
			'closeButtonPos',
			'singleLine',
			'formRadius',
			'formWidth',
			'formHeight',
			'fields',
			'imageMain',
			'image',
			'imagePos',
			'noptinButtonLabel',
			'buttonPosition',
			'noptinButtonBg',
			'noptinButtonColor',
			'hideTitle',
			'title',
			'titleColor',
			'hidePrefix',
			'prefix',
			'prefixColor',
			'hideDescription',
			'description',
			'descriptionColor',
			'hideNote',
			'hideOnNoteClick',
			'note',
			'noteColor',
			'CSS',
			'optinType',
		)
	);

}

/**
 * Returns form field props.
 *
 * @since 1.0.5
 * @return array
 */
function noptin_get_form_field_props() {
	return apply_filters( 'noptin_form_field_props', array( 'fields', 'fieldTypes' ) );
}

/**
 * Function noptin editor localize.
 *
 * @param array $state the current editor state.
 * @since 1.0.5
 * @return void
 */
function noptin_localize_optin_editor( $state ) {
	$props   = noptin_get_form_design_props();
	$props[] = 'DisplayOncePerSession';
	$props[] = 'timeDelayDuration';
	$props[] = 'scrollDepthPercentage';
	$props[] = 'cssClassOfClick';
	$props[] = 'triggerPopup';
	$props[] = 'slideDirection';

	$params = array(
		'ajaxurl'      => admin_url( 'admin-ajax.php' ),
		'api_url'      => get_home_url( null, 'wp-json/wp/v2/' ),
		'nonce'        => wp_create_nonce( 'noptin_admin_nonce' ),
		'data'         => $state,
		'templates'    => noptin_get_optin_templates(),
		'color_themes' => array_flip( noptin_get_color_themes() ),
		'design_props' => $props,
		'field_props'  => noptin_get_form_field_props(),
	);

	$params = apply_filters( 'noptin_form_editor_params', $params );

	wp_localize_script( 'noptin-optin-editor', 'noptinEditor', $params );
}

/**
 * Function noptin editor localize.
 *
 * @since 1.0.5
 */
function noptin_form_template_form_props() {

	$class = "singleLine ? 'noptin-form-single-line' : 'noptin-form-new-line'";
	$style = 'background-color:rgba(0,0,0,0)';

	return " @submit.prevent :class=\"$class\"";
}

/**
 * This will replace the first half of a string with "*" characters.
 *
 * @param string $string The string to obfuscate.
 * @since 1.1.0
 * @return string
 */
function noptin_obfuscate_string( $string ) {
	$length            = strlen( $string );
	$obfuscated_length = ceil( $length / 2 );
	$string            = str_repeat( '*', $obfuscated_length ) . substr( $string, $obfuscated_length );
	return $string;
}

/**
 * Callback to obfuscate an email address.
 *
 * @param string $m The mail to obfuscate.
 * @internal
 * @ignore
 */
function _noptin_obfuscate_email_addresses_callback( $m ) {
	$one   = $m[1] . str_repeat( '*', strlen( $m[2] ) );
	$two   = $m[3] . str_repeat( '*', strlen( $m[4] ) );
	$three = $m[5];
	return sprintf( '%s@%s.%s', $one, $two, $three );
}

/**
 * Obfuscates email addresses in a string.
 *
 * @param string $string possibly containing email address.
 * @since 1.1.0
 * @return string
 */
function noptin_obfuscate_email_addresses( $string ) {
	return preg_replace_callback( '/([\w\.]{1,4})([\w\.]*)\@(\w{1,2})(\w*)\.(\w+)/', '_noptin_obfuscate_email_addresses_callback', $string );
}

/**
 * Returns a link to add a new newsletter campaign.
 *
 * @since 1.2.0
 * @return string
 */
function get_noptin_new_newsletter_campaign_url() {

	$param = array(
		'page'        => 'noptin-email-campaigns',
		'section'     => 'newsletters',
		'sub_section' => 'new_campaign',
	);
	return add_query_arg( $param, admin_url( '/admin.php' ) );

}

/**
 * Returns a link to edit a newsletter.
 *
 * @since 1.2.0
 * @param int $id The campaign's id.
 * @return string.
 */
function get_noptin_newsletter_campaign_url( $id ) {

	$param = array(
		'page'        => 'noptin-email-campaigns',
		'section'     => 'newsletters',
		'sub_section' => 'edit_campaign',
		'id'          => $id,
	);
	return add_query_arg( $param, admin_url( '/admin.php' ) );

}

/**
 * Returns a link to edit an automation campaign.
 *
 * @since 1.2.0
 * @param int $id The campaign's id.
 * @return string.
 */
function get_noptin_automation_campaign_url( $id ) {

	$param = array(
		'page'        => 'noptin-email-campaigns',
		'section'     => 'automations',
		'sub_section' => 'edit_campaign',
		'id'          => $id,
	);
	return add_query_arg( $param, admin_url( '/admin.php' ) );

}

/**
 * Checks if a given post is a noptin campaign.
 *
 * @param int|WP_Post $post The post to check for.
 * @param bool|string $campaign_type Optional. Specify if you need to check for a specific campaign type.
 * @since 1.2.0
 * @return bool.
 */
function is_noptin_campaign( $post, $campaign_type = false ) {

	$campaign = get_post( $post );

	if ( empty( $campaign ) || 'noptin-campaign' !== $campaign->post_type ) {
		return false;
	}

	if ( empty( $campaign_type ) ) {
		return true;
	}

	return trim( $campaign_type ) === get_post_meta( $campaign->ID, 'campaign_type', true );

}

/**
 * Returns the default newsletter subject.
 *
 * @since 1.2.0
 * @return string
 */
function get_noptin_default_newsletter_subject() {

	$subject = '';

	/**
	 * Filters the default newsletter subject
	 *
	 * @param string $subject The default newsletter subject
	 */
	return apply_filters( 'noptin_default_newsletter_subject', $subject );

}

/**
 * Returns the default newsletter preview text.
 *
 * @since 1.2.0
 * @return string
 */
function get_noptin_default_newsletter_preview_text() {

	$preview_text = '';

	/**
	 * Filters the default newsletter preview text
	 *
	 * @param string $preview_text The default newsletter preview text
	 */
	return apply_filters( 'noptin_default_newsletter_preview_text', $preview_text );

}

/**
 * Returns the default newsletter body.
 *
 * @since 1.2.0
 * @return string.
 */
function get_noptin_default_newsletter_body() {

	$body = include locate_noptin_template( 'default-email-body.php' );

	/**
	 * Filters the default newsletter body
	 *
	 * @param string $body The default newsletter body
	 */
	return apply_filters( 'noptin_default_newsletter_body', $body );

}

/**
 * Returns a path to the includes dir.
 *
 * @param string $append The path to append to the include dir path.
 * @return string
 * @since 1.2.0
 */
function get_noptin_include_dir( $append = '' ) {
	return get_noptin_plugin_path( "includes/$append" );
}

/**
 * Returns a path to the noptin dir.
 *
 * @since 1.2.3
 * @param string $append The path to append to the include dir path.
 * @return string
 */
function get_noptin_plugin_path( $append = '' ) {
	return plugin_dir_path( Noptin::$file ) . $append;
}

/**
 * Includes a file.
 *
 * @param string $file The file path.
 * @param array  $args Defaults to an empty array.
 * @since 1.2.0
 */
function noptin_ob_get_clean( $file, $args = array() ) {

	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $args );
	ob_start();
	include $file;
	return ob_get_clean();

}

/**
 *  Returns the appropriate capability to check against
 *
 * @since 1.2.2
 * @return string capability to check against
 * @param string $capalibilty Optional. The alternative capability to check against.
 */
function get_noptin_capability( $capalibilty = 'manage_noptin' ) {

	if ( current_user_can( 'manage_options' ) ) {
		return 'manage_options';
	};

	return $capalibilty;
}

/**
 * Gets and includes template files.
 *
 * @since 1.2.2
 * @param mixed  $template_name The file name of the template to load.
 * @param array  $args (default: array()).
 * @param string $template_path (default: 'noptin').
 * @param string $default_path (default: 'templates').
 */
function get_noptin_template( $template_name, $args = array(), $template_path = 'noptin', $default_path = '' ) {

	$the_template_path = locate_noptin_template( $template_name, $template_path, $default_path );

	if ( ! empty( $the_template_path ) ) {

		if ( $args && is_array( $args ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Please, forgive us.
			extract( $args );
		}

		include $the_template_path;
	}

}

/**
 * Locates a template and returns the path for inclusion.
 *
 * This is the load order:
 *
 *      yourtheme       /   $template_path  /   $template_name
 *      yourtheme       /   noptin-$template_name
 *      $default_path   /   $template_name
 *
 * @since 1.2.2
 * @param string      $template_name The template's file name.
 * @param string      $template_path (default: 'noptin').
 * @param string|bool $default_path (default: 'templates') False to not load a default.
 * @return string
 */
function locate_noptin_template( $template_name, $template_path = 'noptin', $default_path = '' ) {

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			"noptin-$template_name",
		)
	);

	// Get default template.
	if ( ! $template && false !== $default_path ) {

		if ( empty( $default_path ) ) {
			$default_path = get_noptin_plugin_path( 'templates' );
		}

		if ( file_exists( trailingslashit( $default_path ) . $template_name ) ) {
			$template = trailingslashit( $default_path ) . $template_name;
		}
	}

	// Return what we found.
	return apply_filters( 'locate_noptin_template', $template, $template_name, $template_path, $default_path );
}

/**
 * Get current user IP Address.
 *
 * @since 1.2.3
 * @return string
 */
function noptin_get_user_ip() {

	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
	}
	
	if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
	}
	
	if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	return '';
}

/**
 * GeoLocates an ip address.
 *
 * @since 1.2.3
 * @param string $ip_address Optional. The ip address to located. Default's to the current user's IP Address.
 * @return bool|array
 */
function noptin_locate_ip_address( $ip_address = '' ) {

	// Prepare ip address.
	if ( empty( $ip_address ) ) {
		$ip_address = noptin_get_user_ip();
	}

	// Ensure that it is valid.
	if (  empty( $ip_address ) || ! rest_is_ip_address( $ip_address ) ) {
		return false;
	}

	// Try fetching from the cache.
	$transient_name = md5( "noptin_geolocation_cache_$ip_address" );

	if ( get_transient( $transient_name ) ) {
		return get_transient( $transient_name );
	}

	// Retrieve API key.
	$api_key = get_noptin_option( 'ipgeolocation_io_api_key' );

	if ( empty( $api_key ) ) {
		return noptin_locate_ip_address_alt( $ip_address );
	}

	// Geolocate the ip.
	$url      = add_query_arg(
		array(
			'apiKey' => $api_key,
			'ip'     => $ip_address,
			'fields' => 'city,continent_name,country_name,state_prov,zipcode,country_flag,currency,time_zone,latitude,longitude,calling_code',
		),
		'https://api.ipgeolocation.io/ipgeo'
	);
	$response = wp_remote_get( $url );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$geo = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $geo ) ) {
		log_noptin_message( __( 'Error fetching GeoLocation information.', 'newsletter-optin-box' ) );
		return false;
	}

	if ( ! empty( $geo['time_zone'] ) ) {
		$geo['time zone'] = $geo['time_zone']['name'] . ' GMT ' . $geo['time_zone']['offset'];
	}

	if ( ! empty( $geo['currency'] ) ) {
		$geo['currency'] = $geo['currency']['name'];
	}

	if ( ! empty( $geo['continent_name'] ) ) {
		$geo['continent'] = $geo['continent_name'];
		unset( $geo['continent_name'] );
	}

	if ( ! empty( $geo['country_name'] ) ) {
		$geo['country'] 		= $geo['country_name'];
		unset( $geo['country_name'] );
	}

	if ( ! empty( $geo['state_prov'] ) ) {
		$geo['state'] 			= $geo['state_prov'];
		unset( $geo['state_prov'] );
	}

	$fields = noptin_clean( $geo );
	set_transient( $transient_name, $fields, HOUR_IN_SECONDS );
	return $fields;

}

/**
 * Alternate for geolocating an ip address.
 *
 * @since 1.3.1
 * @return bool|array
 */
function noptin_locate_ip_address_alt( $ip_address ) {

	// Ensure that we have an IP.
	if ( empty( $ip_address ) ) {
		return false;
	}

	// Maybe fetch from cache.
	$transient_name = md5( "noptin_geolocation_cache_$ip_address" );

	if ( get_transient( $transient_name ) ) {
		return get_transient( $transient_name );
	}

	// Retrieve API key.
	$geo = wp_remote_get( esc_url( "http://ip-api.com/json/$ip_address?fields=9978329" ) );

	if ( is_wp_error( $geo ) ) {
		return false;
	}

	// Prepare the data.
	$geo = json_decode( wp_remote_retrieve_body( $geo ) );
	if ( empty( $geo ) || 'success' !== $geo->status ) {
		log_noptin_message( __( 'Error fetching GeoLocation information.', 'newsletter-optin-box' ) );
		return false;
	}

	$location = array(
		'continent' => $geo->continent,
		'country'   => $geo->country,
		'state'     => $geo->regionName,
		'city'      => $geo->city,
		'latitude'  => $geo->lat,
		'longitude' => $geo->lon,
		'time zone' => $geo->timezone,
		'currency'  => $geo->currency,
	);

	$location = noptin_clean( $location );
	set_transient( $transient_name, $location, HOUR_IN_SECONDS );
	return $location;

}

/**
 * Cleans up an array, comma- or space-separated list of scalar values.
 *
 * @since 1.2.3
 *
 * @param array|string $list List of values.
 * @return array Sanitized array of values.
 */
function noptin_parse_list( $list ) {
	if ( ! is_array( $list ) ) {
		return preg_split( '/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY );
	}

	return $list;
}

/**
 * Cleans up an array, comma- or space-separated list of integer values.
 *
 * @since 1.2.4
 *
 * @param array|string $list List of values.
 * @return array Sanitized array of values.
 */
function noptin_parse_int_list( $list, $cb = 'absint' ) {
	return array_map( $cb, noptin_parse_list( $list ) );
}

/**
 * Parses an array, comma- or space-separated list of post ids and urls.
 *
 * @since 1.2.4
 *
 * @param array|string $list List of values.
 * @return array Sanitized array of values.
 */
function noptin_parse_post_list( $list ) {
	
	// Convert to array.
	$list = noptin_parse_list( $list );

	// Treat numeric values as ids.
	$ids  = array_filter( $list, 'is_numeric' );

	// Assume the rest to be urls.
	$urls = array_diff( $list, $ids );
	
	// Return an array or ids and urls
	return array(
		'ids'  => array_map( 'absint', $ids ), // convert to integers.
		'urls' => array_map( 'noptin_clean_url', $urls ), // clean the urls.
	);
}

/**
 * Wrapper for is_singular() that takes post ids and urls as a parameter instead of post types.
 *
 * @since 1.2.4
 *
 * @param array|string $posts Array or comma/space-separrated List of post ids and urls to check against.
 * @return bool
 */
function noptin_is_singular( $posts = '' ) {

	// Looking for any single page.
	if ( empty( $posts ) ) {
		return is_singular();
	}

	// Parse the list into ids and urls.
	$posts = noptin_parse_post_list( $posts );

	// Check if the current post is in one of the post ids.
	$ids   = $posts['ids'];
	if ( ! empty( $ids ) && ( is_single( $ids ) || is_page( $ids ) || is_attachment( $ids ) ) ) {
		return true;
	}

	// Check if current url is in one of the urls.
	return in_array( noptin_clean_url(), $posts['urls'], true );

}

/**
 * Returns the hostname and path of a url.
 *
 * @since 1.2.4
 *
 * @param string $url The url to parse.
 * @return string
 */
function noptin_clean_url( $url = '' ) {

	// If no url is passed, use the current url.
	if ( empty( $url ) ) {
		$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	// Remove query variables
	$clean_url = strtok( $url, '?' );

	// Remove the scheme and www parts.
	$clean_url = preg_replace('#^(http(s)?://)?(www\.)?(.+\.)#i', '$4', $clean_url );

	// Take care of edge cases
	$clean_url = preg_replace('#^http(s)?://#i', '', $clean_url );

	// remove forwad slash at the end of the url
	$clean_url = strtolower( untrailingslashit( $clean_url ) );

	return apply_filters( 'noptin_clean_url', $clean_url, $url ); 
}

/**
 * Clean variables using sanitize_text_field.
 *
 * @param string|array $var Data to sanitize.
 * @since 1.2.3
 * @return string|array
 */
function noptin_clean( $var ) {

	if ( is_array( $var ) ) {
		return array_map( 'noptin_clean', $var );
	}

	if ( is_object( $var ) ) {
		$object_vars = get_object_vars( $var );
		foreach ( $object_vars as $property_name => $property_value ) {
			$var->$property_name = noptin_clean( $property_value );
		}
		return $var;
	}

	return is_string( $var ) ? sanitize_text_field( $var ) : $var;
}

/**
 * Logs a message.
 *
 * @since 1.2.3
 * @param mixed  $message The message to log.
 * @param string $code   Optional. The error code.
 * @see get_logged_noptin_messages
 * @return bool.
 */
function log_noptin_message( $message, $code = 'error' ) {

	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	$message = noptin_clean( $message );

	// Next, retrieve the array of existing logged messages.
	$messages   = get_logged_noptin_messages();

	// Add our message.
	$messages[] = array(
		'level'	=> $code,
		'msg'	=> $message,
		'time'	=> current_time( 'mysql' ),
	);

	// Then save to the database.
	return update_option( 'noptin_logged_messages', $messages );

}

/**
 * Logs a message.
 *
 * @since 1.2.3
 * @see log_noptin_message
 * @return array.
 */
function get_logged_noptin_messages() {

	// Retrieve the logged messages.
	$messages = get_option( 'noptin_logged_messages', array() );

	// Ensure it is an array...
	if ( ! is_array( $messages ) ) {
		$messages = array();
	}

	// ... of no more than 20 elements.
	if ( 20 < count( $messages ) ) {
		$messages   = array_slice( $messages, -20 );
		update_option( 'noptin_logged_messages', $messages );
	}

	$prepared = array();

	foreach ( $messages as $message ) {
		if ( ! is_scalar( $message['msg'] ) ) {
			$message['msg'] = print_r( $message['msg'], true );
		}
		$prepared[] = $message;
	}

	return $prepared;

}

/**
 * Sanitizes a slug
 *
 * @since 1.2.4
 * @param array|string $slug The slug to sanitize.
 * @return string|string[].
 */
function noptin_sanitize_title_slug( $slug = '' ) {
	$slug = str_ireplace( array( '_', '-' ), ' ', $slug );
	$slug = map_deep( $slug, 'ucwords' );
	return noptin_clean( $slug );
}

/**
 *  Returns a list of all form fields.
 *
 * @since 1.3.1
 * @return array An array of subscriber fields.
 */
function get_special_noptin_form_fields() {

	// array of fields.
	$fields = array();

	// Retrieve subscription forms.
	$forms = get_posts(
		array(
        	'numberposts' => -1,
        	'post_status' => array( 'draft', 'publish' ),
        	'post_type'   => 'noptin-form',
        	'fields'      => 'ids',
		)
	);

	// Ignore some fields.
	$to_ignore = array(
        'email',
        'first_name',
        'last_name',
        'name',
        'GDPR_consent'
	);
	$to_ignore = apply_filters( 'noptin_special_fields_to_ignore', $to_ignore );

	foreach ( $forms as $form ) {

        // Retrieve state.
        $state = get_post_meta( $form, '_noptin_state', true );
		if ( ! is_array( $state ) ) {
			continue;
        }

        if ( empty( $state['fields'] ) ||  ! is_array( $state['fields'] ) ) {
			continue;
        }

        foreach ( $state['fields'] as $field ) {
            $name  = $field['type']['name'];
            $type  = $field['type']['type'];
            $label = $field['type']['label'];

            if ( in_array( $name, $to_ignore, true ) || in_array( $type, $to_ignore, true ) ) {
                continue;
            }

            if ( 'text' !== $name && 'checkbox' !== $type ) {
                $label = $name;
            }

            $fields[ $name ] = array(
                $type,
                $label
            );
        }

    }

	return apply_filters( 'special_noptin_form_fields', $fields );
}

/**
 * Creates and returns a new task object.
 *
 * Note that this does not run the task. You will have to manually run it.
 *
 * @since 1.2.7
 * @see Noptin_Task
 *
 * @param array $args Required. A numerical array of task args.
 *                    The first item is the name of the action while the other
 *                    arguments will be passed to the action callbacks as parameters.
 * @return Noptin_Task
 */
function create_noptin_task( array $args ) {

	// Create a new task.
	$task = new Noptin_Task( array_shift( $args ) );

	// Maybe attach some params to the task.
	return $task->set_params( $args );

}

/**
 * Enqueue an action to run as soon as possible in the background.
 *
 * This is a wrapper for `do_action()`.
 *
 * You can pass extra arguments to the hooks, much like you can with `do_action()`.
 *
 * Example usage:
 *
 *     // The action callback function.
 *     function log_name( $name ) {
 *         // Log the name.
 *         log_noptin_message( $name, 'notice' );
 *     }
 *     add_action( 'log_name_in_the_background', 'log_name', 10, 1 );
 *
 *      // Ask Noptin to fire the hook in the background.
 *      do_noptin_background_action( 'log_name_in_the_background', 'Brian');
 *
 * @since 1.2.7
 * @see Noptin_Task
 * @see create_noptin_task
 *
 * @param string $tag    (required). Name of the action hook. Default: none.
 * @param mixed  ...$arg Optional. Additional arguments to pass to callbacks when the hook triggers.
 *  @return int|bool The action id on success. False otherwise.
 */
function do_noptin_background_action() {
	return create_noptin_task( func_get_args() )->do_async();
}

/**
 * Schedule an action to run once at some defined point in the future.
 *
 * This is similar to `do_noptin_background_action()` except that the
 * background task fires in the future instead of immeadiately.
 *
 * You can pass extra arguments to the hooks, much like you can with `do_action()`.
 *
 * Example usage:
 *
 *     // The action callback function.
 *     function log_name( $name ) {
 *         // Log the name.
 *         log_noptin_message( $name, 'notice' );
 *     }
 *     add_action( 'log_name_after_a_day', 'log_name', 10, 1 );
 *
 *      // Ask Noptin to fire the hook in in the future.
 *      schedule_noptin_background_action( strtotime( '+1 day' ), 'log_name_after_a_day', 'Brian');
 *
 * @since 1.2.7
 * @see Noptin_Task
 * @see create_noptin_task
 *
 * @param int    $timestamp (required) The Unix timestamp representing the date
 *                          you want the action to run. Default: none.
 * @param string $tag       (required) Name of the action hook. Default: none.
 * @param mixed  ...$arg    Optional. Additional arguments to pass to callbacks when the hook triggers. Default none.
 *  @return int|bool The action id on success. False otherwise.
 */
function schedule_noptin_background_action() {
	$args      = func_get_args();
	$timestamp = array_shift( $args );
	return create_noptin_task( $args )->do_once( $timestamp );
}

/**
 * Schedule an action to run repeatedly with a specified interval in seconds.
 *
 * This is similar to `schedule_noptin_background_action()` except that the
 * background task fires repeatedly.
 *
 * You can pass extra arguments to the hooks, much like you can with `do_action()`.
 *
 * Example usage:
 *
 *     // The action callback function.
 *     function log_name( $name ) {
 *         // Log the name.
 *         log_noptin_message( $name, 'notice' );
 *     }
 *     add_action( 'log_name_every_day', 'log_name', 10, 1 );
 *
 *      // Ask Noptin to fire the hook every x seconds from tomorrow.
 *      schedule_noptin_background_action( DAY_IN_SECONDS, strtotime( '+1 day' ), 'log_name_every_day', 'Brian');
 *
 * @since 1.2.7
 * @see Noptin_Task
 * @see create_noptin_task
 *
 * @param int    $interval  (required) How long ( in seconds ) to wait between runs. Default: none.
 * @param int    $timestamp (required) The Unix timestamp representing the date you
 *                          want the action to run for the first time. Default: none.
 * @param string $tag       (required) Name of the action hook. Default: none.
 * @param mixed  ...$arg    Optional. Additional arguments to pass to callbacks when the hook triggers. Default none.
 * @return int|bool The action id on success. False otherwise.
 */
function schedule_noptin_recurring_background_action() {
	$args      = func_get_args();
	$interval  = array_shift( $args );
	$timestamp = array_shift( $args );
	return create_noptin_task( $args )->do_recurring( $timestamp, $interval );
}

/**
 * Cancels a scheduled action.
 *
 * This is useful if you need to cancel an action that you had previously scheduled via:-
 * - `do_noptin_background_action()`
 * - `schedule_noptin_background_action()`
 * - `schedule_noptin_recurring_background_action()`
 * 
 * Pass `all` as the only argument to cancel all actions scheduled by the above functions.
 *
 * @since 1.2.7
 * @see Noptin_Task
 * @see create_noptin_task
 * @see do_noptin_background_action
 * @see schedule_noptin_background_action
 * @see schedule_noptin_recurring_background_action
 *
 * @param int|string|array    $action_name_id_or_array (required) The action to cancel. Accepted args:-
 *                             - **'all'** Cancel all actions.
 *                             - **$hook_name** Pass a string to cancel all actions using that hook.
 *                             - **$action_id** Pass an integer to cancel an action by its id.
 *                             - **$array** You can also pass an array of the above. If any element in the
 *                               array can't be canceled, the function will return false.
 *
 * @return bool True on success. False otherwise.
 */
function cancel_scheduled_noptin_action( $action_name_id_or_array ) {

	// Ensure the AS db store helper exists.
	if ( class_exists( 'ActionScheduler_DBStore' ) ) {
		return false;
	}

	// In case the developer wants to cancel all actions.
	if ( 'all' === $action_name_id_or_array ) {
		ActionScheduler_DBStore::instance()->cancel_actions_by_group( 'noptin' );
		return true;
	}

	// In case the developer wants to cancel an action by id.
	if ( is_numeric( $action_name_id_or_array ) ) {

		try {
			ActionScheduler_DBStore::instance()->cancel_action( ( int ) $action_name_id_or_array );
			return true;
		} catch ( InvalidArgumentException $e ) {
			log_noptin_message( $e->getMessage() );
			return false;
		}
		
	}

	// Developers can also cancel an action by a hook name.
	if ( is_string( $action_name_id_or_array ) ) {
		ActionScheduler_DBStore::instance()->cancel_actions_by_hook( $action_name_id_or_array );
		return true;
	}

	// You can also pass in an array of hooks/action ids.
	if ( is_array( $action_name_id_or_array ) ) {
		$result = array_map( 'cancel_scheduled_noptin_action', $action_name_id_or_array );
		return ! in_array( false, $result, true );
	}

	// We have an invalid argument.
	return false;
}

/**
 * Is built with Elementor.
 *
 * Check whether the post was built with Elementor.
 *
 * @since 1.3.2
 *
 * @param int $post_id Post ID.
 *
 * @return bool Whether the post was built with Elementor.
 */
function noptin_is_page_built_with_elementor( $post_id ) {
	return ! ! get_post_meta( $post_id, '_elementor_edit_mode', true );
}

/**
 * Pretty prints a variable's data.
 *
 * @since 1.3.3
 *
 * @param mixed $data The data to print.
 */
function noptin_dump( $data ) {
	echo '<pre>';
	var_dump( $data );
	echo '</pre>';
}

/**
 * Checks whether the automation rules table exists
 *
 * @since 1.3.3
 * @return bool
 */
function noptin_automation_rules_table_exists() {
	global $wpdb;
	$table = get_noptin_automation_rules_table_name();

	return $table === $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
}

/**
 *  Returns the name of the automation rules table
 *
 * @since 1.3.3
 * @return string The name of our subscribers meta table
 */
function get_noptin_automation_rules_table_name() {
	return $GLOBALS['wpdb']->prefix . 'noptin_automation_rules';
}

/**
 *  Checks whether we should verify nonces when processing subscription forms.
 *
 * @since 1.3.3
 * @return bool
 */
function noptin_verify_subscription_nonces() {
	return apply_filters( 'noptin_verify_nonce', NOPTIN_VERIFY_NONCE );
}

/**
 *  Converts a classic template to the new editor.
 *
 * @since 1.3.3
 * @return array
 */
function noptin_convert_classic_template( $template ) {

	$new_fields = array(

		'titleTypography'       => array(
			'font_size'      => '30',
			'font_weight'    => '700',
			'line_height'    => '1.5',
			'decoration'     => '',
			'style'          => '',
			'generated'      => 'font-size: 30px; font-weight: 700; line-height: 1.5;',
		),

		'titleAdvanced'         => array(
			'margin' => new stdClass(),
			'padding' => array(
				'top' => '4'
			),
			'generated' => 'padding-top: 4px;',
			'classes'     => ''
		),

		'hidePrefix'           => true,
		'prefix'                 => __( 'Prefix', 'newsletter-optin-box' ),
		'prefixColor'            => '#313131',
		'prefixTypography'       => array(
			'font_size'      => '20',
			'font_weight'    => '500',
			'line_height'    => '1.3',
			'decoration'     => '',
			'style'          => '',
			'generated'      => 'font-size: 20px; font-weight: 500; line-height: 1.3;',
		),

		'prefixAdvanced'         => array(
			'margin' => new stdClass(),
			'padding' => array(
				'top' => '4'
			),
			'generated' => 'padding-top: 4px;',
			'classes'     => ''
		),

		'descriptionTypography' => array(
			'font_size'      => '16',
			'font_weight'    => '500',
			'line_height'    => '1.3',
			'decoration'     => '',
			'style'          => '',
			'generated'      => 'font-size: 16px; font-weight: 500; line-height: 1.3;',
		),

		'descriptionAdvanced'         => array(
			'padding' => new stdClass(),
			'margin' => array(
				'top' => '18'
			),
			'generated' => 'margin-top: 18px;',
			'classes'     => ''
		),

		'noteTypography'       => array(
			'font_size'      => '14',
			'font_weight'    => '400',
			'line_height'    => '1',
			'decoration'     => '',
			'style'          => '',
			'generated'      => 'font-size: 14px; font-weight: 400; line-height: 1;',
		),

		'noteAdvanced'       => array(
			'padding' => new stdClass(),
			'margin'  => array(
				'top' => '10'
			),
			'generated' => 'margin-top: 10px;',
			'classes'     => ''
		),

	);

	$data = $template['data'];

	foreach ( $new_fields as $key => $value ) {
		if ( ! isset( $data[ $key ] ) ) {
			$data[ $key ] = $value;
		}
	}

	// Convert the borders.
	if ( empty( $data['formBorder'] ) || ! is_array( $data['formBorder'] ) ) {
		$data['formBorder'] = array(
			'style'         => 'solid',
			'border_radius' => isset( $data['formRadius'] ) ? intval( $data['formRadius'] ) : 0,
			'border_width'  => isset( $data['borderSize'] ) ? intval( $data['borderSize'] ) : 4,
			'border_color'  => isset( $data['noptinFormBorderColor'] ) ? $data['noptinFormBorderColor'] : '#ffffff',
		);

		extract( $data['formBorder'] );
		$data['formBorder']['generated'] = "border-style: solid; border-radius: {$border_radius}px; border-width: {$border_width}px; border-color: {$border_color};";
	}

	$template['data'] = $data;
	return $template;
}
