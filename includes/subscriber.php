<?php
/**
 * Subscriber API: Subscriber functions
 *
 * Contains functions for manipulating Noptin subscribers
 *
 * @since   1.2.7
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Queries the subscribers database.
 *
 * @param array $args Query arguments.
 * @param string $return See Hizzle\Noptin\DB\Main::query for allowed values.
 * @return int|array|\Hizzle\Noptin\DB\Subscriber[]|\Hizzle\Store\Query|WP_Error
 */
function noptin_get_subscribers( $args = array(), $return = 'results' ) {
	return noptin()->db()->query( 'subscribers', $args, $return );
}

/**
 * Fetch a subscriber by subscriber ID.
 *
 * @param int|string|\Hizzle\Noptin\DB\Subscriber $subscriber Subscriber ID, email, confirm key, or object.
 * @return \Hizzle\Noptin\DB\Subscriber Subscriber object.
 */
function noptin_get_subscriber( $subscriber = 0 ) {

	// Email or confirm key.
	if ( is_string( $subscriber ) && ! is_numeric( $subscriber ) ) {

		if ( is_email( $subscriber ) ) {
			$subscriber = get_noptin_subscriber_id_by_email( $subscriber );
		} else {
			$subscriber = get_noptin_subscriber_id_by_confirm_key( $subscriber );
		}
	}

	$subscriber = noptin()->db()->get( $subscriber );
	return is_wp_error( $subscriber ) ? noptin()->db()->get( 0 ) : $subscriber;
}

/**
 * Fetch subscriber id by confirm key.
 *
 * @param string $confirm_key Subscriber confirm key.
 * @return int|false Subscriber id if found, false otherwise.
 */
function get_noptin_subscriber_id_by_confirm_key( $confirm_key ) {
	return noptin()->db()->get_id_by_prop( 'confirm_key', $confirm_key, 'subscribers' );
}

/**
 * Retrieve subscriber meta field for a subscriber.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to retrieve. By default, returns data for all keys.
 * @param   bool   $single        If true, returns only the first value for the specified meta key. This parameter has no effect if $key is not specified.
 * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single is true.
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscriber_meta( $subscriber_id = 0, $meta_key = '', $single = false ) {
	return noptin()->db()->get_record_meta( $subscriber_id, $meta_key, $single );
}

/**
 * Adds subscriber meta field for a subscriber.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to update.
 * @param   mixed  $meta_value   Metadata value. Must be serializable if non-scalar.
 * @param   mixed  $unique   Whether the same key should not be added.
 * @return  int|false  Meta ID on success, false on failure.
 * @access  public
 * @since   1.0.5
 */
function add_noptin_subscriber_meta( $subscriber_id, $meta_key, $meta_value, $unique = false ) {
	return noptin()->db()->add_record_meta( $subscriber_id, $meta_key, $meta_value, $unique );
}

/**
 * Updates subscriber meta field for a subscriber.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the same key and subscriber ID.
 *
 * If the meta field for the subscriber does not exist, it will be added and its ID returned.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to update.
 * @param   mixed  $meta_value   Metadata value. Must be serializable if non-scalar.
 * @param   mixed  $prev_value   Previous value to check before updating.
 * @return  mixed  The new meta field ID if a field with the given key didn't exist and was therefore added, true on successful update, false on failure.
 * @access  public
 * @since   1.0.5
 */
function update_noptin_subscriber_meta( $subscriber_id, $meta_key, $meta_value, $prev_value = '' ) {
	return noptin()->db()->update_record_meta( $subscriber_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Deletes a subscriber meta field for the given subscriber ID.
 *
 * You can match based on the key, or key and value. Removing based on key and value, will keep from removing duplicate metadata with the same key. It also allows removing all metadata matching the key, if needed.
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to delete.
 * @param   mixed  $meta_value   Metadata value. Must be serializable if non-scalar.
 * @return  bool  True on success, false on failure.
 * @access  public
 * @since   1.0.5
 */
function delete_noptin_subscriber_meta( $subscriber_id, $meta_key, $meta_value = '' ) {
	return noptin()->db()->delete_record_meta( $subscriber_id, $meta_key, $meta_value );
}

/**
 * Determines if a meta field with the given key exists for the given noptin subscriber ID.
 *
 * @param int    $subscriber_id  ID of the subscriber metadata is for.
 * @param string $meta_key       Metadata key.
 *
 */
function noptin_subscriber_meta_exists( $subscriber_id, $meta_key ) {
	return noptin()->db()->record_meta_exists( $subscriber_id, $meta_key );
}

/**
 * Logs whenever a subscriber opens an email
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $campaign_id    The opened email campaign.
 * @access  public
 * @since   1.2.0
 * @return  void
 */
function log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id ) {
	$subscriber = noptin_get_subscriber( $subscriber_id );

	if ( $subscriber->exists() ) {
		$subscriber->record_opened_campaign( $campaign_id );
	}
}

/**
 * Retrieves all the campaigns a given subscriber has opened
 *
 * @deprecated 1.13.0
 * @since   1.2.0
 * @return  int[] Array of opened campaigns.
 */
function get_noptin_subscriber_opened_campaigns() {
	_deprecated_function( __FUNCTION__, '1.13.0' );
	return array();
}

/**
 * Checks whether a subscriber opened a given campaign
 *
 * @deprecated 1.13.0
 * @access  public
 * @since   1.2.0
 */
function did_noptin_subscriber_open_campaign() {
	_deprecated_function( __FUNCTION__, '1.13.0' );
	return false;
}

/**
 * Logs whenever a subscriber clicks on a link in an email
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $campaign_id    The email campaign.
 * @param   string $link    The clicked link.
 * @access  public
 * @since   1.2.0
 */
function log_noptin_subscriber_campaign_click( $subscriber_id, $campaign_id, $link ) {

	$subscriber = noptin_get_subscriber( $subscriber_id );

	if ( $subscriber->exists() ) {
		$subscriber->record_clicked_link( $campaign_id, $link );
	}
}

/**
 * Retrieves all the campaigns a given subscriber has clicked on a link in
 *
 * @deprecated 1.13.0
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_clicked_campaigns() {
	_deprecated_function( __FUNCTION__, '1.13.0' );
	return array();
}

/**
 * Checks whether a subscriber clicked on a link in a given campaign
 *
 * @deprecated 1.13.0
 * @access  public
 * @since   1.2.0
 */
function did_noptin_subscriber_click_campaign() {
	_deprecated_function( __FUNCTION__, '1.13.0' );
	return false;
}

/**
 * Retrieve subscriber merge fields.
 *
 * @param   int $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_merge_fields( $subscriber_id ) {
	$subscriber = noptin_get_subscriber( $subscriber_id );
	return $subscriber->get_data();
}

/**
 * Retrieves the URL to the subscribers page
 *
 * @return  string   The subscribers page url
 * @param   int $page the page to load.
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscribers_overview_url( $page = 1 ) {
	$url = admin_url( 'admin.php?page=noptin-subscribers' );
	return add_query_arg( 'paged', $page, $url );
}

/**
 * Retrieves the subscriber count
 *
 * @return  int   $where Restriction string
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscribers_count( $where = '', $meta_key = '', $meta_value = false ) {
	global $wpdb;

	$extra_sql  = '';

	if ( false !== $meta_value ) {
		$extra_sql = $wpdb->prepare(
			"INNER JOIN {$wpdb->prefix}noptin_subscriber_meta ON ( {$wpdb->prefix}noptin_subscribers.id = {$wpdb->prefix}noptin_subscriber_meta.noptin_subscriber_id ) WHERE ( {$wpdb->prefix}noptin_subscriber_meta.meta_key = %s AND {$wpdb->prefix}noptin_subscriber_meta.meta_value = %s )",
			$meta_key,
			$meta_value
		);
	}

	if ( ! empty( $where ) ) {

		if ( empty( $extra_sql ) ) {
			$where = "WHERE $where";
		} else {
			$where = "$extra_sql AND $where";
		}
	} else {
		$where = "$extra_sql";
	}

	return $wpdb->get_var( "SELECT COUNT(`id`) FROM {$wpdb->prefix}noptin_subscribers $where;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Inserts a new subscriber into the database
 *
 * This function returns the subscriber id if the subscriber exists.
 * It does not update the subscriber though.
 *
 * @access  public
 * @since   1.0.5
 * @return int|string Subscriber id on success, error on failure.
 */
function add_noptin_subscriber( $fields, $silent = false ) {
	global $wpdb;

	if ( empty( $fields['language'] ) && noptin_is_multilingual() ) {
		$fields['language'] = sanitize_text_field( get_locale() );
	}

	$table  = get_noptin_subscribers_table_name();
	$fields = noptin_clean( wp_unslash( apply_filters( 'new_noptin_subscriber_fields', $fields ) ) );

	// Ensure an email address is provided and it doesn't exist already.
	if ( empty( $fields['email'] ) || ! is_email( $fields['email'] ) ) {
		return __( 'Please provide a valid email address', 'newsletter-optin-box' );
	}

	// Abort if the email is not unique.
	$fields['email'] = sanitize_email( $fields['email'] );
	$subscriber_id   = get_noptin_subscriber_id_by_email( $fields['email'] );
	if ( ! empty( $subscriber_id ) ) {
		return (int) $subscriber_id;
	}

	// Maybe split name into first and last.
	if ( isset( $fields['name'] ) ) {
		$names = noptin_split_subscriber_name( $fields['name'] );

		$fields['first_name'] = empty( $fields['first_name'] ) ? $names[0] : trim( $fields['first_name'] );
		$fields['last_name']  = empty( $fields['last_name'] ) ? $names[1] : trim( $fields['last_name'] );
	}

	$database_fields = array(
		'email'        => $fields['email'],
		'first_name'   => empty( $fields['first_name'] ) ? '' : $fields['first_name'],
		'last_name'    => empty( $fields['last_name'] ) ? '' : $fields['last_name'],
		'confirm_key'  => isset( $fields['confirm_key'] ) ? $fields['confirm_key'] : md5( $fields['email'] . wp_generate_password( 32, true, true ) ),
		'date_created' => ! empty( $fields['date_created'] ) ? gmdate( 'Y-m-d', strtotime( $fields['date_created'] ) ) : current_time( 'Y-m-d' ),
		'active'       => isset( $fields['active'] ) ? (int) $fields['active'] : ( get_noptin_option( 'double_optin', false ) ? 1 : 0 ),
		'confirmed'    => ! empty( $fields['confirmed'] ),
	);

	if ( ! $wpdb->insert( $table, $database_fields, '%s' ) ) {
		return 'An error occurred. Try again.';
	}

	$id = $wpdb->insert_id;

	$fields = array_merge( $fields, $database_fields );

	unset( $fields['last_name'] );
	unset( $fields['name'] );

	// Insert additional meta data.
	foreach ( $fields as $field => $value ) {

		if ( isset( $database_fields[ $field ] ) || 'integration_data' === $field ) {
			continue;
		}

		update_noptin_subscriber_meta( $id, $field, $value );
	}

	setcookie( 'noptin_email_subscribed', $database_fields['confirm_key'], time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	$_GET['noptin_key'] = $database_fields['confirm_key'];

	$cookie = get_noptin_option( 'subscribers_cookie' );
	if ( ! empty( $cookie ) && is_string( $cookie ) ) {
		setcookie( $cookie, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	}

	if ( ! $silent ) {
		do_action( 'noptin_insert_subscriber', $id, $fields );
	}

	delete_transient( 'noptin_subscription_sources' );

	return $id;

}

/**
 * Updates a Noptin subscriber
 *
 * @param int|string|\Hizzle\Noptin\DB\Subscriber $subscriber Subscriber ID, email, confirm key, or object.
 * @param array $to_update The subscriber fields to update.
 * @access  public
 * @since   1.2.3
 */
function update_noptin_subscriber( $subscriber_id, $to_update = array() ) {

	// Get the subscriber object.
	$subscriber = noptin_get_subscriber( $subscriber_id );

	if ( ! $subscriber->exists() ) {
		return false;
	}

	// Set the subscriber properties.
	$subscriber->set_props( $to_update );

	// Save the subscriber.
	return $subscriber->save();
}

/**
 * Marks a subscriber as confirmed (Double Opt-in)
 *
 * @param int|string $subscriber Subscriber ID or email.
 * @since   1.3.2
 */
function confirm_noptin_subscriber_email( $subscriber ) {

	// Fetch subscriber.
	$subscriber = noptin_get_subscriber( $subscriber );

	if ( ! $subscriber->exists() || $subscriber->get_confirmed() ) {
		return;
	}

	// Update subscriber's IP address.
	$ip_address = noptin_get_user_ip();
	if ( ! empty( $ip_address ) && '::1' !== $ip_address ) {
		$subscriber->set_ip_address( noptin_get_user_ip() );
	}

	// Confirm them.
	$subscriber->set_confirmed( true );
	$subscriber->save();
}

/**
 * De-activates a Noptin subscriber
 *
 * @deprecated 1.13.0
 * @since      1.3.1
 */
function deactivate_noptin_subscriber( $subscriber ) {
	_deprecated_function( __FUNCTION__, '1.13.0', 'unsubscribe_noptin_subscriber' );
	unsubscribe_noptin_subscriber( $subscriber );
}

/**
 * Unsubscribes a subscriber.
 *
 * @access public
 * @since  1.3.2
 */
function unsubscribe_noptin_subscriber( $subscriber ) {

	// Fetch subscriber.
	$subscriber = noptin_get_subscriber( $subscriber );

	if ( ! $subscriber->exists() || ! $subscriber->is_active() ) {
		return;
	}

	$subscriber->set_status( 'unsubscribed' );
	$subscriber->save();
}

/**
 * Empties the subscriber cache.
 *
 * @deprecated 1.13.0
 * @since      1.2.8
 */
function clear_noptin_subscriber_cache() {
	_deprecated_function( __FUNCTION__, '1.13.0' );
}

/**
 * Retrieves a subscriber
 *
 * @access  public
 * @since   1.1.1
 * @param int|string|Noptin_Subscriber|object|array subscriber The subscribers's ID, email, confirm key, a Noptin_Subscriber object,
 *                                                                or a subscriber object from the DB.
 * @deprecated 1.13.0 User noptin_get_subscriber
 * @return Noptin_Subscriber
 */
function get_noptin_subscriber( $subscriber ) {
	_deprecated_function( __FUNCTION__, '1.13.0', 'noptin_get_subscriber' );
	return new Noptin_Subscriber( $subscriber );
}

/**
 * Retrieves a subscriber by email
 *
 * @access  public
 * @since   1.1.2
 * @param int|string|Noptin_Subscriber|object|array subscriber The subscriber to retrieve.
 * @deprecated 1.13.0 User noptin_get_subscriber
 * @return Noptin_Subscriber
 */
function get_noptin_subscriber_by_email( $email ) {
	_deprecated_function( __FUNCTION__, '1.13.0', 'noptin_get_subscriber' );
	return new Noptin_Subscriber( $email );
}

/**
 * Retrieves a subscriber id by email
 *
 * @access  public
 * @param int|string|Noptin_Subscriber|object|array subscriber The subscriber to retrieve.
 * @since   1.2.6
 * @return int|null
 */
function get_noptin_subscriber_id_by_email( $email ) {
	return noptin()->db()->get_id_by_prop( 'email', $email, 'subscribers' );
}

/**
 * Deletes a subscriber
 *
 * @access  public
 * @param int $subscriber_id The subscriber being deleted
 * @since   1.1.0
 */
function delete_noptin_subscriber( $subscriber_id ) {
	$subscriber = noptin_get_subscriber( $subscriber_id );
	return $subscriber->delete();
}

/**
 * Converts a name field into the first and last name
 *
 * Simple Function, Using Regex (word char and hyphens)
 * It makes the assumption the last name will be a single word.
 * Makes no assumption about middle names, that all just gets grouped into first name.
 * You could use it again, on the "first name" result to get the first and middle though.
 *
 * @access  public
 * @since   1.0.5
 */
function noptin_split_subscriber_name( $name ) {

	$name       = trim( $name );
	$last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
	$first_name = trim( preg_replace( '#' . $last_name . '#', '', $name ) );
	return array( $first_name, $last_name );

}

/**
 * Checks whether the subscriber with a given email exists.
 *
 * @param string $email The email to check for.
 * @since 1.0.5
 * @return bool
 */
function noptin_email_exists( $email ) {
	$id = get_noptin_subscriber_id_by_email( $email );
	return ! empty( $id );
}

/**
 * Retrieves the default double opt-in email details.
 *
 * @since 1.3.3
 * @return array
 */
function get_default_noptin_subscriber_double_optin_email() {

	return array(
		'email_subject'   => __( 'Please confirm your subscription', 'newsletter-optin-box' ),
		'hero_text'       => __( 'Please confirm your subscription', 'newsletter-optin-box' ),
		'email_body'      => sprintf(
			'%s %s %s',
			__( 'Tap the button below to confirm your subscription to our newsletter.', 'newsletter-optin-box' ),
			__( 'If you have received this email by mistake, you can safely delete it.', 'newsletter-optin-box' ),
			__( "You won't be subscribed if you don't click on the button below.", 'newsletter-optin-box' )
		),
		'cta_text'        => __( 'Confirm your subscription', 'newsletter-optin-box' ),
		'after_cta_text'  => sprintf(
			"%s\n\n[[confirmation_link]]\n\n%s\n[[noptin_company]]",
			__( "If that doesn't work, copy and paste the following link in your browser:", 'newsletter-optin-box' ),
			__( 'Cheers,', 'newsletter-optin-box' )
		),
		'permission_text' => __( "You are receiving this email because we got your request to subscribe to our newsletter. If you don't want to join the newsletter, you can safely delete this email", 'newsletter-optin-box' ),
	);

}

/**
 * Whether to use custom double opt-in email.
 *
 * @return bool
 * @since 1.12.0
 */
function use_custom_noptin_double_optin_email() {
	$use_custom_email = (bool) get_noptin_option( 'disable_double_optin_email' );
	return apply_filters( 'use_custom_noptin_double_optin_email', $use_custom_email );
}

/**
 * Whether double opt-in is enabled.
 *
 * @return bool
 * @since 1.12.0
 */
function noptin_has_enabled_double_optin() {
	$has_enabled = (bool) get_noptin_option( 'double_optin', false );
	return apply_filters( 'noptin_has_enabled_double_optin', $has_enabled );
}

/**
 * Sends double optin emails.
 *
 * @param int $id The id of the new subscriber.
 * @since 1.2.4
 */
function send_new_noptin_subscriber_double_optin_email( $id ) {

	// Abort if double opt-in is disabled.
	$double_optin = noptin_has_enabled_double_optin() && ! use_custom_noptin_double_optin_email();
	if ( empty( $double_optin ) && doing_action( 'noptin_subscriber_created' ) ) {
		return false;
	}

	$subscriber = noptin_get_subscriber( $id );

	// Abort if the subscriber is missing or confirmed.
	if ( ! $subscriber->exists() || $subscriber->get_confirmed() ) {
		return false;
	}

	$defaults = get_default_noptin_subscriber_double_optin_email();
	$content  = get_noptin_option( 'double_optin_email_body', $defaults['email_body'] );
	$content .= '<p>[[button url="[[confirmation_url]]" text="[[confirmation_text]]"]]</p>';
	$content .= get_noptin_option( 'double_optin_after_cta_text', $defaults['after_cta_text'] );

	// Handle custom merge tags.
	$url  = $subscriber->get_confirm_subscription_url();
	$link = "<a href='$url' target='_blank'>$url</a>";

	$merge_tags = array(
		'confirmation_link' => $link,
		'confirmation_url'  => $url,
		'confirmation_text' => get_noptin_option( 'double_optin_cta_text', $defaults['cta_text'] ),
	);

	foreach ( $merge_tags as $key => $value ) {

		if ( is_scalar( $key ) ) {
			$content = str_replace( "[[$key]]", wp_kses_post( $value ), $content );
		}
	}

	$args = array(
		'type'        => 'normal',
		'content'     => wpautop( trim( $content ) ),
		'template'    => get_noptin_option( 'email_template', 'paste' ),
		'heading'     => get_noptin_option( 'double_optin_hero_text', $defaults['hero_text'] ),
		'footer_text' => get_noptin_option( 'double_optin_permission_text', $defaults['permission_text'] ),
	);

	noptin()->emails->newsletter->subscriber = $subscriber;
	noptin()->emails->newsletter->register_merge_tags();

	$generator     = new Noptin_Email_Generator();
	$email_body    = $generator->generate( $args );
	$email_subject = noptin_parse_email_subject_tags( get_noptin_option( 'double_optin_email_subject', $defaults['email_subject'] ) );

	foreach ( array_keys( noptin()->emails->newsletter->get_subscriber_merge_tags() ) as $tag ) {
		noptin()->emails->tags->remove_tag( $tag );
	}

	// Send the email.
	return noptin_send_email(
		array(
			'recipients'               => $subscriber->get_email(),
			'subject'                  => $email_subject,
			'message'                  => $email_body,
			'headers'                  => array(),
			'attachments'              => array(),
			'reply_to'                 => '',
			'from_email'               => '',
			'from_name'                => '',
			'content_type'             => 'html',
			'unsubscribe_url'          => '',
			'disable_template_plugins' => ! ( 'default' === $args['template'] ),
		)
	);

}
add_action( 'noptin_subscriber_created', 'send_new_noptin_subscriber_double_optin_email' );

/**
 *  Returns the name of the subscribers' table
 *
 * @since 1.2.2
 * @deprecated 1.13.0
 * @return string The name of our subscribers table
 */
function get_noptin_subscribers_table_name() {
	_deprecated_function( __FUNCTION__, '1.13.0' );
	return $GLOBALS['wpdb']->prefix . 'noptin_subscribers';
}

/**
 *  Returns the name of the subscribers' meta table
 *
 * @since 1.2.2
 * @return string The name of our subscribers meta table
 */
function get_noptin_subscribers_meta_table_name() {
	_deprecated_function( __FUNCTION__, '1.13.0' );
	return $GLOBALS['wpdb']->prefix . 'noptin_subscriber_meta';
}

/**
 * Retrieves the current user's Noptin subscriber id.
 *
 * @return  false|int Subscriber id or false on failure.
 * @access  public
 * @since   1.5.1
 */
function get_current_noptin_subscriber_id() {

	// If the user is logged in, check with their email address.
	$user_data = wp_get_current_user();
	if ( ! empty( $user_data->user_email ) ) {
		$subscriber_id = get_noptin_subscriber_id_by_email( $user_data->user_email );

		if ( ! empty( $subscriber_id ) ) {
			return $subscriber_id;
		}
	}

	// Try retrieveing subscriber key.
	$subscriber_key = '';
	if ( ! empty( $_GET['noptin_key'] ) ) {
		$subscriber_key = sanitize_text_field( urldecode( $_GET['noptin_key'] ) );
	} elseif ( ! empty( $_COOKIE['noptin_email_subscribed'] ) ) {
		$subscriber_key = sanitize_text_field( $_COOKIE['noptin_email_subscribed'] );
	}

	// If we have a subscriber key, use it to retrieve the subscriber.
	if ( ! empty( $subscriber_key ) ) {
		$subscriber_id = get_noptin_subscriber_id_by_confirm_key( $subscriber_key );

		if ( ! empty( $subscriber_id ) ) {
			return $subscriber_id;
		}
	}

	return false;
}

/**
 * Checks if the currently displayed user is subscribed to the newsletter.
 *
 * @since 1.4.4
 * @return bool
 */
function noptin_is_subscriber() {
	$id = get_current_noptin_subscriber_id();

	if ( ! empty( $id ) ) {
		return true;
	}

	$cookie = get_noptin_option( 'subscribers_cookie' );
	if ( ! empty( $cookie ) && is_string( $cookie ) && ! empty( $_COOKIE[ $cookie ] ) ) {
		return true;
	}

	return false;

}

/**
 * Callback for the `[noptin-show-if-subscriber]` shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param string $content Shortcode content.
 * @since 1.4.4
 * @ignore
 * @private
 */
function _noptin_show_if_subscriber( $atts, $content ) {

	if ( noptin_is_subscriber() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'noptin-show-if-subscriber', '_noptin_show_if_subscriber' );

/**
 * Callback for the `[noptin-show-if-non-subscriber]` shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param string $content Shortcode content.
 * @since 1.4.4
 * @ignore
 * @private
 */
function _noptin_show_if_non_subscriber( $atts, $content ) {

	if ( ! noptin_is_subscriber() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'noptin-show-if-non-subscriber', '_noptin_show_if_non_subscriber' );

/**
 * Callback for the `[noptin-subscriber-count]` shortcode.
 *
 * @ignore
 * @since 1.4.4
 * @private
 */
function _noptin_show_subscriber_count() {
	return get_noptin_subscribers_count();
}
add_shortcode( 'noptin-subscriber-count', '_noptin_show_subscriber_count' );

/**
 * Callback for the `[noptin-subscriber-field]` shortcode.
 *
 * @ignore
 * @since 1.4.4
 * @param array $atts Shortcode attributes.
 * @private
 */
function _noptin_show_subscriber_field( $atts ) {

	$subscriber = noptin_get_subscriber( get_current_noptin_subscriber_id() );

	if ( empty( $atts['field'] ) || ! $subscriber->exists() || ! $subscriber->has_prop( $atts['field'] ) ) {
		return '';
	}

	$value = $subscriber->get( $atts['field'] );
	return is_scalar( $value ) ? esc_html( $value ) : esc_html( wp_json_encode( $value ) );

}
add_shortcode( 'noptin-subscriber-field', '_noptin_show_subscriber_field' );

/**
 * Returns an array of available custom field types.
 *
 * @since 1.5.5
 * @see Noptin_Custom_Fields::get_custom_field_types
 * @return array
 */
function get_noptin_custom_field_types() {

	$field_types = apply_filters(
		'noptin_custom_field_types',
		array(
			'email'      => array(
				'predefined' => true,
				'merge_tag'  => 'email',
				'label'      => __( 'Email Address', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Email',
			),
			'first_name' => array(
				'predefined' => true,
				'merge_tag'  => 'first_name',
				'label'      => __( 'First Name', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Text',
			),
			'last_name'  => array(
				'predefined' => true,
				'merge_tag'  => 'last_name',
				'label'      => __( 'Last Name', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Text',
			),
			'language'   => array(
				'predefined' => true,
				'merge_tag'  => 'language',
				'label'      => __( 'Language', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Language',
			),
			'text'       => array(
				'predefined' => false,
				'label'      => __( 'Text Input', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Text',
			),
			'textarea'   => array(
				'predefined' => false,
				'label'      => __( 'Textarea Input', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Textarea',
			),
			'number'     => array(
				'predefined' => false,
				'label'      => __( 'Number Input', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Number',
			),
			'radio'      => array(
				'predefined'       => false,
				'supports_options' => true,
				'label'            => __( 'Radio Buttons', 'newsletter-optin-box' ),
				'class'            => 'Noptin_Custom_Field_Radio',
			),
			'dropdown'   => array(
				'predefined'       => false,
				'supports_options' => true,
				'label'            => __( 'Dropdown', 'newsletter-optin-box' ),
				'class'            => 'Noptin_Custom_Field_Dropdown',
			),
			'date'       => array(
				'predefined' => false,
				'label'      => __( 'Date', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Date',
			),
			'checkbox'   => array(
				'predefined' => false,
				'label'      => __( 'Checkbox', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Checkbox',
			),
		)
	);

	if ( ! noptin_is_multilingual() && isset( $field_types['language'] ) ) {
		unset( $field_types['language'] );
	}

	return $field_types;
}

/**
 * Displays a custom field input.
 *
 * @since 1.5.5
 * @see Noptin_Custom_Field_Type::output
 * @param array $custom_field
 * @param false|Noptin_Subscriber $subscriber
 */
function display_noptin_custom_field_input( $custom_field, $subscriber = false ) {
	$custom_field['name']  = empty( $custom_field['wrap_name'] ) ? $custom_field['merge_tag'] : 'noptin_fields[' . $custom_field['merge_tag'] . ']';
	$custom_field['value'] = empty( $subscriber ) ? '' : $subscriber->get( $custom_field['merge_tag'] );

	if ( empty( $custom_field['id'] ) ) {
		$custom_field['id']    = empty( $custom_field['show_id'] ) ? uniqid( sanitize_html_class( $custom_field['merge_tag'] ) . '_' ) : 'noptin_field_' . sanitize_html_class( $custom_field['merge_tag'] );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ( '' === $custom_field['value'] || array() === $custom_field['value'] ) && ! empty( $_POST ) ) {

		// Below is cleaned on output.
		if ( isset( $_POST['noptin_fields'][ $custom_field['merge_tag'] ] ) ) {
			$custom_field['value'] = $_POST['noptin_fields'][ $custom_field['merge_tag'] ];
		} elseif ( isset( $_POST[ $custom_field['merge_tag'] ] ) ) {
			$custom_field['value'] = $_POST[ $custom_field['merge_tag'] ];
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	do_action( 'noptin_display_custom_field_input', $custom_field, $subscriber );
	do_action( "noptin_display_{$custom_field['type']}_input", $custom_field, $subscriber );
}

/**
 * Sanitize a custom field value.
 *
 * @since 1.5.5
 * @see Noptin_Custom_Field_Type::sanitize_value
 * @param mixed $value
 * @param string $type
 * @param false|Noptin_Subscriber $subscriber
 */
function sanitize_noptin_custom_field_value( $value, $type, $subscriber = false ) {
	return apply_filters( "noptin_sanitize_{$type}_value", $value, $subscriber );
}

/**
 * Formats a custom field value for display.
 *
 * @since 1.5.5
 * @see Noptin_Custom_Field_Type::sanitize_value
 * @param mixed $value
 * @param string $type
 * @param Noptin_Subscriber $subscriber
 */
function format_noptin_custom_field_value( $value, $type, $subscriber ) {
	return apply_filters( "noptin_format_{$type}_value", $value, $subscriber );
}

/**
 * Checks if a custom field should be stored in the subscribers table.
 *
 * @param string $field_type
 * @since 1.13.0
 * @return bool
 */
function noptin_store_custom_field_in_subscribers_table( $field_type ) {
	return apply_filters( "noptin_{$field_type}_store_in_subscribers_table", false );
}

/**
 * Converts a custom field to schema.
 *
 * @param array $custom_field
 * @since 1.13.0
 * @return array
 */
function noptin_convert_custom_field_to_schema( $custom_field ) {
	$field_type = $custom_field['type'];
	return apply_filters( "noptin_filter_{$field_type}_schema", array(), $custom_field );
}

/**
 * Fetches the meta keys to migrate.
 *
 * @param array $custom_field
 * @since 1.13.0
 * @return array
 */
function noptin_fetch_custom_field_meta_to_migrate( $custom_field ) {
	$field_type = $custom_field['type'];
	return apply_filters( "noptin_filter_{$field_type}_meta_to_migrate", array(), $custom_field );
}

/**
 * Returns an array of available custom fields.
 *
 * @param bool $public_only
 * @since 1.5.5
 * @return array
 */
function get_noptin_custom_fields( $public_only = false ) {

	// Fetch available fields.
	$custom_fields = get_noptin_option(
		'custom_fields',
		Noptin_Custom_Fields::default_fields()
	);

	// Remove birthday field.
	$custom_fields = wp_list_filter( $custom_fields, array( 'type' => 'birthday' ), 'NOT' );

	// Maybe add the locale field.
	$has_language_field = current( wp_list_filter( $custom_fields, array( 'type' => 'language' ) ) );

	if ( noptin_is_multilingual() && ! $has_language_field ) {

		$custom_fields[] = array(
			'type'       => 'language',
			'merge_tag'  => 'language',
			'label'      => __( 'Language', 'newsletter-optin-box' ),
			'visible'    => false,
			'subs_table' => false,
			'required'   => false,
			'predefined' => true,
		);
	} elseif ( ! noptin_is_multilingual() && $has_language_field ) {
		$custom_fields = wp_list_filter( $custom_fields, array( 'type' => 'language' ), 'NOT' );
	}

	// Clean the fields.
	$custom_fields = apply_filters( 'noptin_custom_fields', $custom_fields );
	$fields        = array();

	foreach ( $custom_fields as $index => $field ) {
		$prepared_field = array(
			'field_key' => uniqid( 'noptin_' ) . $index,
		);

		foreach ( $field as $key => $value ) {

			if ( in_array( $key, array( 'visible', 'subs_table', 'predefined', 'required' ), true ) ) {
				$prepared_field[ $key ] = ! empty( $value );
			} elseif ( in_array( $key, array( 'options' ), true ) ) {
				$prepared_field[ $key ] = sanitize_textarea_field( $value );
			} else {
				$prepared_field[ $key ] = noptin_clean( $value );
			}
		}

		if ( 'email' === $field['merge_tag'] ) {
			$prepared_field['subs_table'] = true;
		}

		$fields[ $index ] = $prepared_field;
	}

	// Maybe return public fields only.
	if ( $public_only ) {
		$fields = wp_list_filter( $fields, array( 'visible' => true ) );
	}

	return $fields;
}

/**
 * Returns a single custom field.
 *
 * @since 1.5.5
 * @return array|false Array of field data or false if the field does not exist.
 */
function get_noptin_custom_field( $merge_tag ) {

	// Maybe remove the cf_ prefix.
	if ( 'cf_' === substr( $merge_tag, 0, 3 ) ) {
		$merge_tag = substr( $merge_tag, 3 );
	}

	$custom_field = wp_list_filter( get_noptin_custom_fields(), array( 'merge_tag' => trim( $merge_tag ) ) );
	return current( $custom_field );
}

/**
 * Returns available subscriber smart tags.
 *
 * @since 1.9.0
 * @return array
 */
function get_noptin_subscriber_smart_tags() {

	$smart_tags = array(
		'_subscriber_via' => array(
			'label'             => __( 'Subscription Method', 'newsletter-optin-box' ),
			'options'           => noptin_get_subscription_sources(),
			'description'       => __( 'Filter subscribers by how they subscribed.', 'newsletter-optin-box' ),
			'conditional_logic' => 'string',
		),
		'ip_address'      => array(
			'label'             => __( 'IP Address', 'newsletter-optin-box' ),
			'options'           => false,
			'description'       => __( 'Filter subscribers by their IP address.', 'newsletter-optin-box' ),
			'conditional_logic' => 'string',
		),
		'conversion_page' => array(
			'label'             => __( 'Conversion Page', 'newsletter-optin-box' ),
			'options'           => false,
			'description'       => __( 'Filter subscribers by the page they converted on.', 'newsletter-optin-box' ),
			'conditional_logic' => 'string',
		),
	);

	foreach ( get_noptin_custom_fields() as $custom_field ) {

		$options           = false;
		$conditional_logic = false;

		// Checkbox
		if ( 'checkbox' === $custom_field['type'] ) {

			$options = array(
				'1' => __( 'Yes', 'newsletter-optin-box' ),
				'0' => __( 'No', 'newsletter-optin-box' ),
			);

			$conditional_logic = 'string';

			// Select | Radio.
		} elseif ( 'dropdown' === $custom_field['type'] || 'radio' === $custom_field['type'] ) {

			if ( ! empty( $custom_field['options'] ) ) {
				$options = noptin_newslines_to_array( $custom_field['options'] );
			}

			$conditional_logic = 'string';

		} elseif ( 'language' === $custom_field['type'] && noptin_is_multilingual() ) {

			$options           = apply_filters( 'noptin_multilingual_active_languages', array() );
			$conditional_logic = 'string';

		} elseif ( 'date' === $custom_field['type'] ) {

			$conditional_logic = 'date';

		} elseif ( 'number' === $custom_field['type'] ) {

			$conditional_logic = 'number';

		} elseif ( 'text' === $custom_field['type'] || 'textarea' === $custom_field['type'] || 'email' === $custom_field['type'] ) {

			$conditional_logic = 'string';

		}

		$smart_tags[ $custom_field['merge_tag'] ] = array(
			'label'             => sanitize_text_field( $custom_field['label'] ),
			'options'           => $options,
			'description'       => sprintf(
				// translators: %s is the field label.
				__( 'Filter subscribers by %s', 'newsletter-optin-box' ),
				sanitize_text_field( $custom_field['label'] )
			),
			'type'              => $custom_field['type'],
			'conditional_logic' => $conditional_logic,
		);

	}

	return apply_filters( 'noptin_known_subscriber_smart_tags', $smart_tags );
}

/**
 * Returns available subscriber filters.
 *
 * @since 1.8.0
 * @return array
 */
function get_noptin_subscriber_filters() {

	return apply_filters(
		'noptin_subscriber_filters',
		wp_list_filter(
			get_noptin_subscriber_smart_tags(),
			array( 'options' => false ),
			'NOT'
		)
	);
}

/**
 * Checks if a subscriber meets the specified filters.
 *
 * @param array $conditional_logic
 * @param Noptin_Subscriber $subscriber
 * @since 1.8.0
 * @return array
 */
function noptin_subscriber_meets_conditional_logic( $conditional_logic, $subscriber ) {

	// Abort if no conditional logic is set.
	if ( empty( $conditional_logic['enabled'] ) ) {
		return true;
	}

	// Retrieve the conditional logic.
	$action      = $conditional_logic['action']; // allow or prevent.
	$type        = $conditional_logic['type']; // all or any.
	$rules_met   = 0;
	$rules_total = count( $conditional_logic['rules'] );

	// Loop through each rule.
	foreach ( $conditional_logic['rules'] as $rule ) {
		$is_rule_met = $rule['value'] === $subscriber->get( $rule['type'] );
		$should_meet = 'is' === $rule['condition'];

		// If the rule is met.
		if ( $is_rule_met === $should_meet ) {

			// Increment the number of rules met.
			$rules_met ++;

			// If we're using the "any" condition, we can stop here.
			if ( 'any' === $type ) {
				break;
			}
		}
	}

	// Check if the conditions are met.
	if ( 'all' === $type ) {
		$is_condition_met = $rules_met === $rules_total;
	} else {
		$is_condition_met = $rules_met > 0;
	}

	// Return the result.
	return 'allow' === $action ? $is_condition_met : ! $is_condition_met;
}

/**
 * Retrieves Noptin subscription sources.
 *
 * @param string $source Subscrption source.
 * @since 1.5.5
 * @return string
 */
function noptin_format_subscription_source( $source ) {

	if ( is_null( $source ) ) {
		return __( 'Unknown', 'newsletter-optin-box' );
	}

	if ( is_numeric( $source ) ) {
		$title = get_the_title( $source );

		if ( empty( $title ) ) {
			return __( 'Newsletter Form', 'newsletter-optin-box' );
		}

		$url = get_edit_post_link( (int) $source, 'url' );
		return empty( $url ) ? $title : "<a href='$url'>$title</a>";
	}

	if ( 'default_user' === $source ) {
		return __( 'Default', 'newsletter-optin-box' );
	}

	$sources = noptin_get_subscription_sources();

	if ( isset( $sources[ $source ] ) ) {
		return $sources[ $source ];
	}

	return $source;
}

/**
 * Clears cache of known subscription sources.
 *
 * @since 1.13.0
 * @return array
 */
function noptin_clear_subscription_sources_cache() {
	delete_transient( 'noptin_subscription_sources' );
}
add_action( 'noptin_subscriber_created', 'noptin_clear_subscription_sources_cache' );
add_action( 'noptin_subscriber_updated', 'noptin_clear_subscription_sources_cache' );

/**
 * Retrieves a list of known subscription sources.
 *
 * @since 1.7.0
 * @return array
 */
function noptin_get_subscription_sources() {
	global $wpdb;

	// Fetch from cache.
	$sources = get_transient( 'noptin_subscription_sources' );

	if ( $sources ) {
		return apply_filters( 'noptin_subscription_sources', $sources );
	}

	// Fetch saved sources.
	$existing = $wpdb->get_col( "SELECT DISTINCT `meta_value` FROM {$wpdb->prefix}noptin_subscriber_meta WHERE `meta_key`='_subscriber_via'" );
	$sources  = array_combine( $existing, $existing );

	// Add subscription forms.
	$forms = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'noptin-form',
			'post_status' => 'publish',
		)
	);

	foreach ( $forms as $form ) {
		$sources[ "{$form->ID}" ] = sanitize_text_field( $form->post_title );
	}

	// Add other known sources.
	$sources['manual']     = __( 'Manually Added', 'newsletter-optin-box' );
	$sources['shortcode']  = __( 'Subscription Shortcode', 'newsletter-optin-box' );
	$sources['users_sync'] = __( 'Users Sync', 'newsletter-optin-box' );
	$sources['import']     = __( 'Imported', 'newsletter-optin-box' );

	// Cache.
	set_transient( 'noptin_subscription_sources', $sources, HOUR_IN_SECONDS );

	return apply_filters( 'noptin_subscription_sources', $sources );
}

/**
 * Returns a URL to delete a subscriber.
 *
 * @param int $subscriber_id
 * @since 1.7.0
 * @return string
 */
function noptin_subscriber_delete_url( $subscriber_id ) {

	return wp_nonce_url(
		add_query_arg(
			array(
				'subscriber_id'       => $subscriber_id,
				'page'                => 'noptin-subscribers',
				'noptin_admin_action' => 'noptin_delete_email_subscriber',
			),
			admin_url( 'admin.php' )
		),
		'noptin_delete_subscriber',
		'noptin_nonce'
	);

}

/**
 * Records a subscriber's activity.
 *
 * @param string $email_address
 * @param string $activity The activity to record.
 */
function noptin_record_subscriber_activity( $email_address, $activity ) {

	// Get the subscriber.
	$subscriber = noptin_get_subscriber( $email_address );

	if ( ! $subscriber->exists() && ( ! is_string( $email_address ) || ! is_email( $email_address ) ) ) {
		return;
	}

	if ( $subscriber->exists() ) {
		$email_address = $subscriber->get_email();
	}

	do_action( 'noptin_record_subscriber_activity', $email_address, $activity, $subscriber );

	if ( $subscriber->exists() ) {
		$subscriber->record_activity( $activity );
		$subscriber->save();
	}

}

/**
 * Returns the maximum number of allowed subscribers.
 *
 * @return int Zero if unlimited.
 */
function noptin_max_allowed_subscribers() {
	return apply_filters( 'noptin_max_allowed_subscribers', 0 );
}

/**
 * Returns known subscriber statuses.
 *
 * @return array
 */
function noptin_get_subscriber_statuses() {
	return apply_filters(
		'noptin_get_subscriber_statuses',
		array(
			'subscribed'   => __( 'Subscribed', 'newsletter-optin-box' ),
			'unsubscribed' => __( 'Unsubscribed', 'newsletter-optin-box' ),
			'pending'      => __( 'Pending', 'newsletter-optin-box' ),
		)
	);
}
