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
defined( 'ABSPATH' ) || exit;

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
 * Retrieves all noptin options
 *
 * @return  array   options
 * @access  public
 * @since   1.0.6
 */
function get_noptin_options() {
	$options = get_option( 'noptin_options', array() );

	if ( ! is_array( $options ) || empty( $options ) ) {
		$options = array(
			'success_message' => __( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
		);
	}

	return $options;
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

	$value = map_deep( $value, 'noptin_sanitize_booleans' );
	$value = apply_filters( "get_noptin_option_$key", $value );
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
 * Returns the noptin action url
 *
 * @return  string
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 * @param   string $action The action to execute.
 * @param   string $value  Optional. The value to pass to the action handler.
 * @param   bool   $empty  Optional. Whether or not to use an empty template.
 * @access  public
 * @since   1.0.6
 */
function get_noptin_action_url( $action, $value = false, $empty = false ) {

	return add_query_arg(
		array(
			'noptin_ns' => $action,
			'nv'        => $value,
			'nte'       => $empty,
		),
		get_home_url()
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
	return ! empty( $_GET['noptin_ns'] ) || ! empty( $matched_var );
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

	if ( noptin_is_preview() ) {
		return true;
	}

	if ( get_noptin_option( 'hide_from_subscribers', false ) && noptin_is_subscriber() ) {
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
 * Converts a comma- or space-separated list of scalar values into an array.
 *
 * @since 1.2.3
 *
 * @param array|string $list List of values.
 * @param bool $strict Whether to only split on commas.
 * @return array Sanitized array of values.
 */
function noptin_parse_list( $list, $strict = false ) {

	if ( ! is_array( $list ) ) {

		if ( $strict ) {
			$list = preg_split( '/,+/', $list, -1, PREG_SPLIT_NO_EMPTY );
		} else {
			$list = preg_split( '/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY );
		}

	}

	return map_deep( $list, 'trim' );
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
 * Cleans all non-iterable elements of an array or an object.
 *
 * @param string|array|object $var Data to sanitize.
 * @since 1.2.3
 * @return string|array
 */
function noptin_clean( $var ) {
	return map_deep( $var, 'sanitize_text_field' );
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
 * @deprecated
 */
function get_special_noptin_form_fields() {

	$fields = array();
	foreach ( get_noptin_custom_fields() as $custom_field ) {

		if ( empty( $custom_field['predefined'] ) ) {
			$fields[ $custom_field['merge_tag'] ] = $custom_field['label'];
		}

	}

	return $fields;
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
 * Returns an array of connection providers.
 *
 * @since 1.5.1
 * @ignore
 * @return Noptin_Connection_Provider[]
 */
function get_noptin_connection_providers() {
	return apply_filters( 'noptin_connection_providers', array() );
}

/**
 * Returns an array of email senders.
 *
 * @since 1.5.2
 * @return array
 */
function get_noptin_email_senders() {
	return apply_filters(
		'noptin_email_senders',
		array(
			'noptin' => __( 'Noptin Subscribers', 'newsletter-optin-box' ),
		)
	);
}

/**
 * Returns the sender to use for a specific email.
 *
 * @since 1.5.2
 * @param int $campaign_id
 * @return array
 */
function get_noptin_email_sender( $campaign_id ) {
	$sender = get_post_meta( $campaign_id, 'email_sender', true );
	return in_array( $sender, array_keys( get_noptin_email_senders() ) ) ? $sender : 'noptin';
}

/**
 * Applies Noptin merge tags.
 *
 * Noptin uses a fast logic-less templating engine to parse merge tags
 * and insert them into content.
 *
 * @param string $content
 * @param array $merge_tags
 * @param bool $strict
 * @param bool $strip_missing
 * @since 1.5.1
 * @return string
 */
function add_noptin_merge_tags( $content, $merge_tags, $strict = true, $strip_missing = true ) {

	$merge_tags     = $strict ? noptin_clean( $merge_tags ) : wp_kses_post_deep( $merge_tags );
	$all_merge_tags = flatten_noptin_array( $merge_tags );

	// Handle conditions.
	preg_match_all( '/\[\[#(\w*)\]\](.*?)\[\[\/\1\]\]/s', $content, $matches );

	if ( ! empty( $matches ) ) {

		foreach ( $matches[1] as $i => $match ) {

			if ( empty( $all_merge_tags[ $match ] ) ) {
				$content = str_replace( $matches[0][ $i ], '', $content );
			} else {

				$array       = array();
				$multi_array = array();

				foreach ( $all_merge_tags as $key => $value ) {

					if ( false !== strpos( $key, $match ) ) {
						$key = str_replace( $match . '.', '', $key );

						if ( is_numeric( $key ) ) {
							$array[] = $value;
						} else {
							$multi_array[ $key ] = $value;
						}

					}

				}

				// Fetched matched.
				$matched = $matches[2][ $i ];

				// Handle numeric arrays.
				if ( isset( $array[0] ) && is_scalar( $array[0] ) ) {
					$array   = '<ul><li>' . implode( '</li><li>', $array ) . '</li></ul>';
					$matched = str_replace( '[[.]]', $array, $matched );
				} else {
					$matched = add_noptin_merge_tags( $matched, $multi_array, $strict, false );
				}

				$content = str_replace( $matches[0][ $i ], $matched, $content );
			}

		}

	}

	// Replace all available tags with their values.
	foreach ( $all_merge_tags as $key => $value ) {
		if ( is_scalar( $value ) ) {
			$content = str_ireplace( "[[$key]]", $value, $content );
		}
	}

	// Remove unavailable tags.
	if ( $strip_missing ) {
		$content = preg_replace( '/\[\[[\w\.]+\]\]/', '', $content );
	}

	$content = preg_replace( '/ +([,.!])/s', '$1', $content );

	return $content;

}

/**
 * Flattens a multi-dimensional array containing merge tags.
 *
 *
 * @param array $array
 * @param string $prefix
 * @since 1.5.1
 * @return string[]
 */
function flatten_noptin_array( $array, $prefix = '' ) {
	$result = array();

	foreach ( $array as $key => $value ) {

		$_prefix = '' == $prefix ? "$key" : "$prefix.$key";

		$result[ $_prefix ] = 1;

		if ( is_array( $value ) ) {
			$result = array_merge( $result, flatten_noptin_array( $value , $_prefix ) );
		} else if ( is_object( $value ) ) {
			$result = array_merge( $result, flatten_noptin_array( get_object_vars( $value ), $_prefix ) );
		} else {

			if ( false === $value ) {
				$value = __( 'No', 'newsletter-optin-box' );
			}

			if ( true === $value ) {
				$value = __( 'Yes', 'newsletter-optin-box' );
			}

			$result[ $_prefix ] = $value;

			if ( strpos( $_prefix, '.0' ) !== false ) {
				$result[ str_replace( '.0', '', $_prefix ) ] = $value;
			}

		}

	}

	return $result;

}

/**
 * Sanitizes booleans.
 *
 * @param mixed $var Data to sanitize.
 * @since 1.5.5
 * @return mixed
 */
function noptin_sanitize_booleans( $var ) {

	if ( 'true' === $var ) {
		return true;
	}

	if ( 'false' === $var ) {
		return false;
	}

	return $var;
}

/**
 * Get current URL (full)
 *
 * @since 1.6.2
 * @return string
 */
function noptin_get_request_url() {
	global $wp;

	// Get requested url from global $wp object.
	$site_request_uri = $wp->request;

	// Fix for IIS servers using index.php in the URL.
	if ( false !== stripos( $_SERVER['REQUEST_URI'], '/index.php/' . $site_request_uri ) ) {
		$site_request_uri = 'index.php/' . $site_request_uri;
	}

	// Concatenate request url to home url.
	$url = home_url( $site_request_uri );
	$url = trailingslashit( $url );

	return esc_url_raw( $url );
}

/**
 * Get current URL path.
 *
 * @since 1.6.2
 * @return string
 */
function noptin_get_request_path() {
	return $_SERVER['REQUEST_URI'];
}

/**
 * Get a specific key of an array without needing to check if that key exists.
 *
 * Provide a default value if you want to return a specific value if the key is not set.
 *
 * @since  1.6.2
 *
 * @param array  $array   Array from which the key's value should be retrieved.
 * @param string $key    Name of the key to be retrieved.
 * @param string $default Optional. Value that should be returned if the key is not set or empty. Defaults to null.
 *
 * @return null|string|mixed The value
 */
function noptin_array_value( $array, $key, $default = '' ) {

	if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
		return $default;
	}

	if ( isset( $array[ $key ] ) ) {
		return $array[ $key ];
	}

	return $default;
}

/**
 * Checks if this is a preview request.
 *
 * @return bool
 */
function noptin_is_preview() {

	// Widget preview.
	if ( ! empty( $_GET['legacy-widget-preview'] ) || defined( 'IS_NOPTIN_PREVIEW' ) || ( ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) && false !== strpos( $GLOBALS['wp']->query_vars['rest_route'], 'noptin_widget_premade' ) ) ) {
		return true;
	}

	// Divi preview.
	if ( isset( $_REQUEST['et_fb'] ) || isset( $_REQUEST['et_pb_preview'] ) ) {
		return true;
	}

	// Beaver builder.
	if ( isset( $_REQUEST['fl_builder'] ) ) {
		return true;
	}

	// Elementor builder.
	if ( isset( $_REQUEST['elementor-preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor' ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor_ajax' ) ) {
		return true;
	}

	// Siteorigin preview.
	if ( ! empty( $_REQUEST['siteorigin_panels_live_editor'] ) ) {
		return true;
	}

	// Cornerstone preview.
	if ( ! empty( $_REQUEST['cornerstone_preview'] ) || basename( $_SERVER['REQUEST_URI'] ) == 'cornerstone-endpoint' ) {
		return true;
	}

	// Fusion builder preview.
	if ( ! empty( $_REQUEST['fb-edit'] ) || ! empty( $_REQUEST['fusion_load_nonce'] ) ) {
		return true;
	}

	// Oxygen preview.
	if ( ! empty( $_REQUEST['ct_builder'] ) || ( ! empty( $_REQUEST['action'] ) && ( substr( $_REQUEST['action'], 0, 11 ) === "oxy_render_" || substr( $_REQUEST['action'], 0, 10 ) === "ct_render_" ) ) ) {
		return true;
	}

	// Ninja forms preview.
	if ( isset( $_GET['nf_preview_form'] ) || isset( $_GET['nf_iframe'] ) ) {
		return true;
	}

	// Customizer preview.
	if ( is_customize_preview() ) {
		return true;
	}

	return false;

}

/**
 * Checks if the site uses a supported multilingual plugin.
 *
 * @return bool
 */
function noptin_is_multilingual() {
	return apply_filters( 'noptin_is_multilingual', false );
}
