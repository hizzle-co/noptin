<?php

/**
 * Provides hooks for displaying various email campaign sections
 */

/**
 * Email campaigns table class.
 */
class Noptin_Email_Campaigns_Admin {

	/**
	 *  Constructor function.
	 */
	function __construct() {

		// Display the newsletters page.
		add_action( 'noptin_email_campaigns_tab_newsletters', array( $this, 'show_newsletters' ) );

		// Display the automations page.
		add_action( 'noptin_email_campaigns_tab_automations', array( $this, 'show_automations' ) );
		add_action( 'noptin_automations_section_view_campaigns', array( $this, 'view_automation_campaigns' ) );
		add_action( 'noptin_automations_section_edit_campaign', array( $this, 'render_automation_campaign_form' ) );

		// Maybe save campaigns.
		add_action( 'noptin_edit_newsletter', array( $this, 'maybe_save_campaign' ) );
		add_action( 'wp_loaded', array( $this, 'maybe_save_automation_campaign' ) );

		// Maybe send a campaign.
		add_action( 'transition_post_status', array( $this, 'maybe_send_campaign' ), 100, 3 );

		// Delete campaign stats.
		add_action( 'delete_post', array( $this, 'maybe_delete_stats' ) );

	}

	/**
	 *  Displays the newsletters section
	 */
	public function show_newsletters( $tabs ) {

		$sub_section = empty( $_GET['sub_section'] ) ? 'view_campaigns' : $_GET['sub_section'];

		if ( 'view_campaigns' == $sub_section ) {
			$this->view_newsletter_campaigns( $tabs );
		}

		if ( 'edit_campaign' == $sub_section ) {
			$this->render_edit_newsletter_campaign_form( $tabs );
		}

		if ( 'new_campaign' == $sub_section ) {
			$this->render_new_newsletter_campaign_form( $tabs );
		}

	}

	/**
	 *  Displays a list of available newsletters
	 */
	private function view_newsletter_campaigns( $tabs ) {

		/**
		 * Runs before displaying the newsletters overview page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_before_newsletters_overview_page', $tabs );

		$table = new Noptin_Email_Newsletters_Table();
		$table->prepare_items();

		get_noptin_template( 'newsletters/view-newsletters.php', compact( 'table', 'tabs' ) );

		/**
		 * Runs after displaying the newsletters overview page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_after_newsletters_overview_page', $tabs );

	}

	/**
	 *  Displays the edit campaign form.
	 */
	private function render_edit_newsletter_campaign_form( $tabs ) {

		$id       = empty( $_GET['id'] ) ? 0 : $_GET['id'];
		$campaign = false;

		if ( $id ) {
			$campaign = get_post( $id );
		}

		/**
		 * Runs before displaying the newsletter edit page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_before_newsletter_edit_page', $id, $campaign, $tabs );

		if ( ! empty( $campaign ) ) {
			
			$this->register_newsletter_metaboxes( $campaign );
			do_action( 'add_meta_boxes_noptin_newsletters', $campaign );
			do_action( 'add_meta_boxes', 'noptin_newsletters', $campaign );
			get_noptin_template( 'newsletters/edit-newsletter.php', compact( 'id', 'tabs', 'campaign' ) );

		} else {
			get_noptin_template( 'newsletters/404.php', array() );
		}

		/**
		 * Runs after displaying the newsletters edit page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_after_newsletter_edit_page', $id, $campaign, $tabs );

	}

	/**
	 *  Displays the new campaign form.
	 */
	private function render_new_newsletter_campaign_form( $tabs ) {

		$id       = 0;
		$campaign = false;

		/**
		 * Runs before displaying the newsletter edit page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_before_newsletter_edit_page', $id, $campaign, $tabs );

		$this->register_newsletter_metaboxes( $campaign );
		do_action( 'add_meta_boxes_noptin_newsletters', $campaign );
		do_action( 'add_meta_boxes', 'noptin_newsletters', $campaign );
		get_noptin_template( 'newsletters/add-newsletter.php', compact( 'id', 'tabs', 'campaign' ) );

		/**
		 * Runs after displaying the newsletters edit page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_after_newsletter_edit_page', $id, $campaign, $tabs );

	}


	/**
	 *  Displays the automations section
	 */
	function show_automations() {

		$sub_section = empty( $_GET['sub_section'] ) ? 'view_campaigns' : $_GET['sub_section'];

		/**
		 * Runs before displaying the automations section
		 */
		do_action( 'noptin_before_display_automations_section', $sub_section );

		/**
		 * Runs when displaying a specific automations section.
		 */
		do_action( "noptin_automations_section_$sub_section" );

		/**
		 * Runs after displaying the automations section
		 */
		do_action( 'noptin_after_display_automations_section', $sub_section );

	}

	/**
	 *  Displays a list of available automations
	 */
	function view_automation_campaigns() {

		$triggers = $this->get_automation_triggers();
		$table    = new Noptin_Email_Automations_Table();
		$table->prepare_items();

		add_thickbox();

		?>
		<div class="wrap">
			<form id="noptin-automation-campaigns-table" method="GET">
				<input type="hidden" name="page" value="noptin-email-campaigns"/>
				<input type="hidden" name="section" value="automations"/>
				<div class="noptin-campaign-action-links">
					<a href="#" class="button-secondary noptin-create-new-automation-campaign"><?php _e( 'Create New Automation', 'newsletter-optin-box' ); ?></a>
				</div>

				<?php $table->display(); ?>
				<p class="description"><?php _e( 'Use this page to create emails that will be automatically emailed to your subscribers', 'newsletter-optin-box' ); ?></p>
			</form>
			<div id="noptin-create-automation" style="display:none;">
				<?php get_noptin_template( 'new-email-automations-popup.php', compact( 'triggers' ) ); ?>
			</div>
		</div>
		<?php

	}

	/**
	 *  Returns a list of all automations
	 */
	function get_automation_triggers() {

		$triggers = array(
			'post_notifications' => array(
				'title'          => __( 'Post Notifications', 'newsletter-optin-box' ),
				'description'    => __( 'Notify your subscribers everytime you publish new content.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After new content is published', 'newsletter-optin-box' ),
				'support_filter' => true,
			),
			'welcome_email'      => array(
				'title'          => __( 'Welcome Email', 'newsletter-optin-box' ),
				'description'    => __( 'Introduce yourself to new subscribers or set up a series of welcome emails to act as an email course.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After someone subscribes', 'newsletter-optin-box' ),
				'support_filter' => __( 'All new subscribers', 'newsletter-optin-box' ),
			),
			'subscriber_tag'     => array(
				'title'         => __( 'Subscriber Tag', 'newsletter-optin-box' ),
				'description'   => __( 'Send an email to a subscriber when you tag them.', 'newsletter-optin-box' ),
				'support_delay' => __( 'After a subscriber is tagged', 'newsletter-optin-box' ),
			),
			'previous_email'     => array(
				'title'         => __( 'Previous Email', 'newsletter-optin-box' ),
				'description'   => __( 'Send an email to a subscriber when they open or click on a link in another email.', 'newsletter-optin-box' ),
				'support_delay' => true,
			),
		);

		return apply_filters( 'noptin_email_automation_triggers', $triggers, $this );

	}

	/**
	 *  Displays the automation campaign creation form.
	 *
	 * @param int $id the form being rendered.
	 */
	function render_automation_campaign_form( $id = 0 ) {

		if ( empty( $id ) && empty( $_GET['id'] ) ) {
			return;
		}

		if ( empty( $id ) ) {
			$id = trim( $_GET['id'] );
		}

		// Prepare the campaign being edited.
		$campaign_id = absint( $id );
		$campaign    = get_post( $id );

		// Ensure this is an automation campaign.
		if ( ! is_noptin_campaign( $campaign, 'automation' ) ) {
			return;
		}

		// Prepare data.
		$automation_type = sanitize_text_field( stripslashes_deep( get_post_meta( $campaign_id, 'automation_type', true ) ) );
		$preview_text    = sanitize_text_field( stripslashes_deep( get_post_meta( $campaign_id, 'preview_text', true ) ) );
		$subject         = sanitize_text_field( stripslashes_deep( get_post_meta( $campaign_id, 'subject', true ) ) );
		$email_body      = wp_kses_post( stripslashes_deep( $campaign->post_content ) );
		$automations     = $this->get_automation_triggers();
		$supports_filter = ! empty( $automations[ $automation_type ] ) && ! empty( $automations[ $automation_type ]['support_filter'] );
		$automations     = $this->get_automation_triggers();

		// Load the automation campign form.
		include locate_noptin_template( 'automation-campaign-form.php' );

	}

	/**
	 *  Saves an automation campaign
	 */
	function maybe_save_automation_campaign() {

		if ( wp_doing_ajax() ) {
			return;
		}

		$admin = noptin()->admin;

		if ( ! isset( $_POST['noptin-action'] ) || 'save-automation-campaign' !== $_POST['noptin-action'] ) {
			return;
		}

		// Verify nonce.
		if ( empty( $_POST['noptin_campaign_nonce'] ) || ! wp_verify_nonce( $_POST['noptin_campaign_nonce'], 'noptin_campaign' ) ) {
			return $admin->show_error( __( 'Unable to save your campaign', 'newsletter-optin-box' ) );
		}

		// Prepare data.
		$data = stripslashes_deep( $_POST );
		$id   = (int) $data['id'];

		unset( $data['noptin_campaign_nonce'] );
		unset( $data['noptin-action'] );
		unset( $data['id'] );

		// Prepare post status.
		$status = get_post_status( $id );

		if ( ! empty( $data['draft'] ) ) {
			$status = 'draft';
		}

		if ( ! empty( $data['publish'] ) ) {
			$status = 'publish';
		}

		unset( $data['publish'] );
		unset( $data['draft'] );

		// Prepare post args.
		$post = array(
			'ID'           => $id,
			'post_status'  => $status,
			'post_type'    => 'noptin-campaign',
			'post_content' => $data['email_body'],
		);

		unset( $data['email_body'] );
		$post['meta_input'] = $data;

		$post = apply_filters( 'noptin_save_automation_campaign_details', $post, $data );

		$post = wp_update_post( $post, true );

		if ( is_wp_error( $post ) ) {
			$admin->show_error( $post->get_error_message() );
		} else {
			$admin->show_success( __( 'Your changes were saved successfully', 'newsletter-optin-box' ) );
		}

	}

	/**
	 * Saves a newsletter campaign
	 */
	public function maybe_save_campaign() {

		// Ensure that this is not an ajax request.
		if ( wp_doing_ajax() ) {
			return;
		}

		// And that the current user can save a campaign.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-edit-newsletter-nonce'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['noptin-edit-newsletter-nonce'], 'noptin-edit-newsletter' ) ) {
			return;
		}

		// Prepare data.
		$data  = wp_unslash( $_POST );

		// For new campaigns, default the post status to draft.
		$id     = false;
		$status = 'draft';

		// For existing campaigns, default to the saved status.
		if ( ! empty( $data['campaign_id'] ) ) {
			$id     = (int) $data['campaign_id'];
			$status = ( 'draft' === get_post_status( $id ) ) ? 'draft' : 'publish';
		}

		if ( ! empty( $data['draft'] ) ) {
			$status = 'draft';
		}

		if ( ! empty( $data['publish'] ) ) {
			$status = 'publish';
		}

		// Prepare post args.
		$post = array(
			'post_status'   => $status,
			'post_type'     => 'noptin-campaign',
			'post_date'     => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', true ),
			'edit_date'     => 'true',
			'post_title'    => trim( $data['email_subject'] ),
			'post_content'  => $data['email_body'],
			'meta_input'    => array(
				'campaign_type'           => 'newsletter',
				'preview_text'            => empty( $data['preview_text'] ) ? '' : sanitize_text_field( $data['preview_text'] ),
			),
		);

		// Are we scheduling the campaign?
		if ( 'publish' === $status && ! empty( $data['schedule-date'] ) ) {

			$post['post_status']   = 'future';
			$post['post_date']     = date( 'Y-m-d H:i:s', strtotime( $data['schedule-date'] ) );
			$post['post_date_gmt'] = get_gmt_from_date( $post['post_date'] );

		}

		$post = apply_filters( 'noptin_save_newsletter_campaign_details', $post, $data );

		if ( empty( $id ) ) {
			$post = wp_insert_post( $post, true );
		} else {
			$post['ID'] = $id;
			$post       = wp_update_post( $post, true );
		}

		if ( is_wp_error( $post ) ) {
			return noptin()->admin->show_error( $post->get_error_message() );
		}

		$post = get_post( $post );

		if ( 'draft' === $post->post_status ) {
			noptin()->admin->show_success( __( 'Your email has been saved.', 'newsletter-optin-box' ) );
			wp_safe_redirect( get_noptin_newsletter_campaign_url( $post->ID ) );
			exit;
		}

		if ( 'future' === $post->post_status ) {
			noptin()->admin->show_success(
				sprintf(
					__( 'Your email has been scheduled to send on: %s', 'newsletter-optin-box' ),
					"<strong>{$post->post_date}</strong>"
				)
			);
		} else {
			noptin()->admin->show_success( __( 'Your email has been added to the sending qeue and will be sent soon.', 'newsletter-optin-box' ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=noptin-email-campaigns' ) );
		exit;

	}

	/**
	 *  Deletes campaign stats.
	 *
	 * @param int $post_id the form whose stats should be delete.
	 */
	function maybe_delete_stats( $post_id ) {
		global $wpdb;

		$table = get_noptin_subscribers_meta_table_name();
		$wpdb->delete(
			$table,
			array(
				'meta_key' => "_campaign_$post_id",
			)
		);

	}

	/**
	 *  (Maybe) Sends a newsletter campaign.
	 *
	 * @param string  $new_status The new campaign status.
	 * @param string  $old_status The old campaign status.
	 * @param WP_Post $post The new campaign post object.
	 */
	public function maybe_send_campaign( $new_status, $old_status, $post ) {

		// Maybe abort early.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Ensure this is a newsletter campaign.
		if ( 'noptin-campaign' === $post->post_type && 'newsletter' === get_post_meta( $post->ID, 'campaign_type', true ) ) {
			$this->send_campaign( $post );
		}

	}

	/**
	 * Sends a newsletter campaign.
	 *
	 * @param WP_Post $post The new campaign post object.
	 */
	public function send_campaign( $post ) {

		$noptin = noptin();

		$item = array(
			'campaign_id'       => $post->ID,
			'subscribers_query' => array(), // By default, send this to all active subscribers.
			'campaign_data'     => array(
				'campaign_id' => $post->ID,
				'template'    => locate_noptin_template( 'email-templates/paste.php' ),
			),
		);

		$noptin->bg_mailer->push_to_queue( $item );

		$noptin->bg_mailer->save()->dispatch();

	}

	/**
	 * Renders the email campaigns admin page..
	 *
	 *  @since 1.2.9
	 */
	public function render_campaigns_page() {

		// Fetch a list of all tabs.
		$tabs = array(
			'newsletters' => __( 'Newsletters', 'newsletter-optin-box' ),
			'automations' => __( 'Automated Emails', 'newsletter-optin-box' ),
		);

		$tabs = apply_filters( 'noptin_email_campaign_tabs', $tabs );
		$tab  = ! empty( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'newsletters';

		// Default to displaying the list of newsletters if no section is provided.
		if ( ! $tab || empty( $tabs[ $tab ] ) ) {
			$tab = 'newsletters';
		}

		/**
		 * Runs when displaying a specific tab's content.
		 *
		 * @param string $section
		 * @param string $sub_section
		 */
		do_action( "noptin_email_campaigns_tab_$tab", $tabs );

	}

	/**
	 * Registers newsletter metaboxes.
	 *
	 * @since 1.2.9
	 */
	public function register_newsletter_metaboxes( $campaign ) {

		add_meta_box(
			'noptin_newsletter_body',
			__('Email Content','newsletter-optin-box'),
			array( $this, 'render_newsletter_metabox' ),
			'noptin_page_noptin-newsletter',
			'normal',
			'default',
			'body'
		);

		add_meta_box(
			'noptin_newsletter_send',
			__('Send','newsletter-optin-box'),
			array( $this, 'render_newsletter_metabox' ),
			'noptin_page_noptin-newsletter',
			'side',
			'high',
			'send'
		);

		add_meta_box(
			'noptin_newsletter_preview_text',
			__('Preview Text (Optional)','newsletter-optin-box'),
			array( $this, 'render_newsletter_metabox' ),
			'noptin_page_noptin-newsletter',
			'side',
			'low',
			'preview-text'
		);

	}

	/**
	 * Displays a newsletter metabox.
	 *
	 * @since 1.2.9
	 */
	public function render_newsletter_metabox( $campaign, $metabox ) {
		get_noptin_template( "newsletters/{$metabox['args']}.php", array( 'campaign' => $campaign ) );
	}

}
