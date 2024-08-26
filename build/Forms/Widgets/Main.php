<?php

/**
 * Main widgets class.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Forms\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Main widgets class.
 */
class Main {

	/**
	* Inits the main widgets class.
	*
	*/
	public static function init() {

		// Register widgets.
		add_action( 'widgets_init', array( __CLASS__, 'register_widgets' ) );
	}

	/**
	* Register widgets.
	*/
	public static function register_widgets() {

		// Register form widget.
		register_widget( Form::class );

		// Register new form widget.
		register_widget( New_Form::class );
	}
}
