<?php

namespace Hizzle\Noptin\Tests\Emails;

use Hizzle\Noptin\Emails\Admin\Main as Admin_Main;
use Hizzle\Noptin\Emails\Main;
use WP_UnitTestCase;

class Test_Staging_Emails extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		update_option( 'noptin_options', array() );
	}

	public function tear_down() {
		remove_filter( 'noptin_email_environment_type', array( $this, 'filter_environment_to_staging' ) );
		remove_filter( 'noptin_email_environment_type', array( $this, 'filter_environment_to_production' ) );
		update_option( 'noptin_options', array() );
		parent::tear_down();
	}

	public function test_setting_is_enabled_by_default() {
		$settings = Admin_Main::email_settings( array() );
		$setting  = $settings['general_email_info']['settings']['disable_staging_emails'];

		$this->assertTrue( $setting['default'] );
		$this->assertTrue( get_noptin_option( 'disable_staging_emails', true ) );
	}

	public function test_staging_environment_uses_no_op_transport() {
		add_filter( 'noptin_email_environment_type', array( $this, 'filter_environment_to_staging' ) );

		$this->assertSame(
			array( Main::class, 'skip_staging_email' ),
			Main::maybe_disable_staging_email( 'wp_mail' )
		);
		$this->assertTrue( Main::skip_staging_email() );
		$this->assertTrue( Main::is_staging_email_disabled() );
	}

	public function test_setting_can_allow_staging_emails() {
		add_filter( 'noptin_email_environment_type', array( $this, 'filter_environment_to_staging' ) );
		update_noptin_option( 'disable_staging_emails', false );

		$this->assertSame( 'wp_mail', Main::maybe_disable_staging_email( 'wp_mail' ) );
	}

	public function test_non_staging_environment_keeps_transport() {
		add_filter( 'noptin_email_environment_type', array( $this, 'filter_environment_to_production' ) );
		$this->assertSame( 'wp_mail', Main::maybe_disable_staging_email( 'wp_mail' ) );
	}

	public function filter_environment_to_staging() {
		return 'staging';
	}

	public function filter_environment_to_production() {
		return 'production';
	}
}
