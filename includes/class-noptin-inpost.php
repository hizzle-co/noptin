<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

	/**
	 * Displays inpost forms on the front page
	 *
	 * @since       1.0.5
	 */

class Noptin_Inpost {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Prepend/Apend inpost forms to the post content.
		add_filter( 'the_content', array( $this, 'append_inpost' ) );

		// Register shortcode.
		add_shortcode( 'noptin-form', array( $this, 'do_shortcode' ) );

	}

	/**
	 * Appends opt in forms to post content
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public function append_inpost( $content ) {

		// Maybe abort early.
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() || is_noptin_actions_page() || is_preview() ) {
			return  $content;
		}

		// ...or the user is hiding all popups.
		if ( isset( $_GET['noptin_hide'] ) && $_GET['noptin_hide'] == 'true' ) {
			return  $content;
		}

		$forms = $this->get_forms();
		foreach ( $forms as $form ) {

			// Prepare the form.
			$form = noptin_get_optin_form( $form );

			// Can it be displayed?
			if ( ! $form->can_show() || empty( $form->inject ) ) {
				continue;
			}

			// If we are to prepend.
			if ( 'both' == $form->inject || 'before' == $form->inject ) {
				$content = $form->get_html() . $content;
			}

			// If we are to append.
			if ( 'both' == $form->inject || 'after' == $form->inject ) {
				$content .= $form->get_html();
			}
		}

		return $content;

	}

	/**
	 * Returns a list of all published inpost forms
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      array
	 */
	public function get_forms() {

		$args = array(
			'numberposts' => -1,
			'fields'      => 'ids',
			'post_type'   => 'noptin-form',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_noptin_optin_type',
					'value'   => 'inpost',
					'compare' => '=',
				),
			),
		);

		return get_posts( $args );
	}

	/**
	 * Converts shortcode to html
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      array
	 */
	public function do_shortcode( $atts ) {

		// Abort early if no id is specified
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		// Prepare the form.
		$form = noptin_get_optin_form( trim( $atts['id'] ) );

		// Maybe return its html.
		if ( $form->can_show() ) {
			return $form->get_html();
		}

		return '';

	}


}

new Noptin_Inpost();
