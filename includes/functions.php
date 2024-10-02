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

	if ( is_object( $options ) ) {
		$options = (array) $options;
	}

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
 * @param   bool   $use_empty_template  Optional. Whether or not to use an empty template.
 * @access  public
 * @since   1.0.6
 */
function get_noptin_action_url( $action, $value = false, $use_empty_template = false ) {

	$home_url = apply_filters( 'noptin_action_url_home_url', get_home_url() );

	// If home url does not have query vars, add a trailing slash.
	if ( false === strpos( $home_url, '?' ) ) {
		$home_url = trailingslashit( $home_url );
	}

	return add_query_arg(
		array(
			'noptin_ns' => rawurlencode( $action ),
			'nv'        => empty( $value ) ? false : rawurlencode( $value ),
			'nte'       => $use_empty_template,
		),
		$home_url
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

	$show                 = true;
	$hide_if_subscribed   = get_noptin_option( 'hide_from_subscribers', false );
	$always_show_to_admin = get_noptin_option( 'always_show_to_admin', true );

	if ( $hide_if_subscribed && current_user_can( get_noptin_capability() ) && $always_show_to_admin ) {
		$hide_if_subscribed = false;
	}

	if ( ! empty( $_COOKIE['noptin_hide'] ) || ( $hide_if_subscribed && noptin_is_subscriber() ) ) {
		$show = false;
	}

	if ( noptin_is_preview() ) {
		$show = true;
	}

	return apply_filters( 'noptin_should_show_optins', $show );
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
		'page'              => 'noptin-email-campaigns',
		'noptin_email_type' => 'newsletter',
	);
	return add_query_arg( $param, admin_url( '/admin.php' ) );
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
 * Returns the appropriate capability to check against
 *
 * @since 1.2.2
 * @return string capability to check against
 * @param string $capalibilty Optional. The alternative capability to check against.
 */
function get_noptin_capability( $capalibilty = 'manage_noptin' ) {

	if ( current_user_can( 'manage_options' ) ) {
		return 'manage_options';
	}

	// Are we allowing editors to manage noptin?
	if ( get_noptin_option( 'allow_editors', false ) && current_user_can( 'edit_others_posts' ) ) {
		return 'edit_others_posts';
	}

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
 * @deprecated
 */
function noptin_locate_ip_address( $ip_address = '' ) {

	// Prepare ip address.
	if ( empty( $ip_address ) ) {
		$ip_address = noptin_get_user_ip();
	}

	// Ensure that it is valid.
	if ( empty( $ip_address ) || ! rest_is_ip_address( $ip_address ) ) {
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
		$geo['country']         = $geo['country_name'];
		unset( $geo['country_name'] );
	}

	if ( ! empty( $geo['state_prov'] ) ) {
		$geo['state']           = $geo['state_prov'];
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
 * Returns GeoLocation fields.
 *
 * @since 2.0.0
 * @return array
 */
function noptin_geolocation_fields() {
	return array(
		'continent' => __( 'Continent', 'newsletter-optin-box' ),
		'country'   => __( 'Country', 'newsletter-optin-box' ),
		'state'     => __( 'State', 'newsletter-optin-box' ),
		'city'      => __( 'City', 'newsletter-optin-box' ),
		'latitude'  => __( 'Latitude', 'newsletter-optin-box' ),
		'longitude' => __( 'Longitude', 'newsletter-optin-box' ),
		'currency'  => __( 'Currency', 'newsletter-optin-box' ),
		'time zone' => __( 'Time Zone', 'newsletter-optin-box' ),
	);
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

	if ( empty( $list ) ) {
		return array();
	}

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
	$clean_url = preg_replace( '#^(http(s)?://)?(www\.)?(.+\.)#i', '$4', $clean_url );

	// Take care of edge cases
	$clean_url = preg_replace( '#^http(s)?://#i', '', $clean_url );

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
		'level' => $code,
		'msg'   => $message,
		'time'  => current_time( 'mysql' ),
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
			$message['msg'] = print_r( $message['msg'], true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
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
 * Delete an action.
 *
 * You can pass extra arguments to the hooks, much like you can with `do_action()`.
 *
 * @since 3.0.0
 *
 * @param string $hook    (required). Name of the action hook. Default: none.
 * @param mixed  ...$arg Optional. Additional arguments to pass to callbacks when the hook triggers.
 */
function delete_noptin_background_action( $hook, ...$args ) {

	// Delete in new task system.
	\Hizzle\Noptin\Tasks\Main::delete_scheduled_task( $hook, $args, -1 );

	// Delete in wp-cron.
	wp_clear_scheduled_hook( $hook, $args );

	// Delete in action scheduler.
	if ( class_exists( 'ActionScheduler_DataController' ) && ActionScheduler_DataController::is_migration_complete() && function_exists( 'as_unschedule_action' ) ) {
		do {
			$unscheduled_action = as_unschedule_action( $hook, $args, 'noptin' );
		} while ( ! empty( $unscheduled_action ) );
	}
}

/**
 * Returns the next scheduled time for an action.
 */
function next_scheduled_noptin_background_action( $hook, ...$args ) {

	// Fetch from new task system.
	$task = \Hizzle\Noptin\Tasks\Main::get_next_scheduled_task( $hook, $args );

	if ( $task ) {
		return $task->get_date_scheduled() ? $task->get_date_scheduled()->getTimestamp() : time();
	}

	// Check in action scheduler.
	if ( class_exists( 'ActionScheduler_DataController' ) && ActionScheduler_DataController::is_migration_complete() && function_exists( 'as_next_scheduled_action' ) ) {
		$timestamp = as_next_scheduled_action( $hook, $args, 'noptin' );

		if ( is_numeric( $timestamp ) ) {
			return $timestamp;
		}
	}

	return wp_next_scheduled( $hook, $args );
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
 *
 * @param string $tag    (required). Name of the action hook. Default: none.
 * @param mixed  ...$arg Optional. Additional arguments to pass to callbacks when the hook triggers.
 */
function do_noptin_background_action( $hook, ...$args ) {
	return \Hizzle\Noptin\Tasks\Main::schedule_task( $hook, $args );
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
 * ```php
 *     // The action callback function.
 *     function log_name( $name ) {
 *         // Log the name.
 *         log_noptin_message( $name, 'notice' );
 *     }
 *     add_action( 'log_name_after_a_day', 'log_name', 10, 1 );
 *
 *      // Ask Noptin to fire the hook in in the future.
 *      schedule_noptin_background_action( noptin_string_to_timestamp( '+1 day' ), 'log_name_after_a_day', 'Brian');
 * ```
 *
 * @since 1.2.7
 *
 * @param int    $timestamp (required) The Unix timestamp representing the date
 *                          you want the action to run. Default: none.
 * @param string $hook      (required) Name of the action hook. Default: none.
 * @param mixed  ...$arg    Optional. Additional arguments to pass to callbacks when the hook triggers. Default none.
 */
function schedule_noptin_background_action( $timestamp, $hook, ...$args ) {
	return \Hizzle\Noptin\Tasks\Main::schedule_task( $hook, $args, $timestamp - time() );
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
 *      schedule_noptin_background_action( DAY_IN_SECONDS, noptin_string_to_timestamp( '+1 day' ), 'log_name_every_day', 'Brian');
 *
 * @since 1.2.7
 *
 * @param int    $interval  (required) How long ( in seconds ) to wait between runs. Default: none.
 * @param int    $timestamp (required) The Unix timestamp representing the date you
 *                          want the action to run for the first time. Default: none.
 * @param string $hook       (required) Name of the action hook. Default: none.
 * @param mixed  ...$args   Optional. Additional arguments to pass to callbacks when the hook triggers. Default none.
 * @return int|bool The action id on success. False otherwise.
 */
function schedule_noptin_recurring_background_action( $interval, $timestamp, $hook, ...$args ) {
	return \Hizzle\Noptin\Tasks\Main::schedule_task( $hook, $args, $timestamp - time(), $interval );
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

	if ( 1 < func_num_args() ) {
		$data = func_get_args();
	}

	// phpcs:disable WordPress.PHP.DevelopmentFunctions
	echo '<pre>';
	var_dump( $data );
	echo '<br /><code>';
	echo esc_html( implode( "\n &gt;&gt;&nbsp;", array_reverse( wp_debug_backtrace_summary( null, 0, false ) ) ) );
	echo '</code></pre>';
	// phpcs:enable WordPress.PHP.DevelopmentFunctions
}

/**
 * Checks whether the automation rules table exists
 *
 * @deprecated 1.12.0
 * @since 1.3.3
 * @return bool
 */
function noptin_automation_rules_table_exists() {
	return true;
}

/**
 *  Returns the name of the automation rules table
 *
 * @deprecated 1.12.0
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
 * @deprecated 1.11.2
 * @return Noptin_Connection_Provider[]
 */
function get_noptin_connection_providers() {
	return apply_filters( 'noptin_connection_providers', array() );
}

/**
 * Returns an array of premium addons.
 *
 * @since 1.11.2
 * @ignore
 * @return array
 */
function noptin_premium_addons() {
	return apply_filters( 'noptin_premium_addons', array() );
}

/**
 * Checks whether or not to upsell integrations.
 *
 * @since 1.9.0
 * @ignore
 * @return bool
 */
function noptin_upsell_integrations() {
	return apply_filters( 'noptin_upsell_integrations', ! noptin_has_active_license_key() );
}

/**
 * Retrieves an upsell URL.
 *
 * @since 1.9.0
 * @param string $url The URL to redirect to.
 * @param string $utm_source The utm source.
 * @param string $utm_medium The utm medium.
 * @param string $utm_campaign The utm campaign.
 * @ignore
 * @return bool
 */
function noptin_get_upsell_url( $url, $utm_source, $utm_campaign, $utm_medium = 'plugin-dashboard' ) {

	// Check the URL begins with http.
	if ( 0 !== strpos( $url, 'http' ) ) {
		$url = 'https://noptin.com/' . ltrim( $url, '/' );
	}

	return add_query_arg(
		rawurlencode_deep(
			array(
				'utm_medium'   => $utm_medium,
				'utm_campaign' => $utm_campaign,
				'utm_source'   => empty( $utm_source ) ? get_home_url() : $utm_source,
			)
		),
		$url
	);
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
		$_prefix = '' === $prefix ? "$key" : "$prefix.$key";

		$result[ $_prefix ] = 1;

		if ( is_array( $value ) ) {
			$result = array_merge( $result, flatten_noptin_array( $value, $_prefix ) );
		} elseif ( is_object( $value ) ) {
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
	/**@var wp $wp */
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
	if ( isset( $_REQUEST['elementor-preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && 'elementor' === $_REQUEST['action'] ) || ( isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) ) {
		return true;
	}

	// Siteorigin preview.
	if ( ! empty( $_REQUEST['siteorigin_panels_live_editor'] ) ) {
		return true;
	}

	// Cornerstone preview.
	if ( ! empty( $_REQUEST['cornerstone_preview'] ) || 'cornerstone-endpoint' === basename( $_SERVER['REQUEST_URI'] ) ) {
		return true;
	}

	// Fusion builder preview.
	if ( ! empty( $_REQUEST['fb-edit'] ) || ! empty( $_REQUEST['fusion_load_nonce'] ) ) {
		return true;
	}

	// Oxygen preview.
	if ( ! empty( $_REQUEST['ct_builder'] ) || ( ! empty( $_REQUEST['action'] ) && ( 'oxy_render_' === substr( $_REQUEST['action'], 0, 11 ) || 'ct_render_' === substr( $_REQUEST['action'], 0, 10 ) ) ) ) {
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

/**
 * Returns an array of available languages.
 *
 * @return array|null
 */
function noptin_get_available_languages() {

	if ( ! noptin_is_multilingual() ) {
		return null;
	}

	return apply_filters( 'noptin_multilingual_active_languages', array() );
}

/**
 * Formats a date for display.
 *
 * @param string $date_time.
 * @return string
 */
function noptin_format_date( $date_time ) {

	if ( empty( $date_time ) ) {
		return '&mdash;';
	}

	$timestamp = noptin_string_to_timestamp( $date_time );
	$current   = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	$time_diff = $current - $timestamp;

	if ( $timestamp && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
		$relative = sprintf(
			/* translators: %s: Human-readable time difference. */
			__( '%s ago', 'newsletter-optin-box' ),
			human_time_diff( $timestamp, $current )
		);
	} else {
		$relative = date_i18n( get_option( 'date_format' ), $timestamp );
	}

	$date = esc_attr( date_i18n( 'Y/m/d g:i:s a', $timestamp ) );
	return "<abbr title='$date'>$relative<abbr>";
}

/**
 * Encrypts a text string.
 *
 * @param string $plaintext The plain text string to encrypt.
 * @return string
 */
function noptin_encrypt( $plaintext ) {

	$ivlen      = openssl_cipher_iv_length( 'AES-128-CBC' );
	$salt       = ( defined( 'AUTH_SALT' ) && AUTH_SALT ) ? AUTH_SALT : wp_salt(); // backwards compatibility.
	$iv         = substr( $salt, 0, $ivlen );
	$passphrase = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'secure_auth' ); // Local doesn't set AUTH_KEY.

	// Encrypt then encode.
	$encoded = base64_encode( openssl_encrypt( $plaintext, 'AES-128-CBC', $passphrase, OPENSSL_RAW_DATA, $iv ) );

	// Make URL safe.
	return strtr( $encoded, '+/=', '._-' );
}

/**
 * Decrypts a text string.
 *
 * @param string $encoded The string to decode.
 * @return string
 */
function noptin_decrypt( $encoded ) {

	if ( empty( $encoded ) ) {
		return '';
	}

	// Decode.
	// @see noptin_encrypt()
	$decoded = base64_decode( strtr( $encoded, '._-', '+/=' ) );

	if ( empty( $decoded ) ) {
		return '';
	}

	// Prepare args.
	$ivlen      = openssl_cipher_iv_length( 'AES-128-CBC' );
	$salt       = ( defined( 'AUTH_SALT' ) && AUTH_SALT ) ? AUTH_SALT : wp_salt(); // backwards compatibility.
	$iv         = substr( $salt, 0, $ivlen );
	$passphrase = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'secure_auth' ); // Local doesn't set AUTH_KEY.

	$result = openssl_decrypt( $decoded, 'AES-128-CBC', $passphrase, OPENSSL_RAW_DATA, $iv );
	return false === $result ? '' : $result;
}

/**
 * Limit length of a string.
 *
 * @param  string  $string string to limit.
 * @param  integer $limit Limit size in characters.
 * @return string
 */
function noptin_limit_length( $string, $limit ) {

	if ( empty( $limit ) || empty( $string ) || ! is_string( $string ) ) {
		return $string;
	}

	$str_limit = $limit - 3;

	if ( function_exists( 'mb_strimwidth' ) ) {
		if ( mb_strlen( $string ) > $limit ) {
			$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
		}
	} elseif ( strlen( $string ) > $limit ) {
			$string = substr( $string, 0, $str_limit ) . '...';
	}
	return $string;
}

/**
 * Retrieves a post's excerpt.
 *
 * @param  WP_Post $post
 * @param  integer $limit Optional character limit.
 * @return string
 */
function noptin_get_post_excerpt( $post, $limit = 0 ) {

	// Remove read_more string.
	add_filter( 'excerpt_more', '__return_empty_string', 100000 );

	// Prevent wp_rss_aggregator from appending the feed name to excerpts.
	$wp_rss_aggregator_fix = has_filter( 'get_the_excerpt', 'mdwp_MarkdownPost' );

	if ( false !== $wp_rss_aggregator_fix ) {
		remove_filter( 'get_the_excerpt', 'mdwp_MarkdownPost', $wp_rss_aggregator_fix );
	}

	// Generate excerpt.
	$post_excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );

	if ( false !== $wp_rss_aggregator_fix ) {
		add_filter( 'get_the_excerpt', 'mdwp_MarkdownPost', $wp_rss_aggregator_fix );
	}

	remove_filter( 'excerpt_more', '__return_empty_string', 100000 );

	return noptin_limit_length( $post_excerpt, $limit );
}

/**
 * Escapes content with support for svg
 *
 * @param  string $content
 * @since  1.7.0
 */
function noptin_kses_post_e( $content ) {

	echo wp_kses(
		$content,
		array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'svg'   => array(
					'class'           => true,
					'aria-hidden'     => true,
					'aria-labelledby' => true,
					'role'            => true,
					'xmlns'           => true,
					'xmlns:xlink'     => true,
					'xml:space'       => true,
					'y'               => true,
					'x'               => true,
					'width'           => true,
					'height'          => true,
					'viewbox'         => true,
					'version'         => true,
					'fill'            => true,
				),
				'g'     => array( 'fill' => true ),
				'title' => array( 'title' => true ),
				'path'  => array(
					'd'    => true,
					'fill' => true,
				),
			)
		)
	);
}

/**
 * Returns the current user's logged in status.
 *
 * @return string
 */
function noptin_get_user_logged_in_status() {

	if ( get_current_user_id() > 0 ) {
		return 'yes';
	}

	return 'no';
}

/**
 * Checks if a given WP User is unsubscribed.
 *
 * @since 1.7.0
 * @param int $user_id
 * @return bool
 */
function noptin_is_wp_user_unsubscribed( $user_id ) {

	$user = get_user_by( 'ID', $user_id );

	if ( $user ) {
		$subscriber = noptin_get_subscriber( $user->user_email );

		if ( ! $subscriber->is_active() ) {
			return false;
		}

		return 'unsubscribed' === get_user_meta( $user_id, 'noptin_unsubscribed', true );
	}

	return false;
}

/**
 * Checks if a given email is unsubscribed.
 *
 * @since 1.9.0
 * @param string $email
 * @return bool
 */
function noptin_is_email_unsubscribed( $email ) {

	// Abort if not a valid email.
	if ( ! is_string( $email ) || ! is_email( $email ) ) {
		return false;
	}

	// Fetch user by email.
	$user = get_user_by( 'email', $email );

	// If the user is unsubscribed, abort.
	if ( $user && 'unsubscribed' === get_user_meta( $user->ID, 'noptin_unsubscribed', true ) ) {
		return true;
	}

	// Fetch subscriber by email.
	$subscriber = noptin_get_subscriber( $email );

	// If the subscriber is unsubscribed, abort.
	if ( $subscriber->exists() && ! $subscriber->is_active() ) {
		return true;
	}

	return false;
}

/**
 * Converts newlines to an array of options.
 *
 * @since 1.7.4
 * @param string $text
 * @return array
 */
function noptin_newslines_to_array( $text ) {

	if ( is_array( $text ) ) {
		return $text;
	}

	$options = array();

	// Split by newlines.
	foreach ( preg_split( "/\r\n|\n|\r/", $text ) as $option ) {

		// Trim the option.
		$option = trim( $option );

		// Value and label can be separated by a pipe.
		if ( strpos( $option, '|' ) !== false ) {
			list( $value, $label )     = explode( '|', $option );
			$options[ trim( $value ) ] = wp_strip_all_tags( trim( $label ) );
			continue;
		}

		$options[ $option ] = wp_strip_all_tags( $option );
	}

	return $options;
}

/**
 * Checks if a number is even.
 *
 * @since 1.7.5
 * @param int $number
 * @return bool
 */
function noptin_is_even( $number ) {
	return 0 === $number || 0 === $number % 2;
}

/**
 * Returns the default conditional logic.
 *
 * @since 1.8.0
 * @return array
 */
function noptin_get_default_conditional_logic() {
	return array(
		'enabled' => false,
		'action'  => 'allow',
		'type'    => 'all',
		'rules'   => array(),
	);
}

/**
 * Returns the comparisons available for conditional logic.
 *
 * @since 1.9.0
 * @return array
 */
function noptin_get_conditional_logic_comparisons() {
	return array(
		'is'               => array(
			'type' => 'any',
			'name' => __( 'is', 'newsletter-optin-box' ),
		),
		'is_not'           => array(
			'type' => 'any',
			'name' => __( 'is not', 'newsletter-optin-box' ),
		),
		'contains'         => array(
			'type' => 'string',
			'name' => __( 'contains', 'newsletter-optin-box' ),
		),
		'does_not_contain' => array(
			'type' => 'string',
			'name' => __( 'does not contain', 'newsletter-optin-box' ),
		),
		'begins_with'      => array(
			'type' => 'string',
			'name' => __( 'begins with', 'newsletter-optin-box' ),
		),
		'ends_with'        => array(
			'type' => 'string',
			'name' => __( 'ends with', 'newsletter-optin-box' ),
		),
		'is_empty'         => array(
			'type' => 'any',
			'name' => __( 'is empty', 'newsletter-optin-box' ),
		),
		'is_not_empty'     => array(
			'type' => 'any',
			'name' => __( 'is not empty', 'newsletter-optin-box' ),
		),
		'is_greater_than'  => array(
			'type' => 'number',
			'name' => __( 'is greater than', 'newsletter-optin-box' ),
		),
		'is_less_than'     => array(
			'type' => 'number',
			'name' => __( 'is less than', 'newsletter-optin-box' ),
		),
		'is_between'       => array(
			'type' => 'number',
			'name' => __( 'is between', 'newsletter-optin-box' ),
		),
		'is_before'        => array(
			'type' => 'date',
			'name' => __( 'is before', 'newsletter-optin-box' ),
		),
		'is_after'         => array(
			'type' => 'date',
			'name' => __( 'is after', 'newsletter-optin-box' ),
		),
		'is_date_between'  => array(
			'type' => 'date',
			'name' => __( 'is between', 'newsletter-optin-box' ),
		),
	);
}

/**
 * Checks if a given condition is valid.
 *
 * @param string $current_value The current value.
 * @param string $condition_value The condition value.
 * @param string $comparison The comparison to use.
 * @since 1.9.0
 * @return bool
 */
function noptin_is_conditional_logic_met( $current_value, $condition_value, $comparison ) {

	// Convert to strings.
	$current_value   = trim( strtolower( (string) $current_value ) );
	$condition_value = trim( strtolower( (string) $condition_value ) );

	switch ( $comparison ) {
		case 'is':
			return $current_value === $condition_value;

		case 'is_not':
			return $current_value !== $condition_value;

		case 'contains':
			return false !== strpos( $current_value, $condition_value );

		case 'does_not_contain':
			return false === strpos( $current_value, $condition_value );

		case 'begins_with':
			return 0 === strpos( $current_value, $condition_value );

		case 'ends_with':
			return substr( $current_value, - strlen( $condition_value ) ) === $condition_value;

		case 'is_empty':
			return empty( $current_value );

		case 'is_not_empty':
			return ! empty( $current_value );

		case 'is_greater_than':
			return floatval( $current_value ) > floatval( $condition_value );

		case 'is_less_than':
			return floatval( $current_value ) < floatval( $condition_value );

		case 'is_between':
			$condition_value = noptin_parse_list( $condition_value );
			$first_value     = floatval( $condition_value[0] );
			$second_value    = isset( $condition_value[1] ) ? floatval( $condition_value[1] ) : $first_value;

			return floatval( $current_value ) >= $first_value && floatval( $current_value ) <= $second_value;

		case 'is_before':
			$current_value   = noptin_string_to_timestamp( $current_value );
			$condition_value = noptin_string_to_timestamp( $condition_value );
			return $current_value < $condition_value;

		case 'is_after':
			$current_value   = noptin_string_to_timestamp( $current_value );
			$condition_value = noptin_string_to_timestamp( $condition_value );
			return $current_value > $condition_value;

		case 'is_date_between':
			$condition_value = noptin_parse_list( $condition_value );
			$first_value     = noptin_string_to_timestamp( $condition_value[0] );
			$second_value    = isset( $condition_value[1] ) ? noptin_string_to_timestamp( $condition_value[1] ) : $first_value;

			$current_value = noptin_string_to_timestamp( $current_value );
			return $current_value >= $first_value && $current_value <= $second_value;
	}
}

/**
 * Formats conditional logic for display.
 *
 * @param array $conditional_logic
 * @param array $smart_tags
 * @since 1.8.0
 * @return array
 */
function noptin_prepare_conditional_logic_for_display( $conditional_logic, $smart_tags = array() ) {

	// Abort if no conditional logic is set.
	if ( empty( $conditional_logic['enabled'] ) || empty( $conditional_logic['rules'] ) ) {
		return array();
	}

	// Retrieve the conditional logic.
	$rules       = array();
	$comparisons = wp_list_pluck( noptin_get_conditional_logic_comparisons(), 'name' );

	// Loop through each rule.
	foreach ( $conditional_logic['rules'] as $rule ) {
		if ( empty( $rule['type'] ) ) {
			continue;
		}

		$condition = Noptin_Dynamic_Content_Tags::search( $rule['type'], $smart_tags );

		if ( empty( $condition ) ) {
			$condition = array(
				'description'       => '[[' . $rule['type'] . ']]',
				'conditional_logic' => 'string',
			);
		}

		$label     = isset( $condition['label'] ) ? $condition['label'] : $condition['description'];
		$value     = isset( $rule['value'] ) ? $rule['value'] : '';
		$data_type = isset( $condition['conditional_logic'] ) ? $condition['conditional_logic'] : false;

		// Add group to label.
		if ( ! empty( $condition['group'] ) && __( 'General', 'newsletter-optin-box' ) !== $condition['group'] ) {
			$label = sprintf( '<code>%s >> %s</code>', $condition['group'], $label );
		}

		if ( 'number' === $data_type ) {
			if ( 'is_between' === $rule['condition'] ) {
				$value = noptin_parse_list( $value );
				$value = sprintf(
					// translators: %s is a number.
					_x( '%1$s and %2$s', 'number', 'newsletter-optin-box' ),
					floatval( $value[0] ),
					isset( $value[1] ) ? floatval( $value[1] ) : floatval( $value[0] )
				);
			} else {
				$value = floatval( $value );
			}
		} elseif ( 'date' === $data_type ) {
			if ( 'is_date_between' === $rule['condition'] ) {
				$value = noptin_parse_list( $value );
				$value = sprintf(
					// translators: %s is a date.
					__( '%1$s and %2$s', 'newsletter-optin-box' ),
					gmdate( 'Y-m-d', noptin_string_to_timestamp( $value[0] ) ),
					isset( $value[1] ) ? gmdate( 'Y-m-d', noptin_string_to_timestamp( $value[1] ) ) : gmdate( 'Y-m-d', noptin_string_to_timestamp( $value[0] ) )
				);
			} else {
				$value = gmdate( 'Y-m-d', noptin_string_to_timestamp( $value ) );
			}
		} elseif ( isset( $condition['options'] ) ) {
			if ( is_callable( $condition['options'] ) ) {
				$options = call_user_func( $condition['options'] );
			} else {
				$options = $condition['options'];
			}

			if ( isset( $options[ $value ] ) ) {
				$value = $options[ $value ];
			}
		}

		if ( isset( $comparisons[ $rule['condition'] ] ) ) {
			$rules[] = sprintf(
				'%s %s <code>%s</code>',
				wp_kses_post( $label ),
				sanitize_text_field( $comparisons[ $rule['condition'] ] ),
				sanitize_text_field( $value )
			);
		}
	}

	if ( 'any' === $conditional_logic['type'] ) {
		$rules = implode( ' ' . __( 'OR', 'newsletter-optin-box' ) . ' ', $rules );
	} else {
		$rules = implode( ' ' . __( 'AND', 'newsletter-optin-box' ) . ' ', $rules );
	}

	if ( empty( $rules ) ) {
		return array();
	}

	return array(
		'enabled' => true,
		'action'  => $conditional_logic['action'],
		'rules'   => $rules,
	);
}

/**
 * Callback to sort arrays by name.
 *
 * @since 1.9.0
 * @param object $a
 * @param object $b
 * @return int
 */
function noptin_sort_by_name( $a, $b ) {
	return strcmp( $a->get_name(), $b->get_name() );
}

/**
 * Callback to sort arrays by time key.
 *
 * @since 2.0.0
 * @param array $a
 * @param array $b
 * @return int
 */
function noptin_sort_by_time_key( $a, $b ) {
	if ( $a['time'] > $b['time'] ) {
        return 1;
    } elseif ( $a['time'] < $b['time'] ) {
        return -1;
    }

    return 0;
}

/**
 * Sanitize a merge tag.
 *
 * Strips all non-alphanumeric characters except underscores, hyphens, and dots.
 *
 * @since 1.10.1
 * @param string $tag
 * @return string
 */
function noptin_sanitize_merge_tag( $tag ) {
	$sanitized_key = strtolower( $tag );
	return preg_replace( '/[^a-z0-9_\/\-\.]/', '', $sanitized_key );
}

/**
 * Returns the automation rule being edited.
 *
 * @since 1.10.1
 * @return \Hizzle\Noptin\Automation_Rules\Automation_Rule
 */
function noptin_get_current_automation_rule() {

	// Automation rule edit page.
	if ( isset( $_GET['noptin_edit_automation_rule'] ) ) {
		return noptin_get_automation_rule( absint( $_GET['noptin_edit_automation_rule'] ) );
	}

	return noptin_get_automation_rule( 0 );
}

/**
 * Queries the automation rules database.
 *
 * @param array $args Query arguments.
 * @param string $return See Hizzle\Noptin\DB\Main::query for allowed values.
 * @return int|array|\Hizzle\Noptin\Automation_Rules\Automation_Rule[]|\Hizzle\Store\Query|WP_Error
 */
function noptin_get_automation_rules( $args = array(), $to_return = 'results' ) {
	return noptin()->db()->query( 'automation_rules', $args, $to_return );
}

/**
 * Fetch an automation rule by rule ID.
 *
 * @param int|\Hizzle\Noptin\Automation_Rules\Automation_Rule|Noptin_Automation_Rule $automation_rule_id Automation Rule ID, or object.
 * @return \Hizzle\Noptin\Automation_Rules\Automation_Rule|WP_Error Automation Rule object if found, error object if not found.
 */
function noptin_get_automation_rule( $automation_rule_id = 0 ) {

	// If automation rule is already an automation rule object, return it.
	if ( $automation_rule_id instanceof \Hizzle\Noptin\Automation_Rules\Automation_Rule ) {
		return $automation_rule_id;
	}

	// Deprecated object.
	if ( $automation_rule_id instanceof Noptin_Automation_Rule ) {
		$automation_rule_id = $automation_rule_id->id;
	}

	if ( ! is_numeric( $automation_rule_id ) ) {
		noptin_error_log( $automation_rule_id );
		$automation_rule_id = 0;
	}

	return noptin()->db()->get( (int) $automation_rule_id, 'automation_rules' );
}

/**
 * Deletes an automation rule.
 *
 * @param int|\Hizzle\Noptin\Automation_Rules\Automation_Rule|Noptin_Automation_Rule $automation_rule_id Automation Rule ID, or object.
 * @return bool|WP_Error True on success, error object on failure.
 */
function noptin_delete_automation_rule( $automation_rule_id ) {
	$automation_rule = noptin_get_automation_rule( $automation_rule_id );

	if ( ! is_wp_error( $automation_rule ) ) {
		return $automation_rule->delete();
	}

	return $automation_rule;
}

/**
 * Returns an array of WordPress user custom fields.
 *
 * @return array
 * @since 2.2.0
 */
function noptin_get_user_custom_fields() {
	return apply_filters( 'noptin_users_known_custom_fields', array() );
}

/**
 * Returns an array of WordPress post type custom fields.
 *
 * @param string $post_type
 * @return array
 * @since 2.2.0
 */
function noptin_get_post_type_custom_fields( $post_type ) {
	return apply_filters( 'noptin_post_types_known_custom_fields', array(), $post_type );
}

/**
 * Returns random background color.
 *
 * @return array
 * @since 2.2.0
 */
function noptin_get_random_background_color() {
	$red        = wp_rand( 150, 255 );
	$green      = wp_rand( 150, 255 );
	$blue       = wp_rand( 150, 255 );
	$background = sprintf(
		'%02x%02x%02x',
		$red,
		$green,
		$blue
	);

	$brightness = ( $red * 299 + $green * 587 + $blue * 114 ) / 1000;
	$foreground = $brightness > 128 ? '000000' : 'ffffff';

	return array( $background, $foreground );
}

/**
 * Implodes an array into a string.
 *
 * @param array $strings
 * @param string $glue
 */
function noptin_implode_and( $strings, $glue = ', ' ) {

	if ( empty( $strings ) || ! is_array( $strings ) ) {
		return '';
	}

	$last   = array_pop( $strings );
	$string = implode( $glue, $strings );

	if ( empty( $string ) ) {
		return $last;
	}

	return sprintf(
		// translators: %1$s is a string, %2$s is a string.
		__( '%1$s and %2$s', 'newsletter-optin-box' ),
		$string,
		$last
	);
}

/**
 * Prepares merge tags for use in JS.
 *
 * @param array $merge_tags
 * @return array
 */
function noptin_prepare_merge_tags_for_js( $merge_tags ) {

	if ( ! is_array( $merge_tags ) ) {
		return array();
	}

	$prepared = array();

	foreach ( $merge_tags as $key => $value ) {
		$prepared[ $key ] = $value;

		if ( empty( $prepared[ $key ]['group'] ) ) {
			$prepared[ $key ]['group'] = __( 'General', 'newsletter-optin-box' );
		}

		// If options is a callable, call it.
		if ( isset( $prepared[ $key ]['options'] ) && is_callable( $prepared[ $key ]['options'] ) ) {
			$prepared[ $key ]['options'] = call_user_func( $prepared[ $key ]['options'] );
		}

		// Remove callback.
		unset( $prepared[ $key ]['callback'] );
	}

	return $prepared;
}

/**
 * Checks if we have an active license key.
 *
 * @return bool
 * @since 3.0.0
 */
function noptin_has_active_license_key() {
	return ! ! Noptin_COM::get_active_license_key();
}

/**
 * Runs daily maintenance tasks.
 *
 * @since 3.0.0
 */
function noptin_daily_maintenance() {

	// Delete old emails.
	\Hizzle\Noptin\Emails\Main::delete_old_emails();

	// Check license status.
	Noptin_COM::get_active_license_key( true );

	// Refresh integrations after 10 mins.
	if ( ! next_scheduled_noptin_background_action( 'noptin_refresh_integrations' ) ) {
		schedule_noptin_background_action( time() + 600, 'noptin_refresh_integrations' );
	}

	// Refresh email templates after 20 mins.
	if ( ! next_scheduled_noptin_background_action( 'noptin_refresh_email_templates' ) ) {
		schedule_noptin_background_action( time() + 1200, 'noptin_refresh_email_templates' );
	}
}
add_action( 'noptin_daily_maintenance', 'noptin_daily_maintenance' );

/**
 * Merges an array to another array at the specified key.
 *
 * @param array $original_array
 * @param array $new_array
 * @param string $key
 */
function noptin_array_merge_at_key( $original_array, $new_array, $key ) {

	if ( ! isset( $original_array[ $key ] ) ) {
		return array_merge( $original_array, $new_array );
	}

	$prepared = array();

	foreach ( $original_array as $original_key => $original_value ) {
		$prepared[ $original_key ] = $original_value;

		if ( $original_key === $key ) {
			$prepared = array_merge( $prepared, $new_array );
		}
	}

	return $prepared;
}

/**
 * Convert mysql datetime to PHP timestamp, forcing UTC. Wrapper for strtotime.
 *
 * Based on wc_string_to_timestamp().
 *
 * @since  3.0.0
 * @param  string   $time_string    Time string.
 * @param  int|null $from_timestamp Timestamp to convert from.
 * @return int
 */
function noptin_string_to_timestamp( $time_string, $from_timestamp = null ) {
	$time_string = $time_string ?? '';

	$original_timezone = date_default_timezone_get();

	// @codingStandardsIgnoreStart
	date_default_timezone_set( 'UTC' );

	if ( null === $from_timestamp ) {
		$next_timestamp = strtotime( $time_string );
	} else {
		$next_timestamp = strtotime( $time_string, $from_timestamp );
	}

	date_default_timezone_set( $original_timezone );
	// @codingStandardsIgnoreEnd

	return $next_timestamp;
}
