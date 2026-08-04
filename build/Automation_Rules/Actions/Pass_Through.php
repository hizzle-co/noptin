<?php

namespace Hizzle\Noptin\Automation_Rules\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Internal action used to continue workflows whose configured action is unavailable.
 */
class Pass_Through extends Action {

	/**
	 * The unavailable action ID.
	 *
	 * @var string
	 */
	private $missing_action_id;

	/**
	 * Constructor.
	 *
	 * @param string $missing_action_id The unavailable action ID.
	 */
	public function __construct( $missing_action_id ) {
		$this->missing_action_id = (string) $missing_action_id;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'pass_through';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return 'Continue workflow';
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return 'Continues to child rules when the configured action is unavailable.';
	}

	/**
	 * Logs the missing action and succeeds so child rules can continue.
	 *
	 * @param mixed                                                   $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule    The rule.
	 * @param array                                                   $args    Extra arguments.
	 * @return true
	 */
	public function run( $subject, $rule, $args ) {
		log_noptin_message(
			sprintf(
				'Automation rule ID %1$d action "%2$s" is unavailable; continuing to child rules.',
				$rule->get_id(),
				$this->missing_action_id
			),
			'warning'
		);

		return true;
	}
}
