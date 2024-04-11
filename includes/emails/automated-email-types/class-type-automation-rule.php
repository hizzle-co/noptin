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
		$this->type       = $trigger_id;
		$this->trigger_id = str_replace( 'automation_rule_', '', $this->type );

		// Set the category.
		if ( $trigger->depricated ) {
			$this->category = '';
		} else {
			$this->category = $trigger->category;
		}

		// Set the contexts.
		$this->contexts    = $trigger->contexts;
		$this->mail_config = $trigger->mail_config;

		if ( ! empty( $trigger->alias ) ) {
			add_filter( 'noptin_automation_email_sub_type_automation_rule_' . $trigger->alias, array( $this, 'get_type' ) );
		}

		$this->add_hooks();
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
	 * Returns the email type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
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
			$description = $trigger->get_description();

			// Lowercase the first letter.
			$description = strtolower( $description[0] ) . substr( $description, 1 );

			return sprintf(
				// translators: %s: Trigger description.
				__( 'Sends an email %s.', 'newsletter-optin-box' ),
				$description
			);
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
				'<div class="noptin-strong noptin-text-error">%s</div>',
				esc_html__( 'The automation rule for this email does not exist.', 'newsletter-optin-box' )
			);
		}

		$trigger = $rule->get_trigger();

		if ( $trigger ) {
			$trigger_about = $trigger->get_rule_table_description( $rule );

			if ( ! empty( $trigger_about ) ) {
				$about .= '<div>' . $trigger_about . '</div>';
			}
		}

		return $about;
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

		noptin()->emails->tags->smart_tags = $trigger->get_test_smart_tags( $rule );
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
				__( 'General', 'newsletter-optin-box' ) => $trigger->get_known_smart_tags(),
			);
		}

		return array();
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

		$statuses = array( 'publish', 'draft', 'pending' );

		// Abort if no id.
		if ( ! $campaign->exists() || ! in_array( $campaign->status, $statuses, true ) ) {
			return array();
		}

		// Create a matching automation rule if one does not exist.
		$rule = noptin_get_automation_rule( (int) $campaign->get( 'automation_rule' ) );

		if ( is_wp_error( $rule ) ) {
			$rule = noptin_get_automation_rule( 0 );
		}

		$rule->set_trigger_id( $campaign->get_trigger() );
		$rule->set_action_id( 'email' );

		$is_new = ! $rule->exists();
		if ( $is_new ) {
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

		if ( $campaign->sends_immediately() ) {
			$rule->set_delay( 0 );
		} else {
			$interval = '+' . $campaign->get_sends_after() . ' ' . $campaign->get_sends_after_unit();
			$rule->set_delay( strtotime( $interval ) - time() );
		}

		$rule->set_status( $campaign->is_published() );

		// Save the rule.
		$rule->save();

		if ( ! $rule->exists() ) {
			return new \WP_Error( 'noptin_automation_rule', __( 'Failed to save automation rule.', 'newsletter-optin-box' ) );
		}

		if ( $is_new ) {
			$campaign_data = get_post_meta( $campaign->id, 'campaign_data', true );

			// If data is stdClass, convert it to an array.
			if ( is_object( $campaign_data ) ) {
				$campaign_data = (array) $campaign_data;
			}

			$campaign_data = ! is_array( $campaign_data ) ? array() : $campaign_data;

			$campaign_data['automation_rule'] = $rule->get_id();
			update_post_meta( $campaign->id, 'campaign_data', (object) $campaign_data );
		}

		return $rule->get_data();
	}
}
