<?php
/**
 * Forms API: Content Embedder.
 *
 * Embeds opt-in forms into the content.
 *
 * @since             1.6.2
 * @package           Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Embeds opt-in forms into the content.
 *
 * @since 1.6.2
 */
class Content_Embedder {
	/**
	 * Cached forms to embed.
	 *
	 * @var \Noptin_Form[]|\Noptin_Form_Legacy[]
	 */
	private static $forms = null;

	/**
	 * Initializes the content embedder.
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'embed_forms' ) );
		add_filter( 'noptin_load_form_scripts', array( __CLASS__, 'maybe_load_form_scripts' ) );
		add_filter( 'pre_render_block', array( __CLASS__, 'maybe_hide_block' ), 10, 2 );
		// Empty the cache when a form is saved.
		add_action( 'save_post', array( __CLASS__, 'empty_cache' ) );
	}

	/**
	 * Determines whether to load form scripts based on the presence of embedded forms.
	 *
	 * @param bool $load Whether to load form scripts.
	 * @return bool
	 */
	public static function maybe_load_form_scripts( $load ) {
		return $load || ! empty( self::get_forms_to_embed() );
	}

	/**
	 * Embeds opt-in forms into the content.
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public static function embed_forms( $content ) {
		// Abort if not a singular post or page.
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// Get forms to embed.
		$forms = self::get_forms_to_embed();

		if ( empty( $forms ) ) {
			return $content;
		}

		// Embed forms.
		foreach ( $forms as $form ) {
			$position = noptin_clean( $form->inject );

			// If we are to prepend.
			if ( 'both' === $position || 'before' === $position ) {
				$content = show_noptin_form( $form->id, false ) . $content;
			}

			// If we are to append.
			if ( 'both' === $position || 'after' === $position ) {
				$content .= show_noptin_form( $form->id, false );
			}
		}

		return $content;
	}

	/**
	 * Get forms to embed.
	 *
	 * @return \Noptin_Form[]|\Noptin_Form_Legacy[]
	 */
	private static function get_forms_to_embed() {
		global $post;

		// Maybe abort early.
		if ( is_admin() || is_noptin_actions_page() || ! noptin_should_show_optins() || noptin_is_preview() || is_preview() || ! is_singular() ) {
			return array();
		}

		// Avoid elementor pages.
		if ( $post && noptin_is_page_built_with_elementor( $post->ID ) ) {
			return array();
		}

		if ( null === self::$forms ) {
			// Fetch forms.
			$forms = wp_cache_get( 'noptin_forms_to_append', 'noptin' );

			if ( false === $forms ) {

				$forms = get_posts(
					array(
						'numberposts' => -1,
						'fields'      => 'ids',
						'post_type'   => 'noptin-form',
						'post_status' => 'publish',
						'meta_query'  => array(
							'relation' => 'OR',
							array(
								'key'     => 'form_settings',
								'compare' => 'EXISTS',
							),
							array(
								'key'     => '_noptin_optin_type',
								'value'   => 'inpost',
								'compare' => '=',
							),
						),
					)
				);

				wp_cache_set( 'noptin_forms_to_append', $forms, 'noptin', DAY_IN_SECONDS );
			}

			self::$forms = array();

			if ( is_array( $forms ) ) {
				foreach ( $forms as $form ) {
					$form = noptin_get_optin_form( $form );

					// Can it be displayed?
					if ( $form->can_embed() ) {
						self::$forms[] = $form;
					}
				}
			}
		}

		return self::$forms;
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
	public static function maybe_hide_block( $pre_render, $block ) {

		if ( ! is_admin() && 'noptin/email-optin' === $block['blockName'] && ! noptin_should_show_optins() ) {
			return '';
		}

		return $pre_render;
	}

	/**
	 * Empties the cache when a form is saved.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function empty_cache( $post_id ) {
		if ( 'noptin-form' === get_post_type( $post_id ) ) {
			wp_cache_delete( 'noptin_forms_to_append', 'noptin' );
		}
	}
}
