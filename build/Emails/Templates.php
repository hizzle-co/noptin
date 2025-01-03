<?php

/**
 * Main templates class.
 *
 * @since   3.0.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Main templates class.
 */
class Templates {

	/**
	 * Inits the main templates class.
	 *
	 */
	public static function init() {
		add_action( 'noptin_refresh_email_templates', array( __CLASS__, 'refresh_templates' ) );
		add_filter( 'noptin_email_settings_misc', array( __CLASS__, 'add_templates' ), 10, 2 );
		add_filter( 'noptin_get_default_email_props', array( __CLASS__, 'add_template' ), 100, 2 );
	}

	/**
	 * Refreshes all known email templates.
	 *
	 */
	public static function refresh_templates() {

		// Fetch the templates.
		$result = \Noptin_COM::process_api_response( wp_remote_get( 'https://noptin.com/email-templates/list.json' ) );
		if ( is_array( $result ) ) {
			$result = json_decode( wp_json_encode( $result ), true );
			update_option( 'noptin_email_templates', $result, false );
		}
	}

	/**
	 * Retrieves local templates.
	 *
	 * @return \Hizzle\Noptin\Emails\Email[]
	 */
	public static function get_local_templates() {
		return array_map(
			'noptin_get_email_campaign_object',
			get_posts(
				array(
					'numberposts'            => -1,
					'post_type'              => 'noptin-campaign',
					'orderby'                => 'menu_order',
					'order'                  => 'ASC',
					'suppress_filters'       => true, // DO NOT allow WPML to modify the query
					'cache_results'          => true,
					'update_post_term_cache' => false,
					'post_status'            => array( 'publish' ),
					'meta_query'             => array(
						array(
							'key'   => 'campaign_type',
							'value' => 'email_template',
						),
					),
				)
			)
		);
	}

	/**
	 * Add templates.
	 *
	 * @param array $settings The settings.
	 * @param string $script The script.
	 * @return array
	 */
	public static function add_templates( $settings, $script ) {
		if ( 'view-campaigns' !== $script ) {
			return $settings;
		}

		$local_templates = array();
		if ( apply_filters( 'noptin_show_local_templates', true ) ) {
			foreach ( self::get_local_templates() as $template ) {
				$local_templates[] = array(
					'id'   => $template->id,
					'slug' => $template->name,
					'name' => empty( $template->name ) ? $template->subject : $template->name,
				);
			}
		}

		if ( ! empty( $local_templates ) ) {
			$settings['local_templates'] = $local_templates;
		}

		$templates = get_option( 'noptin_email_templates', array() );

		if ( ! is_array( $templates ) || empty( $templates ) ) {
			$templates = wp_json_file_decode( plugin_dir_path( __FILE__ ) . 'templates.json', array( 'associative' => true ) );
		}

		if ( ! is_array( $templates ) || empty( $templates ) ) {
			return $settings;
		}

		$settings['templates'] = $templates;

		return $settings;
	}

	/**
	 * Add template.
	 *
	 * @param array $props The props.
	 * @return array
	 */
	public static function add_template( $props, $edited_campaign ) {
		$template = $edited_campaign->get( 'noptin_source_template' );

		if ( empty( $template ) || 'blank' === $template ) {
			return $props;
		}

		// Check if the template exists locally.
		if ( false !== strpos( $template, 'noptin_campaign_' ) ) {
			$email = noptin_get_email_campaign_object( str_replace( 'noptin_campaign_', '', $template ) );

			if ( empty( $email->id ) ) {
				wp_die( 'Could not load template.' );
			}

			$template = array(
				'content' => $email->content,
				'options' => array_filter(
					array(
						'color'             => $email->get( 'color' ),
						'button_background' => $email->get( 'button_background' ),
						'button_color'      => $email->get( 'button_color' ),
						'background_color'  => $email->get( 'background_color' ),
						'custom_css'        => $email->get( 'custom_css' ),
						'font_family'       => $email->get( 'font_family' ),
						'font_size'         => $email->get( 'font_size' ),
						'font_style'        => $email->get( 'font_style' ),
						'font_weight'       => $email->get( 'font_weight' ),
						'line_height'       => $email->get( 'line_height' ),
						'link_color'        => $email->get( 'link_color' ),
						'block_css'         => $email->get( 'block_css' ),
						'background_image'  => $email->get( 'background_image' ),
						'attachments'       => $email->get( 'attachments' ),
						'email_type'        => $email->get_email_type(),
						'template'          => $email->get_template(),
					)
				),
			);

			if ( 'visual' !== $email->get_email_type() ) {
				$template[ 'content_' . $email->get_email_type() ] = $email->get_content( $email->get_email_type() );
			}
		} else {
			$template = \Noptin_COM::process_api_response( wp_remote_get( "https://noptin.com/email-templates/{$template}.json" ) );

			if ( is_wp_error( $template ) ) {
				wp_die( 'Could not fetch template code:- ' . esc_html( $template->get_error_message() ) );
			}

			$template = json_decode( wp_json_encode( $template ), true );
		}

		$props['content_visual'] = str_ireplace( array( '{{DEFAULT_FOOTER_TEXT}}', '{{FOOTER_TEXT}}' ), get_noptin_footer_text(), $template['content'] );

		return array_merge(
			$props,
			$template['options']
		);
	}
}
