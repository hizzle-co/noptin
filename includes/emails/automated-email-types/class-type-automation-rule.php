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
	 * @var string
	 */
	public $notification_hook = 'noptin_send_automation_rule_email';

	/**
	 * @var Noptin_Automation_Rules_Smart_Tags
	 */
	public $smart_tags;

	/**
	 * Class constructor.
	 *
	 * @param string $trigger_id
	 */
	public function __construct( $trigger_id ) {
		$this->type       = $trigger_id;
		$this->trigger_id = str_replace( 'automation_rule_', '', $this->type );
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
			return $trigger->get_email_description();
		}

		return '';
	}

	/**
	 * Retrieves the automated email type image.
	 *
	 */
	public function the_image() {

		$trigger = $this->get_trigger();

		if ( $trigger && $trigger->get_image() ) {
			printf(
				'<img src="%s" alt="%s" width="100" height="100" />',
				esc_url( $trigger->get_image() ),
				esc_attr( $this->get_name() )
			);

			return;
		}

		echo '<svg xmlns="http://www.w3.org/2000/svg" fill="#008000" viewBox="0 0 122.88 122.88"><path d="M61.44,0A61.46,61.46,0,1,1,18,18,61.21,61.21,0,0,1,61.44,0ZM32.22,79.39,52.1,59.46,32.22,43.25V79.39ZM54.29,61.24,33.79,81.79H88.91L69.33,61.24l-6.46,5.51h0a1.42,1.42,0,0,1-1.8,0l-6.78-5.53Zm17.18-1.82L90.66,79.55V43.07L71.47,59.42ZM34,41.09l27.9,22.76L88.65,41.09Zm65.4-17.64a53.72,53.72,0,1,0,15.74,38,53.56,53.56,0,0,0-15.74-38Z"/></svg>';
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
	 * @param Noptin_Automated_Email $campaign
	 */
	public function about_automation( $about, $campaign ) {

		$trigger = $this->get_trigger();
		$about   = '';

		if ( $trigger ) {
			$about = $trigger->get_about_email();
		}

		return apply_filters( 'noptin_automation_rule_email_about', $about, $campaign );

	}

	/**
	 * (Maybe) Send out an email email.
	 *
	 * @param array $trigger_args
	 * @param Noptin_Automated_Email $campaign
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
			if ( ! is_email( $recipient ) || noptin_is_email_unsubscribed( $recipient ) ) {
				continue;
			}

			// Fetch the wp user.
			$user = get_user_by( 'email', $recipient );

			if ( $user ) {
				$this->user = $user;
			}

			// Fetch the subscriber.
			$subscriber = get_noptin_subscriber( $recipient );

			if ( $subscriber->exists() ) {
				$this->subscriber = $subscriber;
			}

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
	 * @param Noptin_Automated_Email $campaign
	 */
	public function prepare_test_data( $campaign ) {

		// Prepare user and subscriber.
		parent::prepare_test_data( $campaign );

		// Prepare automation rule test data.
		$trigger = $this->get_trigger();
		$rule    = new Noptin_Automation_Rule( (int) $campaign->get( 'automation_rule' ) );

		if ( $trigger ) {
			try {
				$this->smart_tags = $trigger->get_test_smart_tags( $rule );
			} catch ( Exception $e ) {
				$this->smart_tags = null;
			}
		}

	}

	/**
	 * Sends a test email.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param string $recipient
	 * @return bool Whether or not the test email was sent
	 */
	public function send_test( $campaign, $recipient ) {

		// Prepare the test data.
		$this->prepare_test_data( $campaign );

		// Prepare automation rule test data.
		$trigger = $this->get_trigger();
		$rule    = new Noptin_Automation_Rule( (int) $campaign->get( 'automation_rule' ) );

		if ( $trigger ) {
			$this->smart_tags = $trigger->get_test_smart_tags( $rule );
		}


		// Maybe set related subscriber.
		$subscriber = get_noptin_subscriber( sanitize_email( $recipient ) );

		$this->subscriber = $subscriber->exists() ? $subscriber : null;

		// Maybe set related user.
		$user = get_user_by( 'email', sanitize_email( $recipient ) );

		$this->user = $user ? $user : null;

		return $this->send( $campaign, 'test', array( sanitize_email( $recipient ) => false ) );

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
	 * Sends a notification.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param string $key
	 * @param array|string $recipients
	 */
	public function send( $campaign, $key, $recipients ) {
		$result = false;

		// Prepare environment.
		$this->before_send( $campaign );

		// Prepare recipients.
		if ( is_string( $recipients ) ) {
			$recipients = array( $recipients => true );
		}

		// Send to each recipient.
		foreach ( $recipients as $email => $track ) {

			$GLOBALS['current_noptin_email'] = $email;

			// Send the email.
			$result = noptin_send_email(
				array(
					'recipients'               => $email,
					'subject'                  => noptin_parse_email_subject_tags( $campaign->get_subject() ),
					'message'                  => noptin_generate_email_content( $campaign, $this->recipient, $track ),
					'headers'                  => array(),
					'attachments'              => array(),
					'reply_to'                 => '',
					'from_email'               => '',
					'from_name'                => '',
					'campaign_id'              => $campaign->id,
					'content_type'             => $campaign->get_email_type() === 'plain_text' ? 'text' : 'html',
					'unsubscribe_url'          => $this->unsubscribe_url,
					'disable_template_plugins' => ! ( $campaign->get_email_type() === 'normal' && $campaign->get_template() === 'default' ),
				)
			);

		}

		// Clear environment.
		$this->after_send( $campaign );

		// Log.
		if ( 'test' !== $key ) {
			increment_noptin_campaign_stat( $campaign->id, '_noptin_sends' );
		}

		return $result;
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
	public function replace_in_body( $string ) {

		if ( ! empty( $this->smart_tags ) ) {
			return $this->smart_tags->replace_in_body( $string );
		}

		return $string;
	}
}
