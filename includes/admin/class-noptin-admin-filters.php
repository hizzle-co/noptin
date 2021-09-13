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

		// Export subscribers.
		add_action( 'noptin_export_subscribers',  array( $this, 'export_subscribers' ) );

	}

	/**
	 * Filters tools page titles.
	 * @since       1.2.4
	 */
	public function filter_tools_page_titles( $title ) {

		$titles = array(
			'debug_log'	         => __( 'Debug Log', 'newsletter-optin-box' ),
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
							sanitize_email( $subscriber->email )
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
		$fields    = empty( $_POST['fields'] )    ? array( 'email' ) : $_POST['fields'];
		$file_type = empty( $_POST['file_type'] ) ? 'csv' : sanitize_key( $_POST['file_type'] );
		$file_name = empty( $_POST['file_name'] ) ? 'noptin-subscribers-' . time() : sanitize_key( $_POST['file_name'] );

		header( "Content-Type:application/$file_type" );
		header( "Content-Disposition:attachment;filename=$file_name.$file_type" );

		$query = array(
			'subscriber_status' => empty( $_POST['subscriber_status'] ) ? 'all' : sanitize_key( $_POST['subscriber_status'] ),
			'email_status'      => empty( $_POST['email_status'] ) ? 'any' : sanitize_key( $_POST['email_status'] ),
			'order'             => empty( $_POST['order'] ) ? 'DESC' : sanitize_key( $_POST['order'] ),
			'orderby'           => empty( $_POST['orderby'] ) ? 'id' : sanitize_key( $_POST['orderby'] ),
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
		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'label', 'merge_tag' );
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
		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'label', 'merge_tag' );
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
		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'label', 'merge_tag' );
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

}
