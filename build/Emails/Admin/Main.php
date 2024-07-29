<?php
/**
 * Emails API: Emails Admin.
 *
 * Contains the main admin class for Noptin emails
 *
 * @since   2.3.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin emails.
 *
 * @since 2.3.0
 * @internal
 * @ignore
 */
class Main {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * @var \Hizzle\Noptin\Emails\Email[] Edited campaigns.
	 */
	private static $edited_campaigns = array();

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		add_action( 'admin_init', array( __CLASS__, 'maybe_do_action' ) );
		add_action( 'admin_menu', array( __CLASS__, 'email_campaigns_menu' ), 35 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Does an action
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public static function maybe_do_action() {

		if (
			! empty( $_REQUEST['noptin_campaign'] ) &&
			! empty( $_REQUEST['noptin_email_action'] ) &&
			! empty( $_REQUEST['noptin_email_action_nonce'] ) &&
			wp_verify_nonce( $_REQUEST['noptin_email_action_nonce'], 'noptin_email_action' )
		) {
			$method   = 'admin_' . $_REQUEST['noptin_email_action'];
			$campaign = new \Hizzle\Noptin\Emails\Email( intval( $_GET['noptin_campaign'] ) );

			// Abort if not exists.
			if ( ! $campaign->exists() ) {
				self::redirect_from_action_with_error( 'Invalid campaign.' );
			}

			if ( method_exists( __CLASS__, $method ) ) {
				call_user_func(
					array( __CLASS__, $method ),
					$campaign
				);
			} else {
				self::redirect_from_action_with_error( 'Invalid action.' );
			}
		}
	}

	/**
	 * Redirects from an action with an error.
	 *
	 * @since 3.0.0
	 */
	public static function redirect_from_action_with_error( $error ) {
		noptin()->admin->show_error( $error );
		wp_safe_redirect( remove_query_arg( array( 'noptin_email_action', 'noptin_email_action_nonce', 'noptin_campaign' ) ) );
		exit;
	}

	/**
	 * Redirects from an action with success.
	 *
	 * @since 3.0.0
	 */
	public static function redirect_from_action_with_success( $success ) {
		noptin()->admin->show_success( $success );
		wp_safe_redirect( remove_query_arg( array( 'noptin_email_action', 'noptin_email_action_nonce', 'noptin_campaign' ) ) );
		exit;
	}

	/**
	 * Manually sends a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @since 3.0.0
	 */
	public static function admin_force_send_campaign( $campaign ) {

		// Abort if not mass email.
		if ( ! $campaign->is_mass_mail() ) {
			self::redirect_from_action_with_error( 'Invalid campaign.' );
		}

		// Check permissions.
		if ( ! $campaign->current_user_can_edit() ) {
			self::redirect_from_action_with_error( 'You do not have permission to send this campaign.' );
		}

		define( 'NOPTIN_RESENDING_CAMPAIGN', true );

		// Set status to publish to allow sending.
		if ( 'publish' !== $campaign->status ) {
			if ( ! current_user_can( 'publish_post', $campaign->id ) ) {
				self::redirect_from_action_with_error( 'You do not have permission to send this campaign.' );
			}

			wp_publish_post( $campaign->id );
			$campaign->status = 'publish';
		}

		do_action( 'noptin_send_' . $campaign->type, $campaign );

		// Fire another hook for the automation type.
		if ( 'automation' === $campaign->type ) {
			do_action( 'noptin_send_' . $campaign->get_sub_type(), $campaign );
			do_action( 'noptin_send_' . $campaign->get_sub_type() . '_email', $campaign ); // Backwards compatibility.
		}

		// Check if the campaign exists.
		$message = apply_filters(
			'noptin_email_sent_successfully_message',
			__( 'Your email has been added to the sending queue and will be sent soon.', 'newsletter-optin-box' ),
			$campaign
		);

		self::redirect_from_action_with_success( $message );
	}

	/**
	 * Manually resends a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @since 3.0.0
	 */
	public static function admin_resend_campaign( $campaign ) {

		// Check if the user can publish the campaign.
		if ( ! current_user_can( 'publish_post', $campaign->id ) ) {
			self::redirect_from_action_with_error( 'You do not have permission to resend this campaign.' );
		}

		// Resend the campaign.
		delete_post_meta( $campaign->id, 'completed' );
		delete_post_meta( $campaign->id, 'paused' );
		delete_post_meta( $campaign->id, '_bulk_email_last_error' );
		do_action( 'noptin_newsletter_campaign_published', $campaign );

		self::redirect_from_action_with_success( __( 'Your email has been added to the sending queue and will be sent soon.', 'newsletter-optin-box' ) );
	}

	/**
	 * Manually pauses a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @since 3.0.0
	 */
	public static function admin_pause_campaign( $campaign ) {

		// Check if the user can publish the campaign.
		if ( ! current_user_can( 'publish_post', $campaign->id ) ) {
			self::redirect_from_action_with_error( 'You do not have permission to pause this campaign.' );
		}

		// Pause the campaign.
		update_post_meta( $campaign->id, 'paused', 1 );

		self::redirect_from_action_with_success( __( 'The campaign has been paused.', 'newsletter-optin-box' ) );
	}

	/**
	 * Duplicates an email campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @since 1.7.0
	 */
	public static function admin_duplicate_campaign( $campaign ) {

		// Check if the user can publish the campaign.
		if ( ! $campaign->current_user_can_edit() ) {
			self::redirect_from_action_with_error( 'You do not have permission to duplicate this campaign.' );
		}

		$args = array(
			'name' => $campaign->name . ' - ' . __( 'Copy', 'newsletter-optin-box' ),
		);

		if ( 'newsletter' === $campaign->type ) {
			$args['status'] = 'draft';
		}

		$duplicate = $campaign->duplicate( $args );

		if ( $duplicate instanceof \Hizzle\Noptin\Emails\Email && $duplicate->exists() ) {
			noptin()->admin->show_info( __( 'The campaign has been duplicated.', 'newsletter-optin-box' ) );
			wp_safe_redirect( $duplicate->get_edit_url() );
			exit;
		}

		// Redirect.
		$error = is_wp_error( $duplicate ) ? $duplicate->get_error_message() : __( 'Unable to duplicate the campaign.', 'newsletter-optin-box' );
		self::redirect_from_action_with_error( $error );
	}

	/**
	 * Deletes an email campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @since 1.7.0
	 */
	public static function admin_delete_campaign( $campaign ) {

		// Check if the user can delete the campaign.
		if ( ! $campaign->current_user_can_delete() ) {
			self::redirect_from_action_with_error( 'You do not have permission to delete this campaign.' );
		}

		// Delete the campaign.
		$campaign->delete();

		// Show success info.
		self::redirect_from_action_with_success( __( 'The campaign has been deleted.', 'newsletter-optin-box' ) );
	}

	/**
	 * Trashes an email campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @since 1.7.0
	 */
	public static function admin_trash_campaign( $campaign ) {

		// Check if the user can delete the campaign.
		if ( ! $campaign->current_user_can_delete() ) {
			self::redirect_from_action_with_error( 'You do not have permission to trash this campaign.' );
		}

		// Delete the campaign.
		$campaign->trash();

		// Show success info.
		self::redirect_from_action_with_success( __( 'The campaign has been trashed.', 'newsletter-optin-box' ) );
	}

	/**
	 * Restores a trashed email campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @since 1.7.0
	 */
	public static function admin_restore_campaign( $campaign ) {

		// Check if the user can edit the campaign.
		if ( ! $campaign->current_user_can_edit() ) {
			self::redirect_from_action_with_error( 'You do not have permission to restore this campaign.' );
		}

		// Restore the campaign.
		$campaign->restore();

		// Show success info.
		self::redirect_from_action_with_success( __( 'The campaign has been restored.', 'newsletter-optin-box' ) );
	}

	/**
	 * Email campaigns menu.
	 */
	public static function email_campaigns_menu() {

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-email-campaigns',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		$query_args = self::get_query_args();

		// Abort if unknown email type.
		if ( empty( $query_args['noptin_email_type'] ) || ! in_array( $query_args['noptin_email_type'], array_keys( \Hizzle\Noptin\Emails\Main::get_email_types() ), true ) ) {
			printf(
				'<div class="wrap"><div class="notice notice-error"><p>%s</p></div></div>',
				esc_html__( 'Unknown email type.', 'newsletter-optin-box' )
			);
			return;
		}

		$edited_campaign = self::prepare_edited_campaign( $query_args );

		// Check if we are editing a campaign.
		if ( ! empty( $edited_campaign ) ) {
			if ( 'not-found' === $edited_campaign->admin_screen ) {
				include plugin_dir_path( __FILE__ ) . 'views/404.php';
				return;
			}

			if ( ! $edited_campaign->current_user_can_edit() ) {
				include plugin_dir_path( __FILE__ ) . 'views/permission-denied.php';
				return;
			}

			include plugin_dir_path( __FILE__ ) . 'views/campaign.php';
		} else {

			// Include the campaigns view.
			include plugin_dir_path( __FILE__ ) . 'views/campaigns.php';
		}
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function enqueue_scripts( $hook ) {

		// Abort if not on the email campaigns page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		$query_args      = self::get_query_args();
		$edited_campaign = self::prepare_edited_campaign( $query_args );
		$script          = empty( $edited_campaign ) ? 'view-campaigns' : $edited_campaign->admin_screen;
		$type            = \Hizzle\Noptin\Emails\Main::get_email_type( $query_args['noptin_email_type'] );
		$localize_script = 'noptin-' . $script;

		// Load the js.
		if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/js/' . $script . '.js' ) ) {
			$config = include plugin_dir_path( __DIR__ ) . 'assets/js/' . $script . '.asset.php';

			if ( 'view-campaigns' === $script && $type && $type->supports_menu_order ) {
				// Enqueue jQuery UI core
				wp_enqueue_script( 'jquery-ui-core' );

				// Enqueue jQuery UI sortable plugin
				wp_enqueue_script( 'jquery-ui-sortable' );
			}

			// Prepare the block editor.
			if ( 'email-editor' === $script ) {
				$blocks = include plugin_dir_path( __DIR__ ) . 'assets/js/blocks.asset.php';
				wp_enqueue_script(
					'noptin-blocks',
					plugins_url( 'assets/js/blocks.js', __DIR__ ),
					$blocks['dependencies'],
					$blocks['version'],
					true
				);

				$config['dependencies'][] = 'noptin-blocks';
				$localize_script          = 'noptin-blocks';
			}

			wp_enqueue_script(
				'noptin-' . $script,
				plugins_url( 'assets/js/' . $script . '.js', __DIR__ ),
				$config['dependencies'],
				$config['version'],
				true
			);

			// Prepare the block editor.
			if ( 'email-editor' === $script ) {
				Editor::load( $edited_campaign );
			}

			// Localize the script.
			wp_localize_script(
				$localize_script,
				'noptinEmailSettingsMisc',
				apply_filters(
					'noptin_email_settings_misc',
					array(
						'isTest'       => defined( 'NOPTIN_IS_TESTING' ),
						'data'         => (object) ( empty( $type ) ? array() : $type->to_array() ),
						'from_name'    => get_noptin_option( 'from_name', get_option( 'blogname' ) ),
						'from_email'   => get_noptin_option( 'from_email', '' ),
						'reply_to'     => get_noptin_option( 'reply_to', get_option( 'admin_email' ) ),
						'integrations' => 'view-campaigns' === $script ? apply_filters( 'noptin_get_all_known_integrations', array() ) : array(),
						'senders'      => array_merge(
							array(
								'manual_recipients' => array(
									'label'        => __( 'Manual Recipients', 'newsletter-optin-box' ),
									'description'  => __( 'Send an email to a comma separated list of email addresses.', 'newsletter-optin-box' ),
									'image'        => array(
										'icon' => 'businessperson',
										'fill' => '#212121',
									),
									'is_active'    => true,
									'is_installed' => true,
									'is_local'     => true,
									'settings'     => array(
										'disableMergeTags' => false,
										'fields'           => array(
											'recipients' => array(
												'label' => __( 'Recipient(s)', 'newsletter-optin-box' ),
												'description' => sprintf(
													'%s<br /> <br />%s',
													__( 'Enter recipients (comma-separated) for this email.', 'newsletter-optin-box' ),
													sprintf(
														/* translators: %s: code */
														__( 'Add %s after an email to disable send, open and click tracking for that recipient.', 'newsletter-optin-box' ),
														'<code>--notracking</code>'
													)
												),
												'type'  => 'text',
												'placeholder' => sprintf(
													/* translators: %s: Example */
													__( 'For example, %s', 'newsletter-optin-box' ),
													'[[email]], ' . get_option( 'admin_email' ) . ' --notracking'
												),
											),
										),
									),
								),
							),
							get_noptin_email_senders( true )
						),
					),
					$script
				)
			);

			wp_set_script_translations( 'noptin-' . $script, 'newsletter-optin-box', noptin()->plugin_path . 'languages' );
		}

		// Load the css.
		wp_enqueue_style( 'wp-components' );

		if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $script . '.css' ) ) {
			$version = empty( $config ) ? filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $script . '.css' ) : $config['version'];
			wp_enqueue_style(
				'noptin-' . $script,
				plugins_url( 'assets/css/style-' . $script . '.css', __DIR__ ),
				'email-editor' === $script ? array( 'wp-block-editor', 'wp-edit-post', 'wp-format-library' ) : array(),
				$version
			);
		}
	}

	/**
	 * Retrieves the current query args.
	 *
	 * @return array
	 */
	public static function get_query_args() {

		$query_args = urldecode_deep( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Abort if unknown email type.
		if ( empty( $query_args['noptin_email_type'] ) ) {
			if ( ! empty( $query_args['noptin_campaign'] ) ) {
				$query_args['noptin_email_type'] = get_post_meta( intval( $query_args['noptin_campaign'] ), 'campaign_type', true );
			} else {
				$query_args['noptin_email_type'] = \Hizzle\Noptin\Emails\Main::get_default_email_type();
			}
		}

		return $query_args;
	}

	/**
	 * Checks the screen to load.
	 *
	 * @param array $query_args The current query args.
	 * @return \Hizzle\Noptin\Emails\Email|null
	 */
	public static function prepare_edited_campaign( $query_args ) {

		// If we expect a parent ID, check if it exists.
		if ( ! empty( $query_args['noptin_email_type'] ) && empty( $query_args['noptin_campaign'] ) ) {
			$type     = \Hizzle\Noptin\Emails\Main::get_email_type( sanitize_text_field( $query_args['noptin_email_type'] ) );
			$campaign = new \Hizzle\Noptin\Emails\Email( 0 );
			if ( $type && $type->parent_type ) {
				if ( empty( $query_args['noptin_parent_id'] ) ) {
					$campaign->admin_screen = 'not-found';
					return $campaign;
				}

				$parent = new \Hizzle\Noptin\Emails\Email( intval( $query_args['noptin_parent_id'] ) );

				if ( ! $parent->exists() ) {
					$campaign->admin_screen = 'not-found';
					return $campaign;
				}
			}
		}

		// Abort if no campaign is being edited.
		if ( ! isset( $query_args['noptin_campaign'] ) ) {
			return null;
		}

		// Check if we already have the campaign.
		$cache_key = md5( wp_json_encode( $query_args ) );

		if ( isset( self::$edited_campaigns[ $cache_key ] ) ) {
			return self::$edited_campaigns[ $cache_key ];
		}

		// Retrieve campaign object.
		self::$edited_campaigns[ $cache_key ] = new \Hizzle\Noptin\Emails\Email( intval( $query_args['noptin_campaign'] ) );

		$campaign = &self::$edited_campaigns[ $cache_key ];

		if ( $campaign->exists() ) {
			return $campaign;
		}

		if ( ! empty( $query_args['noptin_campaign'] ) ) {
			$campaign->admin_screen = 'not-found';
			return $campaign;
		}

		// Set the parent.
		if ( ! empty( $query_args['noptin_parent_id'] ) ) {
			$campaign->parent_id = intval( $query_args['noptin_parent_id'] );
		}

		// Set the type.
		$campaign->type = sanitize_text_field( $query_args['noptin_email_type'] );

		// Set the sub type.
		if ( ! empty( $query_args['noptin_email_sub_type'] ) ) {
			$campaign->options[ $campaign->type . '_type' ] = sanitize_text_field( $query_args['noptin_email_sub_type'] );
		}

		// Set the sender.
		if ( ! empty( $query_args['noptin_email_sender'] ) ) {
			$campaign->options['email_sender'] = sanitize_text_field( $query_args['noptin_email_sender'] );

			// Check if sender contains a merge tag...
			if ( false !== strpos( $campaign->options['email_sender'], '[[' ) && false !== strpos( $campaign->options['email_sender'], ']]' ) ) {
				$campaign->options['recipients']   = $campaign->options['email_sender'];
				$campaign->options['email_sender'] = 'manual_recipients';
			}

			// ... or an email address.
			if ( 0 < strpos( $campaign->options['email_sender'], '@' ) ) {
				$campaign->options['recipients']   = $campaign->options['email_sender'];
				$campaign->options['email_sender'] = 'manual_recipients';
			}
		}

		// Set the author.
		$campaign->author = get_current_user_id();

		// Check if we have manual recipients.
		if ( ! empty( $query_args['noptin_recipients'] ) ) {
			$campaign->options['manual_recipients_ids'] = noptin_parse_int_list( $query_args['noptin_recipients'] );
		}

		// Set the template.
		if ( ! empty( $query_args['noptin_email_template'] ) ) {
			$campaign->options['noptin_source_template'] = sanitize_text_field( $query_args['noptin_email_template'] );
		}

		return $campaign;
	}

	public static function load_script_translations( $script ) {
		/** @var \WP_Scripts $wp_scripts */
		global $wp_scripts;

		// Check if translations are registered for this script
		if ( ! isset( $wp_scripts->registered[ $script ] ) ) {
			return;
		}

		// Get the translations
		$translations = $wp_scripts->print_translations( $script, false );

		if ( ! empty( $translations ) ) {
			// Remove the <script> tags
			$translations = str_replace( array( '<script>', '</script>' ), '', $translations );

			// Print the translations
			wp_add_inline_script( 'wp-i18n', $translations, 'after' );
		}
	}
}
