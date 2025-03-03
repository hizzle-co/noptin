<?php

namespace Hizzle\Noptin\Integrations\Polylang;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Polylang.
 *
 * @since 2.0.0
 */
class Main {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'pll_get_post_types', array( __CLASS__, 'filter_post_types' ), 10, 2 );
		add_filter( 'translate_noptin_form_id', array( __CLASS__, 'translate_form_id' ) );
		add_filter( 'noptin_post_locale', array( __CLASS__, 'filter_post_locale' ), 10, 2 );
		add_filter( 'noptin_is_multilingual', '__return_true', 5 );
		add_filter( 'noptin_form_scripts_params', array( __CLASS__, 'filter_ajax_params' ), 5 );
		add_filter( 'noptin_multilingual_active_languages', array( __CLASS__, 'filter_active_languages' ) );
		add_filter( 'noptin_convert_language_locale_to_slug', array( __CLASS__, 'convert_language_locale_to_slug' ) );
		add_filter( 'noptin_action_url_home_url', array( __CLASS__, 'filter_home_url' ) );
		add_filter( 'noptin_woocommerce_order_locale', array( __CLASS__, 'filter_order_locale' ), 10, 2 );
	}

	/**
	 * Filters editable post types.
	 *
	 * @param array  $post_types
	 * @param bool   $is_settings
	 *
	 * @return array
	 */
	public static function filter_post_types( $post_types, $is_settings = false ) {

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
	public static function translate_form_id( $form_id ) {

		if ( function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $form_id );

			if ( ! empty( $translated ) ) {
				$form_id = $translated;
			}
		}

		return $form_id;
	}

	/**
	 * Filters the locale for a given post.
	 *
	 * @param string $locale
	 * @param int    $post_id
	 * @return string $locale
	 */
	public static function filter_post_locale( $locale, $post_id ) {
		if ( function_exists( 'pll_get_post_language' ) ) {
			$locale = pll_get_post_language( $post_id, 'locale' );
		}

		return $locale;
	}

	/**
	 * Add language info to REST links.
	 *
	 * @param array $params
	 * @return array $params
	 */
	public static function filter_ajax_params( $params ) {
		$params['resturl'] = $params['ajaxurl'];
		return $params;
	}

	/**
	 * Returns an array of active languages.
	 *
	 * @param array $languages
	 * @return array $languages
	 */
	public static function filter_active_languages( $languages ) {

		if ( function_exists( 'pll_languages_list' ) ) {
			$languages = wp_list_pluck( pll_languages_list( array( 'fields' => array() ) ), 'name', 'locale' );
		}

		return $languages;
	}

	/**
	 * Converts a language locale to a language slug.
	 *
	 * @param string $locale
	 * @return string $slug
	 */
	public static function convert_language_locale_to_slug( $locale ) {

		$lang = PLL()->model->get_language( $locale );

		if ( ! $lang ) {
			return '';
		}

		return $lang->slug;
	}

	/**
	 * Filters the home URL to add the language code.
	 *
	 * @param string $url
	 * @return string $url
	 */
	public static function filter_home_url( $url ) {

		if ( function_exists( 'pll_home_url' ) ) {
			$subscriber = get_current_noptin_subscriber_id();
			$language   = empty( $subscriber ) ? '' : get_noptin_subscriber_meta( $subscriber, 'language', true );
			$url        = pll_home_url( $language );
		}

		return $url;
	}

	/**
	 * Filter the locale of an order.
	 *
	 * @param string $locale
	 * @param int    $order_id
	 * @return string $locale
	 */
	public static function filter_order_locale( $locale, $order_id ) {
		if ( class_exists( 'PLLWC_Data_Store' ) ) {
			/** @var \PLLWC_Order_Language_CPT $data_store */
			$data_store = \PLLWC_Data_Store::load( 'order_language' );
			$saved      = $data_store->get_language( $order_id, 'locale' );
			return empty( $saved ) ? $locale : $saved;
		}

		return $locale;
	}
}
