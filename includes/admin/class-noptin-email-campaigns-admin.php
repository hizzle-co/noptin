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
	public function __construct() {

		// Display the newsletters page.
		add_action( 'noptin_email_campaigns_tab_newsletters', array( $this, 'show_newsletters' ) );

		// Display the automations page.
		add_action( 'noptin_email_campaigns_tab_automations', array( $this, 'show_automations' ) );

		// Maybe save campaigns.
		add_action( 'noptin_edit_newsletter', array( $this, 'maybe_save_campaign' ) );
		add_action( 'noptin_save_edited_automation', array( $this, 'maybe_save_automation' ) );

		// Maybe send a campaign.
		add_action( 'transition_post_status', array( $this, 'maybe_send_campaign' ), 100, 3 );

		// Delete campaign stats.
		add_action( 'delete_post', array( $this, 'maybe_delete_stats' ) );

		// Filter wp users by meta query.
		add_filter( 'pre_get_users', array( $this, 'filter_users_by_campaign' ) );
	}

	/**
	 *  Retrieves campaign meta data
	 */
	public static function get_meta() {
		return apply_filters( 'noptin_get_newsletter_campaign_meta', array() );
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
		get_noptin_template( 'newsletters/add-newsletter.php', compact( 'id', 'tabs', 'campaign' ) );

		/**
		 * Runs after displaying the newsletters edit page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_after_newsletter_edit_page', $id, $campaign, $tabs );

	}


	/**
	 *  Displays the automations section.
	 */
	public function show_automations( $tabs ) {

		$sub_section = empty( $_GET['sub_section'] ) ? 'view_campaigns' : trim( $_GET['sub_section'] );

		if ( 'view_campaigns' === $sub_section ) {
			$this->view_automation_campaigns( $tabs );
		}

		if ( 'edit_campaign' === $sub_section ) {
			$this->edit_automation_campaign( $tabs );
		}
	}

	/**
	 *  Displays a list of available automations
	 */
	private function view_automation_campaigns( $tabs ) {

		/**
		 * Runs before displaying the automations overview page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_before_automations_overview_page', $tabs );

		$triggers = $this->get_automation_triggers();
		$table    = new Noptin_Email_Automations_Table();
		$table->prepare_items();

		get_noptin_template( 'automations/view-automations.php', compact( 'table', 'tabs', 'triggers' ) );

		/**
		 * Runs after displaying the automations overview page.
		 *
		 * @param array $tabs The available email campaign tabs.
		 */
		do_action( 'noptin_after_automations_overview_page', $tabs );

	}

	/**
	 *  Returns a list of all automations
	 */
	public function get_automation_triggers() {

		$triggers = array(
			'post_notifications' => array(
				'setup_title'    => __( 'new post notification', 'newsletter-optin-box' ),
				'title'          => __( 'Post Notifications', 'newsletter-optin-box' ),
				'description'    => __( 'Notify your subscribers everytime you publish new content.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After new content is published', 'newsletter-optin-box' ),
				'support_filter' => true,
			),
			'welcome_email'      => array(
				'setup_title'    => __( 'welcome email', 'newsletter-optin-box' ),
				'title'          => __( 'Welcome Email', 'newsletter-optin-box' ),
				'description'    => __( 'Introduce yourself to new subscribers or set up a series of welcome emails to act as an email course.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After someone subscribes', 'newsletter-optin-box' ),
				'support_filter' => __( 'All new subscribers', 'newsletter-optin-box' ),
			),
			'subscriber_tag'     => array(
				'setup_title'    => __( 'subscriber tag automation', 'newsletter-optin-box' ),
				'title'          => __( 'Subscriber Tag', 'newsletter-optin-box' ),
				'description'    => __( 'Send an email to a subscriber when you tag them.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After a subscriber is tagged', 'newsletter-optin-box' ),
			),
			'subscriber_list'    => array(
				'setup_title'    => __( 'subscriber list automation', 'newsletter-optin-box' ),
				'title'          => __( 'Subscriber List', 'newsletter-optin-box' ),
				'description'    => __( 'Send an email to a subscriber when they join a given email list.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After a subscriber is added to a list', 'newsletter-optin-box' ),
			),
		);

		return apply_filters( 'noptin_email_automation_triggers', $triggers, $this );

	}

	/**
	 *  Displays the automation campaign creation form.
	 *
	 * @param int $id the form being rendered.
	 */
	private function edit_automation_campaign( $tabs ) {

		if ( empty( $_GET['id'] ) ) {
			get_noptin_template( 'newsletters/404.php', array() );
			return;
		}

		$campaign_id = trim( $_GET['id'] );

		// Prepare the campaign being edited.
		$campaign_id = absint( $campaign_id );
		$campaign    = get_post( $campaign_id );

		if ( is_noptin_campaign( $campaign, 'automation' ) ) {

			$automation_type   = esc_html( stripslashes_deep( get_post_meta( $campaign_id, 'automation_type', true ) ) );
			$automations_types = $this->get_automation_triggers();
			$this->register_automation_metaboxes( $campaign, $automation_type, $automations_types );
			do_action( 'add_meta_boxes_noptin_automations', $campaign, $automation_type, $automations_types );
			do_action( "add_meta_boxes_noptin_automations_$automation_type", $campaign, $automations_types );
			get_noptin_template( 'automations/edit-automation.php', compact( 'campaign_id', 'tabs', 'campaign', 'automation_type', 'automations_types' ) );

		} else {
			get_noptin_template( 'newsletters/404.php', array() );
		}

	}

	/**
	 *  Saves an automation campaign
	 */
	public function maybe_save_automation() {

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
		$data = wp_unslash( $_POST );
		$id   = (int) $data['id'];

		unset( $data['noptin-edit-newsletter-nonce'] );
		unset( $data['noptin_admin_action'] );
		unset( $data['id'] );
		unset( $data['_wp_http_referer'] );
		unset( $data['meta-box-order-nonce'] );
		unset( $data['closedpostboxesnonce'] );
		unset( $data['save'] );

		// Prepare post status.
		$status = $data['status'];
		unset( $data['status'] );

		// Prepare post args.
		$post = array(
			'ID'           => $id,
			'post_status'  => $status,
			'post_type'    => 'noptin-campaign',
			'post_content' => $data['email_body'],
		);

		unset( $data['email_body'] );
		$post['meta_input'] = $data;

		foreach ( self::get_meta() as $meta_key ) {
			$post['meta_input'][ $meta_key ] = empty( $data[ $meta_key ] ) ? '' : noptin_clean( $data[ $meta_key ] );
		}

		$post = apply_filters( 'noptin_save_automation_campaign_details', $post, $data );

		$post = wp_update_post( $post, true );

		if ( is_wp_error( $post ) ) {
			noptin()->admin->show_error( $post->get_error_message() );
		} else {
			noptin()->admin->show_success( __( 'Your changes were saved successfully', 'newsletter-optin-box' ) );
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
			'edit_date'     => true,
			'post_title'    => trim( $data['email_subject'] ),
			'post_content'  => $data['email_body'],
			'meta_input'    => array(
				'email_sender'            => empty( $data['email_sender'] ) ? 'noptin' : sanitize_key( $data['email_sender'] ),
				'campaign_type'           => 'newsletter',
				'preview_text'            => empty( $data['preview_text'] ) ? '' : esc_html( $data['preview_text'] ),
			),
		);

		foreach ( self::get_meta() as $meta_key ) {
			$post['meta_input'][ $meta_key ] = empty( $data[ $meta_key ] ) ? '' : noptin_clean( $data[ $meta_key ] );
		}

		// Are we scheduling the campaign?
		if ( 'publish' === $status && ! empty( $data['schedule-date'] ) ) {

			$datetime = date_create( $data['schedule-date'], wp_timezone() );

			if ( false !== $datetime ) {

				$post['post_status']   = 'future';
				$post['post_date']     = $datetime->format( 'Y-m-d H:i:s' );
				$post['post_date_gmt'] = get_gmt_from_date( $datetime->format( 'Y-m-d H:i:s' ) );

			}

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
	public function maybe_delete_stats( $post_id ) {
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

		log_noptin_message(
			sprintf(
				__( 'Sending the campaign: "%s"', 'newsletter-optin-box' ),
				esc_html( $post->post_title )
			)
		);

		$noptin = noptin();

		$item = array(
			'campaign_id'       => $post->ID,
			'subscribers_query' => array(), // By default, send this to all active subscribers.
			'custom_merge_tags' => array(),
			'campaign_data'     => array(
				'campaign_id'   => $post->ID,
				'email_body'    => $post->post_content,
				'email_subject' => $post->post_title,
				'preview_text'  => get_post_meta( $post->ID, 'preview_text', true ),
			),
		);

		foreach ( array( 'custom_merge_tags', 'subscribers_query', 'recipients' ) as $key ) {

			$meta_value = get_post_meta( $post->ID, $key, true );
			if ( ! empty( $meta_value ) ) {
				$item[ $key ] = map_deep( $meta_value, 'wp_kses_post' );
			}

		}

		if ( apply_filters( 'noptin_should_send_campaign', true, $item ) ) {

			$sender = get_noptin_email_sender( $post->ID );

			if ( 'noptin' == $sender ) {
				$noptin->bg_mailer->push_to_queue( $item );
				$noptin->bg_mailer->save()->dispatch();
			} else {
				do_action( "handle_noptin_email_sender_$sender", $item, $post );
			}

		}

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
		$tab  = ! empty( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'newsletters';

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
	 * Registers automation metaboxes.
	 *
	 * @since 1.2.9
	 */
	public function register_automation_metaboxes( $campaign, $automation_type, $automations ) {

		add_meta_box(
			'noptin_automation_body',
			__('Email Content','newsletter-optin-box'),
			array( $this, 'render_automation_metabox' ),
			'noptin_page_noptin-automation',
			'normal',
			'default',
			'body'
		);

		add_meta_box(
			'noptin_automation_save',
			__('Save','newsletter-optin-box'),
			array( $this, 'render_automation_metabox' ),
			'noptin_page_noptin-automation',
			'side',
			'high',
			'save'
		);

		if ( 'post_notifications' == $automation_type && ! empty( $automations[ $automation_type ]['setup_cb'] ) ) {

			add_meta_box(
				'noptin_automation_setup_cb',
				__('Options','newsletter-optin-box'),
				array( $this, 'render_automation_setup_metabox' ),
				'noptin_page_noptin-automation',
				'advanced',
				'default',
				$automations[ $automation_type ]['setup_cb']
			);

		}

		add_meta_box(
			'noptin_automation_preview_text',
			__('Preview Text (Optional)','newsletter-optin-box'),
			array( $this, 'render_automation_metabox' ),
			'noptin_page_noptin-automation',
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

	/**
	 * Displays an automation metabox.
	 *
	 * @since 1.2.9
	 */
	public function render_automation_metabox( $campaign, $metabox ) {
		get_noptin_template( "automations/{$metabox['args']}.php", array( 'campaign' => $campaign ) );
	}

	/**
	 * Displays the setup metabox.
	 *
	 * @since 1.2.9
	 */
	public function render_automation_setup_metabox( $campaign, $cb ) {
		call_user_func( $cb['args'], $campaign );
	}

	/**
	 * Filters the users query.
	 *
	 * @param WP_User_Query $query
	 */
	public function filter_users_by_campaign( $query ) {
		global $pagenow;

		if ( is_admin() && 'users.php' == $pagenow && isset( $_GET['noptin_meta_key'] ) ) {

			$meta_query   = $query->get( 'meta_query' );
			$meta_query   = empty( $meta_query ) ? array() : $meta_query;
			$meta_query[] = array(
				'key'   => sanitize_text_field( $_GET['noptin_meta_key'] ),
				'value' => (int) $_GET['noptin_meta_value']
			);
			$query->set( 'meta_query', $meta_query );

		}

	}

}
