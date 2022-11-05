<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Displays popups on the front page
 *
 * @since 1.0.5
 * @deprecated
 */
class Noptin_Popups {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Add popups to the footer.
		add_action( 'wp_footer', array( $this, 'display_popups' ), 5 );

	}

	/**
	 * Displays popups on the front end
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public function display_popups() {

		// Maybe abort early.
		if ( is_admin() || is_noptin_actions_page() || ! noptin_should_show_optins() || noptin_is_preview() ) {
			return;
		}

		/**
		 * Fires before popups are displayed
		 *
		 * @since 1.0.5
		 */
		do_action( 'before_noptin_popup_display', $this );

		$popups    = $this->get_popups();
		$displayed = false;
		foreach ( $popups as $popup ) {

			// Prepare the form.
			$form = noptin_get_optin_form( $popup );

			// Can it be displayed?
			if ( $form->can_show() ) {
				echo '<div class="noptin-popup-template-holder">';
				$form->display();
				echo '</div>';
				$displayed = true;
			}
		}

		if ( $displayed ) {
			wp_enqueue_script( 'noptin-legacy-popups' );
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
