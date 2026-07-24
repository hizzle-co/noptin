<?php
/**
 * Allows rules to branch into different paths based on conditions.
 *
 * @since 3.0.0
 */

namespace Hizzle\Noptin\Automation_Rules\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Allows rules to branch into different paths based on conditions.
 */
class Path extends Action {

	/**
	 * The task lookup key.
	 *
	 * @var string
	 */
	const LOOKUP_KEY = 'path';

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return self::LOOKUP_KEY;
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Paths', 'noptin-addons-pack' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Branch into different paths based on conditions.', 'noptin-addons-pack' );
	}

	/**
	 * Retrieve the trigger's or action's image.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function get_image() {
		return 'randomize';
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {
		return array(

			'path_execution_mode' => array(
				'label'       => __( 'Path Matching', 'noptin-addons-pack' ),
				'el'          => 'radio', // or 'select'
				'default'     => 'all',
				'options'     => array(
					'all'   => __( 'Run every matching path', 'noptin-addons-pack' ),
					'first' => __( 'Run the first matching path only', 'noptin-addons-pack' ),
				),
				'description' => __( 'Choose whether to execute every path whose conditional logic is met, or stop after the first successful match.', 'noptin-addons-pack' ),
			),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function has_wizard_settings() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {

		$path_execution_mode = $rule->get_action_setting( 'path_execution_mode' );
		$paths               = $rule->get_children();

		$action = Main::get( 'path_branch' );

		foreach ( $paths as $path ) {
			if ( 'path_branch' !== $path->get_action_id() || ! $path->get_status() ) {
				continue;
			}

			$trigger = $path->get_trigger();

			if ( $trigger && $trigger->is_rule_valid_for_args( $path, $args, $subject, $action ) ) {
				$path->maybe_run( $subject, $trigger, $action, $args );

				if ( 'first' === $path_execution_mode ) {
					break;
				}
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function should_auto_run_child_rules() {
		// We don't want to auto-run child rules for the path action because the path branches are the only child rules of the path action,
		// and they will be run based on the selected path_execution_mode.
		return false;
	}
}
