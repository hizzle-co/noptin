<?php

// Exit if accessed directly.
if( ! defined( 'ABSPATH' ) ) {
    die;
}

    /**
     * Prints the noptin page
     *
     * @since       1.0.6
     */

    class Noptin_Page{

    /**
	 * Class Constructor.
	 */
	public function __construct() {


        // Register shortcode.
		add_shortcode( 'noptin_action_page' , array( $this, 'do_shortcode' ) );

		// User unsubscribe.
		add_action( "noptin_page_unsubscribe", array( $this, 'unsubscribe_user' ) );

		// Email open.
		add_filter( "noptin_actions_page_template", array( $this, 'email_open' ) );

		// Email click.
		add_filter( "noptin_actions_page_template", array( $this, 'email_click' ) );

		// Preview email.
		add_action( "noptin_page_preview_email", array( $this, 'preview_email' ) );

		// Filter template.
		add_filter( "page_template", array( $this, 'filter_page_template' ) );

		// Admin bar.
		add_filter('show_admin_bar', array( $this, 'maybe_hide_admin_bar' ) );

    }

    /**
     * Converts shortcode to html
     *
     * @access      public
     * @since       1.0.6
     * @return      array
     */
    public function do_shortcode( $atts ) {

		// Abort early if no action is specified.
		$action = $this->get_request_action();
        if ( empty( $action ) ) {
			return '';
		}

		// Retrieve the optional value.
		$value = $this->get_request_value();

		ob_start();

		do_action( "noptin_page_$action", $value );

        return ob_get_clean();

	}

	/**
     * Retrieves the request action
     *
     * @access      public
     * @since       1.2.2
     * @return      string
     */
    public function get_request_action( ) {

        // Abort early if no action is specified.
        if ( empty( $_REQUEST['noptin_action'] ) && empty( $_REQUEST['na'] ) ) {
			return '';
		}

		// Prepare the action to execute...
		$action = empty( $_REQUEST['noptin_action'] ) ? trim( $_REQUEST['na'] ) : trim( $_REQUEST['noptin_action'] );
		return sanitize_title_with_dashes( urldecode( $action ) );

	}

	/**
     * Retrieves the request value
     *
     * @access      public
     * @since       1.2.2
     * @return      string
     */
    public function get_request_value( ) {

		$value = '';

		if ( isset( $_REQUEST['noptin_value'] ) ) {
            $value = sanitize_title_with_dashes( urldecode( $_REQUEST['noptin_value'] ) );
		}

		if ( isset( $_REQUEST['nv'] ) ) {
            $value = sanitize_title_with_dashes( urldecode( $_REQUEST['nv'] ) );
		}

		return $value;

	}


	/**
     * Logs email opens
     *
     * @access      public
     * @since       1.2.0
     * @return      array
     */
    public function email_open( $filter ) {

		if( 'email_open' != $this->get_request_action() ) {
			return $filter;
		}

		if ( isset( $_GET['sid'] ) && isset( $_GET['cid'] ) ) {
			$subscriber_id = intval( $_GET['sid'] );
			$campaign_id   = intval( $_GET['cid'] );
			log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id );
		}

		// Display 1x1 pixel transparent gif.
		nocache_headers();
		header( "Content-type: image/gif" );
		header( "Content-Length: 42" );
		echo base64_decode('R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEA');
		exit;

	}

	/**
     * Logs email clicks
     *
     * @access      public
     * @since       1.2.0
     * @return      array
     */
    public function email_click( $filter ) {

		if( 'email_click' != $this->get_request_action() ) {
			return $filter;
		}

		$to = get_home_url();

		if( isset( $_GET['to'] ) ) {
			$to = urldecode( $_GET['to'] );
		}

		if ( isset( $_GET['sid'] ) && isset( $_GET['cid'] ) ) {
			$subscriber_id = intval( $_GET['sid'] );
			$campaign_id   = intval( $_GET['cid'] );
			log_noptin_subscriber_campaign_click( $subscriber_id, $campaign_id, $to );
		}

		wp_redirect( $to );
		exit;

	}

	/**
     * Unsubscribes a user
     *
     * @access      public
     * @since       1.0.6
     * @return      array
     */
    public function unsubscribe_user( $key ) {
		global $wpdb;

        // Ensure a user key is specified.
        if ( empty( $key ) ) {
			$this->print_paragraph( __( 'Unable to subscribe you at this time.',  'newsletter-optin-box' ) );
            return;
		}

		$table   = get_noptin_subscribers_table_name();
		$updated = $wpdb->update(
			$table,
			array( 'active' 	 => 1 ),
			array( 'confirm_key' => $key ),
			'%d',
			'%s'
		);

		if( $updated ) {
			$this->print_paragraph( __( 'You have successfully been unsubscribed from this mailing list.',  'newsletter-optin-box' )  );
		} else {
			$this->print_paragraph( __( 'An error occured while trying to unsubscribe you from this mailing list.',  'newsletter-optin-box' )  );
		}


	}

	/**
     * Unsubscribes a user
     *
     * @access      public
     * @since       1.2.2
     * @return      array
     */
    public function preview_email( $campaign_id ) {

		// Ensure an email campaign is specified.
        if ( empty( $campaign_id ) ) {
			$this->print_paragraph( __( 'Invalid or missing campaign id.',  'newsletter-optin-box' ) );
            return;
		}

		// and that the current user is an administrator
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->print_paragraph( __( 'Only administrators can preview email campaigns.',  'newsletter-optin-box' ) );
            return;
		}

		$campaign = get_post( $campaign_id );

		// Ensure this is a newsletter campaign.
		if( empty( $campaign ) || 'noptin-campaign' !== $campaign->post_type || 'newsletter' !== get_post_meta( $campaign->ID, 'campaign_type', true ) ) {
			$this->print_paragraph( __( 'Cannot preview this campaign type.',  'newsletter-optin-box' ) );
            return;
		}

		// Fetch current user to use their details as merge tags.
		$user       = wp_get_current_user();
		$subscriber = get_noptin_subscriber_by_email( $user->user_email );
		$data       = array(
			'campaign_id' 	=> $campaign->ID,
			'template' 		=> locate_noptin_template( 'email-templates/paste.php' ),
			'email_body'	=> $campaign->post_content,
			'preview_text'	=> get_post_meta( $campaign->ID, 'preview_text', true ),
			'email'			=> $user->user_email,
			'merge_tags'	=> array(
				'email'			=> $user->user_email, 
				'first_name'	=> $user->user_firstname, 
				'second_name'	=> $user->user_lastname,
			),
		);

		// If the current user is a subscriber, use their subscriber data as merge tags.
		if( ! empty ( $subscriber ) ) {

			$data['subscriber_id']	=  $subscriber->id;
			$data['merge_tags']     = (array) $subscriber;
			$data['merge_tags']['unsubscribe_url'] = get_noptin_action_url( 'unsubscribe', $subscriber->confirm_key );

			$meta = get_noptin_subscriber_meta( $subscriber->id );
			foreach( $meta as $key=>$values ) {

				if( isset( $values[0] ) && is_string( $values[0] ) ) {
					$data['merge_tags'][$key] = esc_html( $values[0] );
				}

			}

		}

		// Generate and display the email.
		$mailer   = new Noptin_Mailer();
		$mailer->emogrify = false;
		
		echo  $mailer->get_email( $data );

	}

	public function print_paragraph( $content, $class= 'noptin-padded' ){
		echo "<p class='$class'>$content</p>";
	}

	public function filter_page_template( $template ){

		if( is_noptin_actions_page() ) {

			// No action specified, redirect back home.
			if( empty( $this->get_request_action() ) ) {
				wp_redirect( get_home_url() );
				exit;
			}

			$template = locate_noptin_template( 'actions-page.php' );
			if( isset( $_REQUEST['nte'] ) ) {
				$template = locate_noptin_template( 'actions-page-empty.php' );
			}

			$template = apply_filters( 'noptin_actions_page_template', $template );
		}
		return $template;

	}

	public function maybe_hide_admin_bar( $status ) {

		if( is_noptin_actions_page() ) {
			return false;
		}
		return $status;

	}


}
