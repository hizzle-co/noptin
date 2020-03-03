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

		// Show subscriber connection on user's list table.
        add_filter( 'manage_users_columns', array( $this, 'modify_users_table' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'modify_users_table_row' ), 10, 3 );
		
		// Single subscribers.
		add_filter( "noptin_subscriber_wp_user_id_label", array( $this, 'wp_user_id_label' ) );
		add_filter( "noptin_format_subscriber_wp_user_id", array( $this, 'format_user_id' ), 10, 2 );
		add_filter( "noptin_subscriber_GDPR_consent_label", array( $this, 'gdpr_label' ) );
		add_filter( "noptin_format_subscriber_GDPR_consent", array( $this, 'format_gdpr' ), 10, 2 );

		// Filters Noptin subscriber's fields.
		add_filter( "noptin_format_imported_subscriber_fields", array( $this, 'format_imported_subscriber_fields' ) );

		// Select fields.
		add_action( 'admin_footer-noptin_page_noptin-subscribers', array( $this, 'subscriber_fields_select' ) );

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
	 * Returns the wp_user_id key label.
	 */
	public function wp_user_id_label(){
		return __( 'Registered user', 'newsletter-optin-box' );
	}

	/**
	 * Formats the user id.
	 * 
	 * @param int $user_id The subscriber's user id.
	 */
	public function format_user_id( $user_id, $data ){
		$user_id = ( int ) $user_id[0];
		$user = get_user_by( 'id', $user_id );

		if( $user ) {
			$login = esc_html( $user->user_login );
			return "<span style='color: #2e7d32;' class='dashicons dashicons-yes'></span>($login)";
		} 

		delete_noptin_subscriber_meta( $data->id, 'wp_user_id' );
		return '<span style="color: #f44336;" class="dashicons dashicons-no"></span>';
	}

	/**
	 * Returns the gdpr key label.
	 */
	public function gdpr_label(){
		return __( 'GDPR Consent', 'newsletter-optin-box' );
	}

	/**
	 * Formats the gdpr consent field.
	 * 
	 * @param int $gdpr The subscriber's gdpr consent status.
	 */
	public function format_gdpr( $gdpr ){

		$gdpr = $gdpr[0];
		if ( ! is_numeric( $gdpr ) ) {
			return sanitize_text_field( $gdpr );
		}

		if( $gdpr == 1 ) {
			return "<span style='color: #2e7d32;' class='dashicons dashicons-yes'></span>";
		} 

		return '<span style="color: #f44336;" class="dashicons dashicons-no"></span>';
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
			'subscribedon'   => 'date_created',
			'datecreated'    => 'date_created',
			'date'           => 'date_created',
			'confirmkey'     => 'confirm_key',
			'meta'           => 'meta',
			'fields'         => 'meta',
			'metafields'     => 'meta',
		);

		// Prepare subscriber fields.
		foreach ( (array) $subscriber as $key => $value ) {
			$sanitized = strtolower( str_ireplace( array( '_', '-', ' ' ), '', $key ) );

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

		if ( empty( $subscriber['meta'] ) ) {
			$subscriber['meta'] = array();
		}

		$subscriber['meta'] = (array) $subscriber['meta'];

		// Fill in meta fields for missing core fields.
		foreach ( $subscriber['meta'] as $key => $value ) {
			$sanitized = strtolower( str_ireplace( array( '_', '-', ' ' ), '', $key ) );
			$value     = maybe_unserialize( $value );

			if ( isset( $mappings[ $sanitized ] ) && empty( $subscriber[ $mappings[ $sanitized ] ] ) ) {
				$subscriber[ $mappings[ $sanitized ] ] = is_array( $value ) ? $value[0] : $value;
				unset( $subscriber['meta'][ $key ] );
			}
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

}
