<?php
/**
 * class Noptin_New_Post_Notify class.
 *
 */

defined( 'ABSPATH' ) || exit;

class Noptin_New_Post_Notify {

	/**
	 *  Constructor function.
	 */
	function __construct() {

		//Default automation data
		add_filter( 'noptin_email_automation_setup_data', array( $this, 'default_automation_data' ) );

	}

	/**
	 * Filters default automation data
	 */
	public function default_automation_data( $data ) {

		if( 'post_notifications' == $data['automation_type'] ) {
			$data[ 'email_body' ]   = '<p>[[post_excerpt]]</p><p>Learn more about <a href="https://noptin.com/guide/new-post-notifications/">how to set up new post notifications</a>.</p>';
			$data[ 'subject' ]      = '[[post_title]]';
			$data[ 'preview_text' ] = __( 'New article published on [[blog_name]]' );
		}
		return $data;

	}


}
new Noptin_New_Post_Notify();
