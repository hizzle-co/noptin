<?php

namespace Hizzle\Noptin\Integrations\WPML;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with WPML.
 *
 * @since 1.6.2
 */
class Main {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'translate_noptin_form_id', array( __CLASS__, 'translate_form_id' ), 10, 1 );
		add_filter( 'noptin_post_locale', array( __CLASS__, 'filter_post_locale' ), 10, 2 );
		add_filter( 'icl_job_elements', array( __CLASS__, 'remove_body_from_translation_job' ), 10, 2 );
		add_filter( 'wpml_document_view_item_link', array( __CLASS__, 'document_view_item_link' ), 10, 5 );
		add_filter( 'noptin_is_multilingual', '__return_true', 5 );
		add_filter( 'noptin_form_scripts_params', array( __CLASS__, 'filter_ajax_params' ), 5 );
		add_filter( 'noptin_multilingual_active_languages', array( __CLASS__, 'filter_active_languages' ) );
		add_filter( 'noptin_convert_language_locale_to_slug', array( __CLASS__, 'convert_language_locale_to_slug' ) );
		add_filter( 'noptin_woocommerce_order_locale', array( __CLASS__, 'filter_order_locale' ), 10, 2 );
		add_filter( 'noptin_post_type_get_all_filters', array( __CLASS__, 'post_type_get_all_filters' ) );

		add_action( 'admin_init', array( __CLASS__, 'maybe_save_post_language_data' ) );
	}

	/**
	 * Maybe save post language data from heartbeat request.
	 */
	public static function maybe_save_post_language_data() {
		if ( ! isset( $_POST['action'] ) || 'heartbeat' !== $_POST['action'] ) {
			return;
		}

		if ( empty( $_POST['data']['icl_post_language'] ) || empty( $_POST['data']['wp-refresh-post-lock']['post_id'] ) ) {
			return;
		}

		// Check if the current user can edit this post.
		$post_id = absint( $_POST['data']['wp-refresh-post-lock']['post_id'] );
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save the post language data.
		set_transient( 'noptin_wpml_post_language_' . $post_id, $_POST['data']['icl_post_language'], HOUR_IN_SECONDS );
	}

	/**
	 * Find the right form and return it in the current language.
	 *
	 * @param int $form_id The form ID being displayed.
	 *
	 * @return int
	 */
	public static function translate_form_id( $form_id ) {
		return apply_filters( 'wpml_object_id', $form_id, 'noptin-form', true );
	}

	/**
	 * Filter the locale of a post.
	 *
	 * @param string $locale
	 * @param int    $post_id
	 * @return string $locale
	 */
	public static function filter_post_locale( $locale, $post_id ) {
		$lang = apply_filters( 'wpml_post_language_details', '', $post_id );

		if ( is_array( $lang ) && ! empty( $lang['locale'] ) )  {
			return $lang['locale'];
		}

		$lang = get_transient( 'noptin_wpml_post_language_' . $post_id );

		if ( ! empty( $lang ) ) {
			global $sitepress;
			if ( $sitepress ) {
				$locale = $sitepress->get_locale_from_language_code( $lang );
				if ( ! empty( $locale ) ) {
					return $locale;
				}
			}
		}

		return $locale;
	}

	/**
	 * Don't translate the post_content of subscription forms.
	 *
	 * @param array $elements Translation job elements.
	 * @param int   $post_id  The post ID.
	 *
	 * @return array
	 */
	public static function remove_body_from_translation_job( $elements, $post_id ) {

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
	public static function document_view_item_link( $link, $text, $job, $prefix, $type ) {
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
	public static function filter_ajax_params( $params ) {
		$params['resturl'] = apply_filters( 'wpml_permalink', $params['resturl'], null );
		return $params;
	}

	/**
	 * Returns an array of active languages.
	 *
	 * @param array $languages
	 * @return array $languages
	 */
	public static function filter_active_languages( $languages ) {
		$new_languages = apply_filters( 'wpml_active_languages', null, 'skip_missing=0' );

		if ( ! empty( $new_languages ) ) {
			$languages = wp_list_pluck( $new_languages, 'native_name', 'default_locale' );
		}

		return $languages;
	}

	/**
	 * Converts a language locale to a language slug.
	 *
	 * @param string $locale The language locale.
	 * @return string The language slug.
	 */
	public static function convert_language_locale_to_slug( $locale ) {
		global $sitepress;

		if ( ! empty( $sitepress ) ) {
			return $sitepress->get_language_code_from_locale( $locale );
		}
		$languages = apply_filters( 'wpml_active_languages', null, 'skip_missing=0' );

		if ( ! empty( $languages ) ) {
			foreach ( $languages as $lang ) {
				if ( $lang['default_locale'] === $locale ) {
					return $lang['code'];
				}
			}
		}

		return '';
	}

	/**
	 * Filter the locale of an order.
	 *
	 * @param string $locale
	 * @param int    $order_id
	 * @return string $locale
	 */
	public static function filter_order_locale( $locale, $order_id ) {
		$order = wc_get_order( $order_id );
		$saved = $order ? $order->get_meta( 'wpml_language', true ) : '';
		return empty( $saved ) ? $locale : $saved;
	}

	/**
	 * Filters the filters for a post type.
	 *
	 * @param array  $filters
	 * @param string $post_type
	 *
	 * @return array
	 */
	public static function post_type_get_all_filters( $filters ) {
		if ( ! empty( $filters['lang'] ) ) {
			do_action( 'wpml_switch_language', $filters['lang'] );
			add_action( 'noptin_post_type_get_all_after_query', array( __CLASS__, 'restore_language' ) );
		}

		return $filters;
	}

	/**
	 * Restores the language after the query.
	 *
	 * @param array $posts
	 * @param array $filters
	 * @param string $post_type
	 */
	public static function restore_language() {
		do_action( 'wpml_switch_language', null );
	}
}
