<?php

namespace Hizzle\Noptin\Integrations\PMPro;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with PMPro
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * Class constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		add_action( 'noptin_load', __CLASS__ . '::register_custom_objects' );
		add_filter( 'noptin_user_collection_triggers', __CLASS__ . '::load_triggers', 5 );
		add_filter( 'noptin_user_test_args', __CLASS__ . '::add_trigger_test_args', 10, 3 );
		add_filter( 'noptin_user_collection_actions', __CLASS__ . '::load_actions', 5 );
		add_action( 'pmpro_after_change_membership_level', __CLASS__ . '::after_change_membership_level', 100, 3 );
		add_action( 'pmpro_checkout_before_change_membership_level', __CLASS__ . '::remove_trigger' );
		add_action( 'pmpro_after_checkout', __CLASS__ . '::after_checkout', 15 );
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public static function register_custom_objects() {
		\Hizzle\Noptin\Objects\Store::add( new Membership_Levels() );
	}

	/**
	 * Loads automation rule triggers.
	 *
	 * @param array $triggers
	 */
	public static function load_triggers( $triggers ) {

		$triggers['pmpro_membership_level_change'] = array(
			'id'          => 'pmpro_membership_level_change',
			'label'       => __( 'PMPro > Change Membership Level', 'newsletter-optin-box' ),
			'category'    => __( 'Paid Memberships Pro', 'newsletter-optin-box' ),
			'description' => __( "When a user's membership level changes", 'newsletter-optin-box' ),
			'subject'     => 'user',
			'provides'    => array( 'pmpro_membership_level' ),
			'extra_args'  => array(
				'cancel_level' => array(
					'description' => __( 'ID of the level being cancelled if specified', 'newsletter-optin-box' ),
					'type'        => 'number',
				),
			),
		);

		return $triggers;
	}

	/**
	 * Add automation rule test trigger args.
	 *
	 * @param array $args
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @param string $trigger_id
	 */
	public static function add_trigger_test_args( $args, $rule, $trigger_id ) {

		if ( 0 !== strpos( $trigger_id, 'pmpro_' ) || ! get_current_user_id() ) {
			return $args;
		}

		// Fetch current user level.
		$user   = wp_get_current_user();
		$levels = pmpro_getMembershipLevelsForUser( $user->ID );

		if ( empty( $levels ) ) {
			throw new \Exception( 'Current user has no active membership level' );
		}

		return array(
			'email'      => $user->user_email,
			'object_id'  => $user->ID,
			'subject_id' => $user->ID,
			'provides'   => array(
				'pmpro_membership_level' => current( wp_list_pluck( $levels, 'id' ) ),
			),
		);
	}

	/**
	 *
	 * @param int $level_id of the level the user is changing to.
	 * @param int $user_id of the user changing levels.
	 * @param int $cancel_level_id of the level the user is changing from.
	 */
	public static function after_change_membership_level( $level_id, $user_id, $cancel_level_id = 0 ) {

		$collection = \Hizzle\Noptin\Objects\Store::get( 'user' );
		$user       = get_userdata( $user_id );

		// Abort if the action is not known.
		if ( ! $collection || ! $user ) {
			return;
		}

		if ( empty( $level_id ) && ! empty( $cancel_level_id ) ) {
			$collection->trigger(
				'pmpro_membership_level_canceled',
				array(
					'email'      => $user->user_email,
					'object_id'  => $user->ID,
					'subject_id' => $user->ID,
					'provides'   => array(
						'pmpro_membership_level' => $cancel_level_id,
					),
				)
			);
		} elseif ( ! empty( $level_id ) ) {
			$collection->trigger(
				'pmpro_membership_level_change',
				array(
					'email'      => $user->user_email,
					'object_id'  => $user->ID,
					'subject_id' => $user->ID,
					'provides'   => array(
						'pmpro_membership_level' => $level_id,
					),
					'extra_args' => array(
						'user.cancel_level' => $cancel_level_id,
					),
				)
			);
		}
	}

	/**
	 * Delay the call to $this->init_trigger() during checkout until
	 * after usermeta is saved. Function call re-added in $this->after_checkout().
	 */
	public static function remove_trigger() {
		remove_action( 'pmpro_after_change_membership_level', __CLASS__ . '::after_change_membership_level', 100 );
	}

	/**
	 * Fires on checkout after usermeta is saved.
	 *
	 * @param int $user_id of user who checked out.
	 */
	public static function after_checkout( $user_id ) {
		self::after_change_membership_level( $_REQUEST['level'], $user_id );
	}

	/**
	 * Loads automation rule actions.
	 *
	 * @param array $actions
	 */
	public static function load_actions( $actions ) {

		return array_merge(
			$actions,
			array(
				'pmpro_change_membership_level' => array(
					'id'             => 'pmpro_change_membership_level',
					'label'          => __( 'PMPro > Add to Membership Level', 'noptin-addons-pack' ),
					'category'       => __( 'Paid Memberships Pro', 'newsletter-optin-box' ),
					'description'    => __( "Updates the user's membership level", 'noptin-addons-pack' ),
					'callback'       => __CLASS__ . '::change_membership_level',
					'action_fields'  => array( 'email' ),
					'extra_settings' => array(
						'level' => array(
							'label'       => __( 'New Level', 'noptin-addons-pack' ),
							'description' => __( 'Enter the level id or name', 'noptin-addons-pack' ),
							'type'        => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Changes a user's membership level.
	 *
	 * @param array $args
	 */
	public static function change_membership_level( $args ) {

		if ( empty( $args['level'] ) ) {
			return new \WP_Error( 'noptin_invalid_level', 'Invalid membership level.' );
		}

		if ( empty( $args['email'] ) ) {
			return new \WP_Error( 'noptin_invalid_email', __( 'Invalid email address or user ID.', 'noptin-addons-pack' ) );
		}

		if ( is_email( $args['email'] ) ) {
			$user = get_user_by( 'email', $args['email'] );
		} elseif ( is_numeric( $args['email'] ) ) {
			$user = get_userdata( $args['email'] );
		}

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( empty( $user ) ) {
			return new \WP_Error( 'noptin_invalid_email', __( 'Invalid email address or user ID.', 'noptin-addons-pack' ) );
		}

		$level = pmpro_getLevel( $args['level'] );

		if ( empty( $level ) ) {
			return new \WP_Error( 'noptin_invalid_level', 'Invalid membership level.' );
		}

		$custom_level = array(
			'user_id'         => $user->ID,
			'membership_id'   => $level->id,
			'code_id'         => 0,
			'initial_payment' => $level->initial_payment,
			'billing_amount'  => $level->billing_amount,
			'cycle_number'    => $level->cycle_number,
			'cycle_period'    => $level->cycle_period,
			'billing_limit'   => $level->billing_limit,
			'trial_amount'    => $level->trial_amount,
			'trial_limit'     => $level->trial_limit,
			'startdate'       => current_time( 'mysql' ),
			'enddate'         => '0000-00-00 00:00:00',
		);

		if ( ! pmpro_changeMembershipLevel( $custom_level, $user->ID, 'changed' ) && ! empty( $GLOBALS['pmpro_error'] ) ) {
			return new \WP_Error( 'noptin_pmpro_error', $GLOBALS['pmpro_error'] );
		}
	}
}
