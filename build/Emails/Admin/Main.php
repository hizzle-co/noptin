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
		add_filter( 'admin_body_class', array( __CLASS__, 'add_split_menu_body_class' ) );

		// Email settings.
		add_filter( 'noptin_get_settings', array( __CLASS__, 'email_settings' ), 10 );
	}

	/**
	 * Does an action
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public static function maybe_do_action() {

		// Dismiss external CRON notice.
		if ( ! empty( $_REQUEST['noptin_dismiss_cron_notice'] ) && wp_verify_nonce( $_REQUEST['noptin_dismiss_cron_notice'], 'noptin_dismiss_cron_notice' ) ) {
			update_user_meta( get_current_user_id(), 'noptin_dismiss_cron_notice', true );
			wp_safe_redirect( remove_query_arg( 'noptin_dismiss_cron_notice' ) );
		}

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
		wp_safe_redirect( remove_query_arg( array( 'noptin_email_action', 'noptin_email_action_nonce', 'noptin_campaign', 'noptin_email_recipients' ) ) );
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
		update_post_meta( $campaign->id, '_resend_to', $_REQUEST['noptin_email_recipients'] ?? 'all' );
		update_post_meta( $campaign->id, '_resent_on', gmdate( 'Y-m-d H:i:s e', time() ) );
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

		if ( isset( $_GET['name'] ) ) {
			$args['name'] = sanitize_text_field( wp_unslash( rawurldecode( $_GET['name'] ) ) );
		}

		if ( isset( $_GET['subject'] ) ) {
			$args['subject'] = sanitize_text_field( wp_unslash( rawurldecode( $_GET['subject'] ) ) );
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
		if ( noptin_should_split_emails_menu() ) {
			$email_types = array_values( \Hizzle\Noptin\Emails\Main::get_email_types() );

			foreach ( $email_types as $index => $type ) {
				if ( 'trash' === $type->type || ! empty( $type->parent_type ) ) {
					continue;
				}

				if ( empty( $index ) ) {
					self::$hook_suffix = add_submenu_page(
						'noptin',
						$type->plural_label,
						$type->plural_label,
						get_noptin_capability(),
						'noptin-email-campaigns',
						array( __CLASS__, 'render_admin_page' )
					);

					continue;
				}

				add_submenu_page(
					'noptin',
					$type->plural_label,
					$type->plural_label,
					get_noptin_capability(),
					add_query_arg(
						array(
							'page'              => 'noptin-email-campaigns',
							'noptin_email_type' => rawurlencode( $type->type ),
						),
						admin_url( '/admin.php' )
					),
					''
				);
			}
		} else {
			self::$hook_suffix = add_submenu_page(
				'noptin',
				esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
				esc_html__( 'Emails', 'newsletter-optin-box' ),
				get_noptin_capability(),
				'noptin-email-campaigns',
				array( __CLASS__, 'render_admin_page' )
			);
		}
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
	 * Slims down a settings schema for AI consumption.
	 * Removes noise keys and omits defaults (type=text, el=input).
	 *
	 * @param array $settings Raw settings schema.
	 * @return array
	 */
	private static function slim_settings_for_ai( $settings ) {
		$result = array();

		foreach ( $settings as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			// Recurse into grouped fields.
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$children = self::slim_settings_for_ai( $field['fields'] );
				if ( ! empty( $children ) ) {
					$result[ $key ] = $children;
				}
				continue;
			}

			$slim = array();

			$label = $field['label'] ?? $key;

			if ( ! empty( $field['description'] ) ) {
				$label .= ' (' . $field['description'] . ')';
			}

			if ( ! empty( $label ) ) {
				$slim['label'] = $label;
			}

			// type: only include if not the default 'text'.
			if ( ! empty( $field['type'] ) && 'text' !== $field['type'] ) {
				$slim['type'] = $field['type'];
			}

			// el: only include if not the default 'input'.
			if ( ! empty( $field['el'] ) && 'input' !== $field['el'] ) {
				$slim['el'] = $field['el'];
			}

			// options.
			if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
				$slim['options'] = $field['options'];
			}

			// default.
			if ( isset( $field['default'] ) && '' !== $field['default'] ) {
				$slim['default'] = $field['default'];
			}

			if ( ! empty( $slim ) ) {
				$result[ $key ] = $slim;
			}
		}

		return $result;
	}

	/**
	 * Loads AI script
	 */
	public static function load_ai_script( $edited_campaign = null ) {

		self::load_blocks_script( $edited_campaign );

		$ai = include plugin_dir_path( __DIR__ ) . 'assets/js/ai.asset.php';

		$ai['dependencies'][] = 'noptin-blocks';

		wp_register_script(
			'noptin-ai',
			plugins_url( 'assets/js/ai.js', __DIR__ ),
			$ai['dependencies'],
			$ai['version'],
			true
		);

		wp_enqueue_style(
			'noptin-ai',
			plugins_url( 'assets/css/style-ai.css', __DIR__ ),
			array(),
			$ai['version']
		);

		$current_user = wp_get_current_user();

		$brand_color = get_noptin_option( 'brand_color' );
		$brand_color = empty( $brand_color ) ? '#1a82e2' : $brand_color;

		$ai_localization = array(
			'email_types'         => array_filter(
				array_map(
					function ( $type ) {
						// Skip email_templates and trash.
						if ( 'email_template' === $type->type || 'trash' === $type->type ) {
							return null;
						}

						/** @var \Hizzle\Noptin\Emails\Types\Type $type */
						$to_return = array(
							'label'               => $type->label,
							'type'                => $type->type,
							'plural_label'        => $type->plural_label,
							'supports_recipients' => $type->supports_recipients,
							'supports_timing'     => $type->supports_timing,
							'supports_menu_order' => $type->supports_menu_order,
							'contexts'            => $type->contexts,
						);

						if ( $type->supports_sub_types ) {
							$to_return['types'] = array();

							foreach ( $type->get_sub_types() as $sub_type_key => $sub_type ) {
								if ( 0 === strpos( $sub_type_key, 'automation_rule_' ) || empty( $sub_type['category'] ) ) {
									continue;
								}

								$to_return['types'][ $sub_type_key ] = array(
									'label'       => $sub_type['label'],
									'description' => $sub_type['description'],
									'contexts'    => $sub_type['contexts'] ?? array(),
								);
							}
						}

						foreach ( array( 'child_type', 'parent_type' ) as $property ) {
							if ( $type->{$property} ) {
								$to_return[ $property ] = $type->{$property};
							}
						}

						return $to_return;
					},
					\Hizzle\Noptin\Emails\Main::get_email_types()
				)
			),
			'automation_triggers' => array_filter(
				array_map(
					function ( $trigger ) {
						/** @var \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger */
						if ( $trigger->depricated || empty( $trigger->category ) ) {
							return null;
						}

						$entry = array(
							'description' => $trigger->get_description(),
						);
						$settings = self::slim_settings_for_ai( $trigger->get_settings() );
						if ( ! empty( $settings ) ) {
							$entry['settings'] = $settings;
						}
						return $entry;
					},
					\Hizzle\Noptin\Automation_Rules\Triggers\Main::all()
				)
			),
			'automation_actions'  => array_filter(
				array_map(
					function ( $action ) {
						/** @var \Hizzle\Noptin\Automation_Rules\Actions\Action $action */

						if ( $action->depricated || empty( $action->category ) ) {
							return null;
						}

						$entry = array(
							'description' => $action->get_description(),
						);
						$settings = self::slim_settings_for_ai( $action->get_settings() );
						if ( ! empty( $settings ) ) {
							$entry['settings'] = $settings;
						}
						return $entry;
					},
					\Hizzle\Noptin\Automation_Rules\Actions\Main::all()
				)
			),
			'user'                => array(
				'id'    => $current_user->ID,
				'name'  => $current_user->display_name,
				'email' => $current_user->user_email,
			),
			'website'             => array(
				'name'        => get_bloginfo( 'name' ),
				'description' => get_bloginfo( 'description' ),
				'url'         => home_url(),
				'admin_email' => get_option( 'admin_email' ),
				'language'    => get_locale(),
				'brand_color' => $brand_color,
			),
		);

		$senders = array_merge(
			array(
				'manual_recipients' => array(
					'label'        => __( 'Specific People', 'newsletter-optin-box' ),
					'description'  => __( 'Enter one or more email addresses manually, separated by commas.', 'newsletter-optin-box' ),
					'is_installed' => true,
					'settings'     => array(
						'disableMergeTags' => false,
						'fields'           => array(
							'recipients' => array(
								'label'       => __( 'Recipient(s)', 'newsletter-optin-box' ),
								'description' => sprintf(
									'%s<br /> <br />%s',
									__( 'Enter recipients (comma-separated) for this email.', 'newsletter-optin-box' ),
									sprintf(
										/* translators: %s: code */
										__( 'Add %s after an email to disable send, open and click tracking for that recipient.', 'newsletter-optin-box' ),
										'<code>--notracking</code>'
									)
								),
								'type'        => 'text',
								'placeholder' => sprintf(
									/* translators: %s: The Example */
									__( 'For example, %s', 'newsletter-optin-box' ),
									'[[email]], ' . get_option( 'admin_email' ) . ' --notracking'
								),
							),
						),
					),
				),
			),
			get_noptin_email_senders( true )
		);

		$potential_senders = array();

		foreach ( $senders as $key => $sender ) {
			if ( ! is_array( $sender ) || empty( $sender['is_installed'] ) ) {
				continue;
			}

			$potential_senders[ $key ] = array(
				'label'       => $sender['label'] ?? $key,
				'description' => $sender['description'] ?? '',
				'settings'    => $sender['settings'] ?? array(),
			);
		}

		if ( ! empty( $potential_senders ) ) {
			$ai_localization['potential_senders'] = $potential_senders;
		}

		wp_add_inline_script(
			'noptin-ai',
			'window.noptinAIInfo = ' . wp_json_encode( $ai_localization ) . ';',
			'before'
		);
	}

	/**
	 * Loads blocks script
	 */
	public static function load_blocks_script( $edited_campaign = null ) {
		$blocks = include plugin_dir_path( __DIR__ ) . 'assets/js/blocks.asset.php';
		wp_enqueue_script(
			'noptin-blocks',
			plugins_url( 'assets/js/blocks.js', __DIR__ ),
			$blocks['dependencies'],
			$blocks['version'],
			true
		);

		$objects = apply_filters( 'noptin_email_editor_objects', array() );
		$blocks  = array();

		if ( $edited_campaign ) {
			/** @var \Hizzle\Noptin\Emails\Email|null $edited_campaign */
			foreach ( $edited_campaign->get_merge_tags() as $tag => $data ) {
				if ( ! empty( $data['block'] ) ) {
					$blocks[ $tag ] = array_merge(
						array(
							'description' => isset( $data['description'] ) ? $data['description'] : $data['label'],
							'mergeTag'    => $tag,
							'name'        => Editor::merge_tag_to_block_name( $tag ),
						),
						$data['block']
					);

					unset( $blocks[ $tag ]['metadata']['ancestor'] );
				}
			}
		}

		foreach ( wp_list_pluck( $objects, 'merge_tags' ) as $merge_tags ) {
			foreach ( $merge_tags as $tag => $merge_tag_data ) {
				if ( ! empty( $merge_tag_data['block'] ) && ! isset( $blocks[ $tag ] ) ) {
					$blocks[ $tag ] = array_merge(
						array(
							'description' => $merge_tag_data['description'] ?? $merge_tag_data['label'],
							'mergeTag'    => $tag,
							'name'        => Editor::merge_tag_to_block_name( $tag ),
						),
						$merge_tag_data['block']
					);
				}
			}
		}

		wp_localize_script(
			'noptin-blocks',
			'noptinEmailBlocksData',
			apply_filters(
				'noptin_email_blocks_data',
				array(
					'objects'       => (object) $objects,
					'dynamicBlocks' => array_values( $blocks ),
					'context'       => 'ai',
				)
			)
		);
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
		$disable_ai      = get_noptin_option( 'disable_ai', false );

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
			if ( 'view-campaigns' === $script && ! $disable_ai ) {
				self::load_ai_script( $edited_campaign );
				$config['dependencies'][] = 'noptin-ai';
			}

			if ( 'email-editor' === $script ) {
				self::load_blocks_script( $edited_campaign );

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
			$data = apply_filters(
				'noptin_email_settings_misc',
				array(
					'isTest'       => defined( 'NOPTIN_IS_TESTING' ),
					'data'         => (object) ( empty( $type ) ? array() : $type->to_array() ),
					'from_name'    => get_noptin_option( 'from_name', get_option( 'blogname' ) ),
					'from_email'   => get_noptin_option( 'from_email', '' ),
					'reply_to'     => get_noptin_option( 'reply_to', get_option( 'admin_email' ) ),
					'integrations' => 'view-campaigns' === $script ? apply_filters( 'noptin_get_all_known_integrations', array() ) : array(),
					'utm_enabled'  => get_noptin_option( 'add_utm_params', true ),
					'utm_docs_url' => noptin_get_guide_url( 'Settings', 'sending-emails/utm-parameters/' ),
					'assets_url'   => plugins_url( 'static/images/', __DIR__ ),
					'brand'        => noptin()->white_label->get_details(),
					'ai'           => array(
						'disabled' => (bool) $disable_ai,
					),
					'senders'      => array_merge(
						array(
							'manual_recipients' => array(
								'label'        => __( 'Specific People', 'newsletter-optin-box' ),
								'description'  => __( 'Enter one or more email addresses manually, separated by commas.', 'newsletter-optin-box' ),
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
											'label'       => __( 'Recipient(s)', 'newsletter-optin-box' ),
											'description' => sprintf(
												'%s<br /> <br />%s',
												__( 'Enter recipients (comma-separated) for this email.', 'newsletter-optin-box' ),
												sprintf(
													/* translators: %s: code */
													__( 'Add %s after an email to disable send, open and click tracking for that recipient.', 'newsletter-optin-box' ),
													'<code>--notracking</code>'
												)
											),
											'type'        => 'text',
											'placeholder' => sprintf(
												/* translators: %s: The Example */
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
			);

			// Add available automation triggers for sequence emails.
			if (
				'view-campaigns' === $script &&
				$type &&
				'sequence' === $type->type &&
				is_object( $data['data'] ) &&
				! empty( $data['data']->ai ) &&
				is_array( $data['data']->ai )
			) {
				$ai_triggers = array();

				foreach ( \Hizzle\Noptin\Automation_Rules\Triggers\Main::all() as $trigger ) {
					if ( ! empty( $trigger->depricated ) || empty( $trigger->category ) ) {
						continue;
					}

					if ( ! isset( $ai_triggers[ $trigger->category ] ) ) {
						$ai_triggers[ $trigger->category ] = array();
					}

					$ai_triggers[ $trigger->category ][] = array(
						'id'          => $trigger->get_id(),
						'name'        => $trigger->get_name(),
						'description' => $trigger->get_description(),
						'merge_tags'  => $trigger->get_known_smart_tags_for_js(),
					);
				}

				if ( ! empty( $ai_triggers ) ) {
					$data['data']->ai['triggers'] = $ai_triggers;
				}
			}

			wp_add_inline_script(
				$localize_script,
				sprintf(
					'window.noptinEmailSettingsMisc = %s;',
					wp_json_encode( $data )
				),
				'before'
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

		// Set the ai overview.
		if ( ! empty( $query_args['noptin_ai_overview'] ) && 'none' !== $query_args['noptin_ai_overview'] ) {
			$campaign->options['noptin_ai_overview'] = sanitize_textarea_field( $query_args['noptin_ai_overview'] );
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

	/**
	 * Add email settings.
	 *
	 * @param array $settings
	 */
	public static function email_settings( $settings ) {
		$double_optin = get_default_noptin_subscriber_double_optin_email();

		$settings = array_merge(
			$settings,
			array(
				'general_email_info'  => array(
					'el'       => 'settings_group',
					'label'    => __( 'General', 'newsletter-optin-box' ),
					'section'  => 'emails',
					'settings' => array(
						'reply_to'         => array(
							'el'      => 'input',
							'section' => 'emails',
							'type'    => 'email',
							'label'   => __( '"Reply-to" Email', 'newsletter-optin-box' ),
							'default' => get_option( 'admin_email' ),
							'tooltip' => __( 'Where should subscribers reply to in case they need to get in touch with you?', 'newsletter-optin-box' ),
						),

						'from_email'       => array(
							'el'      => 'input',
							'section' => 'emails',
							'type'    => 'email',
							'label'   => __( '"From" Email', 'newsletter-optin-box' ),
							'tooltip' => __( 'How the sender email appears in outgoing emails. Leave this field blank if you are not able to send any emails.', 'newsletter-optin-box' ),
						),

						'from_name'        => array(
							'el'          => 'input',
							'section'     => 'emails',
							'label'       => __( '"From" Name', 'newsletter-optin-box' ),
							'placeholder' => get_option( 'blogname' ),
							'default'     => get_option( 'blogname' ),
							'tooltip'     => __( 'How the sender name appears in outgoing emails', 'newsletter-optin-box' ),
						),

						'delete_campaigns' => array(
							'el'               => 'input',
							'type'             => 'number',
							'section'          => 'emails',
							'label'            => __( 'Auto-Delete Campaigns', 'newsletter-optin-box' ),
							'placeholder'      => __( 'Never Delete', 'newsletter-optin-box' ),
							'tooltip'          => __( 'The number of days after which to delete a sent campaign. Leave empty if you do not want to automatically delete campaigns.', 'newsletter-optin-box' ),
							'customAttributes' => array(
								'min'    => 0,
								'prefix' => __( 'After', 'newsletter-optin-box' ),
								'suffix' => array( __( 'day', 'newsletter-optin-box' ), __( 'days', 'newsletter-optin-box' ) ),
							),
						),
					),
				),

				'tracking_email_info' => array(
					'el'       => 'settings_group',
					'label'    => __( 'Tracking', 'newsletter-optin-box' ),
					'section'  => 'emails',
					'settings' => array(
						'track_campaign_stats'      => array(
							'label'       => __( 'Track campaign performance', 'newsletter-optin-box' ),
							'description' => __( 'See how subscribers interact with your emails via opens and clicks.', 'newsletter-optin-box' ),
							'type'        => 'checkbox_alt',
							'el'          => 'input',
							'default'     => true,
						),
						'enable_ecommerce_tracking' => array(
							'label'            => __( 'Track e-commerce revenue', 'newsletter-optin-box' ),
							'description'      => __( 'Measure exactly how much revenue each email generates.', 'newsletter-optin-box' ) . ( noptin_has_alk() ? '' : ' ' . sprintf(
								'<a href="%s" target="_blank">%s</a>',
								noptin_get_upsell_url( '/pricing', 'settings', 'ecommerce-tracking' ),
								__( 'Activate your license key to unlock', 'newsletter-optin-box' )
							) ),
							'type'             => 'checkbox_alt',
							'el'               => 'input',
							'default'          => noptin_has_alk(),
							'customAttributes' => array(
								'disabled' => ! noptin_has_alk(),
							),
							'conditions'       => array(
								array(
									'key'      => 'track_campaign_stats',
									'operator' => '==',
									'value'    => true,
								),
							),
						),

						'add_utm_params'            => array(
							'el'          => 'input',
							'type'        => 'checkbox_alt',
							'section'     => 'emails',
							'label'       => __( 'Auto-tag links (UTM)', 'newsletter-optin-box' ),
							'description' => sprintf(
								'%s <a href="%s" target="_blank">%s</a>',
								__( 'Add UTM parameters so you can track traffic sources in analytics tools.', 'newsletter-optin-box' ),
								noptin_get_guide_url( 'Settings', 'sending-emails/utm-parameters/' ),
								__( 'Learn more', 'newsletter-optin-box' )
							),
							'default'     => true,
						),
					),
				),

				'email_sending_limit' => array(
					'el'       => 'settings_group',
					'label'    => __( 'Email Sending Limit', 'newsletter-optin-box' ),
					'section'  => 'emails',
					'help_url' => noptin_get_guide_url( 'Settings', 'sending-emails/email-sending-limits/' ),
					'settings' => array(
						'sending_frequency' => array(
							'el'       => 'horizontal',
							'settings' => array(
								'per_hour' => array(
									'el'               => 'input',
									'type'             => 'number',
									'section'          => 'emails',
									'label'            => __( 'Maximum Emails', 'newsletter-optin-box' ),
									'placeholder'      => __( 'Unlimited', 'newsletter-optin-box' ),
									'customAttributes' => array(
										'min'    => 1,
										'suffix' => array( __( 'email', 'newsletter-optin-box' ), __( 'emails', 'newsletter-optin-box' ) ),
									),
								),
								'email_sending_rolling_period' => array(
									'el'               => 'unit',
									'section'          => 'emails',
									'label'            => __( 'Time Period', 'newsletter-optin-box' ),
									'default'          => '1hours',
									'customAttributes' => array(
										'min'           => 1,
										'placeholder'   => '1 hour',
										'prefix'        => __( 'per', 'newsletter-optin-box' ),
										'className'     => 'hizzlewp-components-unit-control__select--large',
										'units'         => array(
											array(
												'default' => HOUR_IN_SECONDS,
												'label'   => __( 'second(s)', 'newsletter-optin-box' ),
												'value'   => 'seconds',
											),
											array(
												'default' => MINUTE_IN_SECONDS,
												'label'   => __( 'minute(s)', 'newsletter-optin-box' ),
												'value'   => 'minutes',
											),
											array(
												'default' => 1,
												'label'   => __( 'hour(s)', 'newsletter-optin-box' ),
												'value'   => 'hours',
											),
											array(
												'default' => 1,
												'label'   => __( 'day(s)', 'newsletter-optin-box' ),
												'value'   => 'days',
											),
										),
										'__unstableInputWidth' => null,
										'labelPosition' => 'top',
									),
								),
							),
						),
					),
				),

				'template_info'       => array(
					'el'       => 'settings_group',
					'label'    => __( 'Email Template', 'newsletter-optin-box' ),
					'section'  => 'emails',
					'settings' => array(
						'email_template' => array(
							'el'          => 'select',
							'label'       => __( 'Email Template', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select a template', 'newsletter-optin-box' ),
							'options'     => get_classic_noptin_email_templates(),
							'default'     => 'paste',
							'tooltip'     => __( 'Select "No Template" if you are using an email templates plugin.', 'newsletter-optin-box' ),
						),
						'footer_text'    => array(
							'el'          => 'textarea',
							'label'       => __( 'Footer text', 'newsletter-optin-box' ),
							'placeholder' => get_default_noptin_footer_text(),
							'default'     => get_default_noptin_footer_text(),
							'tooltip'     => __( 'This text appears below all emails.', 'newsletter-optin-box' ),
						),
						'custom_css'     => array(
							'el'      => 'textarea',
							'label'   => __( 'Custom CSS', 'newsletter-optin-box' ),
							'tooltip' => __( 'Optional. Add any custom CSS to style your emails.', 'newsletter-optin-box' ),
						),
					),
				),

				'enable_double_optin' => array(
					'el'          => 'settings_group',
					'label'       => __( 'Enable Double Opt-in', 'newsletter-optin-box' ),
					'section'     => 'emails',
					'sub_section' => 'double_opt_in',
					'settings'    => array(
						'double_optin'               => array(
							'el'          => 'input',
							'type'        => 'checkbox_alt',
							'label'       => __( 'Double Opt-in', 'newsletter-optin-box' ),
							'description' => __( 'Require new subscribers to confirm their email addresses.', 'newsletter-optin-box' ),
							'default'     => false,
						),

						'disable_double_optin_email' => array(
							'el'          => 'input',
							'type'        => 'checkbox_alt',
							'label'       => __( 'Disable default double opt-in email', 'newsletter-optin-box' ),
							'default'     => false,
							'description' => sprintf(
								'%s <a href="%s" target="_blank">%s</a>',
								__( 'You can disable the default double opt-in email if you wish to use a custom email or set-up different emails.', 'newsletter-optin-box' ),
								noptin_get_upsell_url( '/guide/email-subscribers/double-opt-in/#how-to-customize-the-email-or-set-up-multiple-double-opt-in-emails', 'double-opt', 'settings' ),
								__( 'Learn more', 'newsletter-optin-box' )
							),
							'restrict'    => 'double_optin',
						),
					),
				),

				'double_optin_email'  => array(
					'el'          => 'settings_group',
					'label'       => __( 'Double Opt-in Email', 'newsletter-optin-box' ),
					'section'     => 'emails',
					'sub_section' => 'double_opt_in',
					'conditions'  => array(
						array(
							'key'      => 'double_optin',
							'operator' => '==',
							'value'    => true,
						),
						array(
							'key'      => 'disable_double_optin_email',
							'operator' => '!=',
							'value'    => true,
						),
					),
					'settings'    => array(
						'double_optin_email_subject'   => array(
							'el'          => 'input',
							'label'       => __( 'Email Subject', 'newsletter-optin-box' ),
							'default'     => $double_optin['email_subject'],
							'placeholder' => $double_optin['email_subject'],
							'tooltip'     => __( 'The subject of the subscription confirmation email', 'newsletter-optin-box' ),
						),

						'double_optin_hero_text'       => array(
							'el'          => 'input',
							'label'       => __( 'Email Title', 'newsletter-optin-box' ),
							'default'     => $double_optin['hero_text'],
							'placeholder' => $double_optin['hero_text'],
							'tooltip'     => __( 'The title of the email', 'newsletter-optin-box' ),
						),

						'double_optin_email_body'      => array(
							'el'          => 'textarea',
							'label'       => __( 'Email Body', 'newsletter-optin-box' ),
							'placeholder' => $double_optin['email_body'],
							'default'     => $double_optin['email_body'],
							'tooltip'     => __( 'This is the main content of the email', 'newsletter-optin-box' ),
						),

						'double_optin_cta_text'        => array(
							'el'          => 'input',
							'label'       => __( 'Call to Action', 'newsletter-optin-box' ),
							'default'     => $double_optin['cta_text'],
							'placeholder' => $double_optin['cta_text'],
							'tooltip'     => __( 'The text of the call to action button', 'newsletter-optin-box' ),
						),

						'double_optin_after_cta_text'  => array(
							'el'          => 'textarea',
							'label'       => __( 'Extra Text', 'newsletter-optin-box' ),
							'default'     => $double_optin['after_cta_text'],
							'placeholder' => $double_optin['after_cta_text'],
							'tooltip'     => __( 'This text is shown after the call to action button', 'newsletter-optin-box' ),
						),

						'double_optin_permission_text' => array(
							'el'          => 'textarea',
							'label'       => __( 'Permission Text', 'newsletter-optin-box' ),
							'default'     => $double_optin['permission_text'],
							'placeholder' => $double_optin['permission_text'],
							'tooltip'     => __( 'Remind the subscriber how they signed up.', 'newsletter-optin-box' ),
						),
					),
				),
			)
		);

		if ( ! noptin_supports_ecommerce_tracking() ) {
			unset( $settings['tracking_email_info']['settings']['enable_ecommerce_tracking'] );
		}

		return $settings;
	}

	/**
	 * Adds 'noptin-has-split-email-menu' to the body class.
	 *
	 * @param string $classes The current body classes.
	 * @return string The modified body classes.
	 */
	public static function add_split_menu_body_class( $classes ) {
		if ( noptin_should_split_emails_menu() ) {
			$classes .= ' noptin-has-split-email-menu';
		}

		return $classes;
	}
}
