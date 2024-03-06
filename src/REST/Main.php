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
	 * @var Bounce_Handler The bounce handler controller.
	 */
	public $bounce_handler;

	/**
	 * @var Controller[] Rest controllers.
	 */
	public $routes = array();

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

		$this->bounce_handler = new Bounce_Handler( 'bounce_handler' );

		// Fire action hook.
		do_action( 'noptin_rest_api_init', $this );
	}

}
