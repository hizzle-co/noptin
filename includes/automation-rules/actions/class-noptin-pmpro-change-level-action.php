<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Updates a user's membership level.
 *
 * @since 1.10.0
 */
class Noptin_PMPro_Change_Level_Action extends Noptin_Abstract_Action {

	/**
	 * @var string
	 */
	public $category = 'Paid Memberships Pro';

	/**
	 * @var string
	 */
	public $integration = 'paid-memberships-pro';

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'pmpro_change_membership_level';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Update Membership Level (PMPro)', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return $this->get_name();
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		return array(
			'level' => array(
				'el'    => 'input',
				'label' => __( 'Membership Level', 'newsletter-optin-box' ),
			),
		);
	}

	/**
	 * Returns whether or not the action can run (dependancies are installed).
	 *
	 * @since 1.10.0
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return bool
	 */
	public function can_run( $subject, $rule, $args ) {

		// Check if we have a membership level.
		$settings = wp_unslash( $rule->action_settings );

		if ( empty( $settings['level'] ) ) {
			return false;
		}

		// Check if we have a user.
		if ( $subject instanceof WP_User ) {
			return true;
		}

		// Check if we have an email address.
		$subject_email = $this->get_subject_email( $subject, $rule, $args );
		return is_email( $subject_email ) && email_exists( $subject_email );
	}

	/**
	 * Update a user's membership level.
	 *
	 * @since 1.10.0
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		// Fetch user.
		if ( $subject instanceof WP_User ) {
			$user = $subject;
		} else {
			$user = get_user_by( 'email', $this->get_subject_email( $subject, $rule, $args ) );
		}

		// Fetch level.
		$settings = wp_unslash( $rule->action_settings );
		$level    = $settings['level'];

		pmpro_changeMembershipLevel( $level, $user->ID );
	}

}
