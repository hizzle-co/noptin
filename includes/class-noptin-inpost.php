<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Displays inpost forms on the front page
 *
 * @since 1.0.5
 * @deprecated
 */
class Noptin_Inpost {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Appends opt-in forms to the post content.
		if ( is_using_new_noptin_forms() ) {
			add_filter( 'the_content', array( $this, 'append_forms_to_content' ) );
		} else {
			add_filter( 'the_content', array( $this, 'append_legacy_forms_to_content' ) );
		}

		// Hide block content.
		add_filter( 'pre_render_block', array( $this, 'maybe_hide_block' ), 10, 2 );

	}

	/**
	 * Appends opt-in forms to post content
	 *
	 * @access      public
	 * @param       string $content The content to append an opt-in form to.
	 * @since       1.6.2
	 * @return      string
	 */
	public function append_forms_to_content( $content ) {
		global $post;

		// Maybe abort early.
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() || is_noptin_actions_page() || ! noptin_should_show_optins() || noptin_is_preview() || is_preview() ) {
			return $content;
		}

		// Avoid elementor pages.
		if ( $post && noptin_is_page_built_with_elementor( $post->ID ) ) {
			return $content;
		}

		// Fetch forms.
		$forms = get_transient( 'noptin_forms_to_append' );

		if ( false === $forms ) {

			$forms = get_posts(
				array(
					'numberposts' => -1,
					'fields'      => 'ids',
					'post_type'   => 'noptin-form',
					'post_status' => 'publish',
					'meta_query'  => array(
						array(
							'key'     => 'form_settings',
							'compare' => 'EXISTS',
						),
					),
				)
			);

			set_transient( 'noptin_forms_to_append', $forms, HOUR_IN_SECONDS );
		}

		// Abort if non-exists;
		if ( empty( $forms ) ) {
			return $content;
		}

		// Loop through each form.
		foreach ( $forms as $form ) {

			// Prepare the form.
			$form = new Noptin_Form( $form );

			// Can it be displayed?
			if ( ! $form->can_show() || empty( $form->settings['inject'] ) ) {
				continue;
			}

			// Type of injection.
			$inject = $form->settings['inject'];

			// If we are to prepend.
			if ( 'both' === $inject || 'before' === $inject ) {
				$content = $form->get_html() . $content;
			}

			// If we are to append.
			if ( 'both' === $inject || 'after' === $inject ) {
				$content .= $form->get_html();
			}

			break;
		}

		return $content;

	}

	/**
	 * Appends opt in forms to post content
	 *
	 * @access      public
	 * @param       string $content The content to append an opt-in form to.
	 * @since       1.6.2
	 * @return      string
	 */
	public function append_legacy_forms_to_content( $content ) {
		global $post;

		// Maybe abort early.
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() || is_noptin_actions_page() || ! noptin_should_show_optins() || noptin_is_preview() || is_preview() ) {
			return $content;
		}

		// Avoid elementor pages.
		if ( $post && noptin_is_page_built_with_elementor( $post->ID ) ) {
			return $content;
		}

		$forms = $this->get_forms();
		foreach ( $forms as $form ) {

			// Prepare the form.
			$form = noptin_get_optin_form( $form );

			// Can it be displayed?
			if ( ! $form->can_show() || empty( $form->inject ) ) {
				continue;
			}

			// Type of injection.
			$inject = noptin_clean( $form->inject );

			// If we are to prepend.
			if ( 'both' === $inject || 'before' === $inject ) {
				$content = $form->get_html() . $content;
			}

			// If we are to append.
			if ( 'both' === $inject || 'after' === $inject ) {
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
	 * Hides legacy blocks if subscription forms are being hidden.
	 *
	 * @access      public
	 * @param       string|null $pre_render The pre-rendered content.
	 * @param       array $block The block being rendered.
	 * @since       1.2.8
	 * @return      string
	 */
	public function maybe_hide_block( $pre_render, $block ) {

		if ( ! is_admin() && 'noptin/email-optin' === $block['blockName'] && ! noptin_should_show_optins() ) {
			return '';
		}

		return $pre_render;

	}

}
