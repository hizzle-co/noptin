<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with WPML.
 *
 * @since       1.6.2
 */
class Noptin_WPML {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'translate_noptin_form_id', array( $this, 'translate_form_id' ), 10, 1 );
		add_filter( 'icl_job_elements', array( $this, 'remove_body_from_translation_job' ), 10, 2 );
		add_filter( 'wpml_document_view_item_link', array( $this, 'document_view_item_link' ), 10, 5 );
		add_filter( 'noptin_is_multilingual', '__return_true', 5 );
		add_filter( 'noptin_form_scripts_params', array( $this, 'filter_ajax_params' ), 5 );
		add_filter( 'noptin_multilingual_active_languages', array( $this, 'filter_active_languages' ) );
	}

	/**
	 * Find the right form and return it in the current language.
	 *
	 * @param int $form_id The form ID being displayed.
	 *
	 * @return int
	 */
	public function translate_form_id( $form_id ) {
		return apply_filters( 'wpml_object_id', $form_id, 'noptin-form', true );
	}

	/**
	 * Don't translate the post_content of subscription forms.
	 *
	 * @param array $elements Translation job elements.
	 * @param int   $post_id  The post ID.
	 *
	 * @return array
	 */
	public function remove_body_from_translation_job( $elements, $post_id ) {

		// Bail out early if its not a noptin form.
		if ( 'noptin-form' !== get_post_type( $post_id ) ) {
			return $elements;
		}

		// Search for the body element and empty it so that it's not displayed in the TE.
		$field_types = wp_list_pluck( $elements, 'field_type' );
		$index       = array_search( 'body', $field_types, true );
		if ( false !== $index ) {
			$elements[ $index ]->field_data            = '';
			$elements[ $index ]->field_data_translated = '';
		}

		return $elements;
	}

	/**
	 * Remove the 'View' link from translation jobs because Subscription
	 * Forms don't have a link to 'View' them.
	 *
	 * @param string $link   The complete link.
	 * @param string $text   The text to link.
	 * @param object $job    The corresponding translation job.
	 * @param string $prefix The prefix of the element type.
	 * @param string $type   The element type.
	 *
	 * @return string
	 */
	public function document_view_item_link( $link, $text, $job, $prefix, $type ) {
		if ( 'noptin-form' === $type ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Add language info to REST links.
	 *
	 * @param array $params
	 * @return array $params
	 */
	public function filter_ajax_params( $params ) {
		$params['resturl'] = apply_filters( 'wpml_permalink', $params['resturl'], null );
		return $params;
	}

	/**
	 * Returns an array of active languages.
	 *
	 * @param array $languages
	 * @return array $languages
	 */
	public function filter_active_languages( $languages ) {
		$new_languages = apply_filters( 'wpml_active_languages', null, 'skip_missing=0' );

		if ( ! empty( $new_languages ) ) {
			$languages = wp_list_pluck( $new_languages, 'native_name', 'default_locale' );
		}

		return $languages;
	}

}
