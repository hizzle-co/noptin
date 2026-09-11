<?php
/**
 * Emails API: Automation Rule.
 *
 * Send an email as an automation rule action.
 *
 * @since   1.11.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules\Triggers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send an email as an automation rule action.
 *
 * @since 1.11.0
 * @internal
 * @ignore
 */
class Email_Type {

	/**
	 * Email sub-type ID.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Email sub-type category.
	 *
	 * @var string
	 */
	public $category = 'General';

	/**
	 * Supported editor contexts.
	 *
	 * @var string[]
	 */
	public $contexts = array();

	/**
	 * Trigger-provided email configuration.
	 *
	 * @var array
	 */
	public $mail_config = array();

	/**
	 * @var string Trigger ID.
	 */
	protected $trigger_id;

	/**
	 * @var \Hizzle\Noptin\Automation_Rules\Smart_Tags
	 */
	public $smart_tags;

	/**
	 * Class constructor.
	 *
	 * @param string $trigger_id
	 * @param Trigger $trigger
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

		if ( ! empty( $trigger->featured ) ) {
			$this->mail_config['featured'] = $trigger->featured;
		}

		if ( ! empty( $trigger->alias ) ) {
			add_filter( 'noptin_automation_email_sub_type_automation_rule_' . $trigger->alias, array( $this, 'get_type' ) );
		}

		$this->add_hooks();
	}

	/**
	 * Registers the hooks used by automation-rule email sub-types.
	 */
	public function add_hooks() {
		add_filter( 'noptin_automation_sub_types', array( $this, 'register_automation_type' ) );
		add_filter( "noptin_automation_{$this->type}_merge_tags", array( $this, 'get_flattened_merge_tags' ), -1 );
		add_filter( "noptin_automation_table_about_{$this->type}", array( $this, 'about_automation' ), 10, 2 );
		add_action( "noptin_automation_{$this->type}_campaign_saved", array( $this, 'on_save_campaign' ) );
		add_action( "noptin_automation_{$this->type}_campaign_deleted", array( $this, 'on_delete_campaign' ) );
		add_action( 'noptin_prepare_email_preview', array( $this, 'prepare_preview' ) );
		add_filter( 'noptin_get_email_prop', array( $this, 'maybe_set_default' ), 10, 3 );
		add_filter( 'noptin_get_default_email_props', array( $this, 'get_default_props' ), 10, 2 );
	}

	/**
	 * Registers this trigger as an automated email sub-type.
	 *
	 * @param array $types Existing automated email sub-types.
	 * @return array
	 */
	public function register_automation_type( $types ) {
		$defaults = array(
			'label'                      => $this->get_name(),
			'description'                => $this->get_description(),
			'image'                      => $this->get_image(),
			'category'                   => $this->category,
			'is_mass_mail'               => 'Mass Mail' === $this->category,
			'supports_timing'            => 'Mass Mail' !== $this->category,
			'contexts'                   => $this->contexts,
			'supports_general_templates' => empty( $this->mail_config['defaults']['blocks'] ),
		);

		$types[ $this->type ] = array_merge( $defaults, $this->mail_config );
		return $types;
	}

	/**
	 * Returns default email properties for this sub-type.
	 *
	 * @param array                        $props Existing defaults.
	 * @param \Hizzle\Noptin\Emails\Email $email Email campaign.
	 * @return array
	 */
	public function get_default_props( $props, $email ) {
		if ( $email->type !== $this->type && $email->get_sub_type() !== $this->type ) {
			return $props;
		}

		if ( ! empty( $this->mail_config['defaults'] ) ) {
			$props = array_merge( $props, $this->mail_config['defaults'] );
		}

		foreach ( get_class_methods( $this ) as $method ) {
			if ( 0 !== strpos( $method, 'default_' ) ) {
				continue;
			}

			$props[ str_replace( 'default_', '', $method ) ] = call_user_func( array( $this, $method ), $email );
		}

		return $props;
	}

	/**
	 * Filters an unsaved email property with this sub-type's default.
	 *
	 * @param mixed                        $value Current value.
	 * @param string                       $prop Property name.
	 * @param \Hizzle\Noptin\Emails\Email $email Email campaign.
	 * @return mixed
	 */
	public function maybe_set_default( $value, $prop, $email ) {
		if ( ! empty( $value ) || $email->exists() || $email->get_sub_type() !== $this->type ) {
			return $value;
		}

		$method = sanitize_key( "default_$prop" );
		if ( is_callable( array( $this, $method ) ) ) {
			$value = $this->$method();
		}

		return apply_filters( "noptin_{$this->type}_default_$prop", $value );
	}

	/**
	 * Returns the default campaign name.
	 */
	public function default_name() {
		if ( ! empty( $this->mail_config['label'] ) ) {
			return $this->mail_config['label'];
		}

		return $this->get_name();
	}

	/**
	 * Returns the default normal email content.
	 */
	public function default_content_normal() {
		$content = $this->mail_config['defaults']['content_normal'] ?? '';
		return apply_filters( "noptin_default_{$this->type}_body", $content );
	}

	/**
	 * Returns the default visual email content.
	 */
	public function default_content_visual() {
		$blocks = $this->mail_config['defaults']['blocks'] ?? '';

		if ( empty( $blocks ) ) {
			$normal = $this->default_content_normal();
			$blocks = empty( $normal ) ? '' : sprintf( '<!-- wp:html -->%s<!-- /wp:html -->', wpautop( $normal ) );
		}

		$content = noptin_email_wrap_blocks( $blocks, get_noptin_footer_text() );
		return apply_filters( "noptin_default_{$this->type}_body_visual", $content );
	}

	/**
	 * Flattens grouped merge tags for the email API and editor.
	 *
	 * @param array $existing Existing merge tags.
	 * @return array
	 */
	public function get_flattened_merge_tags( $existing = array() ) {
		$existing = is_array( $existing ) ? $existing : array();
		$prepared = array();

		foreach ( $this->get_merge_tags() as $group => $merge_tags ) {
			foreach ( $merge_tags as $tag => $details ) {
				if ( empty( $details['group'] ) ) {
					$details['group'] = $group;
				}

				$prepared[ $tag ] = $details;
			}
		}

		return array_merge( $prepared, $existing );
	}

	/**
	 * Returns the trigger object.
	 */
	public function get_trigger() {
		return Main::get( $this->trigger_id );
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

		return 'email-alt';
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
				'The automation rule for this email does not exist.'
			);
		}

		$trigger = $rule->get_trigger();

		if ( $trigger ) {
			$trigger_about = $trigger->get_rule_table_description( $rule );

			if ( ! empty( $trigger_about ) ) {
				$about .= $trigger_about;
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
		do_action( 'noptin_prepare_test_data', $this, $campaign );

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
	 * Prepares an email preview for this sub-type.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign Email campaign.
	 */
	public function prepare_preview( $campaign ) {
		if ( $this->type === $campaign->type || $this->type === $campaign->get_sub_type() ) {
			$this->prepare_test_data( $campaign );
		}
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
			return new \WP_Error( 'noptin_automation_rule', 'Failed to save automation rule.' );
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
