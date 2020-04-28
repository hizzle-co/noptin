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

		// Register our action page's query vars.
		add_filter( 'query_vars', array( $this, 'custom_query_vars' ) );
		add_action( 'init', array( $this, 'add_rewrite_rule' ), 10, 0 );

	}

	/**
	 * Registers our action's page query var.
	 *
	 * @since 1.2.9
	 * @param array $vars The array of available query variables
	 * @return array
	 */
	public function custom_query_vars( $vars ) {
		$vars[] = 'noptin_newsletter';
        return $vars;
	}

	/**
	 * Add our noptin page rewrite tag and rule.
	 *
	 * @since 1.2.9
	 */
	public function add_rewrite_rule() {

        $tag = 'noptin_newsletter';
        add_rewrite_tag( "%$tag%", '([^&]+)' );
		add_rewrite_rule( "^$tag/([^/]*)/?", "index.php?$tag=\$matches[1]",'top' );

		if ( ! get_option( 'noptin_flushed_rules' ) ) {
			flush_rewrite_rules();
			add_option( 'noptin_flushed_rules', 1 );
		}

	}
}
