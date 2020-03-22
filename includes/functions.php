<?php
/**
 * Admin section
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
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
		$opened_campaigns[] = $campaign_id;
		update_noptin_subscriber_meta( $subscriber_id, '_opened_campaigns', $opened_campaigns );
		update_noptin_subscriber_meta( $subscriber_id, "_campaign_{$campaign_id}_opened", 1 );

		if ( is_int( $campaign_id ) ) {
			$open_counts = (int) get_post_meta( $campaign_id, '_noptin_opens', true );
			update_post_meta( $campaign_id, '_noptin_opens', $open_counts + 1 );

		}

		do_action( 'log_noptin_subscriber_campaign_open', $subscriber_id, $campaign_id );

	}

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
	return array_map( 'intval', noptin_parse_list( $opened_campaigns ) );

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

	log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id );

	$clicked_campaigns = get_noptin_subscriber_clicked_campaigns( $subscriber_id );

	if ( ! isset( $clicked_campaigns[ $campaign_id ] ) ) {
		$clicked_campaigns[ $campaign_id ] = array();
	}

	if ( ! in_array( $link, $clicked_campaigns[ $campaign_id ], true ) ) {
		$clicked_campaigns[ $campaign_id ][] = noptin_clean( $link );
		update_noptin_subscriber_meta( $subscriber_id, '_clicked_campaigns', $clicked_campaigns );
		update_noptin_subscriber_meta( $subscriber_id, "_campaign_{$campaign_id}_clicked", 1 );

		$click_counts = (int) get_post_meta( $campaign_id, '_noptin_clicks', true );
		update_post_meta( $campaign_id, '_noptin_clicks', $click_counts + 1 );

		do_action( 'log_noptin_subscriber_campaign_click', $subscriber_id, $campaign_id );
	}

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

	if ( empty( $subscriber ) ) {
		return array();
	}

	$merge_tags                    = (array) $subscriber;
	$merge_tags['unsubscribe_url'] = get_noptin_action_url( 'unsubscribe', $subscriber->confirm_key );
	$meta                          = get_noptin_subscriber_meta( $subscriber_id );

	foreach ( $meta as $key => $values ) {

		if ( isset( $values[0] ) && is_scalar( maybe_unserialize( $values[0] ) ) ) {
				$merge_tags[ $key ] = esc_html( $values[0] );
		}
	}

	return apply_filters( 'noptin_subscriber_merge_fields', $merge_tags, $subscriber, $meta );
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
		'from_email'            => get_option( 'admin_email' ),
		'from_name'             => get_option( 'blogname' ),
		'company'               => get_option( 'blogname' ),
		'comment_form'          => false,
		'comment_form_msg'      => __( 'Subscribe To Our Newsletter', 'newsletter-optin-box' ),
		'register_form'         => false,
		'register_form_msg'     => __( 'Subscribe To Our Newsletter', 'newsletter-optin-box' ),
		'hide_from_subscribers' => false,
		'address'               => __( '31 North San Juan Ave.', 'newsletter-optin-box' ),
		'city'                  => __( 'Santa Clara', 'newsletter-optin-box' ),
		'state'                 => __( 'San Francisco', 'newsletter-optin-box' ),
		'country'               => __( 'United States', 'newsletter-optin-box' ),
		'success_message'       => __( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
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
 * Returns the noptin action page
 *
 * @return  int
 * @access  public
 * @since   1.2.0
 */
function get_noptin_action_page() {

	$page = get_option( 'noptin_actions_page' );

	if ( empty( $page ) ) {

		$content = '
		<!-- wp:shortcode -->
		[noptin_action_page]
		<!-- /wp:shortcode -->';

		$page = wp_insert_post(
			array(
				'post_content' => $content,
				'post_title'   => __( 'Noptin Subsciber Action', 'newsletter-optin-box' ),
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( empty( $page ) ) {
			return 0;
		}

		update_option( 'noptin_actions_page', $page );

	}

	return (int) $page;

}

/**
 * Returns the noptin action url
 *
 * @return  sting
 * @param   string $action The action to execute.
 * @param   string $value  Optional. The value to pass to the action handler.
 * @param   bool   $empty  Optional. Whether or not to use an empty template.
 * @access  public
 * @since   1.0.6
 */
function get_noptin_action_url( $action, $value = false, $empty = false ) {

	$page = get_noptin_action_page();

	if ( empty( $page ) ) {
		return get_home_url();
	}

	$url = get_the_permalink( $page );

	if ( $url ) {

		$args = array(
			'na' => $action,
			'nv' => $value,
		);

		if ( $empty ) {
			$args['nte'] = 1;
		}

		return add_query_arg( $args, $url );
	}

	return get_home_url();

}

/**
 * Checks if this is a noptin actions page
 *
 * @return  bool
 * @since   1.2.0
 */
function is_noptin_actions_page() {
	$page = get_noptin_action_page();
	return ! empty( $page ) && is_page( $page );
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
 * Retrieves the subscriber count
 *
 * @return  int   $where Restriction string
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscribers_count( $where = '', $meta_key = '', $meta_value = false ) {
	global $wpdb;

	$table      = get_noptin_subscribers_table_name();
	$meta_table = get_noptin_subscribers_meta_table_name();
	$extra_sql  = '';

	if ( false !== $meta_value ) {
		$extra_sql = "INNER JOIN $meta_table ON ( $table.id = $meta_table.noptin_subscriber_id ) WHERE ( $meta_table.meta_key = '%s' AND $meta_table.meta_value = '%s' )";
		$extra_sql = $wpdb->prepare( $extra_sql, $meta_key, $meta_value );
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

	return $wpdb->get_var( "SELECT COUNT(`id`) FROM $table $where;" );
}

/**
 * Inserts a new subscriber into the database
 *
 * @access  public
 * @since   1.0.5
 */
function add_noptin_subscriber( $fields ) {
	global $wpdb;

	$table  = get_noptin_subscribers_table_name();
	$fields = wp_unslash( $fields );

	// Ensure an email address is provided and it doesn't exist already.
	if ( empty( $fields['email'] ) || ! is_email( $fields['email'] ) ) {
		return __( 'Please provide a valid email address', 'newsletter-optin-box' );
	}

	if ( noptin_email_exists( $fields['email'] ) ) {
		return true;
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
		'confirm_key'  => md5( $fields['email']  . wp_generate_password( 32, true, true ) ),
		'date_created' => date_i18n( 'Y-m-d' ),
		'active'       => get_noptin_option( 'double_optin' ) ? 1 : 0,
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

		if ( isset( $database_fields[ $field ] ) || 'name' === $field || 'integration_data' === $field ) {
			continue;
		}

		update_noptin_subscriber_meta( $id, $field, $value );
	}

	setcookie( 'noptin_email_subscribed', '1', time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );

	$cookie = get_noptin_option( 'subscribers_cookie' );
	if ( ! empty( $cookie ) && is_string( $cookie ) ) {
		setcookie( $cookie, '1', time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
	}

	do_action( 'noptin_insert_subscriber', $id, $fields );

	return $id;

}

/**
 * Updates a WordPress subscriber
 *
 * @access  public
 * @since   1.2.3
 */
function update_noptin_subscriber( $subscriber_id, $details = array() ) {
	global $wpdb;
	$subscriber_id = absint( $subscriber_id );

	// Ensure the subscriber exists.
	$subscriber = get_noptin_subscriber( $subscriber_id );
	if ( empty( $subscriber ) ) {
		return false;
	}

	// Prepare main variables.
	$table     = get_noptin_subscribers_table_name();
	$fields    = wp_unslash( $details );
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
		update_noptin_subscriber_meta( $subscriber_id, $field, $value );
	}

	do_action( 'noptin_update_subscriber', $subscriber_id, $details );

	return true;

}


/**
 * Retrieves a subscriber
 *
 * @access  public
 * @since   1.1.1
 */
function get_noptin_subscriber( $subscriber ) {
	global $wpdb;

	$table = get_noptin_subscribers_table_name();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d;", $subscriber ) );

}

/**
 * Retrieves a subscriber by email
 *
 * @access  public
 * @since   1.1.2
 */
function get_noptin_subscriber_by_email( $email ) {
	global $wpdb;

	$table = get_noptin_subscribers_table_name();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE email=%s;", $email ) );

}

/**
 * Retrieves a subscriber id by email
 *
 * @access  public
 * @since   1.2.6
 */
function get_noptin_subscriber_id_by_email( $email ) {
	global $wpdb;

	$table = get_noptin_subscribers_table_name();
	return $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $table WHERE email=%s;", $email ) );

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
	$user_id = get_noptin_subscriber_meta ( (int) $subscriber, 'wp_user_id', true );
	if ( ! empty( $user_id ) ) {
		delete_user_meta ( $user_id, 'noptin_subscriber_id' );
	}

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
	$table = get_noptin_subscribers_table_name();
	$sql   = $wpdb->prepare( "SELECT COUNT(id) FROM $table WHERE email =%s;", $email );

	return 0 < $wpdb->get_var( $sql );
}

/**
 * Checks whether the subscribers table exists
 *
 * @since 1.0.5
 * @return bool
 */
function noptin_subscribers_table_exists() {
	global $wpdb;
	$table = get_noptin_subscribers_table_name();

	return $table === $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
}

/**
 * Checks whether the subscribers meta table exists
 *
 * @since 1.0.5
 * @return bool
 */
function noptin_subscribers_meta_table_exists() {
	global $wpdb;
	$table = get_noptin_subscribers_meta_table_name();

	return $table === $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
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
 * Checks whether an optin form should be displayed.
 *
 * @since 1.0.7
 * @return bool
 */
function noptin_should_show_optins() {

	if ( get_noptin_option( 'hide_from_subscribers' ) ) {

		if ( ! empty( $_COOKIE['noptin_email_subscribed'] ) ) {
			return false;
		}

		$cookie = get_noptin_option( 'subscribers_cookie' );
		if ( ! empty( $cookie ) && is_string( $cookie ) && ! empty( $_COOKIE[ $cookie ] ) ) {
			return false;
		}

	}

	if ( ! empty( $_REQUEST['noptin_hide'] ) ) {
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
			'Red'         => '#e51c23 #fafafa #c62828', // Base color, Secondary color, border color.
			'Pink'        => '#e91e63 #fafafa #ad1457',
			'Purple'      => '#9c27b0 #fafafa #6a1b9a',
			'Deep Purple' => '#673ab7 #fafafa #4527a0',
			'Purple'      => '#9c27b0 #fafafa #4527a0',
			'Indigo'      => '#3f51b5 #fafafa #283593',
			'Blue'        => '#2196F3 #fafafa #1565c0',
			'Light Blue'  => '#03a9f4 #fafafa #0277bd',
			'Cyan'        => '#00bcd4 #fafafa #00838f',
			'Teal'        => '#009688 #fafafa #00695c',
			'Green'       => '#4CAF50 #fafafa #2e7d32',
			'Light Green' => '#8bc34a #191919 #558b2f',
			'Lime'        => '#cddc39 #191919 #9e9d24',
			'Yellow'      => '#ffeb3b #191919 #f9a825',
			'Amber'       => '#ffc107 #191919 #ff6f00',
			'Orange'      => '#ff9800 #fafafa #e65100',
			'Deep Orange' => '#ff5722 #fafafa #bf360c',
			'Brown'       => '#795548 #fafafa #3e2723',
			'Blue Grey'   => '#607d8b #fafafa #263238',
			'Black'       => '#313131 #fafafa #607d8b',
			'White'       => '#ffffff #191919 #191919',
			'Grey'        => '#aaaaaa #191919 #191919',
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

	$templates = array_replace( $custom_templates, $inbuilt_templates );

	return apply_filters( 'noptin_form_templates', $templates );

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
			'closeButtonPos',
			'singleLine',
			'formRadius',
			'formWidth',
			'formHeight',
			'noptinFormBg',
			'fields',
			'imageMain',
			'noptinFormBorderColor',
			'image',
			'imagePos',
			'noptinButtonLabel',
			'buttonPosition',
			'noptinButtonBg',
			'noptinButtonColor',
			'hideTitle',
			'title',
			'titleColor',
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
		'color_themes' => noptin_get_color_themes(),
		'design_props' => $props,
		'field_props'  => noptin_get_form_field_props(),
	);

	wp_localize_script( 'noptin', 'noptinEditor', $params );
}

/**
 * Function noptin editor localize.
 *
 * @since 1.0.5
 */
function noptin_form_template_form_props() {

	$class = "singleLine ? 'noptin-form-single-line' : 'noptin-form-new-line'";
	$style = "noptinFormBgVideo ? 'background-color:rgba(0,0,0,0.4)' : 'background-color:rgba(0,0,0,0)'";

	return " @submit.prevent :class=\"$class\"";
}

/**
 * Function noptin editor localize.
 *
 * @since 1.0.5
 */
function noptin_form_template_wrapper_props() {

	$props = array(
		':data-trigger="triggerPopup"',
		':data-after-click="cssClassOfClick"',
		':data-on-scroll="scrollDepthPercentage"',
		':data-after-delay="timeDelayDuration"',
		'class="noptin-optin-form-wrapper"',
		':class="\'noptin-slide-from-\' + slideDirection"',
		':data-once-per-session="DisplayOncePerSession"',
		':style="{
			borderColor: noptinFormBorderColor,
			backgroundColor: noptinFormBg,
			backgroundImage: \'url(\' + noptinFormBgImg + \')\',
			borderRadius: formRadius,
			width: formWidth,
			minHeight: formHeight
		}"',
	);

	return implode( ' ', $props );
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

	$noptin_admin = Noptin_Admin::instance();
	$body         = include locate_noptin_template( 'default-email-body.php' );

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
 * Notifies the site admin when there is a new subscriber.
 *
 * @param int   $id The id of the new subscriber.
 * @param array $fields The subscription field values.
 * @since 1.2.0
 */
function noptin_new_subscriber_notify( $id, $fields ) {

	// Are we sending new subscriber notifications?
	$notify = get_noptin_option( 'notify_admin' );
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
			$message .= sprintf( '%s: %s', sanitize_text_field( $key ), esc_html( $val ) ) . "\r\n";
		}
	}

	$to = get_option( 'admin_email' );

	$subject = sprintf( __( '[%s] New Subscriber', 'newsletter-optin-box' ), $blogname );

	@wp_mail( $to, wp_specialchars_decode( $subject ), $message );

}
add_action( 'noptin_insert_subscriber', 'noptin_new_subscriber_notify', 10, 2 );

/**
 * Sends double optin emails.
 *
 * @param int   $id The id of the new subscriber.
 * @param array $fields The subscription field values.
 * @since 1.2.4
 */
function send_new_noptin_subscriber_double_optin_email( $id, $fields ) {

	// Is double optin enabled?
	$double_optin = get_noptin_option( 'double_optin' );
	if ( empty( $double_optin ) ) {
		return;
	}

	$data = array(
		'email_subject' => 'Please confirm your subscription',
		'merge_tags'    => array(
			'confirmation_link' => get_noptin_action_url( 'confirm', $fields['confirm_key'] ),
		),
		'template'      => locate_noptin_template( 'email-templates/confirm-subscription.php' ),
	);


	// Try sending the email.
	$mailer  = new Noptin_Mailer();
	$email   = $mailer->get_email( $data );
	$subject = $mailer->get_subject( $data );
	$to      = sanitize_email( $fields['email'] );

	if ( $mailer->send( $to, $subject, $email ) ) {
		return true;
	}

	log_noptin_message( "An error occured while sending a double-optin confimation email to subscriber #$id ($to)" );
	return false;
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
 */
function get_noptin_subscribers_fields() {
	global $wpdb;

	// Base subscriber fields.
	$fields = array( 'first_name', 'second_name', 'full_name', 'email', 'active', 'confirm_key', 'confirmed', 'date_created' );

	// Add in some meta fields.
	$table       = get_noptin_subscribers_meta_table_name();
	$meta_fields = $wpdb->get_col( "SELECT DISTINCT `meta_key` FROM `$table`" );

	if ( is_array( $meta_fields ) ) {
		foreach ( $meta_fields as $field ) {
			if ( 0 !== stripos( $field, '_' ) && ! is_numeric( $field ) ) {
				$fields[] = $field;
			}
		}
	}

	return apply_filters( 'noptin_subscribers_fields', $fields );
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

	if ( $args && is_array( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Please, forgive us.
		extract( $args );
	}

	include locate_noptin_template( $template_name, $template_path, $default_path );

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
 * Fetches the current user's ip address.
 *
 * @since 1.2.3
 * @return string
 */
function noptin_get_user_ip() {
	$ip = '';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
	}

	return apply_filters( 'noptin_get_user_ip', $ip );
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

	// Retrieve API key.
	$api_key = get_noptin_option( 'ipgeolocation_io_api_key' );

	if ( empty( $api_key ) || empty( $ip_address ) ) {
		return false;
	}

	// Geolocate the ip.
	$response = wp_remote_get(
		'https://api.ipgeolocation.io/ipgeo',
		array(
			'apiKey' => $api_key,
			'ip'     => $ip_address,
			'fields' => 'city,continent_name,country_name,country_code2,state_prov,zipcode,country_flag,currency,time_zone',
		)
	);

	$geo = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $geo ) ) {
		log_noptin_message( __( 'Error fetching GeoLocation information.', 'newsletter-optin-box' ) );
		return false;
	}

	if ( ! empty( $geo['time_zone'] ) ) {
		$geo['time_zone'] = $geo['time_zone']['name'];
	}

	if ( ! empty( $geo['currency'] ) ) {
		$geo['currency'] 		= $geo['currency']['name'];
	}

	if ( ! empty( $geo['continent_name'] ) ) {
		$geo['continent'] 		= $geo['continent_name'];
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

	$return = array();
	$fields = noptin_parse_list( 'continent country state city zipcode calling_code languages country_flag currency time_zone' );

	foreach ( $fields as $field ) {
		if ( ! empty( $geo[ $field ] ) ) {
			$return[ $field ] = $geo[ $field ];
		}
	}
	return noptin_clean( $return );

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

	// Scalars only please.
	if ( ! is_scalar( $message ) ) {
		$message = print_r( $message, true );
	}

	// Obfuscate email addresses in log message since log might be public.
	$message = noptin_obfuscate_email_addresses( (string) $message );

	// First, get rid of everything between "invisible" tags.
	$message = preg_replace( '/<(?:style|script|head)>.+?<\/(?:style|script|head)>/is', '', $message );

	// Then, strip some tags.
	$message = wp_kses( $message, 'user_description' );

	// Next, retrieve the array of existing logged messages.
	$messages   = get_logged_noptin_messages();

	// Add our message.
	$messages[] = array(
		'level'	=> $code,
		'msg'	=> trim( $message ),
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

	return $messages;

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
	$users_to_sync = array_filter( array_map( 'absint', noptin_parse_list( $users_to_sync ) ) );

	foreach ( array_unique( $users_to_sync ) as $user_id ) {

		// Get the user data...
		$user_info = get_userdata( $user_id );

		// ... and abort if it is missing.
		if ( empty( $user_info ) ) {
			continue;
		}

		// If the user is not yet subscribed, subscribe them.
		add_noptin_subscriber(
			array(
				'email'           => $user_info->user_email,
				'name'            => $user_info->display_name,
				'_subscriber_via' => 'users_sync',
			)
		);

		// Then update the subscriber.
		$subscriber = get_noptin_subscriber_by_email( $user_info->user_email );

		if ( empty( $subscriber ) ) {
			continue;
		}

		update_user_meta( $user_info->ID, 'noptin_subscriber_id', $subscriber->id );

		$to_update = array(
			'description' => $user_info->description,
			'website'	  => esc_url( $user_info->user_url ),
			'wp_user_id'  => $user_info->ID,
		);

		$to_update = apply_filters( 'noptin_sync_users_to_subscribers', $to_update, $subscriber, $user_info );
		foreach ( $to_update as $key => $value ) {
			if ( is_null( $value ) ) {
				unset( $to_update[ $key ] );
			}
		}

		if ( ! empty( $to_update ) ) {
			update_noptin_subscriber( $subscriber->id, $to_update );
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
	$subscribers_to_sync = array_filter( array_map( 'absint', noptin_parse_list( $subscribers_to_sync ) ) );

	foreach ( array_unique( $subscribers_to_sync ) as $subscriber_id ) {

		// Get the subscriber data...
		$subscriber = get_noptin_subscriber( $subscriber_id );

		// ... and abort if it is missing.
		if ( empty( $subscriber ) ) {
			continue;
		}

		// If the subscriber is a WordPress user, continue.
		$user = get_user_by( 'email', $subscriber->email );
		if ( $user ) {
			update_noptin_subscriber_meta( $subscriber->id, 'wp_user_id', $user->ID );
			continue;
		}

		// Prepare user values.
		$args = array(
			'user_login' => noptin_generate_user_name( $subscriber->email ),
			'user_pass'  => wp_generate_password(),
			'user_email' => $subscriber->email,
			'role'       => 'subscriber',
		);

		$user_id = wp_insert_user( $args );
		if ( is_wp_error( $user_id ) ) {
			log_noptin_message(
				sprintf(
					__( 'WordPress returned the error: <strong>%s</strong> when syncing subscriber <em>%s</em>', 'newsletter-optin-box' ),
					$user_id->get_error_message(),
					$subscriber->email
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
	$prefix = strtok( $prefix, '@' );

	// Trim to 4 characters max.
	$prefix = sanitize_user( substr( $prefix, 0, 4 ) );

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/** @ignore */
	$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );
	if ( empty( $prefix ) || in_array( strtolower( $prefix ), array_map( 'strtolower', $illegal_logins ), true ) ) {
		$prefix = 'noptin';
	}

	$username = $prefix . '_' . zeroise( wp_rand( 0, 9999 ), 4 );
	if ( username_exists( $username ) ) {
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
