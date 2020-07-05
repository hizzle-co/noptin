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

		// Export subscribers.
		add_action( 'noptin_export_subscribers',  array( $this, 'export_subscribers' ) );

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
				$user = get_userdata( $user_id );
				$id   = get_noptin_subscriber_id_by_email ( $user->user_email );

				if ( $id ) {
					$subscriber_id = (int) $id;
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
			'firstname'        => 'first_name',
			'fname'            => 'first_name',
			'secondname'       => 'second_name',
			'lastname'         => 'second_name',
			'lname'            => 'second_name',
			'name'             => 'name',
			'fullname'         => 'name',
			'familyname'       => 'name',
			'displayname'      => 'name',
			'emailaddress'     => 'email',
			'email'            => 'email',
			'active'           => 'active',
			'liststatus'       => 'active',
			'status'           => 'active',
			'emailconfirmed'   => 'confirmed',
			'confirmed'        => 'confirmed',
			'globalstatus'     => 'confirmed',
			'verified'         => 'confirmed',
			'is_verified'      => 'confirmed',
			'datetime'         => 'date_created',
			'subscribedon'     => 'date_created',
			'datecreated'      => 'date_created',
			'subscriptiondate' => 'date_created',
			'createdon'        => 'date_created',
			'date'             => 'date_created',
			'confirmkey'       => 'confirm_key',
			'meta'             => 'meta',
			'fields'           => 'meta',
			'metafields'       => 'meta',
			'customfields'     => 'meta',
			'conversionpage'   => 'conversion_page',
			'ip'               => 'ip_address',
			'ipaddress'        => 'ip_address',
		);

		$extra_mappings = get_noptin_subscriber_fields();

		foreach ( $extra_mappings as $key => $label ) {
			$label      = strtolower( preg_replace( "/[^A-Za-z0-9]/", '', $key ) );
			$mappings[ $label ] = $key;
		}

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
			$subscriber['date_created'] = is_string( $subscriber['date_created'] ) ? date( 'Y-m-d', strtotime( $subscriber['date_created'] ) ) : date( 'Y-m-d', current_time( 'timestamp' ) );
		}

		return $subscriber;

	}

	/**
	 * Exports subscribers.
	 * 
	 * @since 1.3.1
	 */
	public function export_subscribers() {

		// Security checks.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-export-subscribers'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['noptin-export-subscribers'], 'noptin-export-subscribers' ) ) {
			return;
		}

		/**
		 * Runs before downloading subscribers.
		 *
		 */
		do_action( 'noptin_before_download_subscribers' );

		$output  = fopen( 'php://output', 'w' ) or die( __( 'Unsupported server', 'newsletter-optin-box' ) );

		// Prepare variables.
		$fields    = empty( $_POST['fields'] )    ? array_keys( get_noptin_subscriber_fields() ) : $_POST['fields'];
		$file_type = empty( $_POST['file_type'] ) ? 'csv' : sanitize_text_field( $_POST['file_type'] );
		$file_name = empty( $_POST['file_name'] ) ? 'noptin-subscribers-' . time() : sanitize_text_field( $_POST['file_name'] );

		header( "Content-Type:application/$file_type" );
		header( "Content-Disposition:attachment;filename=$file_name.$file_type" );

		$query = array(
			'subscriber_status' => empty( $_POST['subscriber_status'] ) ? 'all' : sanitize_text_field( $_POST['subscriber_status'] ),
			'email_status'      => empty( $_POST['email_status'] ) ? 'any' : sanitize_text_field( $_POST['email_status'] ),
			'order'             => empty( $_POST['order'] ) ? 'DESC' : sanitize_text_field( $_POST['order'] ),
			'orderby'           => empty( $_POST['orderby'] ) ? 'id' : sanitize_text_field( $_POST['orderby'] ),
			'count_total'       => false,
		);

		if ( ! empty( $_POST['search'] ) ) {
			$query['search'] = sanitize_text_field( $_POST['search'] );
		}

		if ( ! empty( $_POST['date'] ) ) {
			$date       = sanitize_text_field( $_POST['date'] );
			$date_query = array(
				'relation' => 'OR'
			);

			$date_type = 'on';
			if ( ! empty( $_POST['date_type'] ) ) {
				$date_type = $_POST['date_type'];
			}
		
			if ( 'on' === $date_type ) {
				$date_query[] = array(
					'year'          => date( 'Y', strtotime( $date ) ),
					'month'         => date( 'm', strtotime( $date ) ),
					'day'           => date( 'j', strtotime( $date ) ),
				);
			}
				
			if ( 'before' === $date_type ) {
				$date_query[] = array(
					'before'    => $date,
					'inclusive' => true,
				);
			}

			if ( 'after' === $date_type ) {
				$date_query[] = array(
					'after'    => $date,
					'inclusive' => true,
				);
			}

			$query['date_query'] = $date_query;

		}

		$query = apply_filters( 'noptin_filter_subscribers_export_query', $query );
		$query = new Noptin_Subscriber_Query( $query );
		$query->get_results();

		if ( 'csv' == $file_type ) {
			$this->download_subscribers_csv( $query->get_results(), $output, $fields );
		} else if( 'xml' == $file_type ) {
			$this->download_subscribers_xml( $query->get_results(), $output, $fields );
		} else {
			$this->download_subscribers_json( $query->get_results(), $output, $fields );
		}

		fclose( $output );

		/**
		 * Runs after after downloading.
		 *
		 */
		do_action( 'noptin_after_download_subscribers' );

		exit; // This is important.

	}

	/**
	 * Downloads subscribers as csv
	 *
	 * @access      public
	 * @param Noptin_Subscriber[] $subscribers The subscribers being downloaded.
	 * @param resource $output The stream to output to.
	 * @param array $fields The fields to stream.
	 * @since       1.3.1
	 */
	public function download_subscribers_csv( $subscribers, $output, $fields ) {

		// Retrieve subscribers.
		$all_fields = get_noptin_subscriber_fields();
		$all_fields = apply_filters( 'noptin_subscriber_export_fields', $all_fields );
		$labels     = array('ID');

		foreach ( $fields as $field ) {
			if ( isset( $all_fields[ $field ] ) ) {
				$labels[] = $all_fields[ $field ];
				continue;
			}
			$labels[] = noptin_sanitize_title_slug( $field );
		}

		// Output the csv column headers.
		fputcsv( $output, $labels );

		// Loop through 
		foreach ( $subscribers as $subscriber ) {
			$row  = array( $subscriber->id );

			foreach ( $fields as $field ) {

				if ( $field === 'confirmed' ) {
					$row[] = intval( $subscriber->confirmed );
					continue;
				}

				if ( $field === 'active' ) {
					$row[] = empty( $subscriber->active ) ? 1 : 0;
					continue;
				}

				if ( $field === 'full_name' ) {
					$row[] = trim( $subscriber->first_name . ' ' . $subscriber->second_name );
					continue;
				}

				$row[] = maybe_serialize( apply_filters( 'noptin_subscriber_export_field_value', $subscriber->get( $field ), $field, $subscriber ) );

			}

			fputcsv( $output, $row );
		}

	}

	/**
	 * Downloads subscribers as json
	 *
	 * @param Noptin_Subscriber[] $subscribers The subscribers being downloaded.
	 * @param resource $stream The stream to output to.
	 * @param array $fields The fields to stream.
	 * @since       1.3.1
	 */
	public function download_subscribers_json( $subscribers, $stream, $fields ) {
		$output     = array();
		$all_fields = get_noptin_subscriber_fields();
		$all_fields = apply_filters( 'noptin_subscriber_export_fields', $all_fields );

		// Loop through 
		foreach ( $subscribers as $subscriber ) {
			$row  = array();

			foreach ( $fields as $field ) {

				if ( isset( $all_fields[ $field ] ) ) {
					$label  = $all_fields[ $field ];
				} else {
					$label = noptin_sanitize_title_slug( $field );
				}

				if ( $field === 'active' ) {
					$row[ $label ] = empty( $subscriber->active ) ? 1 : 0;
					continue;
				}

				if ( $field === 'confirmed' ) {
					$row[ $label ] = intval( $subscriber->confirmed );
					continue;
				}

				if ( $field === 'full_name' ) {
					$row[ $label ] = trim( $subscriber->first_name . ' ' . $subscriber->second_name );
					continue;
				}

				$row[ $label ] = apply_filters( 'noptin_subscriber_export_field_value', $subscriber->get( $field ), $field, $subscriber );

			}

			$output[] = $row;

		}

		fwrite( $stream, wp_json_encode( $output ) );

	}

	/**
	 * Downloads subscribers as xml
	 *
	 * @param Noptin_Subscriber[] $subscribers The subscribers being downloaded.
	 * @param resource $stream The stream to output to.
	 * @param array $fields The fields to stream.
	 * @since       1.3.1
	 */
	public function download_subscribers_xml( $subscribers, $stream, $fields ) {
		$output     = array();
		$all_fields = get_noptin_subscriber_fields();
		$all_fields = apply_filters( 'noptin_subscriber_export_fields', $all_fields );

		// Loop through 
		foreach ( $subscribers as $subscriber ) {
			$row  = array();

			foreach ( $fields as $field ) {

				if ( isset( $all_fields[ $field ] ) ) {
					$label  = $all_fields[ $field ];
				} else {
					$label = $field;
				}

				$label = preg_replace("/[^A-Za-z0-9_\-]/", '', $label);

				if ( $field === 'active' ) {
					$row[ $label ] = empty( $subscriber->active ) ? 1 : 0;
					continue;
				}

				if ( $field === 'confirmed' ) {
					$row[ $label ] = intval( $subscriber->confirmed );
					continue;
				}

				if ( $field === 'full_name' ) {
					$row[ $label ] = trim( $subscriber->first_name . ' ' . $subscriber->second_name );
					continue;
				}
				
				$row[ $label ] = apply_filters( 'noptin_subscriber_export_field_value', $subscriber->get( $field ), $field, $subscriber );

			}

			$output[] = $row;

		}
		
		$xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
		$this->convert_array_xml( $output, $xml );

		fwrite( $stream, $xml->asXML() );

	}

	/**
	 * Converts subscribers array to xml
	 *
	 * @access      public
	 * @since       1.2.4
	 */
	public function convert_array_xml( $data, $xml ) {

		// Loop through 
		foreach ( $data as $key => $value ) {

			if ( is_array( $value ) ) {

				if( is_numeric( $key ) ){
					$key = 'item'.$key; //dealing with <0/>..<n/> issues
				}

				$subnode = $xml->addChild( $key );
				$this->convert_array_xml( $value, $subnode );

			} else {
				$xml->addChild( $key, htmlspecialchars( $value ) );
			}
		}

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
		$fields  = get_noptin_subscriber_fields();

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
