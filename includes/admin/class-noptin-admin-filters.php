<?php
/**
 * Registers admin filters and actions
 *
 * @since             1.2.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Main Class
 *
 * @since       1.2.4
 */
class Noptin_Admin_Filters {

	/**
	 * Class constructor.
	 * @since       1.2.4
	 */
	public function __construct() {

		add_filter( 'noptin_admin_tools_page_title', array( $this, 'filter_tools_page_titles' ) );
		add_filter( 'noptin_admin_subscribers_page_title', array( $this, 'filter_subscribers_page_titles' ) );

		// Show subscriber connection on user's list table.
        add_filter( 'manage_users_columns', array( $this, 'modify_users_table' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'modify_users_table_row' ), 10, 3 );

		// Filters Noptin subscriber's fields.
		add_filter( "noptin_format_imported_subscriber_fields", array( $this, 'format_imported_subscriber_fields' ) );

		// Templates.
		add_action( 'admin_footer-noptin_page_noptin-subscribers', array( $this, 'subscriber_fields_select' ) );
		add_action( 'admin_footer-noptin_page_noptin-subscribers', array( $this, 'create_subscriber_template' ) );

	}

	/**
	 * Filters tools page titles.
	 * @since       1.2.4
	 */
	public function filter_tools_page_titles( $title ) {
		
		$titles = array(
			'debug_log'	   => __( 'Debug Log', 'newsletter-optin-box' ),
			'system_info'  => __( 'System Information', 'newsletter-optin-box' ),
		);

		if ( isset( $_GET['tool'] ) && isset( $titles[ $_GET['tool'] ] ) ) {
			return $titles[ $_GET['tool'] ];
		}

		return $title;

	}

	/**
	 * Filters tools page titles.
	 * @since       1.2.7
	 */
	public function filter_subscribers_page_titles( $title ) {
		
		if ( ! empty( $_GET['subscriber'] ) ) {
			$subscriber = new Noptin_Subscriber( $_GET['subscriber'] );

			if ( ! empty( $subscriber->email ) ) {
				return sprintf(
							__( 'View Noptin Subscriber (%s)', 'newsletter-optin-box' ),
							sanitize_text_field( $subscriber->email )
				);
			}

		}
	}

	/**
	 * Adds a user's subscription status column
	 * @since       1.2.4
	 * @param array $columns User columns
	 */
	public function modify_users_table( $columns ) {
        $columns['noptin_subscriber'] = __( 'Email Subscriber', 'newsletter-optin-box' );
        return $columns;
	}
	
	/**
	 * Displays a user's subscription status
	 * @since       1.2.4
	 * @param mixed $val The current column value.
	 * @param string $column_name The current column name.
	 * @param int $user_id The current user id.
	 */
	public function modify_users_table_row( $val, $column_name, $user_id ) {

        switch ( $column_name ) {
			case 'noptin_subscriber' :
				$subscriber_id = get_user_meta ( $user_id, 'noptin_subscriber_id', true );

				if ( $subscriber_id ) {
					$subscriber_id = (int) $subscriber_id;
					$view_url      = esc_url( admin_url( "admin.php?page=noptin-subscribers&subscriber=$subscriber_id" ) );
					$text          = __( 'View', 'newsletter-optin-box' );
					return "<span style='color: #2e7d32;' class='dashicons dashicons-yes'></span><a href='$view_url' class='description'>$text</a>";
				}
                return '<span style="color: #f44336;" class="dashicons dashicons-no"></span>';
            default:
        }
        return $val;

	}

	/**
	 * Formats imported subscriber fields.
	 * 
	 * @param array $subscriber Subscriber fields.
	 */
	public function format_imported_subscriber_fields( $subscriber ) {

		$mappings = array(
			'firstname'      => 'first_name',
			'fname'          => 'first_name',
			'secondname'     => 'second_name',
			'lastname'       => 'second_name',
			'lname'          => 'second_name',
			'name'           => 'name',
			'fullname'       => 'name',
			'familyname'     => 'name',
			'displayname'    => 'name',
			'emailaddress'   => 'email',
			'email'          => 'email',
			'active'         => 'active',
			'liststatus'     => 'active',
			'status'         => 'active',
			'emailconfirmed' => 'confirmed',
			'confirmed'      => 'confirmed',
			'globalstatus'   => 'confirmed',
			'verified'       => 'confirmed',
			'is_verified'    => 'confirmed',
			'datetime'       => 'date_created',
			'subscribedon'   => 'date_created',
			'datecreated'    => 'date_created',
			'createdon'      => 'date_created',
			'date'           => 'date_created',
			'confirmkey'     => 'confirm_key',
			'meta'           => 'meta',
			'fields'         => 'meta',
			'metafields'     => 'meta',
			'customfields'   => 'meta',
			'conversionpage' => 'conversion_page',
			'ip'             => 'ip_address',
			'ipaddress'      => 'ip_address',
		);

		foreach( array_keys( $mappings ) as $key ) {
			$mappings["subscriber$key"] = $mappings[ $key ];
		}

		// Prepare subscriber fields.
		foreach ( (array) $subscriber as $key => $value ) {
			$sanitized = strtolower( preg_replace( "/[^A-Za-z0-9]/", '', $key ) );

			if ( isset( $mappings[ $sanitized ] ) && empty( $subscriber[ $mappings[ $sanitized ] ] ) ) {
				$subscriber[ $mappings[ $sanitized ] ] = $value;
				unset( $subscriber[ $key ] );
			}

		}

		// Meta data.
		if ( empty( $subscriber['meta'] ) ) {
			$subscriber['meta'] = array();
		}

		$subscriber['meta'] = maybe_unserialize( $subscriber['meta'] );

		if ( is_string( $subscriber['meta'] ) ) {
			$subscriber['meta'] = json_decode( $subscriber['meta'], true );
		}

		if ( ! is_array( $subscriber['meta'] ) ) {
			$subscriber['meta'] = array();
		}

		// Fill in meta fields for missing core fields.
		foreach ( $subscriber['meta'] as $key => $value ) {
			$sanitized = strtolower( preg_replace( "/[^A-Za-z0-9]/", '', $key ) );
			$value     = maybe_unserialize( $value );

			if ( isset( $mappings[ $sanitized ] ) && empty( $subscriber[ $mappings[ $sanitized ] ] ) ) {
				$subscriber[ $mappings[ $sanitized ] ] = is_array( $value ) ? $value[0] : $value;
				unset( $subscriber['meta'][ $key ] );
			}
		}

		// Date created.
		if ( ! empty( $subscriber['date_created'] ) ) {
			$subscriber['date_created'] = is_string( $subscriber['date_created'] ) ? date_i18n( 'Y-m-d', strtotime( $subscriber['date_created'] ) ) : date_i18n( 'Y-m-d' );
		}

		return $subscriber;

	}

	/**
	 * Select subscriber fields.
	 * 
	 */
	public function subscriber_fields_select() {
		echo '<div id="noptin-subscriber-fields-select-template" style="display:none"><p>';
		echo __( 'Select the subscriber fields to export', 'newsletter-optin-box' );
		echo '<style>.select2-container {z-index: 99999999999999 !important;}</style>';
		echo '</p><select class="noptin-subscriber-fields-select" name="noptin-subscriber-fields[]" multiple="multiple">';

		$default = array( 'first_name', 'second_name', 'email', 'active', 'confirmed' );
		$fields  = get_noptin_subscribers_fields();

		foreach( $fields as $field ) {
			$field    = noptin_clean( $field );
			$selected = selected( in_array( $field, $default ), true, false );
			echo "<option value='$field' $selected>$field</option>";
		}
		echo '</select></div>';
	}

	/**
	 * Create new subscriber
	 * 
	 */
	public function create_subscriber_template() {
		echo '<div id="noptin-create-subscriber-template" style="display:none"';
		echo '<label><input type="text" name="name" placeholder="Subscriber Name" class="noptin-create-subscriber-name swal2-input" /></label>';
		echo '<label><input type="email" name="email" placeholder="Email Address" class="noptin-create-subscriber-email swal2-input" /></label>';
		echo '</div>';
	}

}
