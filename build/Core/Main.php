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
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
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
		$collection->capabillity = get_noptin_collection_capability( $collection->get_name() );
	}

	/**
	 * Preserves legacy Noptin access while allowing granular role capabilities.
	 *
	 * @param string[] $caps    Primitive capabilities.
	 * @param string   $cap     Requested capability.
	 * @param int      $user_id User ID.
	 * @param mixed[]  $args    Capability arguments.
	 * @return string[]
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return $caps;
		}

		// Map campaign object operations to the capability for the saved type.
		if ( function_exists( 'get_noptin_campaign_type_capability' ) ) {
			if ( in_array( $cap, array( 'edit_post', 'delete_post', 'read_post', 'edit_noptin_campaign', 'delete_noptin_campaign', 'read_noptin_campaign' ), true ) && ! empty( $args[0] ) ) {
				$post = get_post( $args[0] );
				if ( $post && 'noptin-campaign' === $post->post_type ) {
					$cap  = get_noptin_campaign_type_capability( get_post_meta( $post->ID, 'campaign_type', true ) );
					$caps = array( $cap );
				}
			}

			// The campaign post type's plural capabilities mean access to any type.
			if ( in_array( $cap, array( 'edit_noptin_campaigns', 'publish_noptin_campaigns', 'delete_noptin_campaigns', 'delete_others_noptin_campaigns', 'edit_others_noptin_campaigns', 'read_private_noptin_campaigns' ), true ) ) {
				$available = get_noptin_accessible_campaign_type_capability( $user_id );
				return array( $available ? $available : $cap );
			}
		}

		if ( 0 !== strpos( $cap, 'manage_noptin_' ) ) {
			return $caps;
		}

		// Administrators, legacy Noptin managers, and opted-in editors retain full access.
		if ( ! empty( $user->allcaps['manage_options'] ) ) {
			return array( 'manage_options' );
		}

		if ( ! empty( $user->allcaps['manage_noptin'] ) ) {
			return array( 'manage_noptin' );
		}

		if ( 0 === strpos( $cap, 'manage_noptin_campaign_type_' ) && ! empty( $user->allcaps['manage_noptin_campaigns'] ) ) {
			return array( 'manage_noptin_campaigns' );
		}

		if ( get_noptin_option( 'allow_editors', false ) && ! empty( $user->allcaps['edit_others_posts'] ) ) {
			return array( 'edit_others_posts' );
		}

		return $caps;
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
