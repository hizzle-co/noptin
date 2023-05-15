<?php

namespace Hizzle\Noptin\REST;

// Import any required dependencies or include necessary files

/**
 * Settings API endpoint.
 */
class Settings_API extends Controller {

    /**
     * Registers REST routes.
     */
    public function register_routes() {
        // Register the /settings endpoint
        register_rest_route(
            $this->namespace,
            '/settings',
            array(
                'methods'             => \WP_REST_Server::READABLE | \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );
        // Register other endpoints for saving, updating, or deleting settings if needed
        // register_rest_route(...);
        // register_rest_route(...);
    }

    /**
     * Retrieves the settings.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_settings( $request ) {
        // Add your code to fetch the settings from your storage (e.g., database)

        // Example code:
        $settings = array(
            'setting_1' => 'value_1',
            'setting_2' => 'value_2',
        );

        // You can perform any necessary transformations or filtering on the settings here

        // Return the settings as a response
        $response = rest_ensure_response( $settings );

        // Set headers or modify the response as needed
        // $response->header( 'Header-Name', 'Header-Value' );

        return $response;
    }

    /**
     * Checks the permission for accessing the settings.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return bool|\WP_Error True if the user has permission, WP_Error object otherwise.
     */
    public function check_permission( $request ) {
        // Implement your permission check logic here

        // Example code:
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        } else {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to access the settings.', 'noptin' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }
    }

    // Implement methods for saving, updating, or deleting settings if needed
    // public function save_settings( $request ) { ... }
    // public function update_settings( $request ) { ... }
    // public function delete_settings( $request ) { ... }
}

// Instantiate the Settings_API class and register the routes
$settings_api = new Settings_API();
$settings_api->register_routes();
