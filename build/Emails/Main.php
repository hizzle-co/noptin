<?php

/**
 * Main emails class.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Main emails class.
 */
class Main {

	/**
     * @var Type[] The email types.
     */
	private static $types = array();

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		// Register REST controllers.
		add_action( 'noptin_rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		// Register email types.
		self::register_email_types();

		if ( is_admin() ) {
			Admin\Main::init();
		}
	}

	/**
	 * Registers REST routes.
	 *
	 * @param \Hizzle\Noptin\REST\Main $rest_api The REST API instance.
	 */
	public static function register_rest_routes( $rest_api ) {

		// Register email content controller.
		$rest_api->routes['emails'] = new REST( 'emails' );
	}

	/**
	 * Registers email types.
	 *
	 */
	private static function register_email_types() {

		// Newsletter emails.
		self::register_email_type(
			array(
				'type'               => 'newsletter',
				'plural'             => 'newsletters',
				'label'              => __( 'Newsletter', 'newsletter-optin-box' ),
				'plural_label'       => __( 'Newsletters', 'newsletter-optin-box' ),
				'new_campaign_label' => __( 'New Campaign', 'newsletter-optin-box' ),
				'click_to_add_first' => __( 'Click the button below to send your first newsletter campaign', 'newsletter-optin-box' ),
				'is_mass_mail'       => true,
			)
		);

		// Automation emails.
		self::register_email_type(
			array(
				'type'               => 'automation',
				'plural'             => 'automations',
				'label'              => __( 'Automated Email', 'newsletter-optin-box' ),
				'plural_label'       => __( 'Automated Emails', 'newsletter-optin-box' ),
				'new_campaign_label' => __( 'New Automated Email', 'newsletter-optin-box' ),
				'click_to_add_first' => __( 'Click the button below to set-up your first automated email', 'newsletter-optin-box' ),
			)
		);
	}

	/**
	 * Registers a single email type.
	 *
	 * @param array $args The email type args.
	 */
	public static function register_email_type( $args ) {
		$type = new Type( $args );

		self::$types[ $type->type ] = $type;
	}

	/**
	 * Returns an email type.
	 *
	 * @param string $type The email type.
	 * @return Type|false
	 */
	public static function get_email_type( $type ) {

		// Abort if email type is empty.
		if ( empty( $type ) ) {
			return false;
		}

		return isset( self::$types[ $type ] ) ? self::$types[ $type ] : false;
	}

	/**
	 * Returns an email type by its plural name.
	 *
	 * @param string $plural The email type plural name.
	 * @return Type|false
	 */
	public static function get_email_type_by_plural( $plural ) {
		return current(
			wp_list_filter( self::$types, array( 'plural' => $plural ) )
		);
	}

	/**
	 * Returns the default email type.
	 *
	 * @return string|false
	 */
	public static function get_default_email_type() {
		return current( array_keys( self::$types ) );
	}

	/**
	 * Returns all email types.
	 *
	 * @return Type[]
	 */
	public static function get_email_types() {
		return self::$types;
	}
}
