<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Polylang.
 *
 * @since       1.6.2
 */
class Noptin_Polylang {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'pll_get_post_types', array( $this, 'filter_post_types' ), 10, 2 );
		add_filter( 'translate_noptin_form_id', array( $this, 'translate_form_id' ) );
		add_filter( 'noptin_is_multilingual', '__return_true', 5 );
		add_filter( 'noptin_form_scripts_params', array( $this, 'filter_ajax_params' ), 5 );

	}

	/**
	 * Filters editable post types.
	 *
	 * @param array  $post_types
	 * @param bool   $is_settings
	 *
	 * @return array
	 */
	public function filter_post_types( $post_types, $is_settings = false ) {

		if ( ! $is_settings ) {
			$post_types['noptin-form'] = 'noptin-form';
		}

		return $post_types;
	}

	/**
	 * Find the right form and return it in the current language.
	 *
	 * @param int $form_id The form ID being displayed.
	 *
	 * @return int
	 */
	public function translate_form_id( $form_id ) {

		if ( function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $form_id );

			if ( ! empty( $translated ) ) {
				$form_id = $translated;
			}
		}

		return $form_id;
	}

	/**
	 * Add language info to REST links.
	 *
	 * @param array $params
	 * @return array $params
	 */
	public function filter_ajax_params( $params ) {
		$params['resturl'] = $params['ajaxurl'];
		return $params;
	}

}
