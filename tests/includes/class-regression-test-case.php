<?php
/** Shared state isolation for PHP regression tests. */
abstract class Noptin_Regression_Test_Case extends WP_UnitTestCase {

	private $regression_globals;
	private $regression_hooks;
	private $regression_user;
	private $regression_query_vars;
	private $regression_recipient;
	private $regression_email;
	private $regression_tags;

	public function set_up() {
		parent::set_up();
		$this->regression_globals = array();
		foreach ( array( '_GET', '_POST', '_REQUEST', '_COOKIE', '_SERVER', 'phpmailer', 'shortcode_tags', 'wp_actions', 'noptin_include_executed', 'noptin_email_sender_last_error' ) as $key ) {
			$this->regression_globals[ $key ] = array( array_key_exists( $key, $GLOBALS ), $GLOBALS[ $key ] ?? null );
		}
		$this->regression_hooks = array();
		foreach ( $GLOBALS['wp_filter'] as $name => $hook ) {
			$this->regression_hooks[ $name ] = clone $hook;
		}
		$this->regression_user = get_current_user_id();
		$this->regression_query_vars = $GLOBALS['wp']->query_vars;
		$this->regression_recipient = Hizzle\Noptin\Emails\Main::$current_email_recipient;
		$this->regression_email = Hizzle\Noptin\Emails\Main::$current_email;
		$this->regression_tags = noptin()->emails->tags->tags;
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		add_filter( 'pre_http_request', array( $this, 'block_regression_http' ), PHP_INT_MAX );
	}

	public function block_regression_http() {
		return new WP_Error( 'test_no_network', 'Network disabled during regression tests.' );
	}

	public function tear_down() {
		try {
			$GLOBALS['wp_filter'] = $this->regression_hooks;
			foreach ( $this->regression_globals as $key => list( $exists, $value ) ) {
				if ( $exists ) {
					$GLOBALS[ $key ] = $value;
				} else {
					unset( $GLOBALS[ $key ] );
				}
			}
			$GLOBALS['wp']->query_vars = $this->regression_query_vars;
			Hizzle\Noptin\Emails\Main::$current_email_recipient = $this->regression_recipient;
			Hizzle\Noptin\Emails\Main::$current_email = $this->regression_email;
			noptin()->emails->tags->tags = $this->regression_tags;
			wp_set_current_user( $this->regression_user );
		} finally {
			parent::tear_down();
		}
	}
}
