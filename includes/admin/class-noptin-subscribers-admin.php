<?php
/**
 * Manages the subscribers admin page.
 *
 * @since             1.5.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage subscribers page.
 *
 * @since 1.5.5
 */
class Noptin_Subscribers_Admin {

	/**
	 * Inits relevant hooks.
	 *
	 * @since 1.5.5
	 */
	public static function init_hooks() {
		add_action( 'noptin_after_register_menus', 'Noptin_Subscribers_Admin::register_menu', 30 );
		add_action( 'add_meta_boxes_noptin_subscribers', 'Noptin_Subscribers_Admin::register_metaboxes' );
		add_action( 'noptin_admin_delete_all_subscribers', 'Noptin_Subscribers_Admin::delete_all_subscribers' );
		add_action( 'noptin_admin_add_subscriber', 'Noptin_Subscribers_Admin::add_subscriber' );
		add_action( 'noptin_update_admin_edited_subscriber', 'Noptin_Subscribers_Admin::update_edited_subscriber' );
		add_action( 'load-noptin_page_noptin-subscribers', 'Noptin_Subscribers_Admin::add_subscribers_page_screen_options' );
		add_filter( 'set-screen-option', 'Noptin_Subscribers_Admin::save_subscribers_page_screen_options', 10, 3 );
	}

	/**
	 * Registers the admin menu.
	 *
	 * @since 1.5.5
	 */
	public static function register_menu() {

		$subscribers_page_title = apply_filters( 'noptin_admin_subscribers_page_title', __( 'Email Subscribers', 'newsletter-optin-box' ) );
		add_submenu_page(
			'noptin',
			$subscribers_page_title,
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-subscribers',
			'Noptin_Subscribers_Admin::output'
		);

	}

	/**
	 * Registers metaboxes.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @since 1.5.5
	 */
	public static function register_metaboxes( $subscriber ) {

		add_meta_box(
			'noptin_subscriber_details',
			__( 'Subscriber Details', 'newsletter-optin-box' ),
			'Noptin_Subscribers_Admin::metabox_callback',
			'noptin_page_noptin-subscribers',
			'normal',
			'default',
			'details'
		);

		add_meta_box(
			'noptin_subscriber_activity',
			__( 'Activity Feed','newsletter-optin-box' ),
			'Noptin_Subscribers_Admin::metabox_callback',
			'noptin_page_noptin-subscribers',
			'advanced',
			'default',
			'activity'
		);

		add_meta_box(
			'noptin_subscriber_save',
			__( 'Save Changes', 'newsletter-optin-box' ),
			'Noptin_Subscribers_Admin::metabox_callback',
			'noptin_page_noptin-subscribers',
			'side',
			'default',
			'save'
		);

		if ( 1 !== (int) $subscriber->confirmed ) {

			add_meta_box(
				'noptin_subscriber_double_optin',
				__( 'Confirmation Email', 'newsletter-optin-box' ),
				'Noptin_Subscribers_Admin::metabox_callback',
				'noptin_page_noptin-subscribers',
				'side',
				'default',
				'double-optin'
			);

		}

		if ( apply_filters( 'noptin_enable_geolocation', true ) ) {
			$ip_address = $subscriber->ip_address;
			if ( ! empty( $ip_address ) && noptin_locate_ip_address( $ip_address ) ) {

				add_meta_box(
					'noptin_subscriber_location',
					__( 'GeoLocation', 'newsletter-optin-box' ),
					'Noptin_Subscribers_Admin::metabox_callback',
					'noptin_page_noptin-subscribers',
					'side',
					'default',
					'geolocation'
				);

			}
		}

	}

	/**
	 * Displays default metaboxes.
	 *
	 * @param Noptin_Subscriber $subscriber.
	 * @param array $metabox.
	 * @since 1.5.5
	 */
	public static function metabox_callback( $subscriber, $metabox ) {

		$file = trim( $metabox['args'] );
		$file = plugin_dir_path( __FILE__ ) . "views/single-subscriber/$file.php";

		if ( file_exists( $file ) ) {
			include $file;
		}

	}

	/**
	 * Displays the subscribers admin page.
	 *
	 * @since       1.5.5
	 */
	public static function output() {

		// Only admins should access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		do_action( 'noptin_before_admin_subscribers_page', noptin()->admin );

		$is_component_page = false;

		// Either render the appropriate component...
		foreach ( self::get_components() as $component => $details ) {

			if ( isset( $_GET[ $component ] ) ) {
				call_user_func( $details['callback'] );
				$is_component_page = true;
				break;
			}

		}

		// Or the subscriber's overview page.
		if ( ! $is_component_page ) {
			self::render_subscribers_overview_page();
		}

		do_action( 'noptin_after_admin_subscribers_page', noptin()->admin );
	}

	/**
	 * Returns the sub-pages.
	 *
	 * @since       1.5.5
	 */
	public static function get_components() {

		return apply_filters(
			'noptin_admin_subscribers_page_components',
			array(
				'subscriber'       => array(
					'callback'     => 'Noptin_Subscribers_Admin::render_single_subscriber_page',
					'show_on_tabs' => false,
					'label'        => __( 'Edit Subscriber', 'newsletter-optin-box' ),
				),
				'add'              => array(
					'callback'     => 'Noptin_Subscribers_Admin::render_add_subscriber_page',
					'show_on_tabs' => false,
					'label'        => __( 'Add Subscriber', 'newsletter-optin-box' ),
				),
				'custom_fields'    => array(
					'callback'     => 'Noptin_Subscribers_Admin::render_custom_fields_page',
					'show_on_tabs' => true,
					'label'        => __( 'Custom Fields', 'newsletter-optin-box' ),
				),
				'import'           => array(
					'callback'     => 'Noptin_Subscribers_Admin::render_import_subscribers_page',
					'show_on_tabs' => true,
					'label'        => __( 'Import', 'newsletter-optin-box' ),
				),
				'export'           => array(
					'callback'     => 'Noptin_Subscribers_Admin::render_export_subscribers_page',
					'show_on_tabs' => true,
					'label'        => __( 'Export', 'newsletter-optin-box' ),
				),

			)
		);

	}

	/**
	 * Renders subscribers overview page
	 *
	 * @since       1.5.5
	 */
	public static function render_subscribers_overview_page() {
		do_action( 'noptin_before_subscribers_overview_page', noptin()->admin );
		include plugin_dir_path( __FILE__ ) . 'views/view-subscribers.php';
		do_action( 'noptin_after_subscribers_overview_page', noptin()->admin );
	}

	/**
	 * Displays a single subscriber.
	 *
	 * @param       int $subscriber The subscriber to display.
	 * @access      public
	 * @since       1.1.1
	 * @return      void
	 */
	public static function render_single_subscriber_page() {

		$data = '';
		$data_array = apply_filters( 'noptin_subscribers_page_extra_ajax_data', $_GET );
		foreach( $data_array as $key => $value ) {

			if ( is_scalar( $value ) ) {
				$value = esc_attr( $value );
				$key   = esc_attr( $key );
				$data .= " data-$key='$value'";
			}

		}

		echo "<div id='noptin-subscribers-page-data' $data></div>";

		$subscriber = isset( $_GET['subscriber'] ) ? (int) $_GET['subscriber'] : 0;
		$subscriber = new Noptin_Subscriber( $subscriber );

		do_action( 'noptin_admin_before_single_subscriber_page', $subscriber, noptin()->admin );

		if ( $subscriber->exists() ) {

			do_action( 'add_meta_boxes_noptin_subscribers', $subscriber );
			do_action( 'add_meta_boxes', 'noptin_subscribers', $subscriber );
			include plugin_dir_path( __FILE__ ) . 'views/single-subscriber/subscriber.php';

		} else {
			include plugin_dir_path( __FILE__ ) . 'views/single-subscriber/404.php';
		}

		do_action( 'noptin_admin_after_single_subscriber_page', $subscriber, noptin()->admin );
	}

	/**
	 * Displays the export subscribers.
	 *
	 * @since 1.5.5
	 */
	public static function render_export_subscribers_page() {
		do_action( 'noptin_admin_before_subscribers_export_page', noptin()->admin );
		include plugin_dir_path( __FILE__ ) . 'views/export-subscribers.php';
		do_action( 'noptin_admin_after_subscribers_export_page', noptin()->admin );
	}

	/**
	 * Displays the import subscribers.
	 *
	 * @since 1.5.5
	 */
	public static function render_import_subscribers_page() {
		do_action( 'noptin_admin_before_subscribers_import_page', noptin()->admin );
		include plugin_dir_path( __FILE__ ) . 'views/import-subscribers.php';
		do_action( 'noptin_admin_after_subscribers_import_page', noptin()->admin );
	}

	/**
	 * Displays the add subscriber page.
	 *
	 * @since 1.5.5
	 */
	public static function render_add_subscriber_page() {
		do_action( 'noptin_admin_before_add_subscriber_page', noptin()->admin );
		include plugin_dir_path( __FILE__ ) . 'views/add-subscriber.php';
		do_action( 'noptin_admin_after_add_subscriber_page', noptin()->admin );
	}

	/**
	 * Saves a submitted subscriber.
	 *
	 * @since       1.5.5
	 */
	public static function add_subscriber() {

		// Only admins should be able to add subscribers.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-admin-add-subscriber'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks. 
		if ( ! wp_verify_nonce( $_POST['noptin-admin-add-subscriber'], 'noptin-admin-add-subscriber' ) ) {
			return;
		}

		// Prepare subscriber fields.
		$subscriber_fields = array(
			'_subscriber_via' => 'manual',
			'active'          => (int) $_POST['noptin_fields']['active'],
			'confirmed'       => (int) $_POST['noptin_fields']['confirmed'],
		);

		foreach ( get_noptin_custom_fields() as $custom_field ) {

			$name  = $custom_field['merge_tag'];
			$value = isset( $_POST['noptin_fields'][ $name ] ) ? $_POST['noptin_fields'][ $name ] : '';
 
			$subscriber_fields[ $name ] = sanitize_noptin_custom_field_value( $value, $custom_field['type'], false );

		}

		// Ensure the email address is unique.
		if ( noptin_email_exists( $subscriber_fields['email'] ) ) {
			noptin()->admin->show_error( __( 'A subscriber with that email address exists.', 'newsletter-optin-box' ) );
			return;
		}

		// Add the subscriber.
		$result = add_noptin_subscriber( $subscriber_fields );

		// If an error occured, show it.
		if ( is_string( $result ) ) {
			noptin()->admin->show_error( $result );
		} else {

			// Else, show a success message and redirect to the added subscriber.
			noptin()->admin->show_success( __( 'Suscriber added successfully.', 'newsletter-optin-box' ) );

			wp_redirect(
				add_query_arg( 'subscriber', (int) $result, admin_url( 'admin.php?page=noptin-subscribers' ) )
			);
			exit;

		}

	}

	/**
	 * Updates a subscriber after they've been edited by admin.
	 *
	 * @since       1.5.5
	 */
	public static function update_edited_subscriber() {

		// Only admins should be able to edit subscribers.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-admin-update-subscriber-nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks. 
		if ( ! wp_verify_nonce( $_POST['noptin-admin-update-subscriber-nonce'], 'noptin-admin-update-subscriber' ) ) {
			return;
		}

		// Do we have a subscriber id?
		if ( empty( $_POST['subscriber_id'] ) ) {
			return;
		}

		// Prepare subscriber fields.
		$subscriber_fields = array(
			'active'    => (int) $_POST['noptin_fields']['active'],
			'confirmed' => (int) $_POST['noptin_fields']['confirmed'],
		);
		$subscriber        = get_noptin_subscriber( (int) $_POST['subscriber_id'] );

		if ( ! $subscriber->exists() ) {
			return;
		}

		// NOTE: We're not filtering visible fields only...
		// Since admin can also update private fields.
		foreach ( get_noptin_custom_fields() as $custom_field ) {

			$name  = $custom_field['merge_tag'];
			$value = isset( $_POST['noptin_fields'][ $name ] ) ? $_POST['noptin_fields'][ $name ] : '';
 
			$subscriber_fields[ $name ] = sanitize_noptin_custom_field_value( $value, $custom_field['type'], $subscriber );

		}

		// Add the subscriber.
		$result = update_noptin_subscriber( (int) $_POST['subscriber_id'], $subscriber_fields );

		if ( $result ) {
			noptin()->admin->show_success( __( 'Subscriber successfully updated', 'newsletter-optin-box' ) );
		} else {
			noptin()->admin->show_error( __( 'Unable to update the subscriber', 'newsletter-optin-box' ) );
		}

	}

	/**
	 * Registers screen options for the subscribers page.
	 *
	 * @access      public
	 * @since       1.5.5
	 */
	public static function add_subscribers_page_screen_options() {

		if ( 0 !== count( array_intersect_key( Noptin_Subscribers_Admin::get_components(), $_GET ) ) ) {
			return;
		}

		$args = array(
			'default' => 10,
			'option'  => 'noptin_subscribers_per_page'
		);

		add_screen_option( 'per_page', $args );

	}

	/**
	 * Saves subscribers page screen options.
	 *
	 * @access      public
	 * @since       1.5.5
	 */
	public static function save_subscribers_page_screen_options( $skip, $option, $value ) {
		return 'noptin_subscribers_per_page' === $option ? $value : $skip;
	}

	/**
	 * Deletes all subscribers.
	 *
	 * @since       1.5.5
	 */
	public static function delete_all_subscribers() {
		global $wpdb;

		// Only admins should be able to add subscribers.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks. 
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'noptin-delete-subscribers' ) ) {
			return;
		}

		$table    = get_noptin_subscribers_table_name();
		$wpdb->query( "TRUNCATE TABLE $table" );

		$table    = get_noptin_subscribers_meta_table_name();
		$wpdb->query( "TRUNCATE TABLE $table" );

		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'noptin_subscriber_id' ), '%s' );

		do_action( 'noptin_delete_all_subscribers' );

		noptin()->admin->show_info( __( 'Successfully deleted all subscribers.', 'newsletter-optin-box' ) );
		wp_redirect( remove_query_arg( array( 'noptin_admin_action', '_wpnonce' ) ) );
		exit;
	}

}
