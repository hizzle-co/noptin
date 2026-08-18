<?php

/**
 * Main core class.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Loads core functionality.
 */
class Main {

	/**
	 * Webhooks manager.
	 *
	 * @var \Hizzle\Store\Webhooks
	 */
	public static $webhooks;

	/**
	 * REST controllers keyed by collection name.
	 *
	 * @var \Hizzle\Store\REST_Controller[]
	 */
	public static $controllers = array();

	/**
	 * Loads core functions.
	 */
	public static function init() {
		require_once plugin_dir_path( __FILE__ ) . 'functions.php';

		add_action( 'init', array( __CLASS__, 'load_database' ), -100 );
		add_action( 'noptin_init', array( __CLASS__, 'load_rest_routes' ) );
		add_action( 'noptin_collection_registered', array( __CLASS__, 'update_capabilities' ) );
	}

	/**
	 * Initializes Noptin's datastore and webhooks.
	 */
	public static function load_database() {
		do_action( 'noptin_db_before_init' );

		$store          = noptin()->db()->init_store( apply_filters( 'noptin_db_schema', array() ) );
		self::$webhooks = new \Hizzle\Store\Webhooks( $store );
	}

	/**
	 * Applies Noptin's capability to a registered collection.
	 *
	 * @param \Hizzle\Store\Collection $collection Collection instance.
	 */
	public static function update_capabilities( $collection ) {
		$collection->capabillity = get_noptin_capability();
	}

	/**
	 * Registers REST controllers for CRUD-backed collections.
	 */
	public static function load_rest_routes() {
		foreach ( noptin()->db()->store->get_collections() as $collection ) {
			if ( empty( $collection->object ) ) {
				continue;
			}

			self::$controllers[ $collection->get_name() ] = new \Hizzle\Store\REST_Controller( 'noptin', $collection->get_name() );
		}
	}
}
