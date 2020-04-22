<?php

/**
 * Automation rule editor
 *
 * Responsible for editing the automation rules
 *
 * @since             1.2.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Noptin_Automation_Rule_Editor {

    /**
	 * Class Constructor.
	 */
	public function __construct() {

    }

    /**
	 * Displays the editor
	 */
	public function display() {
        $step = ( int ) $_GET['create'];
		get_noptin_template( 'automation-rules/step_1.php', compact( 'sidebar', 'state' ) );
	}

}
