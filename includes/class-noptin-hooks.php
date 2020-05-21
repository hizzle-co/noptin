<?php

/**
 * This class defines various actions and hooks registered by Noptin.
 *
 * @since 1.2.9
 */
class Noptin_Hooks {

	/**
	 * Task constructor.
	 *
	 * @since 1.2.9
	 */
	public function __construct() {

		// Register our action page's endpoint.
		add_action( 'init', array( $this, 'add_rewrite_rule' ), 10, 0 );

	}

	/**
	 * Add our noptin page rewrite tag and rule.
	 *
	 * @since 1.2.9
	 */
	public function add_rewrite_rule() {

		add_rewrite_endpoint( 'noptin_newsletter', EP_ALL );

		if ( ! get_option( 'noptin_flushed_rules2' ) ) {
			flush_rewrite_rules();
			add_option( 'noptin_flushed_rules2', 1 );
		}

	}
}
