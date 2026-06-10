<?php
/**
 * Represents the end marker for a loop.
 *
 * @since 3.4.6
 */

namespace Hizzle\Noptin\Automation_Rules\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the end marker for a loop.
 */
class Loop_End extends Action {

	/**
	 * @var string
	 */
	public $category = '';

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'loop_end';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Loop End', 'noptin-addons-pack' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Marks the end of the loop.', 'noptin-addons-pack' );
	}

	/**
	 * Retrieve the action's image.
	 *
	 * @return string
	 */
	public function get_image() {
		return 'controls-repeat';
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {
		// The runner automatically continues to child steps.
	}
}
