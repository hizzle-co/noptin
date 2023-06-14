<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base class for triggers and actions.
 *
 * @since 1.11.9
 */
abstract class Noptin_Abstract_Trigger_Action {

	/**
	 * @var array
	 */
	protected $rules;

	/**
	 * @var bool
	 */
	public $depricated = false;

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
			$integration = Noptin_COM::get_integration( $this->integration );

			if ( ! empty( $integration ) ) {
				return $integration->image_url;
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
		return plugin_dir_url( Noptin::$file ) . 'includes/assets/images/logo.png';
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
					'<span class="noptin-rule-meta"><span class="noptin-rule-meta-key">%s</span>: <span class="noptin-rule-meta-value">%s</span></span>',
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
	 * @param Noptin_Automation_Rule $rule
	 * @return string
	 */
	public function get_rule_description( $rule ) {
		return $this->get_description();
	}

	/**
	 * Retrieve the trigger's or actions's rule table description.
	 *
	 * @since 1.11.9
	 * @param Noptin_Automation_Rule $rule
	 * @return string
	 */
	public function get_rule_table_description( $rule ) {
		return '';
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
	 * Returns all active rules attached to this trigger or action.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	abstract public function get_rules();

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
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return false|string
	 */
	public function get_subject_email( $subject, $rule, $args ) {

		// Objects.
		if ( is_object( $subject ) ) {

			// Subscriber, customer, etc.
			if ( is_callable( array( $subject, 'get_email' ) ) ) {
				return $subject->get_email();
			}

			// Raw data.
			if ( isset( $subject->email ) ) {
				return $subject->email;
			}

			if ( isset( $subject->user_email ) ) {
				return $subject->user_email;
			}
		}

		// Subject is an email.
		if ( is_string( $subject ) && is_email( $subject ) ) {
			return $subject;
		}

		// ... or the email argument.
		if ( ! empty( $args['email'] ) ) {
			return $args['email'];
		}

		return apply_filters( 'noptin_automation_action_get_subject_email', false, $subject, $rule, $args );
	}

}
