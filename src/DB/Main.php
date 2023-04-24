<?php

namespace Hizzle\Noptin\DB;

/**
 * Contains the main DB class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main DB class.
 */
class Main {

	/**
	 * @var Schema The database schema.
	 */
	public $schema;

	/**
	 * The installer.
	 *
	 * @var Installer
	 */
	public $installer;

	/**
	 * The data store.
	 *
	 * @var \Hizzle\Store\Store
	 */
	public $store;

	/**
	 * Webhooks manager.
	 *
	 * @var \Hizzle\Store\Webhooks
	 */
	public $webhooks;

	/**
	 * Route controller classes.
	 *
	 * @param \Hizzle\Store\REST_Controller[]
	 */
	public $controllers;

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
		add_action( 'plugins_loaded', array( $this, 'load' ) );
	}

	/**
	 * Loads the DB class.
	 *
	 * @return void
	 */
	public function load() {

		// Schema.
		$this->schema = new Schema();

		// The installer.
		$this->installer = new Installer();

		// Init the data store.
		$this->store = \Hizzle\Store\Store::init( 'noptin', $this->schema->get_schema() );

		// Init the webhooks manager.
		$this->webhooks = new \Hizzle\Store\Webhooks( $this->store );

		// Init the REST API manager.
		foreach ( $this->store->get_collections() as $collection ) {

			// Ignore events that are not associated with any CRUD class.
			if ( empty( $collection->object ) ) {
				continue;
			}

			// Init the controller class.
			$this->controllers[ $collection->get_name() ] = new \Hizzle\Store\REST_Controller( $this->store->get_namespace(), $collection->get_name() );
		}

		// Fire action hook.
		do_action( 'hizzle_noptin_db_init', $this );
	}
}
