<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
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


        // Register shortcode
		add_shortcode( 'noptin_action_page' , array( $this, 'do_shortcode' ) );

		// User unsubscribe
		add_action( "noptin_page_unsubscribe", array( $this, 'unsubscribe_user' ) );

		// Email open
		add_filter( "noptin_actions_page_template", array( $this, 'email_open' ) );

		// Email click
		add_filter( "noptin_actions_page_template", array( $this, 'email_click' ) );

		// Filter template
		add_filter( "page_template", array( $this, 'filter_page_template' ) );

		// Admin bar
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

        // Abort early if no action is specified
        if ( empty( $_REQUEST['noptin_action'] ) ) {
			return '';
		}

		$action = sanitize_text_field( $_REQUEST['noptin_action'] );
		$value = '';

		if ( isset( $_REQUEST['noptin_value'] ) ) {
            $value = sanitize_text_field( $_REQUEST['noptin_value'] );
		}

		ob_start();

		do_action( "noptin_page_$action", $value );

        return ob_get_clean();

	}

	/**
     * Logs email opens
     *
     * @access      public
     * @since       1.2.0
     * @return      array
     */
    public function email_open( $filter ) {

		if( 'email_open' != $_GET['noptin_action'] ) {
			return $filter;
		}

		if ( isset( $_GET['sid'] ) && isset( $_GET['cid'] ) ) {
			$subscriber_id = intval( $_GET['sid'] );
			$campaign_id   = intval( $_GET['cid'] );
			log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id );
		}

		// Display 1x1 pixel transparent gif
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

		if( 'email_click' != $_GET['noptin_action'] ) {
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

        // Ensure a user key is specified
        if ( empty( $key ) ) {
			$this->print_paragraph( __( 'Unable to subscribe you at this time.',  'newsletter-optin-box' ) );
            return;
		}

		$table   = $wpdb->prefix . 'noptin_subscribers';
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

	public function print_paragraph( $content, $class= 'noptin-padded' ){
		echo "<p class='$class'>$content</p>";
	}

	public function filter_page_template( $template ){

		if( is_noptin_actions_page() ) {

			// No action specified, redirect back home
			if( empty( $_REQUEST['noptin_action'] ) ) {
				wp_redirect( get_home_url() );
				exit;
			}

			$template = get_noptin_include_dir( 'admin/templates/actions-page.php' );
			if( isset( $_REQUEST['noptin_template_empty'] ) ) {
				$template = get_noptin_include_dir( 'admin/templates/actions-page-empty.php' );
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

new Noptin_Page();
