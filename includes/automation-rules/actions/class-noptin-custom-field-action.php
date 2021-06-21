<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Updates a subscriber's custom field.
 *
 * @since       1.2.8
 */
class Noptin_Custom_Field_Action extends Noptin_Abstract_Action {

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'custom-field';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'Subscriber Field', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( "Add/Update the subscriber's custom field", 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {

        $settings = $rule->action_settings;

        if ( empty( $settings['field_name'] ) ) {
            return __( 'update a custom field', 'newsletter-optin-box' );
        }

        $field_name  = esc_html( $settings['field_name'] );
        if ( empty( $settings['field_value'] ) ) {
            return sprintf(
                __( "delete the subscriber's field %s", 'newsletter-optin-box' ),
               "<code>$field_name</code>"
            );
        }

        $field_value = esc_html( $settings['field_value'] );

        return sprintf(
            __( "update the subscriber's field %s to %s", 'newsletter-optin-box' ),
           "<code>$field_name</code>",
           "<code>$field_value</code>"
        );

    }

    /**
     * @inheritdoc
     */
    public function get_image() {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'noptin',
            'custom',
            'field',
            'custom field'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
        return array(

            'field_name'   => array(
				'el'          => 'input',
				'label'       => __( 'Field Name', 'newsletter-optin-box' ),
                'description' => __( 'Enter the name of the field', 'newsletter-optin-box' ),
                'placeholder' => __( 'Sample Field', 'newsletter-optin-box' ),
            ),

            'field_value'   => array(
				'el'          => 'input',
				'label'       => __( 'Field Value', 'newsletter-optin-box' ),
                'description' => __( 'Enter a value to assign the field', 'newsletter-optin-box' ),
                'placeholder' => __( 'Sample Value', 'newsletter-optin-box' ),
            )

        );
    }

    /**
     * Update a subscriber's custom field.
     *
     * @since 1.2.8
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function run( $subscriber, $rule, $args ) {

        $settings = $rule->action_settings;

        // Nothing to do here.
        if ( empty( $settings['field_name'] ) || $subscriber->is_virtual ) {
            return;
        }

        $field_name  = esc_html( $settings['field_name'] );
        if ( empty( $settings['field_value'] ) ) {
            return delete_noptin_subscriber_meta( $subscriber->id, $field_name );
        }

        $field_value = esc_html( $settings['field_value'] );

        return update_noptin_subscriber_meta( $subscriber->id, $field_name, $field_value );

    }

}
