<?php
/**
 * Emails API: Emails Manager.
 *
 * Contains the main class for Noptin emails
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main class for Noptin emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
#[AllowDynamicProperties]
class Noptin_Email_Manager {

	/** @var Noptin_Email_Sender */
	public $sender;

	/** @var Noptin_Emails_Admin */
	public $admin;

	/** @var Noptin_Email_Tags */
	public $tags;

	/** @var Noptin_Automated_Email_Types */
	public $automated_email_types;

	/** @var Noptin_Newsletter_Email_Type */
	public $newsletter;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		// Load files.
		$this->load_files();

		// Init class properties.
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Add hooks.
		add_action( 'plugins_loaded', array( $this, 'add_hooks' ) );

	}

	/**
	 * Loads required files.
	 */
	public function load_files() {

		require_once plugin_dir_path( __FILE__ ) . 'emails.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-sender.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-generator.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-html-to-text.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-tags.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-automated-email.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-newsletter-email.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-newsletter-email-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-type-automation-rule.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-types.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-mass-mailer.php';

	}

	/**
	 * Init class properties.
	 */
	public function init() {
		$this->sender                = new Noptin_Email_Sender();
		$this->admin                 = new Noptin_Emails_Admin();
		$this->tags                  = new Noptin_Email_Tags();
		$this->automated_email_types = new Noptin_Automated_Email_Types();
		$this->newsletter            = new Noptin_Newsletter_Email_Type();

		do_action( 'noptin_email_manager_init', $this );
	}

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {

		// Delete related meta whenever a campaign is deleted.
		add_action( 'delete_post', array( $this, 'delete_stats' ) );

		// Periodically delete sent campaigns.
		add_action( 'noptin_daily_maintenance', array( $this, 'maybe_delete_campaigns' ) );

		$this->sender->add_hooks();
		$this->admin->add_hooks();
		$this->tags->add_hooks();
		$this->newsletter->add_hooks();
		$this->automated_email_types->add_hooks();
	}

	/**
	 * Deletes campaign stats when the campaign is deleted.
	 *
	 * @param int $post_id the campaign whose stats should be deleted.
	 */
	public function delete_stats( $post_id ) {
		global $wpdb;

		delete_noptin_subscriber_meta_by_key( "_campaign_$post_id" );

		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key' => "_campaign_$post_id",
			)
		);

	}

	/**
	 * Deletes sent campaigns.
	 *
	 */
	public function maybe_delete_campaigns() {

		$save_days = (int) get_noptin_option( 'delete_campaigns', 0 );
		if ( empty( $save_days ) ) {
			return;
		}

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'noptin-campaign',
			'fields'         => 'ids',
			'date_query'     => array(
				'before' => "-$save_days days",
			),
			'meta_query'     => array(
				array(
					'key'   => 'completed',
					'value' => '1',
				),
				array(
					'key'   => 'campaign_type',
					'value' => 'newsletter',
				),
			),
		);

		foreach ( get_posts( $args ) as $post_id ) {
			wp_delete_post( $post_id, true );
		}

	}

}
