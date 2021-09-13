<?php
/**
 * Forms API: Forms Controller.
 *
 * Contains main class for manipulating Noptin forms
 *
 * @since             1.6.1
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Noptin_Forms' ) ) :

	/**
	 * Forms controller class.
	 *
	 * @since 1.6.1
	 * @internal
	 * @ignore
	 */
	class Noptin_Forms extends Noptin_Forms_Legacy {

		/**
		 * Class Constructor.
		 */
		public function __construct() {

			do_action( 'noptin_forms_load', $this );

		}

		/**
		 * Loads the GetPaid and Noptin integration
		 *
		 * @access      public
		 * @since       1.4.1
		 */
		public function load_getpaid_integration() {
			$this->integrations['getpaid'] = new Noptin_GetPaid();
		}

	}

endif;
