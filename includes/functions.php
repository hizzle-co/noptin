<?php
/**
 * Admin section
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

/**
 * Returns a reference to the main Noptin instance.
 *
 * @return  object An object containing a reference to Noptin.
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
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to update.
 * @param   mixed   $meta_value   Metadata value. Must be serializable if non-scalar.
 * @param   mixed   $unique   Whether the same key should not be added.
 * @return  int|false         Meta ID on success, false on failure.
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
 * @param   mixed   $meta_value   Metadata value. Must be serializable if non-scalar.
 * @param   mixed   $prev_value   Previous value to check before updating.
 * @return  mixed                 The new meta field ID if a field with the given key didn't exist and was therefore added, true on successful update, false on failure.
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
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $meta_key      The meta key to delete.
 * @param   mixed   $meta_value   Metadata value. Must be serializable if non-scalar.
 * @return  bool                 True on success, false on failure.
 * @access  public
 * @since   1.0.5
 */
function delete_noptin_subscriber_meta( $subscriber_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'noptin_subscriber', $subscriber_id, $meta_key, $meta_value );
}

/**
 * Logs whenever a subscriber opens an email
 *
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @param   string $campaign_id    The opened email campaign.
 * @access  public
 * @since   1.2.0
 */
function log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id ) {

	$opened_campaigns = get_noptin_subscriber_opened_campaigns( $subscriber_id );
	if(! in_array( $campaign_id, $opened_campaigns ) ) {
		$opened_campaigns[] = $campaign_id;
		update_noptin_subscriber_meta( $subscriber_id, "_opened_campaigns", $opened_campaigns );
		update_noptin_subscriber_meta( $subscriber_id, "_campaign_{$campaign_id}_opened", 1 );


		if( is_int( $campaign_id ) ) {
			$open_counts = (int) get_post_meta( $campaign_id, '_noptin_opens', true );
			update_post_meta( $campaign_id, '_noptin_opens', $open_counts + 1 );;
		}

	}

}

/**
 * Retrieves all the campaigns a given subscriber has opened
 *
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_opened_campaigns( $subscriber_id ) {

	$opened_campaigns = get_noptin_subscriber_meta( $subscriber_id, "_opened_campaigns", true );
	if( empty( $opened_campaigns ) ) {
		$opened_campaigns = array();
	}
	return array_map( 'intval', $opened_campaigns );

}

/**
 * Checks whether a subscriber opened a given campaign
 *
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function did_noptin_subscriber_open_campaign( $subscriber_id, $campaign_id ) {

	$opened_campaigns = get_noptin_subscriber_opened_campaigns( $subscriber_id );
	return in_array( $campaign_id, $opened_campaigns );

}

/**
 * Logs whenever a subscriber clicks on a link in an email
 *
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

	if(! isset( $clicked_campaigns[ $campaign_id ] ) ) {
		$clicked_campaigns[ $campaign_id ] = array();
	}

	if(! in_array( $link, $clicked_campaigns[ $campaign_id ] ) ) {
		$clicked_campaigns[ $campaign_id ][] = $link;
		update_noptin_subscriber_meta( $subscriber_id, "_clicked_campaigns", $clicked_campaigns );
		update_noptin_subscriber_meta( $subscriber_id, "_campaign_{$campaign_id}_clicked", 1 );

		$click_counts = (int) get_post_meta( $campaign_id, '_noptin_clicks', true );
		update_post_meta( $campaign_id, '_noptin_clicks', $click_counts + 1 );
	}

}

/**
 * Retrieves all the campaigns a given subscriber has clicked on a link in
 *
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function get_noptin_subscriber_clicked_campaigns( $subscriber_id ) {

	$clicked_campaigns = get_noptin_subscriber_meta( $subscriber_id, "_clicked_campaigns", true );
	if( empty( $clicked_campaigns ) ) {
		$clicked_campaigns = array();
	}
	return $clicked_campaigns;

}

/**
 * Checks whether a subscriber clicked on a link in a given campaign
 *
 *
 * @param   int    $subscriber_id  Subscriber ID.
 * @access  public
 * @since   1.2.0
 */
function did_noptin_subscriber_click_campaign( $subscriber_id, $campaign_id, $link = false ) {

	$clicked_campaigns = get_noptin_subscriber_clicked_campaigns( $subscriber_id );

	if( empty( $clicked_campaigns[ $campaign_id ] ) ) {
		return false;
	}

	if( empty( $link ) ) {
		return true;
	}

	return in_array( $link, $clicked_campaigns[ $campaign_id ] );

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
		'notify_new_post' => 0,
		'from_email' 	  => get_option('admin_email'),
		'from_name' 	  => get_option('blogname'),
		'company' 	  	  => get_option('blogname'),
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

	if( empty( $noptin_options ) ) {
		$noptin_options = get_option( 'noptin_options', array() );
	}

	if(! is_array( $noptin_options ) || empty( $noptin_options ) ) {
		$noptin_options = get_default_noptin_options();
	}
	return $noptin_options;
}

/**
 * Retrieves an option from the db
 *
 * @return  mixed|null   option or null
 * @access  public
 * @since   1.0.5
 */
function get_noptin_option( $key, $default = null ) {

	$options = get_noptin_options();
	$value   = $default;
	if( isset( $options[ $key ] ) ) {
		$value   = $options[ $key ];
	}

	if( 'false' === $value ) {
		$value = false;
	}

	if( 'true' === $value ) {
		$value = true;
	}

	return apply_filters( 'noptin_get_option', $value, $key );

}

/**
 * Updates noptin options
 *
 * @return  array
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
 * @return  array
 * @access  public
 * @since   1.0.5
 */
function update_noptin_option( $key, $value ) {

	$options       = get_noptin_options();
	$options[$key] = $value;
	update_noptin_options( $options );

}

/**
 * Prepare noptin email body
 *
 * @return  sting
 * @access  public
 * @since   1.0.6
 */
function prepare_noptin_email( $email, $subscriber ) {
	return $email;
}

/**
 * Returns the noptin action page
 *
 * @return  int
 * @access  public
 * @since   1.2.0
 */
function get_noptin_action_page() {

	$page = get_option('noptin_actions_page');

	if( empty( $page ) ) {

		$content = '
		<!-- wp:shortcode -->
		[noptin_action_page]
		<!-- /wp:shortcode -->';

		$page = wp_insert_post(
			array(
				'post_content' => $content,
				'post_title'   => __( 'Noptin Subsciber Action',  'newsletter-optin-box' ),
				'post_status'  => 'publish',
				'post_type'	   => 'page',
			)
		);

		update_option('noptin_actions_page', $page);

	}

	return $page;

}

/**
 * Returns the noptin action url
 *
 * @return  sting
 * @access  public
 * @since   1.0.6
 */
function get_noptin_action_url( $action, $value = false ) {

	$page = get_noptin_action_page();

	if( empty( $page ) ) {
		return get_home_url();
	}

	$url = get_the_permalink( $page );

	if( $url ) {
		return add_query_arg( array(
			'noptin_action' => $action,
			'noptin_value'  => $value,
		), $url );
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
    return !empty( $page ) && is_page( $page );
}

/**
 * Retrieves the URL to the subscribers page
 *
 * @return  string   The subscribers page url
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscribers_overview_url( $page=1 ) {
	$url = admin_url('admin.php?page=noptin-subscribers');
	return add_query_arg( 'paged', $page, $url );
}

/**
 * Retrieves the URL to the forms creation page
 *
 *
 *
 * @return  string   The forms page url
 * @access  public
 * @since   1.0.5
 */
function get_noptin_new_form_url() {
	return admin_url('post-new.php?post_type=noptin-form');
}

/**
 * Retrieves the URL to a forms edit url
 *
 *
 *
 * @return  string   The form edit page url
 * @access  public
 * @since   1.1.1
 */
function get_noptin_edit_form_url( $form_id ) {
	$url = admin_url('post.php?action=edit');
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
	$url = admin_url('edit.php?post_type=noptin-form');
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

	$table     		= $wpdb->prefix . 'noptin_subscribers';
	$meta_table     = $wpdb->prefix . 'noptin_subscriber_meta';
	$extra_sql 		= '';

	if( false !== $meta_value ) {
		$extra_sql = "INNER JOIN $meta_table ON ( $table.id = $meta_table.noptin_subscriber_id ) WHERE ( $meta_table.meta_key = '%s' AND $meta_table.meta_value = '%s' )";
		$extra_sql = $wpdb->prepare( $extra_sql, $meta_key, $meta_value );
	}

	if(! empty( $where ) ) {

		if( empty( $extra_sql ) ) {
			$where = "WHERE $where";
		} else {
			$where = "$extra_sql AND $where";
		}

	} else {
		$where = "$extra_sql";
	}

	return $wpdb->get_var("SELECT COUNT(`id`) FROM $table $where;");
}

/**
 * Retrieves the subscriber growth
 *
 * @access  public
 * @since   1.0.5
 */
function get_noptin_subscribers_growth() {
	global $wpdb;

	$table = $wpdb->prefix . 'noptin_subscribers';
	$sql   = "SELECT COUNT(`id`)/count(distinct `date_created`) as avg FROM `$table`;";

	return (float) $wpdb->get_var($sql );

}

/**
 * Inserts a new subscriber into the database
 *
 * @access  public
 * @since   1.0.5
 */
function add_noptin_subscriber( $fields ) {
	global $wpdb;

	$table = $wpdb->prefix . 'noptin_subscribers';
	$fields= wp_unslash( $fields );

	//Ensure an email address is provided and it doesn't exist already
	if( empty( $fields['email'] ) || !is_email( $fields['email'] ) ) {
		return __( "Please provide a valid email address",  'newsletter-optin-box' );
	}

	if( noptin_email_exists( $fields['email'] ) ) {
		return true;
	}

	//Maybe split name into first and last
	if( isset( $fields['name'] ) ) {
		$names = noptin_split_subscriber_name( $fields['name'] );

		$fields['first_name']  = empty( $fields['first_name'] ) ? $names[0] : trim( $fields['first_name'] );
		$fields['last_name'] = empty( $fields['last_name'] ) ? $names[1] : trim( $fields['last_name'] );
	}

	$database_fields = array(
		'email' 		=> $fields['email'],
		'first_name'	=> empty( $fields['first_name'] ) ? '' : $fields['first_name'],
		'second_name'	=> empty( $fields['last_name'] ) ? '' : $fields['last_name'],
		'confirm_key'	=> md5($email) . wp_generate_password(4, false),
		'date_created'	=> date("Y-m-d"),
	);

	if(! $wpdb->insert( $table, $database_fields, '%s' ) ) {
		return "An error occurred. Try again.";
	}

	$id = $wpdb->insert_id;

	//Insert additional meta data
	foreach( $fields as $field=>$value ){

		if( isset( $database_fields[ $field ] ) || 'name' == $field ) {
			continue;
		}

		update_noptin_subscriber_meta( $id, $field, $value );
	}

	setcookie( 'noptin_email_subscribed', '1', time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);

	do_action( 'noptin_insert_subscriber', $id, $fields );

	return $id;

}


/**
 * Retrieves a subscriber
 *
 * @access  public
 * @since   1.1.1
 */
function get_noptin_subscriber( $subscriber ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'noptin_subscribers';
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

	$table  = $wpdb->prefix . 'noptin_subscribers';
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE email=%s;", $email ) );

}

/**
 * Deletes a subscriber
 *
 * @access  public
 * @since   1.1.0
 */
function delete_noptin_subscriber( $subscriber ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'noptin_subscribers';
	$table2 = $wpdb->prefix . 'noptin_subscriber_meta';

	//Delete the subscriber...
	$true1 = $wpdb->delete( $table, array( 'id' => $subscriber ), '%d');

	//... and its meta data
	$true2 = $wpdb->delete( $table2, array( 'noptin_subscriber_id' => $subscriber ), '%d');

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

	$name       = trim($name);
    $last_name  = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
    return array($first_name, $last_name);

}

/**
 * Checks whether the subscriber with a given email exists.
 *
 * @param string The email to check for
 * @return bool
 */
function noptin_email_exists( $email ){
	global $wpdb;
	$table = $wpdb->prefix . 'noptin_subscribers';
	$sql   = $wpdb->prepare( "SELECT COUNT(id) FROM $table WHERE email =%s;", $email );

	return 0 < $wpdb->get_var( $sql );
}

/**
 * Checks whether the subscribers table exists
 *
 * @return bool
 */
function noptin_subscribers_table_exists(){
	global $wpdb;
	$table = $wpdb->prefix . 'noptin_subscribers';

	return $table == $wpdb->get_var("SHOW TABLES LIKE '$table'" );
}

/**
 * Checks whether the subscribers meta table exists
 *
 * @return bool
 */
function noptin_subscribers_meta_table_exists(){
	global $wpdb;
	$table = $wpdb->prefix . 'noptin_subscriber_meta';

	return $table == $wpdb->get_var("SHOW TABLES LIKE '$table'" );
}

/**
 * Retrieves an optin form.
 *
 * @param int|Noptin_Form The id or Noptin_Form object of the optin to retrieve
 * @return Noptin_Form
 */
function noptin_get_optin_form( $id ){
    return new Noptin_Form( $id );
}

/**
 * Retrieves the total opt-in forms count.
 *
 * @param string Optionally filter by opt-in type
 * @return int
 */
function noptin_count_optin_forms( $type = '' ){
	global $wpdb;

	$sql   = "SELECT COUNT(`ID`) FROM {$wpdb->posts} as forms";
	$where = "WHERE `post_type`='noptin-form'";

	if(! empty( $type ) ) {
		$sql = "$sql LEFT JOIN {$wpdb->postmeta} as meta
			ON meta.post_id = forms.ID
			AND meta.meta_key = '_noptin_optin_type'
			AND meta.meta_value = %s";

		$sql   = $wpdb->prepare( $sql, $type );
		$where .= " AND meta.meta_key='_noptin_optin_type'";
	}

    return $wpdb->get_var("$sql $where;");
}

/**
 * Creates an optin form
 */
function noptin_create_optin_form( $data = false ){
    $form    = new Noptin_Form( $data );
    $created = $form->save();

    if( is_wp_error( $created ) ) {
        return $created;
    }

    return $form->id;
}


/**
 * Deletes an optin form
 */
function noptin_delete_optin_form( $id ){
    return wp_delete_post( $id, true );
}

/**
 * Duplicates an optin form
 */
function noptin_duplicate_optin_form( $id ){
    $form = noptin_get_optin_form( $id );
    $form->duplicate();
    return $form->id;
}

/**
 * Returns all optin forms
 */
function noptin_get_optin_forms( $meta_key = '', $meta_value = '', $compare = '='){
    $args   = array(
        'numberposts'      => -1,
        'post_type'        => 'noptin-form',
        'post_status'      => array( 'draft', 'publish' )
    );

    if( $meta_key ) {
        $args['meta_query'] = array(
                array(
                    'key'       => $meta_key,
                    'value'     => $meta_value,
                    'compare'   => $compare,
                )
            );

    }
    return get_posts( $args );
}

/**
 * Returns post types
 */
function noptin_get_post_types(){
    $return = array();
    $args   = array(
        'public'    => true,
        'show_ui'   => true
    );
    $post_types = get_post_types( $args, 'objects' );

    foreach( $post_types as $obj ){
        $return[$obj->name] = $obj->label;
    }
    unset( $return['attachment'] );

    return $return;

}

/**
 * Checks whether an optin form should be displayed
 */
function noptin_should_show_optins(){

	if(! empty( $_COOKIE['noptin_email_subscribed'] ) && get_noptin_option( 'hide_from_subscribers' ) ){
		return false;
	}

	if(! empty( $_REQUEST['noptin_hide'] ) ) {
		return false;
	}

	return true;

}

/**
 * Returns opt-in forms stats
 */
function noptin_get_optin_stats(){
	global $wpdb;
	$table 	= $wpdb->prefix . 'noptin_subscriber_meta';
	$sql 	= "SELECT `meta_value`, COUNT( DISTINCT `noptin_subscriber_id`) AS stats FROM `$table` WHERE `meta_key`='_subscriber_via' GROUP BY `meta_value`";
	$stats 	= $wpdb->get_results( $sql );

	if(! $stats ) {
		$stats = array();
	}

	return wp_list_pluck( $stats, 'stats', 'meta_value' );

}


/**
 * Returns color themess
 */
function noptin_get_color_themes(){
    return apply_filters(
		'noptin_form_color_themes',
		array(
            'Red'           => '#e51c23 #fafafa #c62828', //Base color, Secondary color, border color
            'Pink'          => '#e91e63 #fafafa #ad1457',
            'Purple'        => '#9c27b0 #fafafa #6a1b9a',
            'Deep Purple'   => '#673ab7 #fafafa #4527a0',
            'Purple'        => '#9c27b0 #fafafa #4527a0',
            'Indigo'        => '#3f51b5 #fafafa #283593',
            'Blue'          => '#2196F3 #fafafa #1565c0',
            'Light Blue'    => '#03a9f4 #fafafa #0277bd',
            'Cyan'          => '#00bcd4 #fafafa #00838f',
            'Teal'          => '#009688 #fafafa #00695c',
            'Green'         => '#4CAF50 #fafafa #2e7d32',
            'Light Green'   => '#8bc34a #191919 #558b2f',
            'Lime'          => '#cddc39 #191919 #9e9d24',
            'Yellow'        => '#ffeb3b #191919 #f9a825',
            'Amber'         => '#ffc107 #191919 #ff6f00',
            'Orange'        => '#ff9800 #fafafa #e65100',
            'Deep Orange'   => '#ff5722 #fafafa #bf360c',
            'Brown'         => '#795548 #fafafa #3e2723',
            'Blue Grey'     => '#607d8b #fafafa #263238',
            'Black'         => '#313131 #fafafa #607d8b',
            'White'         => '#ffffff #191919 #191919',
            'Grey'          => '#aaaaaa #191919 #191919',
        ));

}

/**
 * Returns optin templates
 */
function noptin_get_optin_templates(){
    $custom_templates = get_option( 'noptin_templates' );
	$inbuilt_templates = include 'admin/templates/templates.php';

    if(! is_array( $custom_templates ) ) {
        $custom_templates = array();
	}

	$templates = array_replace( $custom_templates, $inbuilt_templates );

    return apply_filters( 'noptin_form_templates', $templates );

}

/**
 * Returns color themess
 */
function noptin_get_form_design_props(){
    return apply_filters(
		'noptin_form_design_props',
		array(
			'hideCloseButton', 'closeButtonPos', 'singleLine', 'formRadius', 'formWidth',
			'formHeight', 'noptinFormBg', 'fields', 'imageMain',
            'noptinFormBorderColor', 'image', 'imagePos', 'noptinButtonLabel', 'buttonPosition',
            'noptinButtonBg', 'noptinButtonColor', 'hideTitle', 'title', 'titleColor',
            'hideDescription', 'description', 'descriptionColor', 'hideNote', 'hideOnNoteClick',
            'note', 'noteColor', 'CSS', 'optinType'
        ));

}

/**
 * Returns form field props
 */
function noptin_get_form_field_props(){
    return apply_filters( 'noptin_form_field_props', array( 'fields','fieldTypes' ));

}

/**
 * Function noptin editor localize
 */
function noptin_localize_optin_editor( $state ){
	$props   = noptin_get_form_design_props();
	$props[] = 'DisplayOncePerSession';
	$props[] = 'timeDelayDuration';
	$props[] = 'scrollDepthPercentage';
	$props[] = 'cssClassOfClick';
	$props[] = 'triggerPopup';

    $params = array(
        'ajaxurl'      => admin_url('admin-ajax.php'),
        'api_url'      => get_home_url( null, 'wp-json/wp/v2/'),
        'nonce'        => wp_create_nonce('noptin_admin_nonce'),
        'data'         => $state,
        'templates'    => noptin_get_optin_templates(),
        'color_themes' => noptin_get_color_themes(),
		'design_props' => $props,
		'field_props'  => noptin_get_form_field_props(),
	);

    wp_localize_script('noptin', 'noptinEditor', $params);
}

/**
 * Function noptin editor localize
 */
function noptin_form_template_form_props(){

	$class = "singleLine ? 'noptin-form-single-line' : 'noptin-form-new-line'";
	$style = "noptinFormBgVideo ? 'background-color:rgba(0,0,0,0.4)' : 'background-color:rgba(0,0,0,0)'";

	return " @submit.prevent :class=\"$class\"";
}

/**
 * Function noptin editor localize
 */
function noptin_form_template_wrapper_props(){

    $props = array(
		':data-trigger="triggerPopup"',
		':data-after-click="cssClassOfClick"',
		':data-on-scroll="scrollDepthPercentage"',
		':data-after-delay="timeDelayDuration"',
		'class="noptin-optin-form-wrapper"',
		':data-once-per-session="DisplayOncePerSession"',
		':style="{
			borderColor: noptinFormBorderColor,
			backgroundColor: noptinFormBg,
			backgroundImage: \'url(\' + noptinFormBgImg + \')\',
			borderRadius: formRadius,
			width: formWidth,
			minHeight: formHeight
		}"'
	);

	return implode( ' ', $props );
}

/**
 * Returns a path to the debug log
 *
 * @return Noptin_Debug_Log
 */
function noptin_get_debug_log_file(){

    // get default log file location
    $upload_dir = wp_upload_dir(null, false);
    $file = trailingslashit($upload_dir['basedir']) . 'noptin-debug-log.php';

    /**
     * Filters the log file to write to.
     *
     * @param string $file The log file location. Default: /wp-content/uploads/noptin-debug.log
     */
    return apply_filters('noptin_debug_log_file', $file);

}

/**
 * This will replace the first half of a string with "*" characters.
 *
 * @param string $string
 * @return string
 */
function noptin_obfuscate_string($string){
    $length = strlen($string);
    $obfuscated_length = ceil($length / 2);
    $string = str_repeat('*', $obfuscated_length) . substr($string, $obfuscated_length);
    return $string;
}

/**
 * @internal
 * @ignore
 */
function _noptin_obfuscate_email_addresses_callback($m){
    $one = $m[1] . str_repeat('*', strlen($m[2]));
    $two = $m[3] . str_repeat('*', strlen($m[4]));
    $three = $m[5];
    return sprintf('%s@%s.%s', $one, $two, $three);
}

/**
 * Obfuscates email addresses in a string.
 *
 * @param $string String possibly containing email address
 * @return string
 */
function noptin_obfuscate_email_addresses($string){
    return preg_replace_callback('/([\w\.]{1,4})([\w\.]*)\@(\w{1,2})(\w*)\.(\w+)/', '_noptin_obfuscate_email_addresses_callback', $string);
}

/**
 *  Returns a link to add a new newsletter campaign
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
 *  Returns a link to edit a newsletter
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
 *  Returns a link to edit an automation campaign
 */
function get_noptin_automation_campaign_url( $id ) {

	$param = array(
		'page'        => 'noptin-email-campaigns',
		'section'     => 'automations',
		'sub_section' => 'edit_campaign',
		'id' 		  => $id,
	);
	return add_query_arg( $param, admin_url( '/admin.php' ) );

}

/**
 *  Checks if a given post is a noptin campaign
 */
function is_noptin_campaign( $post, $campaign_type = false ) {

	$campaign     = get_post( $post );

	if( empty( $campaign ) || 'noptin-campaign' != $campaign->post_type ) {
		return false;
	}

	if( empty( $campaign_type ) ) {
		return true;
	}

	return trim( $campaign_type ) == get_post_meta( $campaign->ID, 'campaign_type', true );

}

/**
 *  Returns the default newsletter subject
 */
function get_noptin_default_newsletter_subject() {

	$subject = '';

	/**
     * Filters the default newsletter subject
     *
     * @param string $subject The default newsletter subject
     */
    return apply_filters('noptin_default_newsletter_subject', $subject);

}

/**
 *  Returns the default newsletter preview text
 */
function get_noptin_default_newsletter_preview_text() {

	$preview_text = '';

	/**
     * Filters the default newsletter preview text
     *
     * @param string $preview_text The default newsletter preview text
     */
    return apply_filters('noptin_default_newsletter_preview_text', $preview_text);

}

/**
 *  Returns the default newsletter body
 */
function get_noptin_default_newsletter_body() {

	$noptin_admin = Noptin_Admin::instance();
	$body         = include $noptin_admin->admin_path . 'templates/default-email-body.php';

	/**
     * Filters the default newsletter body
     *
     * @param string $body The default newsletter body
     */
    return apply_filters('noptin_default_newsletter_body', $body);

}

/**
 *  Returns a path to the includes dir
 */
function get_noptin_include_dir( $append = '' ) {

	$noptin  = noptin();
	return $noptin->plugin_path . "includes/$append";

}

/**
 *  Includes a file
 */
function noptin_ob_get_clean( $file ) {

	ob_start();
	include $file;
	return ob_get_clean();

}

/**
 *  Notifies the site admin when there is a new subscriber
 */
function noptin_new_subscriber_notify( $id, $fields ) {

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	/* translators: %s: site title */
	$message = sprintf( __( '%s has a new email subscriber' ), $blogname ) . "\r\n\r\n";

	unset( $fields[ 'Email' ] );
	unset( $fields[ 'name' ] );

	foreach( $fields as $key => $val ) {

		if( !empty( $val ) && is_scalar( $val ) ) {
			$message .= sprintf( '%s: %s', sanitize_text_field( $key ), esc_html( $val ) ) . "\r\n";
		}

	}

	/* translators: %s: user email address */
	$message .= sprintf( __( 'Email: %s' ), $fields['email'] ) . "\r\n";

	$to      = get_option( 'admin_email' );

	$subject = sprintf( __( '[%s] New Subscriber' ), $blogname );

	@wp_mail( $to, wp_specialchars_decode( $subject ), $message );

}
add_action( 'noptin_insert_subscriber', 'noptin_new_subscriber_notify', 10, 2 );
