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

	private static $file;
	/**
	 * Inits the main templates class.
	 *
	 */
	public static function init() {
		self::$file = plugin_dir_path( \Noptin::$file ) . 'includes/assets/email-templates.json';

		add_action( 'noptin_register_email_types', array( __CLASS__, 'register_email_type' ), 1000 );
		add_action( 'noptin_email_template_campaign_saved', array( __CLASS__, 'on_save' ) );
		add_action( 'noptin_email_template_campaign_deleted', array( __CLASS__, 'on_delete' ) );
		add_filter( 'noptin_email_template_email_extra_settings', array( __CLASS__, 'email_settings' ) );
		add_filter( 'noptin_email_settings_misc', array( __CLASS__, 'add_templates' ), 10, 2 );
		add_filter( 'noptin_get_default_email_props', array( __CLASS__, 'add_template' ), 100, 2 );
	}

	/**
	 * Register email type.
	 */
	public static function register_email_type() {
		if ( defined( 'NOPTIN_TEMPLATE_EDITOR' ) && NOPTIN_TEMPLATE_EDITOR ) {
			Main::register_email_type(
				array(
					'type'                => 'email_template',
					'plural'              => 'email_templates',
					'label'               => 'Template',
					'plural_label'        => 'Templates',
					'new_campaign_label'  => 'New Email Template',
					'click_to_add_first'  => 'Click the button below to set-up your first email template',
					'supports_recipients' => false,
					'supports_menu_order' => true,
					'icon'                => 'page',
				)
			);
		}
	}

	/**
	 * On save.
	 *
	 * @param Email $email The template.
	 */
	public static function on_save( $email ) {
		global $wp_filesystem;

		// Templates are keyed by their subject.
		if ( empty( $email->subject ) ) {
			return;
		}

		// Make sure the WP_Filesystem is initialized
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$templates = wp_json_file_decode( self::$file, array( 'associative' => true ) );

		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		if ( 'publish' === $email->status ) {
			$user = array(
				'sid'   => get_current_noptin_subscriber_id(),
				'uid'   => get_current_user_id(),
				'email' => 'test@example.com',
			);

			$email->prepare_preview( 'preview', $user );

			$templates[ $email->name ] = array(
				'order'     => $email->menu_order,
				'name'      => $email->subject,
				'content'   => $email->content,
				'image_url' => $email->get( 'template_image_url' ),
				'requires'  => $email->get( 'template_requires' ),
				'options'   => array(
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
				),
			);
		} elseif ( isset( $templates[ $email->subject ] ) ) {
			unset( $templates[ $email->subject ] );
		}

		// Order the templates by their menu order.
		uasort(
			$templates,
			function ( $a, $b ) {
				return $a['order'] <=> $b['order'];
			}
		);

		// Save the templates.
		/** @var \WP_Filesystem_Direct $wp_filesystem */
		$wp_filesystem->put_contents( self::$file, wp_json_encode( $templates ) );
	}

	/**
	 * On delete.
	 *
	 * @param Email $email The template.
	 */
	public static function on_delete( $email ) {
		global $wp_filesystem;

		// Templates are keyed by their subject.
		if ( empty( empty( $email->subject ) ) ) {
			return;
		}

		// Make sure the WP_Filesystem is initialized
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$templates = wp_json_file_decode( self::$file, array( 'associative' => true ) );

		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		if ( isset( $templates[ $email->subject ] ) ) {
			unset( $templates[ $email->subject ] );

			// Save the templates.
			/** @var \WP_Filesystem_Direct $wp_filesystem */
			$wp_filesystem->put_contents( self::$file, wp_json_encode( $templates ) );
		}
	}

	/**
	 * Email settings.
	 *
	 * @param array $settings The settings.
	 *
	 * @return array
	 */
	public static function email_settings( $settings ) {
		return array_merge(
			$settings,
			array(
				'template_image_url' => array(
					'el'      => 'input',
					'type'    => 'url',
					'label'   => 'Template Image URL',
					'default' => '',
				),
				'template_requires'  => array(
					'el'      => 'form_token',
					'label'   => 'Template Requires',
					'default' => '',
				),
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

		$templates = wp_json_file_decode( self::$file, array( 'associative' => true ) );

		if ( ! is_array( $templates ) || empty( $templates ) ) {
			return $settings;
		}

		$settings['templates'] = array_map(
			function ( $template ) {
				return array(
					'name'     => $template['name'],
					'image'    => $template['image_url'],
					'requires' => $template['requires'],
				);
			},
			$templates
		);

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

		$templates = wp_json_file_decode( self::$file, array( 'associative' => true ) );

		if ( ! is_array( $templates ) || empty( $templates[ $template ] ) ) {
			return $props;
		}

		$props['content_visual'] = str_replace( '{{footer_text}}', get_noptin_footer_text(), $templates[ $template ]['content'] );

		return array_merge(
			$props,
			$templates[ $template ]['options']
		);
	}
}
