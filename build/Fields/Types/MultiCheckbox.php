<?php
/**
 * Handles multiple checkboxes.
 *
 * @since 2.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles multiple checkboxes.
 *
 * @since 2.0.0
 */
class MultiCheckbox extends Dropdown {
	protected $is_multiple = true;
}
