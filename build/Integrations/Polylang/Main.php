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
		add_filter( 'noptin_post_type_get_all_filters', array( __CLASS__, 'post_type_get_all_filters' ) );
		add_filter( 'noptin_form_editor_rest_query_args', array( __CLASS__, 'form_editor_rest_query_args' ) );

		add_action( 'noptin_prepare_form_editor_post', array( __CLASS__, 'prepare_new_form_translation' ) );
		add_action( 'rest_after_insert_noptin-form', array( __CLASS__, 'set_rest_api_language' ), 10, 3 );
	}

	/**
	 * Prepare the auto-draft created by WordPress for a new form translation.
	 *
	 * Noptin uses its own editor, so Polylang's normal block-editor copy routine
	 * does not copy the form state before Noptin bootstraps the editor.
	 *
	 * @param \WP_Post $post Form editor post.
	 */
	public static function prepare_new_form_translation( $post ) {
		global $pagenow;

		if (
			'post-new.php' !== $pagenow ||
			! $post instanceof \WP_Post ||
			'noptin-form' !== $post->post_type ||
			empty( $_GET['_wpnonce'] ) ||
			empty( $_GET['from_post'] ) ||
			empty( $_GET['new_lang'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'new-post-translation' )
		) {
			return;
		}

		$from_post = absint( $_GET['from_post'] );
		$language  = sanitize_key( wp_unslash( $_GET['new_lang'] ) );
		$source    = get_post( $from_post );

		if (
			! $source instanceof \WP_Post ||
			'noptin-form' !== $source->post_type ||
			! current_user_can( 'edit_post', $source->ID ) ||
			! current_user_can( 'edit_post', $post->ID )
		) {
			return;
		}

		$source_form = new \Hizzle\Noptin\Forms\Form( $source->ID );
		$state       = $source_form->get_all_data();
		$form_type   = isset( $state['optinType'] ) ? $state['optinType'] : get_post_meta( $source->ID, '_noptin_optin_type', true );

		unset( $state['id'], $state['optinType'], $state['optinStatus'] );

		update_post_meta( $post->ID, '_noptin_state', $state );
		update_post_meta( $post->ID, '_noptin_optin_type', $form_type );
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_title'   => $source->post_title,
				'post_content' => $source->post_content,
				'post_status'  => 'draft',
			)
		);

		// Keep the global post in sync. Noptin passes this object to its editor
		// bootstrap, and its FormName component generates a new title whenever
		// the post is still an auto-draft.
		$post->post_title   = $source->post_title;
		$post->post_content = $source->post_content;
		$post->post_status  = 'draft';

		self::connect_translation( $post->ID, $from_post, $language );
	}

	/**
	 * Adds Polylang's translation context to form editor REST writes.
	 *
	 * @param array $args REST query arguments.
	 * @return array
	 */
	public static function form_editor_rest_query_args( $args ) {
		foreach ( array( 'new_lang', 'from_post' ) as $param ) {
			if ( isset( $_GET[ $param ] ) ) {
				$args[ 'noptin_' . $param ] = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
			}
		}

		return $args;
	}

	/**
	 * Assign and connect a form translation created through the REST API.
	 *
	 * @param \WP_Post         $post     The inserted/updated post object.
	 * @param \WP_REST_Request $request  The REST request.
	 * @param bool             $creating True on insert, false on update.
	 */
	public static function set_rest_api_language( $post, $request, $creating ) {
		$language  = sanitize_key( (string) $request->get_param( 'noptin_new_lang' ) );
		$from_post = absint( $request->get_param( 'noptin_from_post' ) );

		if (
			empty( $language ) ||
			empty( $from_post ) ||
			'noptin-form' !== get_post_type( $from_post ) ||
			! current_user_can( 'edit_post', $from_post )
		) {
			return;
		}

		self::connect_translation( $post->ID, $from_post, $language );
	}

	/**
	 * Assign a language to a form and add it to a translation group.
	 *
	 * @param int    $post_id   Translated form ID.
	 * @param int    $from_post Source form ID.
	 * @param string $language  Target language slug.
	 */
	private static function connect_translation( $post_id, $from_post, $language ) {
		if (
			! function_exists( 'pll_set_post_language' ) ||
			! function_exists( 'pll_get_post_language' ) ||
			! function_exists( 'pll_get_post_translations' ) ||
			! function_exists( 'pll_save_post_translations' )
		) {
			return;
		}

		pll_set_post_language( $post_id, $language );

		if ( pll_get_post_language( $post_id ) !== $language ) {
			return;
		}

		$source_language = pll_get_post_language( $from_post );

		if ( empty( $source_language ) ) {
			return;
		}

		$translations                     = pll_get_post_translations( $from_post );
		$translations[ $source_language ] = $from_post;
		$translations[ $language ]        = $post_id;
		pll_save_post_translations( $translations );
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

	/**
	 * Filters the query arguments for a post type.
	 *
	 * An empty language filter means all languages in Noptin, while Polylang
	 * otherwise defaults secondary queries to the current language.
	 *
	 * @param array $filters Query arguments.
	 * @return array
	 */
	public static function post_type_get_all_filters( $filters ) {
		if ( empty( $filters['lang'] ) ) {
			$filters['lang'] = 'all';
		}

		return $filters;
	}
}
