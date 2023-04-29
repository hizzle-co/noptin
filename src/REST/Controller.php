<?php

namespace Hizzle\Noptin\REST;

/**
 * The rest controller for a single collection.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Controller.
 */
class Controller extends \WP_REST_Controller {

	/**
	 * Loads the class.
	 *
	 * @param string $rest_base The rest base.
	 */
	public function __construct( $rest_base ) {
		$this->namespace = 'noptin/v1';
		$this->rest_base = $rest_base;

		// Register rest routes.
        $this->register_routes();
	}

	/**
	 * Checks if the current user can manage noptin.
	 *
	 */
	public function can_manage_noptin() {
		if ( ! current_user_can( get_noptin_capability() ) ) {
            return new \WP_Error( 'noptin_rest_cannot_view', 'Sorry, you cannot view this resource.', array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
	}

	/**
	 * Get normalized rest base.
	 *
	 * @return string
	 */
	protected function get_normalized_rest_base() {
		return preg_replace( '/\(.*\)\//i', '', trim( $this->namespace, '/v1' ) . '_' . $this->rest_base );
	}
}
