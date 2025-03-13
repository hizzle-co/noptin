<?php
namespace Hizzle\Noptin\Integrations\Pods;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles integrations with Pods.
 *
 * @since 1.0.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

    /**
     * @var string
     */
    public $slug = 'pods';

    /**
     * @var string
     */
    public $name = 'Pods';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        // Process form submission.
        add_action( 'pods_api_post_save_pod_item', array( $this, 'process_form_submission' ), 10, 3 );

        // Register a custom hook for Pods forms.
        add_action( 'pods_form_pre_submit', array( $this, 'register_action' ), 10, 3 );
    }

    /**
     * Retrieves all forms.
     * 
     * @return array
     */
    protected function get_forms() {
        // Get all Pods forms.
        $all_pods = pods_api()->load_pods();
        $all_pods = is_array( $all_pods ) ? $all_pods : array();
        $prepared = array();

        foreach ( $all_pods as $pod ) {
            $fields = array();

            // Get fields for this pod.
            $pod_fields = pods_api()->load_pod_fields( array( 'pod' => $pod['id'] ) );

            foreach ( $pod_fields as $pod_field ) {
                // Skip certain field types that wouldn't make sense for a subscriber.
                if ( in_array( $pod_field['type'], array( 'file', 'avatar', 'heading', 'html', 'separator' ), true ) ) {
                    continue;
                }

                $logic = 'string';

                // Identify numerical field types.
                if ( in_array( $pod_field['type'], array( 'number', 'currency', 'slider' ), true ) ) {
                    $logic = 'number';
                }

                $fields[ $pod_field['name'] ] = array(
                    'description'       => $pod_field['label'],
                    'conditional_logic' => $logic,
                );

                // Add options for select, radio, and checkbox fields.
                if ( in_array( $pod_field['type'], array( 'pick', 'boolean' ), true ) ) {
                    if ( isset( $pod_field['options'] ) && is_array( $pod_field['options'] ) ) {
                        $fields[ $pod_field['name'] ]['options'] = $pod_field['options'];
                    } elseif ( 'boolean' === $pod_field['type'] ) {
                        $fields[ $pod_field['name'] ]['options'] = array(
                            '1' => 'Yes',
                            '0' => 'No',
                        );
                    }
                }
            }

            $prepared[ $pod['id'] ] = array(
                'name'   => $pod['name'],
                'fields' => $fields,
            );
        }

        return $prepared;
    }

    /**
     * Process form submission.
     * 
     * @param int $id The item ID.
     * @param array $data The submitted data.
     * @param array $pod The pod configuration.
     */
    public function process_form_submission( $id, $data, $pod ) {
        // Get the form ID (pod ID).
        $form_id = $pod['id'];
        
        // Prepare the posted data.
        $posted = array();
        
        foreach ( $data as $key => $value ) {
            if ( ! is_array( $value ) ) {
                $posted[ $key ] = $value;
            } else {
                // Handle array values (like checkboxes).
                $posted[ $key ] = implode( ', ', $value );
            }
        }

        // Trigger the action.
        $this->process_form_submission( $form_id, $posted );
    }

    /**
     * Registers Pods action for Noptin integration.
     * 
     * @param array $params The form parameters.
     * @param object $obj The Pods object.
     * @param array $fields The form fields.
     */
    public function register_action( $params, $obj, $fields ) {
        // Custom action.
        if ( ! function_exists( 'add_noptin_subscriber' ) ) {
            return;
        }

        $form_id = isset( $params['pod'] ) ? $params['pod'] : '';
        
        if ( empty( $form_id ) ) {
            return;
        }

        // Check if Noptin is enabled for this form.
        $enabled = get_option( 'noptin_pods_enabled_' . $form_id, false );
        
        if ( ! $enabled ) {
            return;
        }

        // Get mapping settings.
        $mapping = get_option( 'noptin_pods_mapping_' . $form_id, array() );
        
        if ( empty( $mapping ) || empty( $mapping['email'] ) ) {
            return;
        }

        // Add the form submission hook.
        add_action( 'pods_api_post_save_pod_item', function( $id, $data, $pod ) use ( $form_id, $mapping ) {
            if ( $pod['id'] != $form_id ) {
                return;
            }

            // Check for GDPR consent if configured.
            if ( ! empty( $mapping['GDPR_consent'] ) && empty( $data[ $mapping['GDPR_consent'] ] ) ) {
                return;
            }

            // Prepare subscriber fields.
            $subscriber = array(
                'source' => 'Pods Forms',
            );

            // Add the subscriber's IP address.
            $address = noptin_get_user_ip();
            if ( ! empty( $address ) && '::1' !== $address ) {
                $subscriber['ip_address'] = $address;
            }

            // Map fields.
            foreach ( $mapping as $noptin_field => $pods_field ) {
                if ( ! empty( $pods_field ) && isset( $data[ $pods_field ] ) ) {
                    $subscriber[ $noptin_field ] = $data[ $pods_field ];
                }
            }

            // Check if we have an email.
            if ( empty( $subscriber['email'] ) || ! is_email( $subscriber['email'] ) ) {
                return;
            }

            // Filter the subscriber fields.
            $subscriber = apply_filters( 'noptin_pods_integration_new_subscriber_fields', $subscriber, $data, $pod );

            // Add the subscriber.
            add_noptin_subscriber( $subscriber );
        }, 10, 3 );
    }
}
