<?php
/**
 * Represents a path branch in the automation rules engine.
 *
 * @since 3.0.0
 */

namespace Hizzle\Noptin\Automation_Rules\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a path branch in the automation rules engine.
 */
class Path_Branch extends Action {

	/**
	 * @var string
	 */
	public $category = '';

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'path_branch';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Branch', 'noptin-addons-pack' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Branch conditions', 'noptin-addons-pack' );
	}

	/**
	 * Retrieve the trigger's or action's image.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function get_image() {
		return 'image-filter';
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {
		\Hizzle\Noptin\Automation_Rules\Main::run_child_rules( $rule, $args, $rule->get_trigger() );
	}
}
