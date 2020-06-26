<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Displays popups on the front page
 *
 * @since       1.0.5
 */
class Noptin_Popups {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Add popups to the footer.
		add_action( 'wp_footer', array( $this, 'display_popups' ) );

	}

	/**
	 * Displays popups on the front end
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public function display_popups() {

		// Abort if this is an admin page...
		if ( is_admin() || is_noptin_actions_page() || is_customize_preview() ) {
			return;
		}

		// ...or the user is hiding all popups.
		if ( ! empty( $_GET['noptin_hide'] ) ) {
			return;
		}

		// Do not show on elementor previews.
		if ( isset( $_GET['elementor-preview'] ) ) {
			return;
		}

		// Do not show on Ninja Forms previews.
		if ( isset( $_GET['nf_preview_form'] ) || isset( $_GET['nf_iframe'] ) ) {
			return;
		}

		/**
		 * Fires before popups are displayed
		 *
		 * @since 1.0.5
		 */
		do_action( 'before_noptin_popup_display', $this );

		$popups = $this->get_popups();
		foreach ( $popups as $popup ) {

			// Prepare the form.
			$form = noptin_get_optin_form( $popup );

			// Can it be displayed?
			if ( $form->can_show() ) {
				echo '<div class="noptin-popup-template-holder">';
				echo $form->get_html();
				echo '</div>';
			}
		}

		/**
		 * Fires after popups have been displayed
		 *
		 * @since 1.0.5
		 */
		do_action( 'after_noptin_popup_display', $this );

	}

	/**
	 * Returns a list of all published popup forms
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      array
	 */
	public function get_popups() {

		$args = array(
			'numberposts' => -1,
			'fields'      => 'ids',
			'post_type'   => 'noptin-form',
			'post_status' => 'publish',
			'meta_query'  => array(
				'relation' => 'OR',
				array(
					'key'     => '_noptin_optin_type',
					'value'   => 'popup',
					'compare' => '=',
				),
				array(
					'key'     => '_noptin_optin_type',
					'value'   => 'slide_in',
					'compare' => '=',
				),
			),
		);

		return get_posts( $args );
	}


}
