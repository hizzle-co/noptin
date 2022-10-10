<?php
/**
 * Subscriber API: Subscriber functions
 *
 * Contains functions for manipulating Noptin subscribers
 *
 * @since             1.2.7
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
	return get_metadata( 'noptin_subscriber', $subscriber_id, $meta_key, $single );
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
	return add_metadata( 'noptin_subscriber', $subscriber_id, $meta_key, $meta_value, $unique );
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
	return update_metadata( 'noptin_subscriber', $subscriber_id, $meta_key, $meta_value, $prev_value );
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
	return delete_metadata( 'noptin_subscriber', $subscriber_id, $meta_key, $meta_value );
}

/**
 * Determines if a meta field with the given key exists for the given noptin subscriber ID.
 *
 * @param int    $subscriber_id  ID of the subscriber metadata is for.
 * @param string $meta_key       Metadata key.
 *
 */
function noptin_subscriber_meta_exists( $subscriber_id, $meta_key ) {
	return metadata_exists( 'noptin_subscriber', $subscriber_id, $meta_key );
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

	$opened_campaigns = get_noptin_subscriber_opened_campaigns( $subscriber_id );
	if ( ! in_array( (int) $campaign_id, $opened_campaigns, true ) ) {

		// Log the campaign open.
		$opened_campaigns[] = $campaign_id;
		update_noptin_subscriber_meta( $subscriber_id, '_opened_campaigns', $opened_campaigns );

		// Fire action.
		do_action( 'log_noptin_subscriber_campaign_open', $subscriber_id, $campaign_id );

		return true;
	}

	return false;
}

/**
 * Retrieves all the campaigns a given subscriber has opened
 *
 * @param   int $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 * @return  int[] Array of opened campaigns.
 */
function get_noptin_subscriber_opened_campaigns( $subscriber_id ) {

	$opened_campaigns = get_noptin_subscriber_meta( $subscriber_id, '_opened_campaigns', true );
	if ( empty( $opened_campaigns ) ) {
		$opened_campaigns = array();
	}
	return wp_parse_id_list( $opened_campaigns );

}

/**
 * Checks whether a subscriber opened a given campaign
 *
 * @param   int $subscriber_id  Subscriber ID.
 * @param   int $campaign_id    The campaign to check for.
 * @access  public
 * @since   1.2.0
 */
function did_noptin_subscriber_open_campaign( $subscriber_id, $campaign_id ) {

	$opened_campaigns = get_noptin_subscriber_opened_campaigns( $subscriber_id );
	return in_array( (int) $campaign_id, $opened_campaigns, true );

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

	$clicked_campaigns = get_noptin_subscriber_clicked_campaigns( $subscriber_id );

	// Ensure we have an array.
	if ( ! isset( $clicked_campaigns[ $campaign_id ] ) ) {
		$clicked_campaigns[ $campaign_id ] = array();
	}

	if ( ! in_array( $link, $clicked_campaigns[ $campaign_id ], true ) ) {

		// Log the campaign click.
		$clicked_campaigns[ $campaign_id ][] = noptin_clean( $link );
		update_noptin_subscriber_meta( $subscriber_id, '_clicked_campaigns', $clicked_campaigns );

		// Fire action.
		do_action( 'log_noptin_subscriber_campaign_click', $subscriber_id, $campaign_id, $link );

		return true;
	}

	return false;

}

/**
 * Retrieves all the campaigns a given subscriber has clicked on a link in
 *
 * @param   int $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_clicked_campaigns( $subscriber_id ) {

	$clicked_campaigns = get_noptin_subscriber_meta( $subscriber_id, '_clicked_campaigns', true );
	if ( empty( $clicked_campaigns ) ) {
		$clicked_campaigns = array();
	}
	return $clicked_campaigns;

}

/**
 * Checks whether a subscriber clicked on a link in a given campaign
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   int    $campaign_id    The campaign id to check for a click from.
 * @param   string $link        Optional. The specific link to check for.
 * @access  public
 * @since   1.2.0
 */
function did_noptin_subscriber_click_campaign( $subscriber_id, $campaign_id, $link = false ) {

	$clicked_campaigns = get_noptin_subscriber_clicked_campaigns( $subscriber_id );

	if ( empty( $clicked_campaigns[ $campaign_id ] ) ) {
		return false;
	}

	if ( empty( $link ) ) {
		return true;
	}

	return in_array( noptin_clean( $link ), $clicked_campaigns[ $campaign_id ], true );

}

/**
 * Retrieve subscriber merge fields.
 *
 * @param   int $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_merge_fields( $subscriber_id ) {
	$subscriber = get_noptin_subscriber( $subscriber_id );

	if ( ! $subscriber->exists() ) {
		return array();
	}

	$merge_tags                    = $subscriber->to_array();
	$merge_tags['unsubscribe_url'] = get_noptin_action_url( 'unsubscribe', $subscriber->confirm_key );
	$merge_tags['resubscribe_url'] = get_noptin_action_url( 'resubscribe', $subscriber->confirm_key );
	$meta                          = $subscriber->get_meta();

	foreach ( $meta as $key => $values ) {

		if ( isset( $values[0] ) && is_scalar( maybe_unserialize( $values[0] ) ) ) {
			$merge_tags[ $key ] = esc_html( maybe_unserialize( $values[0] ) );
		}
	}

	$merge_tags['name']      = trim( $merge_tags['first_name'] . ' ' . $merge_tags['second_name'] );
	$merge_tags['last_name'] = $merge_tags['second_name'];

	return apply_filters( 'noptin_subscriber_merge_fields', $merge_tags, $subscriber, $meta );
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
		'second_name'  => empty( $fields['last_name'] ) ? '' : $fields['last_name'],
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

	return $id;

}

/**
 * Updates a Noptin subscriber
 *
 * @access  public
 * @since   1.2.3
 */
function update_noptin_subscriber( $subscriber_id, $details = array(), $silent = false ) {
	global $wpdb;
	$subscriber_id = absint( $subscriber_id );

	// Ensure the subscriber exists.
	$subscriber = get_noptin_subscriber( $subscriber_id );
	if ( ! $subscriber->exists() ) {
		return false;
	}

	// Prepare main variables.
	$table     = get_noptin_subscribers_table_name();
	$fields    = noptin_clean( wp_unslash( $details ) );
	$to_update = array();

	// Maybe split name into first and last.
	if ( isset( $fields['name'] ) ) {
		$names = noptin_split_subscriber_name( $fields['name'] );

		$fields['first_name']  = empty( $fields['first_name'] ) ? $names[0] : trim( $fields['first_name'] );
		$fields['second_name'] = empty( $fields['second_name'] ) ? $names[1] : trim( $fields['second_name'] );
		unset( $fields['name'] );

	}

	if ( isset( $fields['last_name'] ) ) {
		$fields['second_name']  = empty( $fields['second_name'] ) ? trim( $fields['last_name'] ) : $fields['second_name'];
		unset( $fields['last_name'] );
	}

	if ( isset( $fields['id'] ) ) {
		unset( $fields['id'] );
	}

	// Subscriber email confirmation.
	if ( ! empty( $fields['confirmed'] ) && empty( $subscriber->confirmed ) && ! $silent ) {
		confirm_noptin_subscriber_email( $subscriber );
	}

	// Are we deactivating the subscriber?
	if ( $subscriber->is_active() && ! empty( $fields['active'] ) && ! $silent ) {
		deactivate_noptin_subscriber( $subscriber );
	}

	foreach ( noptin_parse_list( 'email first_name second_name confirm_key date_created active confirmed' ) as $field ) {
		if ( isset( $fields[ $field ] ) ) {
			$to_update[ $field ] = noptin_clean( $fields[ $field ] );
			unset( $fields[ $field ] );
		}
	}

	if ( ! empty( $to_update ) ) {
		$wpdb->update( $table, $to_update, array( 'id' => $subscriber_id ) );
	}

	// Insert additional meta data.
	foreach ( $fields as $field => $value ) {

		if ( 'name' === $field || 'integration_data' === $field ) {
			continue;
		}

		if ( '' === $value ) {
			delete_noptin_subscriber_meta( $subscriber_id, $field );
		} else {
			update_noptin_subscriber_meta( $subscriber_id, $field, $value );
		}
	}

	// Clean the cache.
	$old_subscriber = new Noptin_Subscriber( $subscriber_id );
	$old_subscriber->clear_cache();

	if ( ! $silent ) {
		do_action( 'noptin_update_subscriber', $subscriber_id, $details );
	}

	return true;

}

/**
 * Marks a subscriber as confirmed (Double Opt-in)
 *
 * @access  public
 * @since   1.3.2
 */
function confirm_noptin_subscriber_email( $subscriber ) {
	global $wpdb;

	// Prepare the subscriber.
	$subscriber = new Noptin_Subscriber( $subscriber );
	if ( ! $subscriber->exists() || ! empty( $subscriber->confirmed ) ) {
		return false;
	}

	do_action( 'noptin_before_confirm_subscriber_email', $subscriber );

	$table = get_noptin_subscribers_table_name();
	$wpdb->update(
		$table,
		array(
			'active'    => 0,
			'confirmed' => 1,
		),
		array( 'id' => $subscriber->id ),
		'%d',
		'%d'
	);

	$subscriber->clear_cache();

	update_noptin_subscriber_meta( $subscriber->id, 'confirmed_on', current_time( 'mysql' ) );
	do_action( 'noptin_subscriber_confirmed', new Noptin_Subscriber( $subscriber->id ) );

	return true;

}

/**
 * De-activates a Noptin subscriber
 *
 * @access  public
 * @since   1.3.1
 */
function deactivate_noptin_subscriber( $subscriber ) {
	global $wpdb;

	// Prepare the subscriber.
	$subscriber = new Noptin_Subscriber( $subscriber );
	if ( ! $subscriber->exists() || $subscriber->is_virtual || ! $subscriber->is_active() ) {
		return false;
	}

	do_action( 'noptin_before_deactivate_subscriber', $subscriber );

	$wpdb->update(
		get_noptin_subscribers_table_name(),
		array( 'active' => 1 ),
		array( 'id' => $subscriber->id ),
		'%d',
		'%d'
	);

	update_noptin_subscriber_meta( $subscriber->id, 'unsubscribed_on', current_time( 'mysql' ) );
	$subscriber->clear_cache();

	return true;

}

/**
 * Unsubscribes a subscriber.
 *
 * @access  public
 * @since   1.3.2
 */
function unsubscribe_noptin_subscriber( $subscriber ) {
	$subscriber = new Noptin_Subscriber( $subscriber );

	if ( $subscriber->exists() && ! $subscriber->is_virtual ) {

		// Deactivate the subscriber.
		deactivate_noptin_subscriber( $subscriber );

		// (maybe) delete the subscriber.
		if ( get_noptin_option( 'delete_on_unsubscribe' ) ) {
			delete_noptin_subscriber( $subscriber->id );
		}
	}

}

/**
 * Empties the subscriber cache.
 *
 * @access  public
 * @since   1.2.8
 */
function clear_noptin_subscriber_cache( $subscriber ) {

	// Clean the cache.
	$old_subscriber = new Noptin_Subscriber( $subscriber );
	$old_subscriber->clear_cache();

}

/**
 * Retrieves a subscriber
 *
 * @access  public
 * @since   1.1.1
 * @param int|string|Noptin_Subscriber|object|array subscriber The subscribers's ID, email, confirm key, a Noptin_Subscriber object,
	 *                                                                or a subscriber object from the DB.
 * @see Noptin_Subscriber
 * @return Noptin_Subscriber
 */
function get_noptin_subscriber( $subscriber ) {
	return new Noptin_Subscriber( $subscriber );
}

/**
 * Retrieves a subscriber by email
 *
 * @access  public
 * @since   1.1.2
 * @param int|string|Noptin_Subscriber|object|array subscriber The subscriber to retrieve.
 * @return Noptin_Subscriber
 */
function get_noptin_subscriber_by_email( $email ) {
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
	$subscriber = new Noptin_Subscriber( $email );
	return $subscriber->id;
}

/**
 * Deletes a subscriber
 *
 * @access  public
 * @param int $subscriber The subscriber being deleted
 * @since   1.1.0
 */
function delete_noptin_subscriber( $subscriber ) {
	global $wpdb;

	/**
	 * Fires immediately before a subscriber is deleted from the database.
	 *
	 * @since 1.2.4
	 *
	 * @param int      $subscriber       ID of the subscriber to delete.
	 */
	do_action( 'delete_noptin_subscriber', $subscriber );

	// Maybe delete WP User connection.
	$user_id = get_noptin_subscriber_meta( (int) $subscriber, 'wp_user_id', true );
	if ( ! empty( $user_id ) ) {
		delete_user_meta( $user_id, 'noptin_subscriber_id' );
	}

	clear_noptin_subscriber_cache( $subscriber );

	$table  = get_noptin_subscribers_table_name();
	$table2 = get_noptin_subscribers_meta_table_name();

	// Delete the subscriber...
	$true1 = $wpdb->delete( $table, array( 'id' => $subscriber ), '%d' );

	// ... and its meta data.
	$true2 = $wpdb->delete( $table2, array( 'noptin_subscriber_id' => $subscriber ), '%d' );

	return $true1 && $true2;
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
	global $wpdb;

	if ( empty( $email ) ) {
		return false;
	}

	$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}noptin_subscribers WHERE email =%s LIMIT 1;", $email ) );

	return ! empty( $id );
}

/**
 * Checks whether the subscribers table exists
 *
 * @since 1.0.5
 * @return bool
 */
function noptin_subscribers_table_exists() {
	global $wpdb;
	return get_noptin_subscribers_table_name() === $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}noptin_subscribers'" );
}

/**
 * Checks whether the subscribers meta table exists
 *
 * @since 1.0.5
 * @return bool
 */
function noptin_subscribers_meta_table_exists() {
	global $wpdb;
	return get_noptin_subscribers_meta_table_name() === $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}noptin_subscriber_meta'" );
}

/**
 * Notifies the site admin when there is a new subscriber.
 *
 * @param int   $id The id of the new subscriber.
 * @param array $fields The subscription field values.
 * @since 1.2.0
 */
function noptin_new_subscriber_notify( $id, $fields ) {

	// Are we sending new subscriber notifications?
	$notify = get_noptin_option( 'notify_admin', false );
	if ( empty( $notify ) ) {
		return;
	}

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option.
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	/* translators: %s: site title */
	$message = sprintf( __( '%s has a new email subscriber', 'newsletter-optin-box' ), $blogname ) . "\r\n\r\n";

	unset( $fields['Email'] );
	unset( $fields['name'] );

	foreach ( $fields as $key => $val ) {

		if ( ! empty( $val ) && is_scalar( $val ) ) {
			$message .= sprintf( '%s: %s', esc_html( $key ), esc_html( $val ) ) . "\r\n";
		}
	}

	$to = get_noptin_option( 'admin_email', get_option( 'admin_email' ) );

	if ( empty( $to ) ) {
		return;
	}

	// translators: %s: site title
	$subject = sprintf( __( '[%s] New Subscriber', 'newsletter-optin-box' ), $blogname );

	@wp_mail( noptin_parse_list( $to ), wp_specialchars_decode( $subject ), $message );

}
add_action( 'noptin_insert_subscriber', 'noptin_new_subscriber_notify', 10, 2 );

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
 * Sends double optin emails.
 *
 * @param int   $id The id of the new subscriber.
 * @param array $fields The subscription field values.
 * @since 1.2.4
 */
function send_new_noptin_subscriber_double_optin_email( $id, $fields, $force = false ) {

	// Abort if double opt-in is disabled.
	$double_optin = get_noptin_option( 'double_optin', false );
	if ( empty( $double_optin ) && ! $force ) {
		return false;
	}

	// Retrieve subscriber.
	$subscriber = get_noptin_subscriber( $id );

	// Abort if the subscriber is missing or confirmed.
	if ( ! $subscriber->exists() || $subscriber->confirmed ) {
		return false;
	}

	// TODO: Edit double opt-in email similar to how normal emails are edited.
	$defaults = get_default_noptin_subscriber_double_optin_email();
	$content  = get_noptin_option( 'double_optin_email_body', $defaults['email_body'] );
	$content .= '<p>[[button url="[[confirmation_url]]" text="[[confirmation_text]]"]]</p>';
	$content .= get_noptin_option( 'double_optin_after_cta_text', $defaults['after_cta_text'] );

	// Handle custom merge tags.
	$url  = esc_url_raw( get_noptin_action_url( 'confirm', $fields['confirm_key'] ) );
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

	foreach ( noptin()->emails->newsletter->get_subscriber_merge_tags() as $tag => $details ) {
		noptin()->emails->tags->add_tag( $tag, $details );
	}

	$generator     = new Noptin_Email_Generator();
	$email_body    = $generator->generate( $args );
	$email_subject = noptin_parse_email_subject_tags( get_noptin_option( 'double_optin_email_subject', $defaults['email_subject'] ) );

	foreach ( array_keys( noptin()->emails->newsletter->get_subscriber_merge_tags() ) as $tag ) {
		noptin()->emails->tags->remove_tag( $tag );
	}

	// Send the email.
	return noptin_send_email(
		array(
			'recipients'               => $subscriber->email,
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
add_action( 'noptin_insert_subscriber', 'send_new_noptin_subscriber_double_optin_email', 10, 2 );

/**
 *  Returns the name of the subscribers' table
 *
 * @since 1.2.2
 * @return string The name of our subscribers table
 */
function get_noptin_subscribers_table_name() {
	return $GLOBALS['wpdb']->prefix . 'noptin_subscribers';
}

/**
 *  Returns the name of the subscribers' meta table
 *
 * @since 1.2.2
 * @return string The name of our subscribers meta table
 */
function get_noptin_subscribers_meta_table_name() {
	return $GLOBALS['wpdb']->prefix . 'noptin_subscriber_meta';
}

/**
 *  Returns a list of available subscriber fields.
 *
 * @since 1.2.4
 * @return array An array of subscriber fields.
 * @deprecated Use get_noptin_custom_fields()
 */
function get_noptin_subscriber_fields() {

	// Base subscriber fields.
	$fields = array(
		'first_name'   => __( 'First Name', 'newsletter-optin-box' ),
		'second_name'  => __( 'Last Name', 'newsletter-optin-box' ),
		'full_name'    => __( 'Full Name', 'newsletter-optin-box' ),
		'email'        => __( 'Email Address', 'newsletter-optin-box' ),
		'active'       => __( 'Active', 'newsletter-optin-box' ),
		'confirm_key'  => __( 'Confirm Key', 'newsletter-optin-box' ),
		'confirmed'    => __( 'Email Confirmed', 'newsletter-optin-box' ),
		'date_created' => __( 'Subscription Date', 'newsletter-optin-box' ),
		'GDPR_consent' => __( 'GDPR Consent', 'newsletter-optin-box' ),
		'ip_address'   => __( 'IP Address', 'newsletter-optin-box' ),
	);

	// Subscription fields.
	$extra_fields = get_special_noptin_form_fields();

	foreach ( $extra_fields as $name => $field ) {
		$label = wp_kses_post( $field[1] );

		if ( empty( $fields[ $name ] ) ) {
			$fields[ $name ] = $label;
		}
	}

	return apply_filters( 'get_noptin_subscriber_fields', $fields );
}

/**
 * Synces users to existing subscribers.
 *
 * @since 1.2.3
 * @param string|array $users_to_sync The WordPress users to sync to Noptin.
 * @see sync_noptin_subscribers_to_users
 * @return void.
 */
function sync_users_to_noptin_subscribers( $users_to_sync = array() ) {

	// Arrays only please.
	$users_to_sync = array_filter( noptin_parse_int_list( $users_to_sync ) );

	foreach ( array_unique( $users_to_sync ) as $user_id ) {

		// Get the user data...
		$user_info = get_userdata( $user_id );

		// ... and abort if it is missing.
		if ( empty( $user_info ) ) {
			continue;
		}

		// If the user is not yet subscribed, subscribe them.
		$subscriber_id = add_noptin_subscriber(
			array(
				'email'           => $user_info->user_email,
				'name'            => $user_info->display_name,
				'active'          => 0,
				'_subscriber_via' => 'users_sync',
			)
		);

		if ( is_numeric( $subscriber_id ) ) {
			update_user_meta( $user_id, 'noptin_subscriber_id', $subscriber_id );
			update_noptin_subscriber_meta( $subscriber_id, 'wp_user_id', $user_id );
		}
	}

}

/**
 * Synces existing subscribers to WordPress users.
 *
 * @since 1.2.3
 * @param string|array $subscribers_to_sync The Noptin subscribers to sync to WordPress Users.
 * @see sync_noptin_subscribers_to_users
 * @return void.
 */
function sync_noptin_subscribers_to_users( $subscribers_to_sync = array() ) {

	// Arrays only please.
	$subscribers_to_sync = array_filter( noptin_parse_int_list( $subscribers_to_sync ) );

	foreach ( array_unique( $subscribers_to_sync ) as $subscriber_id ) {

		// Get the subscriber data...
		$subscriber = get_noptin_subscriber( $subscriber_id );

		// ... and abort if it is missing.
		if ( ! $subscriber->exists() ) {
			continue;
		}

		// If the subscriber is a WordPress user, continue.
		$user = get_user_by( 'email', $subscriber->email );
		if ( $user ) {
			update_noptin_subscriber_meta( $subscriber->id, 'wp_user_id', $user->ID );
			continue;
		}

		$username = trim( $subscriber->first_name . $subscriber->second_name );

		if ( empty( $username ) ) {
			$username = $subscriber->email;
		}

		// Prepare user values.
		$args = array(
			'user_login' => noptin_generate_user_name( $username ),
			'user_pass'  => wp_generate_password(),
			'user_email' => $subscriber->email,
			'role'       => 'subscriber',
		);

		$user_id = wp_insert_user( $args );
		if ( is_wp_error( $user_id ) ) {
			log_noptin_message(
				sprintf(
					'WordPress returned the error: <strong>%s</strong> when syncing subscriber <em>%s</em>',
					$user_id->get_error_message(),
					sanitize_email( $subscriber->email )
				)
			);
			continue;
		}

		update_user_option( $user_id, 'default_password_nag', true, true ); // Set up the Password change nag.
		update_user_meta( $user_id, 'noptin_subscriber_id', $subscriber->id );
		update_noptin_subscriber_meta( $subscriber->id, 'wp_user_id', $user_id );
		wp_send_new_user_notifications( $user_id, 'user' );

	}

}

/**
 * Generates a unique username for new users.
 *
 * @since 1.2.3
 * @param string $prefix The prefix to use for the generated user name.
 * @return string.
 */
function noptin_generate_user_name( $prefix = '' ) {

	// If prefix is an email, retrieve the part before the email.
	$prefix = strtolower( trim( strtok( $prefix, '@' ) ) );

	// Remove whitespace.
	$prefix = preg_replace( '|\s+|', '_', $prefix );

	// Trim to 8 characters max.
	$prefix = sanitize_user( $prefix );

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/** @ignore */
	$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );
	if ( empty( $prefix ) || in_array( strtolower( $prefix ), array_map( 'strtolower', $illegal_logins ), true ) ) {
		$prefix = 'noptin';
	}

	$username = $prefix;
	if ( username_exists( $username ) ) {
		$prefix = $prefix . zeroise( wp_rand( 0, 9999 ), 4 );
		return noptin_generate_user_name( $prefix );
	}

	/**
	 * Filters an autogenerated user_name.
	 *
	 * @since 1.2.3
	 * @param string $prefix      A prefix for the user name. Can be any string including an email address.
	 */
	return apply_filters( 'noptin_generate_user_name', $prefix );
}

/**
 * Retrieves the current user's Noptin subscriber id.
 *
 * @return  false|int Subscriber id or false on failure.
 * @access  public
 * @since   1.5.1
 */
function get_current_noptin_subscriber_id() {

	// Try retrieveing subscriber key.
	$subscriber_key = '';
	if ( ! empty( $_GET['noptin_key'] ) ) {
		$subscriber_key = sanitize_text_field( urldecode( $_GET['noptin_key'] ) );
	} elseif ( ! empty( $_COOKIE['noptin_email_subscribed'] ) ) {
		$subscriber_key = sanitize_text_field( $_COOKIE['noptin_email_subscribed'] );
	}

	// If we have a subscriber key, use it to retrieve the subscriber.
	if ( ! empty( $subscriber_key ) ) {
		$subscriber = new Noptin_Subscriber( $subscriber_key );

		if ( $subscriber->exists() && $subscriber_key === $subscriber->confirm_key ) {
			return $subscriber->id;
		}
	}

	// If the user is logged in, check with their email address.
	$user_data = wp_get_current_user();
	if ( ! empty( $user_data->user_email ) ) {
		$subscriber = get_noptin_subscriber_by_email( $user_data->user_email );

		if ( $subscriber->exists() ) {
			return $subscriber->id;
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

	// If the user is logged in, check with their email address and ensure they are active.
	$user_data = wp_get_current_user();
	if ( ! empty( $user_data->user_email ) ) {
		$subscriber = get_noptin_subscriber_by_email( $user_data->user_email );

		if ( $subscriber->exists() ) {
			return empty( $subscriber->active );
		}
	}

	// Check from the login cookies.
	if ( ! empty( $_COOKIE['noptin_email_subscribed'] ) ) {
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

	$subscriber = new Noptin_Subscriber( get_current_noptin_subscriber_id() );

	if ( empty( $atts['field'] ) || ! $subscriber->exists() || ! $subscriber->has_prop( $atts['field'] ) ) {
		return '';
	}

	$value = $subscriber->get( $atts['field'] );
	return is_scalar( $value ) ? esc_html( $value ) : '';

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
			'birthday'   => array(
				'predefined' => true,
				'merge_tag'  => 'birthday',
				'label'      => __( 'Birthday', 'newsletter-optin-box' ),
				'class'      => 'Noptin_Custom_Field_Birthday',
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
}//TODO: Move custom fields, source, activity and IP address to the subscribers table.

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

	// Maybe add the localse field.
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
	$fields = map_deep( apply_filters( 'noptin_custom_fields', $custom_fields ), 'esc_html' );

	foreach ( $fields as $index => $field ) {
		$field['field_key'] = uniqid( 'noptin_' ) . $index;

		if ( 'email' === $field['merge_tag'] ) {
			$field['subs_table'] = true;
		}

		$fields[ $index ] = $field;
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

	// Cache. TODO: Clear cache when subscriber or form is added/updated.
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
