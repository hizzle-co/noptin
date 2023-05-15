<?php

namespace Hizzle\Noptin\REST;

/**
 * Settings API for reading and saving settings.
 */
class Settings_API extends Controller {

    /**
     * Registers the routes for the objects of the controller.
     */
    public function register_routes() {
        parent::register_routes();

        register_rest_route(
            $this->namespace,
            '/' . $this->get_normalized_rest_base() . '/settings',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'can_manage_noptin' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->get_normalized_rest_base() . '/settings',
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'save_settings' ),
                'permission_callback' => array( $this, 'can_manage_noptin' ),
            )
        );
    }

    /**
     * Retrieves the settings.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        // Implementing the logic to retrieve the settings and prepare the response data.

        $settings = array(
            'setting1' => get_option( 'setting1', 'default_value1' ),
            'setting2' => get_option( 'setting2', 'default_value2' ),
        );

        // Wrapping data in a response object and return.
        $response = rest_ensure_response( $settings );
        return $response;
    }


    /**
     * Saves the settings.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function save_settings( $request ) {
        $data = $request->get_json_params();

        // Implementing the logic to save the settings based on the request data.

        if ( isset( $data['setting1'] ) ) {
            update_option( 'setting1', $data['setting1'] );
        }

        if ( isset( $data['setting2'] ) ) {
            update_option( 'setting2', $data['setting2'] );
        }

        // Return a success response or an error response if something went wrong.
        $response = array( 'success' => true );
        return rest_ensure_response( $response );
    }

}
