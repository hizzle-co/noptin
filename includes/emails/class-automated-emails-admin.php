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

		add_action( 'noptin_email_campaigns_tab_automations_main', array( $this, 'render_main_admin_page' ) );
		add_action( 'noptin_email_campaigns_tab_automations_edit_campaign', array( $this, 'render_edit_form' ) );
		add_action( 'noptin_email_campaigns_tab_automations_new_campaign', array( $this, 'render_new_campaign_form' ) );
		add_action( 'add_meta_boxes_noptin_automations', array( $this, 'register_metaboxes' ) );
		add_action( 'noptin_save_edited_automation', array( $this, 'maybe_save_automation' ) );

		// Backwards compat.
		add_action( 'noptin_email_campaigns_tab_automations_view_campaigns', array( $this, 'render_main_admin_page' ) );
	}

	/**
	 * Render the main newsletters admin page.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_main_admin_page( $tabs ) {
		include plugin_dir_path( __FILE__ ) . 'class-automated-emails-table.php';
		include plugin_dir_path( __FILE__ ) . 'views/automations/view-automations.php';
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
		if ( ! is_numeric( $_GET['campaign'] ) ) {
			$title = __( 'Add New Automation', 'newsletter-optin-box' );
		} else {
			$title = __( 'Edit Automation', 'newsletter-optin-box' );

			if ( ! is_noptin_campaign( (int) $_GET['campaign'], 'automation' ) ) {
				include plugin_dir_path( __FILE__ ) . 'views/404.php';
				return;
			}

		}

		// Prepare automated email object.
		$campaign = new Noptin_Automated_Email( $_GET['campaign'] );

		$automation_type = $campaign->type;
		do_action( 'add_meta_boxes_noptin_automations', $campaign, $automation_type, array() );
		do_action( "add_meta_boxes_noptin_automations_$automation_type", $campaign, array() );
		include plugin_dir_path( __FILE__ ) . 'views/automations/edit-automation.php';

	}

	/**
	 * Displays the new campaign form.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_new_campaign_form( $tabs ) {
		include plugin_dir_path( __FILE__ ) . 'views/automations/new-automation.php';
	}

	/**
	 * Registers newsletter metaboxes.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function register_metaboxes( $campaign ) {

		// Campaign options.
		add_meta_box(
			'noptin_automation_options',
			__( 'Options','newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-automation',
			'side',
			'high',
			'options'
		);

		// Saves the campaign.
		add_meta_box(
			'noptin_automation_save',
			__( 'Save','newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-automation',
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

		if ( is_array( $metabox['args'] ) ) {
			call_user_func( $metabox['args'], $campaign );
			return;
		}

		include plugin_dir_path( __FILE__ ) . "views/automations/{$metabox['args']}.php";
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
		$automation = new Noptin_Automated_Email( wp_unslash( $_POST['noptin_automation'] ) );
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
			wp_safe_redirect( get_noptin_automation_campaign_url( $automation->id ) );
			exit;
		}

	}

}
