<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds a noptin subscriber.
 *
 * @since 1.9.0
 */
class Noptin_Subscribe_Action extends Noptin_Abstract_Action {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'subscribe';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Subscribe', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Create / Update Noptin Subscriber', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {
		return $this->get_description();
	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'noptin',
			'add',
			'subscribe',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings = array(
			'noptin_map_custom_fields_heading' => array(
				'el'      => 'hero',
				'content' => __( 'Map custom fields', 'newsletter-optin-box' ),
			)
		);

		foreach ( get_noptin_custom_fields() as $field ) {

			if ( 'email' === $field['merge_tag'] ) {
				continue;
			}

			$settings[ $field['merge_tag'] ] = array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => $field['label'],
				'placeholder' => sprintf(
					/* translators: %s: The field name. */
					__( 'Enter %s', 'newsletter-optin-box' ),
					$field['label']
				),
				'description' => sprintf(
					'<p class="description" v-show="availableSmartTags">%s</p>',
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'Enter a value or %1$suse smart tags%2$s to map a dynamic value.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
				'append'      => '<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox"><span class="dashicons dashicons-shortcode"></span></a>',
			);
		}

		return $settings;
	}

	/**
	 * Returns whether or not the action can run (dependancies are installed).
	 *
	 * @since 1.9.0
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return bool
	 */
	public function can_run( $subject, $rule, $args ) {

		// Check if we have an email address.
		return is_email( $this->get_subject_email( $subject, $rule, $args ) );
	}

	/**
	 * Add / update the subscriber.
	 *
	 * @since 1.3.1
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		$settings = wp_unslash( $rule->action_settings );
		$details  = array(
			'email' => $this->get_subject_email( $subject, $rule, $args ),
		);

		/** @var Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		foreach ( get_noptin_custom_fields() as $field ) {

			if ( 'email' === $field['merge_tag'] || ! isset( $settings[ $field['merge_tag'] ] ) || '' === $settings[ $field['merge_tag'] ] ) {
				continue;
			}

			$details[ $field['merge_tag'] ] = $smart_tags->replace_in_text_field( $settings[ $field['merge_tag'] ] );
		}

		$subscriber_id = get_noptin_subscriber_id_by_email( $details['email'] );

		if ( $subscriber_id ) {
			update_noptin_subscriber( $subscriber_id, $details );
		} else {
			add_noptin_subscriber( $details );
		}
	}

}
