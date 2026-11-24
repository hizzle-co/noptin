<?php
/**
 * Regression coverage for option reads before translations are available.
 *
 * @package Noptin
 */

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Verify option defaults do not load translations during plugin startup. */
class Test_Option_Translations extends Noptin_Regression_Test_Case {

	/**
	 * Empty and malformed stored settings must use the same safe fallback.
	 *
	 * @return array Stored option cases.
	 */
	public static function empty_option_cases() {
		return array(
			'missing'      => array( null ),
			'empty array'  => array( array() ),
			'empty object' => array( new stdClass() ),
			'invalid'      => array( 'invalid' ),
		);
	}

	/**
	 * Early unrelated reads must not translate; later reads must still translate.
	 *
	 * @dataProvider empty_option_cases
	 * @param mixed $stored The persisted settings value, or null for no option.
	 */
	public function test_fallback_translation_waits_for_init( $stored ) {
		if ( null === $stored ) {
			delete_option( 'noptin_options' );
		} else {
			update_option( 'noptin_options', $stored );
		}

		$translations = 0;
		$translate = static function ( $translation, $text ) use ( &$translations ) {
			++$translations;
			return 'Translated: ' . $text;
		};
		add_filter( 'gettext_newsletter-optin-box', $translate, 10, 2 );
		unset( $GLOBALS['wp_actions']['init'] );

		$this->assertNull( get_noptin_option( 'white_label' ) );
		$this->assertSame( 'fallback', get_noptin_option( 'unconfigured', 'fallback' ) );
		$this->assertSame( array( 'success_message' => 'Thanks for subscribing to our newsletter' ), get_noptin_options() );
		$this->assertSame( 0, $translations, 'Startup option reads must not invoke translation.' );

		// Mark init as entered without rerunning unrelated initialization hooks.
		$GLOBALS['wp_actions']['init'] = 1;
		$this->assertSame( 'Translated: Thanks for subscribing to our newsletter', get_noptin_option( 'success_message' ) );
		$this->assertSame( 1, $translations, 'The fallback must be translated once init is available.' );
	}

	/** Saved array and legacy object settings must retain their values before init. */
	public function test_saved_options_remain_available_before_init() {
		unset( $GLOBALS['wp_actions']['init'] );
		$options = array( 'white_label' => array( 'name' => 'Custom brand' ), 'success_message' => 'Saved message' );
		foreach ( array( $options, (object) $options ) as $stored ) {
			update_option( 'noptin_options', $stored );
			$this->assertSame( $options, get_noptin_options() );
			$this->assertSame( $options['white_label'], get_noptin_option( 'white_label' ) );
			$this->assertSame( 'Saved message', get_noptin_option( 'success_message' ) );
		}
	}
}
