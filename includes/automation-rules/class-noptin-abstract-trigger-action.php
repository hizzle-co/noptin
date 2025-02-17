<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use \Hizzle\Noptin\Automation_Rules\Automation_Rule;

/**
 * Base class for triggers and actions.
 *
 * @since 1.11.9
 */
abstract class Noptin_Abstract_Trigger_Action {

	/**
	 * @var  Automation_Rule[]
	 */
	protected $rules;

	/**
	 * @var bool
	 */
	public $depricated = false;

	/**
	 * @var bool
	 */
	public $is_action_or_trigger = 'trigger';

	/**
	 * @var string
	 */
	public $category = 'General';

	/**
	 * @var string
	 */
	public $integration;

	/**
	 * @var string
	 */
	public $connection;

	/**
	 * @var string[]
	 */
	public $contexts = array();

	/**
	 * Trigger id alias.
	 *
	 * @var string
	 */
	public $alias = null;

	/**
	 * Unique ID for the trigger or action.
	 *
	 * Only alphanumerics, dashes and underscrores are allowed.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Retrieve the trigger's or action's name.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Retrieve the trigger's or action's description.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Retrieve the trigger's or action's image.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function get_image() {

		// Integrations.
		if ( ! empty( $this->integration ) ) {
			$integration = \Hizzle\Noptin\Integrations\Main::get_integration_info( $this->integration );

			if ( ! empty( $integration ) ) {
				return $integration['icon_url'];
			}
		}

		// Connections.
		if ( ! empty( $this->connection ) ) {
			$connection = Noptin_COM::get_connection( $this->connection );

			if ( ! empty( $connection ) ) {
				return $connection->image_url;
			}
		}

		// Default image.
		noptin()->white_label->get( 'logo', noptin()->plugin_url . 'includes/assets/images/logo.png' );
	}

	/**
	 * Retrieve the trigger's or action's keywords.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_keywords() {
		return array_merge(
			explode( ' ', $this->get_name() ),
			explode( '_', $this->get_id() ),
			array( $this->category )
		);
	}

	/**
	 * Stringify the trigger's or action's meta.
	 *
	 * @since 1.11.9
	 * @param array $meta
	 * @return string
	 */
	protected function prepare_rule_meta( $meta ) {
		$prepared = array();

		if ( ! is_array( $meta ) ) {
			return '';
		}

		foreach ( $meta as $key => $value ) {
			if ( '' !== $value && false !== $value ) {
				$prepared[] = sprintf(
					'<span class="noptin-rule-meta noptin-rule-meta__%s"><span class="noptin-rule-meta-key">%s</span>: <span class="noptin-rule-meta-value">%s</span></span>',
					esc_attr( sanitize_html_class( preg_replace( '/[\s]+/', '-', strtolower( $key ) ) ) ),
					esc_html( $key ),
					esc_html( $value )
				);
			}
		}

		return implode( '', $prepared );
	}

	/**
	 * Retrieve the trigger's or action's rule description.
	 *
	 * @since 1.3.0
	 * @param Automation_Rule $rule
	 * @return string
	 */
	public function get_rule_description( $rule ) {
		return $this->get_description();
	}

	/**
	 * Retrieve the trigger's or actions's rule table description.
	 *
	 * @since 1.11.9
	 * @param Automation_Rule $rule
	 * @return string
	 */
	public function get_rule_table_description( $rule ) {
		return '';
	}

	/**
	 * Retrieves the map fields section title.
	 *
	 * @since 1.11.9
	 * @return string
	 */
	public function get_map_fields_section_title() {
		return __( 'Map custom fields', 'newsletter-optin-box' );
	}

	/**
	 * Retrieve the trigger's settings.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_settings() {
		return array();
	}

	/**
	 * Returns all active rules attached to this action.
	 *
	 * @param bool|string $status Can be any, true, or false;
	 * @since 1.2.8
	 * @return Automation_Rule[]
	 */
	public function get_rules( $status = true ) {

		if ( ! is_array( $this->rules ) ) {
			$this->rules = noptin_get_automation_rules(
				array(
					$this->is_action_or_trigger . '_id' => empty( $this->alias ) ? $this->get_id() : array( $this->get_id(), $this->alias ),
					'status'                            => $status,
				)
			);
		}

		return $this->rules;
	}

	/**
	 * Checks if there are rules for this trigger.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function has_rules() {
		$rules = $this->get_rules();
		return ! empty( $rules );
	}

	/**
	 * Returns the subject's email address.
	 *
	 * @since 1.11.0
	 * @param mixed $subject The subject.
	 * @param Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return string
	 */
	public function get_subject_email( $subject, $rule, $args ) {

		// Objects.
		if ( is_object( $subject ) ) {

			// Raw data.
			if ( isset( $subject->email ) && ! empty( $subject->email ) ) {
				return $subject->email;
			}

			if ( isset( $subject->user_email ) && ! empty( $subject->user_email ) ) {
				return $subject->user_email;
			}

			// Subscriber, customer, etc.
			if ( is_callable( array( $subject, 'get_email' ) ) ) {
				$email = $subject->get_email();

				if ( ! empty( $email ) ) {
					return $email;
				}
			}
		}

		// Subject is an email.
		if ( is_string( $subject ) && is_email( $subject ) ) {
			return $subject;
		}

		// ... or the email argument.
		if ( ! empty( $args['email'] ) ) {
			$email = $args['email'];

			if ( isset( $args['smart_tags'] ) && is_callable( array( $args['smart_tags'], 'replace_in_text_field' ) ) ) {
				$email = $args['smart_tags']->replace_in_text_field( $email );
			}

			return $email;
		}

		return apply_filters( 'noptin_automation_action_get_subject_email', '', $subject, $rule, $args );
	}
}
