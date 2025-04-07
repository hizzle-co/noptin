<?php

namespace Hizzle\Noptin\Subscribers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main subscribers class.
 *
 * @since 3.0.0
 */
class Main {

	private static $hook_suffix;

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		add_action( 'noptin_init', __CLASS__ . '::register_objects' );
		add_filter( 'noptin_automation_rule_migrate_triggers', __CLASS__ . '::migrate_triggers' );
		add_filter( 'noptin_subscriber_should_fire_has_changes_hook', __CLASS__ . '::should_fire_has_changes_hook', 10, 2 );
		add_filter( 'hizzle_rest_noptin_subscribers_record_tabs', __CLASS__ . '::add_collection_subscriber_tabs' );
		add_action( 'noptin_pre_load_actions_page', __NAMESPACE__ . '\Actions::init' );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'subscribers_menu' ), 33 );

			if ( defined( 'NOPTIN_IS_TESTING' ) && NOPTIN_IS_TESTING ) {
				add_action( 'admin_menu', array( __CLASS__, 'new_subscribers_menu' ), 33 );
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			}
		}

		// Initialize the privacy class.
		Privacy::init();

		// Initialize the manage preferences class.
		Manage_Preferences::init();

		// Initialize the bounce handler.
		Bounce_Handler::init();
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public static function register_objects() {
		\Hizzle\Noptin\Objects\Store::add( new Records() );
	}

	/**
	 * Migrates triggers.
	 *
	 * @since 3.0.0
	 *
	 * @param array $triggers The triggers.
	 */
	public static function migrate_triggers( $triggers ) {

		$triggers[] = array(
			'id'         => 'new_subscriber',
			'trigger_id' => 'new_subscriber',
			'callback'   => function ( &$automation_rule ) {

				/** @var \Hizzle\Noptin\DB\Automation_Rule $automation_rule */
				if ( noptin_has_enabled_double_optin() && ! $automation_rule->get_trigger_setting( 'fire_after_confirmation' ) ) {
					$automation_rule->set_trigger_id( 'noptin_subscriber_status_set_to_pending' );
				} else {
					$automation_rule->set_trigger_id( 'noptin_subscriber_status_set_to_subscribed' );
				}

				// Update the conditional logic.
				$automation_rule->add_conditional_logic_rules(
					array(),
					array( 'fire_after_confirmation' )
				);
			},
		);

		return $triggers;
	}

	/**
	 * Should fire has changes hook.
	 *
	 * @since 3.0.0
	 *
	 * @param bool  $should_fire The should fire.
	 * @param array $changes An array of changes.
	 */
	public static function should_fire_has_changes_hook( $should_fire, $changes ) {

		if ( ! $should_fire ) {
			return $should_fire;
		}

		$ignore = array( 'activity', 'sent_campaigns', 'date_modified', 'date_created', 'confirm_key' );

		// Abort if all keys in the changes are in the ignore list.
		if ( empty( array_diff( $changes, $ignore ) ) ) {
			return false;
		}

		return $should_fire;
	}

	/**
	 * Registers collection subscriber tabs.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public static function add_collection_subscriber_tabs( $tabs ) {
		$collections = \Hizzle\Noptin\Objects\Store::filtered( array( 'show_tab' => true ) );

		foreach ( $collections as $collection ) {
			$tabs[ $collection->type ] = array_merge(
				$collection->get_custom_tab_details(),
				array( 'callback' => __CLASS__ . '::process_collection_tab' )
			);
		}

		return $tabs;
	}

	/**
	 * Processes collection subscriber tabs.
	 *
	 * @param array $request
	 * @return array
	 */
	public static function process_collection_tab( $request ) {
		$subscriber = noptin_get_subscriber( $request['id'] );

		if ( empty( $subscriber->get_email() ) ) {
			return new \WP_Error( 'subscriber_not_found', 'Subscriber not found', array( 'status' => 400 ) );
		}

		if ( empty( $request['tab_id'] ) ) {
			return new \WP_Error( 'tab_id_not_provided', 'Tab not provided', array( 'status' => 400 ) );
		}

		$collection = \Hizzle\Noptin\Objects\Store::get( $request['tab_id'] );

		if ( empty( $collection ) ) {
			return new \WP_Error( 'collection_not_found', 'Collection not found', array( 'status' => 400 ) );
		}

		return $collection->process_custom_tab( $subscriber->get_email() );
	}

	/**
	 * New subscribers menu.
	 */
	public static function new_subscribers_menu() {

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			esc_html__( 'Subscribers', 'newsletter-optin-box' ) . ' <span class="menu-counter">Beta</span>',
			get_noptin_capability(),
			'noptin-subscribers__new',
			__CLASS__ . '::render_admin_page'
		);
	}

	/**
	 * Subscribers menu.
	 */
	public static function subscribers_menu() {

		$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-subscribers',
			'\Hizzle\Noptin\Misc\Store_UI::render_admin_page'
		);

		\Hizzle\Noptin\Misc\Store_UI::collection_menu( $hook_suffix, 'noptin/subscribers' );
	}

	/**
	 * Renders the admin page.
	 */
	public static function render_admin_page() {
		?>
			<div id="hizzlewp-store-ui">
				<!-- spinner -->
				<span class="spinner" style="visibility: visible; float: none;"></span>
				<!-- /spinner -->
			</div>
		<?php
	}

	/**
	 * Enqueues scripts.
	 *
	 * @param string $hook The hook.
	 */
	public static function enqueue_scripts( $hook ) {
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		wp_enqueue_script( 'hizzlewp-store-ui' );

		// Localize the script.
		wp_localize_script(
			'hizzlewp-store-ui',
			'hizzleWPStore',
			array(
				'data' => array(
					'namespace'  => 'noptin',
					'collection' => 'subscribers',
					'brand'      => noptin()->white_label->get_details(),
				),
			)
		);

		wp_set_script_translations( 'hizzlewp-store-ui', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );
	}
}
