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


        //Register shortcode
		add_shortcode( 'noptin_action_page' , array( $this, 'do_shortcode') );

		//User unsubscribe
		add_action( "noptin_page_unsubscribe", array( $this, 'unsubscribe_user') );

    }

    /**
     * Converts shortcode to html
     *
     * @access      public
     * @since       1.0.6
     * @return      array
     */
    public function do_shortcode( $atts ) {

        //Abort early if no action is specified
        if ( empty( $_REQUEST['noptin_action'] ) ) {
            return '';
		}

		$action = sanitize_text_field( $_REQUEST['noptin_action'] );
		$value = '';

		if (! empty( $_REQUEST['noptin_value'] ) ) {
            $value = sanitize_text_field( $_REQUEST['noptin_value'] );
		}

		ob_start();

		do_action( "noptin_page_$action", $value );

        return ob_get_clean();

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

        //Ensure a user key is specified
        if ( empty( $key ) ) {
			$this->print_paragraph( __( 'Unable to subscribe you at this time.', 'noptin' ) );
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
			$this->print_paragraph( __( 'You have successfully been unsubscribed from this mailing list.', 'noptin' ) );
		} else {
			$this->print_paragraph( __( 'An error occured while trying to unsubscribe you from this mailing list.', 'noptin' ) );
		}


	}

	public function print_paragraph( $content ){
		echo "<p>$content</p>";
	}


}

new Noptin_Page();
