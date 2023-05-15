<?php

namespace Hizzle\Noptin\REST;

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

        // Register the /settings endpoint for saving, updating, and deleting settings
        register_rest_route(
            $this->namespace,
            '/settings',
            array(
                'methods'             => \WP_REST_Server::CREATABLE | \WP_REST_Server::EDITABLE | \WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'save_settings' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );
    }

    /**
     * Saves the settings.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function save_settings( $request ) {
        // Implement code to save the settings
        $params = $request->get_params();

        // Validate and sanitize the input as needed
        $validated_data = $this->validate_and_sanitize_settings( $params );

        // Save the validated and sanitized settings to your storage (e.g., database)
        $saved = $this->save_settings_to_database( $validated_data );

        if ( $saved ) {
            // Return a success response
            $response = array(
                'success' => true,
                'message' => 'Settings saved successfully.',
            );
        } else {
            // Return an error response if the settings failed to save
            return new \WP_Error(
                'rest_settings_save_error',
                __( 'Failed to save settings.', 'newsletter-optin-box' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Saves the validated and sanitized settings to the database.
     *
     * @param array $settings The validated and sanitized settings data.
     * @return bool True on success, false on failure.
     */
    private function save_settings_to_database( $settings ) {
        // Implement your code to save the settings to the database
        // You can use WordPress functions like update_option() or custom database queries

        // Example code using update_option() to save the settings
        $saved = update_option( 'my_plugin_settings', $settings );

        // Return true if the data was successfully saved, or false otherwise
        return (bool) $saved;
    }

   /**
     * Updates the settings.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_settings( $request ) {
        // Implement your code to update the settings here

        // Example code:
        $params = $request->get_params();

        // Validate and sanitize the input as needed
        $validated_data = $this->validate_and_sanitize_settings( $params );

        // Update the validated and sanitized settings in your storage (e.g., database)
        $updated = $this->update_settings_in_database( $validated_data );

        if ( $updated ) {
            // Return a success response
            $response = array(
                'success' => true,
                'message' => 'Settings updated successfully.',
            );
        } else {
            // Return an error response if the settings failed to update
            return new \WP_Error(
                'rest_settings_update_error',
                __( 'Failed to update settings.', 'newsletter-optin-box' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Updates the validated and sanitized settings in the database.
     *
     * @param array $settings The validated and sanitized settings data.
     * @return bool True on success, false on failure.
     */
    private function update_settings_in_database( $settings ) {
        // Implement your code to update the settings in the database
        // You can use WordPress functions like update_option() or custom database queries

        // Example code using update_option() to update the settings
        $updated = update_option( 'my_plugin_settings', $settings );

        // Return true if the data was successfully updated, or false otherwise
        return (bool) $updated;
    }

   /**
     * Deletes the settings.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_settings( $request ) {
        // Implement your code to delete the settings here

        // Example code:
        // Delete the settings from your storage (e.g., database)
        $deleted = $this->delete_settings_from_database();

        if ( $deleted ) {
            // Return a success response
            $response = array(
                'success' => true,
                'message' => 'Settings deleted successfully.',
            );
        } else {
            // Return an error response if the settings failed to delete
            return new \WP_Error(
                'rest_settings_delete_error',
                __( 'Failed to delete settings.', 'newsletter-optin-box' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Deletes the settings from the database.
     *
     * @return bool True on success, false on failure.
     */
    private function delete_settings_from_database() {
        // Implement your code to delete the settings from the database
        // You can use WordPress functions like delete_option() or custom database queries

        // Example code using delete_option() to delete the settings
        $deleted = delete_option( 'my_plugin_settings' );

        // Return true if the data was successfully deleted, or false otherwise
        return (bool) $deleted;
    }

    /**
     * Validates and sanitizes the settings data.
     *
     * @param array $settings The settings data to validate and sanitize.
     * @return array The validated and sanitized settings data.
     */
    private function validate_and_sanitize_settings( $settings ) {
        //validation and sanitization logic
       
        $validated_settings = array();

        // Validate and sanitize each setting
        if ( isset( $settings['setting_1'] ) ) {
            $setting_1 = sanitize_text_field( $settings['setting_1'] );
            // Additional validation rules for setting_1
            // ...

            // Add the validated and sanitized setting to the $validated_settings array
            $validated_settings['setting_1'] = $setting_1;
        }

        if ( isset( $settings['setting_2'] ) ) {
            $setting_2 = sanitize_text_field( $settings['setting_2'] );
            // Additional validation rules for setting_2
            // ...

            // Add the validated and sanitized setting to the $validated_settings array
            $validated_settings['setting_2'] = $setting_2;
        }

        // Validate and sanitize other settings if needed

        return $validated_settings;
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
                __( 'You do not have permission to access the settings.', 'newsletter-optin-box' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }
    }

}
// Instantiate the Settings_API class and register the routes
$settings_api = new Settings_API();
$settings_api->register_routes();

