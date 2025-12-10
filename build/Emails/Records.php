<?php

namespace Hizzle\Noptin\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a Noptin emails.
 *
 * @since 3.0.0
 */
class Records extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Emails\Record';

	/**
	 * @var string integration.
	 */
	public $integration = 'noptin';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		$this->label             = __( 'Email Campaigns', 'newsletter-optin-box' );
		$this->singular_label    = __( 'Email Campaign', 'newsletter-optin-box' );
		$this->type              = 'noptin-campaign';
		$this->smart_tags_prefix = 'campaign';
		$this->can_list          = false;
		$this->icon              = array(
			'icon' => 'email',
			'fill' => '#008000',
		);

		parent::__construct();
		add_filter( 'noptin_automation_rules_email_conditional_logic_skip_tags', array( __CLASS__, 'conditional_logic_skip_tags' ), 10, 2 );
		add_action( 'noptin_automation_rules_email_prepare_skipped_rules', array( __CLASS__, 'prepare_skipped_rules' ), 10, 2 );
		add_action( 'updated_postmeta', array( $this, 'check_newsletter_sent' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'check_newsletter_sent' ), 10, 4 );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		return array(
			'id'                  => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'parent_id'           => array(
				'label' => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'name'                => array(
				'label'      => __( 'Name', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'campaign_title',
			),
			'subject'             => array(
				'label' => __( 'Subject', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'preview_url'         => array(
				'label' => __( 'Preview URL', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Preview URL', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object link destination. */
						__( 'Displays a button link to %s', 'newsletter-optin-box' ),
						strtolower( __( 'Preview URL', 'newsletter-optin-box' ) )
					),
					'icon'        => 'visibility',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'Preview', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'preview_url' ),
					),
					'element'     => 'button',
				),
			),
			'edit_url'            => array(
				'label' => __( 'Edit URL', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Edit URL', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object link destination. */
						__( 'Displays a button link to %s', 'newsletter-optin-box' ),
						strtolower( __( 'Edit URL', 'newsletter-optin-box' ) )
					),
					'icon'        => 'edit',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'Edit', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'edit_url' ),
					),
					'element'     => 'button',
				),
			),
			'view_in_browser_url' => array(
				'label' => __( 'View in browser', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'activity_url'        => array(
				'label' => __( 'Activity URL', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Activity URL', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object link destination. */
						__( 'Displays a button link to %s', 'newsletter-optin-box' ),
						strtolower( __( 'Activity URL', 'newsletter-optin-box' ) )
					),
					'icon'        => 'chart-line',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'View Stats', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'activity_url' ),
					),
					'element'     => 'button',
				),
			),
			'send_count'          => array(
				'label' => __( 'Sends', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'open_count'          => array(
				'label' => __( 'Opens', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'click_count'         => array(
				'label' => __( 'Clicks', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'unsubscribe_count'   => array(
				'label' => __( 'Unsubscribed', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'created'             => array(
				'label' => __( 'Created', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
		);
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {

		return array_merge(
			parent::get_triggers(),
			// Newsletter sent.
			array(
				'noptin_newsletter_sent' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Newsletter Sent', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => __( 'When a newsletter is sent', 'newsletter-optin-box' ),
					'subject'     => 'post_author',
				),
			)
		);
	}

	/**
	 * Checks newsletter sent to trigger automation rules.
	 *
	 * @param int    $meta_id    The meta ID.
	 * @param int    $post_id    The post ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 */
	public function check_newsletter_sent( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( 'completed' !== $meta_key || empty( $meta_value ) ) {
			return;
		}

		$email = noptin_get_email_campaign_object( $post_id );

		if ( $email->exists() && 'newsletter' === $email->type ) {
			$author = $email->author ? get_userdata( $email->author ) : null;

			$this->trigger(
				'noptin_newsletter_sent',
				array(
					'email'      => $author ? $author->user_email : '',
					'object_id'  => $email->id,
					'subject_id' => $email->author,
				)
			);

			do_action( 'noptin_newsletter_sent', $post_id, $meta_value );
		}
	}

	/**
	 * Returns a list of available (actions).
	 *
	 * @return array $actions The actions.
	 */
	public function get_actions() {
		return array_merge(
			parent::get_actions(),
			array(
				'email'           => array(
					'id'            => 'email',
					'label'         => __( 'Send Email', 'newsletter-optin-box' ),
					'description'   => __( 'Send Email', 'newsletter-optin-box' ),
					'callback'      => __CLASS__ . '::run_send_email_action',
					'can_run'       => __CLASS__ . '::can_run_send_email_action',
					// translators: %s is a list of conditions.
					'run_if'        => __( 'Sends if %s', 'newsletter-optin-box' ),
					// translators: %s is a list of conditions.
					'skip_if'       => __( 'Does not send if %s', 'newsletter-optin-box' ),
					'callback_args' => array( 'rule', 'args', 'smart_tags' ),
				),
				'delete_campaign' => array(
					'id'             => 'delete_campaign',
					'label'          => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Delete', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description'    => sprintf(
						/* translators: %s: Object type label. */
						__( 'Delete an %s', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'callback'       => __CLASS__ . '::delete_record_action',
					'can_run'        => __CLASS__ . '::can_delete_record_action',
					'extra_settings' => array(
						'campaign' => array(
							'label'    => __( 'Campaign ID', 'newsletter-optin-box' ),
							'type'     => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Sends an email.
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public static function run_send_email_action( $rule, $args, $smart_tags ) {
		// Avoid resending the email when a post is re-published.
		if ( ! empty( $args['post_meta'] ) ) {
			$meta_key = $args['post_meta']['key'];
			$post_id  = (int) $args['post_meta']['id'];
			$email_id = (int) $rule->get_action_setting( 'automated_email_id' );
			$existing = get_post_meta( $post_id, $meta_key );
			$found    = false;

			foreach ( $existing as $cache ) {
				if ( is_array( $cache ) && ( $cache[0] ?? 0 ) === $post_id && ( $cache[1] ?? 0 ) === $email_id ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				add_post_meta( $post_id, $meta_key, array( $post_id, $email_id ) );
			}
		}

		$result = noptin_send_email_campaign(
			$rule->get_action_setting( 'automated_email_id' ),
			$smart_tags
		);

		if ( is_wp_error( $result ) ) {
			throw new \Exception( esc_html( $result->get_error_message() ) );
		}

		if ( empty( $result ) ) {
			$error = Main::get_phpmailer_last_error();
			throw new \Exception( 'Failed sending an email' . ( $error ? ': ' . esc_html( $error ) : '' ) );
		}

		return $result;
	}

	/**
	 * Checks if we can send an email.
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public static function can_run_send_email_action( $subject, $rule, $args ) {
		global $noptin_subscribers_batch_action;

		// Abort if we do not have a campaign.
		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );
		if ( empty( $automated_email_id ) ) {
			throw new \Exception( 'No email campaign found for automation rule:- ' . esc_html( $rule->get_id() ) );
		}

		// ... or if we're importing subscribers.
		if ( 'import' === $noptin_subscribers_batch_action && 'noptin_subscribers_import_item' !== $rule->get_trigger_id() ) {
			if ( ! apply_filters( 'noptin_can_send_email_campaign_while_importing_subscribers', false, $rule ) ) {
				throw new \Exception( 'Cannot send email campaign while importing subscribers' );
			}
		}

		$campaign = noptin_get_email_campaign_object( $automated_email_id );
		$can_send = $campaign->can_send();

		if ( is_wp_error( $can_send ) ) {
			throw new \Exception( esc_html( $can_send->get_error_message() ) );
		}

		if ( ! $can_send ) {
			throw new \Exception( 'Email campaign cannot be sent:- ' . esc_html( $campaign->get( 'name' ) ) );
		}

		if ( absint( $campaign->get( 'automation_rule' ) ) !== absint( $rule->get_id() ) ) {
			throw new \Exception( 'Email campaign does not belong to the automation rule:- ' . esc_html( $campaign->get( 'name' ) ) );
		}

		// Avoid resending the email when a post is re-published.
		if ( ! empty( $args['post_meta'] ) ) {
			$post_id  = (int) $args['post_meta']['id'];
			$existing = get_post_meta( $post_id, $args['post_meta']['key'] );

			foreach ( $existing as $cache ) {
				if ( is_array( $cache ) && ( $cache[0] ?? 0 ) === $post_id && ( $cache[1] ?? 0 ) === $campaign->id ) {
					throw new \Exception( 'Email campaign has already been sent:- ' . esc_html( $campaign->get( 'name' ) ) );
				}
			}
		}

		return true;
	}

	/**
	 * Deletes a record.
	 *
	 * @param array $settings The action settings.
	 */
	public static function delete_record_action( $settings ) {
		if ( ! empty( $settings['campaign'] ) ) {
			$campaign = noptin_get_email_campaign_object( $settings['campaign'] );

			if ( $campaign->exists() ) {
				$campaign->delete();
			}
		}
	}

	/**
	 * Checks if we can delete a record.
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public static function can_delete_record_action( $subject, $rule, $args ) {
		$campaign = $rule->get_action_setting( 'campaign' );
		if ( empty( $campaign ) || ! is_numeric( $campaign ) ) {
			throw new \Exception( 'No campaign ID specified for deletion' );
		}

		$campaign = noptin_get_email_campaign_object( $campaign );
		if ( ! $campaign->exists() ) {
			throw new \Exception( 'Campaign does not exist for deletion:- ID #' . esc_html( $campaign ) );
		}
		return true;
	}

	/**
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public static function conditional_logic_skip_tags( $tags, $rule = null ) {
		// Abort if we do not have a campaign.
		if ( ! empty( $rule ) ) {
			$automated_email_id = $rule->get_action_setting( 'automated_email_id' );
		} else {
			$automated_email_id = empty( Main::$current_email ) ? null : Main::$current_email->id;
		}

		if ( empty( $automated_email_id ) ) {
			return $tags;
		}

		$campaign = noptin_get_email_campaign_object( $automated_email_id );

		if ( ! $campaign->is_mass_mail() || ! noptin_has_alk() ) {
			return $tags;
		}

		$collection = apply_filters( 'noptin_' . $campaign->get_sender() . '_email_sender_collection_object', false );

		if ( ! is_object( $collection ) ) {
			return $tags;
		}

		$collection_tags = new \Hizzle\Noptin\Objects\Tags( $collection->type );
		return array_merge(
			$tags,
			array_keys( $collection_tags->tags )
		);
	}

	/**
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public static function prepare_skipped_rules( $conditional_logic, $rule ) {
		if ( ! noptin_has_alk() ) {
			return;
		}

		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );

		$GLOBALS[ 'noptin_email_' . $automated_email_id . '_extra_conditional_logic' ] = $conditional_logic;
	}
}
