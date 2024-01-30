<?php
/**
 * Emails API: Automation Rule.
 *
 * Send an email as an automation rule action.
 *
 * @since   1.11.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send an email as an automation rule action.
 *
 * @since 1.11.0
 * @internal
 * @ignore
 */
class Noptin_Automation_Rule_Email extends Noptin_Automated_Email_Type {

	/**
	 * @var string Trigger ID.
	 */
	protected $trigger_id;

	/**
	 * @var Noptin_Automation_Rules_Smart_Tags
	 */
	public $smart_tags;

	/**
	 * Class constructor.
	 *
	 * @param string $trigger_id
	 * @param \Noptin_Abstract_Trigger $trigger
	 */
	public function __construct( $trigger_id, $trigger ) {
		$this->type              = $trigger_id;
		$this->trigger_id        = str_replace( 'automation_rule_', '', $this->type );
		$this->notification_hook = 'noptin_send_automation_rule_email_' . $this->trigger_id;

		// Set the category.
		if ( $trigger->depricated ) {
			$this->category = '';
		} else {
			$this->category = $trigger->category;
		}

		// Set the contexts.
		$this->contexts = $trigger->contexts;

		$this->add_hooks();
	}

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {
		add_filter( 'noptin_parse_email_subject_tags', array( $this, 'replace_in_subject' ), 5 );
		add_filter( 'noptin_parse_email_content_tags', array( $this, 'replace_in_body' ), 5 );

		parent::add_hooks();
	}

	/**
	 * Returns the trigger object.
	 *
	 * @return Noptin_Abstract_Trigger|null
	 */
	public function get_trigger() {
		return noptin()->automation_rules->get_trigger( $this->trigger_id );
	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		$trigger = $this->get_trigger();

		if ( $trigger ) {
			return $trigger->get_name();
		}

		return $this->trigger_id;
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		$trigger = $this->get_trigger();

		if ( $trigger ) {
			return $trigger->get_about_email();
		}

		return '';
	}

	/**
	 * Returns the image URL or dashicon for the automated email type.
	 *
	 * @return string|array
	 */
	public function get_image() {

		$trigger = $this->get_trigger();

		if ( $trigger && $trigger->get_image() ) {
			return $trigger->get_image();
		}

		return parent::get_image();
	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function get_default_recipient() {
		return '[[email]]';
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		$trigger = $this->get_trigger();

		if ( $trigger ) {
			return $trigger->get_default_email_subject();
		}

		return '';
	}

	/**
	 * Returns the default heading.
	 *
	 */
	public function default_heading() {
		$trigger = $this->get_trigger();

		if ( $trigger ) {
			return $trigger->get_default_email_heading();
		}

		return '';
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		$trigger = $this->get_trigger();

		if ( $trigger ) {
			return $trigger->get_default_email_content();
		}

		return '';
	}

	/**
	 * Returns the default plain text content.
	 *
	 */
	public function default_content_plain_text() {
		return noptin_convert_html_to_text( $this->default_content_normal() );
	}

	/**
	 * Filters automation summary.
	 *
	 * @param string $about
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function about_automation( $about, $campaign ) {

		$trigger = $this->get_trigger();
		$rule    = noptin_get_automation_rule( absint( $campaign->get( 'automation_rule' ) ) );

		if ( is_wp_error( $rule ) || ! $rule->exists() ) {
			return $about . sprintf(
				'<div class="noptin-text-error">%s</div>',
				esc_html__( 'The automation rule for this email does not exist.', 'newsletter-optin-box' )
			);
		}

		$trigger = $rule->get_trigger();

		if ( $trigger ) {
			return $about . $trigger->get_rule_table_description( $rule );
		}

		return $about;
	}

	/**
	 * (Maybe) Send out an email email.
	 *
	 * @param array $trigger_args
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function maybe_send_notification( $trigger_args, $campaign ) {

		// Abort if not our email.
		if ( $this->trigger_id !== $trigger_args['trigger_id'] ) {
			return;
		}

		// Ensure the campaign is active.
		if ( ! $campaign->can_send() ) {
			return;
		}

		// Allow sending custom double opt-in emails.
		$confirm_active = true;
		if ( 'new_subscriber' === $this->trigger_id && noptin_has_enabled_double_optin() && isset( $trigger_args['rule_id'] ) ) {

			$rule           = noptin_get_automation_rule( $trigger_args['rule_id'] );
			$settings       = is_wp_error( $rule ) ? array() : $rule->get_action_settings();
			$confirm_active = ! empty( $settings['fire_after_confirmation'] );
		}

		if ( ! empty( $trigger_args['send_email_to_inactive'] ) ) {
			$confirm_active = false;
		}

		/** @var Noptin_Automation_Rules_Smart_Tags */
		$this->smart_tags = isset( $trigger_args['smart_tags'] ) ? $trigger_args['smart_tags'] : null;

		foreach ( $this->get_recipients( $campaign, array( '[[email]]' => $trigger_args['email'] ) ) as $recipient => $track ) {

			$this->user       = null;
			$this->subscriber = null;

			// Prepare the email.
			if ( ! empty( $this->smart_tags ) ) {
				$recipient = $this->smart_tags->replace_in_text_field( $recipient );
			}

			// Abort if not a valid email or is unsubscribed.
			if ( ! is_email( $recipient ) || ( $confirm_active && noptin_is_email_unsubscribed( $recipient ) ) ) {
				continue;
			}

			// Fetch the wp user.
			$this->maybe_set_subscriber_and_user( $recipient );

			// Send the email.
			$key = $recipient . '_' . $campaign->id;
			$this->send( $campaign, $key, array( $recipient => $track ) );

			// Record the activity.
			noptin_record_subscriber_activity(
				$recipient,
				sprintf(
					// translators: %s is the email name.
					__( 'Sent the email %1$s', 'newsletter-optin-box' ),
					'<code>' . esc_html( $campaign->name ) . '</code>'
				)
			);
		}

		$this->user       = null;
		$this->subscriber = null;
		$this->smart_tags = null;
	}

	/**
	 * Prepares test data.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @throws \Exception
	 */
	public function prepare_test_data( $campaign ) {

		// Prepare user and subscriber.
		parent::prepare_test_data( $campaign );

		// Prepare automation rule test data.
		$trigger = $this->get_trigger();

		if ( empty( $trigger ) ) {
			throw new \Exception( 'Trigger not found' );
		}

		$rule = noptin_get_automation_rule( (int) $campaign->get( 'automation_rule' ) );

		if ( is_wp_error( $rule ) || ! $rule->exists() ) {
			throw new \Exception( 'Automation rule not found' );
		}

		$this->smart_tags = $trigger->get_test_smart_tags( $rule );
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		$trigger = $this->get_trigger();

		if ( $trigger ) {
			return array(
				__( 'Trigger', 'newsletter-optin-box' ) => $trigger->get_known_smart_tags(),
			);
		}

		return array();
	}

	/**
	 * Replaces in subject
	 *
	 * @param string $string
	 * @return string
	 */
	public function replace_in_subject( $string ) {

		if ( ! empty( $this->smart_tags ) ) {
			return $this->smart_tags->replace_in_text_field( $string );
		}

		return $string;
	}

	/**
	 * Replaces in the email body
	 *
	 * @param string $string
	 * @return string
	 */
	public function replace_in_body( $content ) {

		if ( ! empty( $this->smart_tags ) ) {
			return $this->smart_tags->replace_in_body( $content );
		}

		return $content;
	}

	/**
	 * Fired before deleting an email campaign.
	 *
	 * @param Hizzle\Noptin\Emails\Email $campaign
	 */
	public function on_delete_campaign( $campaign ) {
		$rule = noptin_get_automation_rule( (int) $campaign->get( 'automation_rule' ) );

		if ( ! is_wp_error( $rule ) && $rule->exists() ) {
			$rule->delete( false );
		}
	}

	/**
	 * Fires after an automation is saved.
	 *
	 * @param Hizzle\Noptin\Emails\Email $campaign
	 */
	public function on_save_campaign( $campaign ) {
		self::sync_campaign_to_rule( $campaign );
	}

	/**
	 * Fires after an automation is saved.
	 *
	 * @param Hizzle\Noptin\Emails\Email $campaign
	 * @param array | null $trigger_settings
	 */
	public static function sync_campaign_to_rule( $campaign, $trigger_settings = null ) {

		// Abort if no id.
		if ( ! $campaign->exists() || 'auto-draft' === $campaign->status ) {
			return array();
		}

		// Create a matching automation rule if one does not exist.
		$rule = noptin_get_automation_rule( (int) $campaign->get( 'automation_rule' ) );

		if ( is_wp_error( $rule ) ) {
			$rule = noptin_get_automation_rule( 0 );
		}

		$is_new = ! $rule->exists();
		if ( $is_new ) {
			$rule->set_action_id( 'email' );
			$rule->set_trigger_id( $campaign->get_trigger() );
			$rule->set_action_settings( array( 'automated_email_id' => $campaign->id ) );
			$rule->set_trigger_settings( array( 'conditional_logic' => noptin_get_default_conditional_logic() ) );
		} elseif ( (int) $rule->get_action_setting( 'automated_email_id' ) !== $campaign->id ) {
			$rule->set_action_settings(
				array_merge(
					$rule->get_action_settings(),
					array( 'automated_email_id' => $campaign->id )
				)
			);
		}

		if ( is_array( $trigger_settings ) ) {
			$rule->set_trigger_settings(
				array_merge(
					$rule->get_trigger_settings(),
					$trigger_settings
				)
			);
		}

		// Save the rule.
		$rule->save();

		if ( ! $rule->exists() ) {
			return new \WP_Error( 'noptin_automation_rule', __( 'Failed to save automation rule.', 'newsletter-optin-box' ) );
		}

		if ( $is_new ) {
			$campaign_data = get_post_meta( $campaign->id, 'campaign_data', true );
			$campaign_data = ! is_array( $campaign_data ) ? array() : $campaign_data;

			$campaign_data['automation_rule'] = $rule->get_id();
			update_post_meta( $campaign->id, 'campaign_data', $campaign_data );
		}

		return $rule->get_data();
	}
}
