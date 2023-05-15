<?php

namespace Hizzle\Noptin\REST;

/**
 * Contains the main REST class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main REST class.
 */
class Main {

	/**
	 * @var Automated_Email_Campaign_Types The automated email campaign types.
	 */
	public $automated_email_campaign_types;

	/**
	 * @var Settings The settings controller.
	 */
	public $settings;

	/**
	 * Stores the main db instance.
	 *
	 * @access private
	 * @var    Main $instance The main db instance.
	 * @since  1.0.0
	 */
	private static $instance = null;

	/**
	 * Get active instance
	 *
	 * @access public
	 * @since  1.0.0
	 * @return Main The main db instance.
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads the class.
	 *
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'init' ) );
	}

	/**
	 * Loads the DB class.
	 *
	 * @return void
	 */
	public function init() {

		$this->automated_email_campaign_types = new Automated_Email_Campaign_Types( 'automated-email-campaign-types' );
		$this->settings                       = new Settings( 'settings' );

		// Fire action hook.
		do_action( 'noptin_rest_api_init', $this );
	}

}
