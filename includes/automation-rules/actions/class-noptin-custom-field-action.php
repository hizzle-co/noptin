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
        return __( 'update the custom field', 'newsletter-optin-box' );
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

    }

}
