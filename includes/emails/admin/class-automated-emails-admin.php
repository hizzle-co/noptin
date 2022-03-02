<?php
/**
 * Emails API: Automated Emails Admin.
 *
 * Contains the main admin class for Noptin automated emails
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin automated emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Automated_Emails_Admin {

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {

		add_action( 'noptin_email_campaigns_tab_automations_edit_campaign', array( $this, 'render_edit_form' ) );
		add_action( 'add_meta_boxes_noptin_automations', array( $this, 'register_metaboxes' ) );
		add_action( 'noptin_save_edited_automation', array( $this, 'maybe_save_automation' ) );

	}

	/**
	 * Displays the edit campaign form.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_edit_form( $tabs ) {

		// Check if there is a campaign.
		if ( empty( $_GET['campaign'] ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/404.php';
			return;
		}

		// Creating a new campaign.
		if ( is_numeric( $_GET['campaign'] ) && ! is_noptin_campaign( (int) $_GET['campaign'], 'automation' ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/404.php';
			return;
		}

		// Prepare automated email object.
		$campaign = new Noptin_Automated_Email( $_GET['campaign'] );

		$automation_type = $campaign->type;
		do_action( 'add_meta_boxes_noptin_automations', $campaign, $automation_type, array() );
		do_action( "add_meta_boxes_noptin_automations_$automation_type", $campaign, array() );
		include plugin_dir_path( __FILE__ ) . 'views/automations/view-edit-automation.php';

	}

	/**
	 * Registers newsletter metaboxes.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function register_metaboxes( $campaign ) {

		// Email timing.
		if ( $campaign->supports_timing() ) {

			add_meta_box(
				'noptin_automation_timing',
				__( 'Timing','newsletter-optin-box' ),
				array( $this, 'render_metabox' ),
				get_current_screen()->id,
				'side',
				'high',
				'timing'
			);

		}

		// Saves the campaign.
		add_meta_box(
			'noptin_automation_save',
			__( 'Save','newsletter-optin-box' ) . '<a class="noptin-send-test-email" style="cursor: pointer;color: red;">' . __( 'Send a test email', 'newsletter-optin-box' ) . '</a>',
			array( $this, 'render_metabox' ),
			get_current_screen()->id,
			'side',
			'default',
			'save'
		);

	}

	/**
	 * Displays a metabox.
	 *
	 */
	public function render_metabox( $campaign, $metabox ) {
		include plugin_dir_path( __FILE__ ) . "views/automations/metabox-{$metabox['args']}.php";
	}

	/**
	 * Saves an automated email
	 */
	public function maybe_save_automation() {

		// Ensure that this is not an ajax request.
		if ( wp_doing_ajax() ) {
			return;
		}

		// And that the current user can save a campaign.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-edit-automation-nonce'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['noptin-edit-automation-nonce'], 'noptin-edit-automation' ) ) {
			return;
		}

		// Save automation.
		$automation = new Noptin_Automated_Email( wp_unslash( $_POST['noptin_email'] ) );
		$result     = $automation->save();

		if ( is_wp_error( $result ) ) {
			noptin()->admin->show_error( $result );
		} else if ( false === $result ) {
			noptin()->admin->show_error( __( 'Could not save your changes.', 'newsletter-optin-box' ) );
		} else {
			noptin()->admin->show_success( __( 'Your changes were saved successfully', 'newsletter-optin-box' ) );
		}

		// Redirect to automation edit page.
		if ( $automation->exists() ) {
			wp_safe_redirect( $automation->get_edit_url() );
			exit;
		}

	}

}
