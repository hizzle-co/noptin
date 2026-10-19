<?php

/**
 * Main rest class.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Main rest class.
 */
class REST extends \WP_REST_Posts_Controller {
	/**
	 * Registers the routes for posts.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_field(
			'noptin-campaign',
			'noptin_list',
			array(
				'get_callback' => function ( $record ) {
					return $this->get_list_data( new Email( $record['id'] ) );
				},
				'schema'       => array(
					'type'                 => 'object',
					'readonly'             => true,
					'context'              => array( 'edit' ),
					'additionalProperties' => true,
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/browser',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( Admin\Browser::class, 'state' ),
					'permission_callback' => function () {
						return (bool) get_noptin_accessible_campaign_type_capability();
					},
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( Admin\Browser::class, 'dismiss' ),
					'permission_callback' => function () {
						return (bool) get_noptin_accessible_campaign_type_capability();
					},
					'args'                => array(
						'notice' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// Get template content.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-template',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_template_content' ),
				'permission_callback' => function () {
					return (bool) get_noptin_accessible_campaign_type_capability();
				},
				'args'                => array(
					'noptin_template' => array(
						'description' => 'The template ID.',
						'type'        => 'string',
						'required'    => true,
					),
				),
			)
		);

		// Send a test email.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/send-test',
			array(
				'args'        => array(
					'id' => array(
						'description' => 'Unique identifier for the post.',
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_test_email' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);

		// Reorder emails.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reorder',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'reorder_emails' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'ids'    => array(
						'description' => 'The IDs of the emails to reorder.',
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
					),
					'offset' => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
					),
				),
				'schema'              => array( $this, 'get_public_item_schema' ),
			)
		);

		// Campaign-list actions use REST nonces and the same per-campaign permissions.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\\d]+)/list-action',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'list_action' ),
				'allow_batch'         => $this->allow_batch,
				'permission_callback' => array( $this, 'list_action_permissions_check' ),
				'args'                => array(
					'id'         => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'action'     => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'name'       => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'subject'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'recipients' => array(
						'type'    => 'string',
						'default' => 'all',
						'enum'    => array( 'all', 'new' ),
					),
				),
			)
		);

		parent::register_routes();
	}

	/** Restore through WordPress's untrash API when Core Data moves a trashed record to draft. */
	public function update_item( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( ! is_wp_error( $post ) && 'trash' === $post->post_status && 'draft' === $request['status'] ) {
			$restored = wp_untrash_post( $post->ID );
			if ( ! $restored ) {
				return new \WP_Error( 'noptin_restore_failed', 'Could not restore this campaign.', array( 'status' => 409 ) );
			}
			// Respect WordPress's untrash status filter, including its default of draft.
			$request->set_param( 'status', get_post_status( $post->ID ) );
		}
		return parent::update_item( $request );
	}

	/** Standard REST deletion also owns sequence descendants and legacy cleanup hooks. */
	public function delete_item( $request ) {
		if ( ! $request['force'] ) {
			return parent::delete_item( $request );
		}
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$ids = $this->campaign_tree( $post->ID );
		// Authorize the entire tree before deleting any part of it.
		foreach ( $ids as $id ) {
			if ( ! $this->check_delete_permission( get_post( $id ) ) ) {
				return new \WP_Error( 'rest_forbidden', 'You cannot delete every campaign in this sequence.', array( 'status' => 403 ) );
			}
		}
		foreach ( array_reverse( $ids ) as $id ) {
			$child = new Email( $id );
			do_action( 'noptin_' . $child->type . '_campaign_before_delete', $child );
			do_action( 'noptin_' . $child->get_sub_type() . '_campaign_before_delete', $id );
			$child_request = clone $request;
			$child_request->set_param( 'id', $id );
			$response = parent::delete_item( $child_request );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}
		return $response;
	}

	/** Collection filters used by the React campaign browser. */
	public function get_collection_params() {
		$params                     = parent::get_collection_params();
		$params['campaign_type']    = array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
		);
		$params['campaign_subtype'] = array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);
		$params['delivery_status']  = array(
			'type' => 'string',
			'enum' => array( '', 'completed', 'sending', 'paused' ),
		);
		$params['orderby']['enum']  = array_values( array_unique( array_merge( $params['orderby']['enum'], array( 'sent', 'opened', 'clicked', 'unsubscribed' ) ) ) );
		return $params;
	}

	/**
	 * Describe a campaign for its authorized manager without loading editor content.
	 *
	 * @param Email $email
	 */
	public function get_list_data( $email ) {
		$type      = Main::get_email_type( $email->type );
		$subtype   = $type ? $type->get_sub_type( $email->get_sub_type() ) : false;
		$completed = '' !== get_post_meta( $email->id, 'completed', true );
		$paused    = '' !== get_post_meta( $email->id, 'paused', true );
		$statuses  = get_post_statuses();
		$status    = 'future' === $email->status ? __( 'Scheduled', 'newsletter-optin-box' ) : ( $statuses[ $email->status ] ?? $email->status );
		$state     = $email->status;

		if ( 'newsletter' === $email->type && 'publish' === $state ) {
			$state  = $completed ? 'completed' : ( $paused ? 'paused' : 'sending' );
			$status = array(
				'completed' => __( 'Sent', 'newsletter-optin-box' ),
				'paused'    => __( 'Paused', 'newsletter-optin-box' ),
				'sending'   => __( 'Sending', 'newsletter-optin-box' ),
			)[ $state ];
		}

		$sender     = $email->get_sender();
		$senders    = get_noptin_email_senders( true );
		$recipients = $senders[ $sender ]['label'] ?? $sender;
		$recipient_tokens = '';
		if ( 'manual_recipients' === $sender ) {
			$recipient_list = noptin_parse_list( $email->get_recipients(), true );
			$labels         = \Hizzle\Noptin\Misc\Main::get_manual_recipient_labels( $subtype );
			$raw_recipients = implode( ', ', $recipient_list );
			$recipients     = implode(
				', ',
				array_map(
					static function ( $recipient ) use ( $labels ) {
						return $labels[ $recipient ] ?? $recipient;
					},
					$recipient_list
				)
			);
			$recipient_tokens = $raw_recipients !== $recipients ? $raw_recipients : '';
		}
		$sender_filters  = array();
		$sender_settings = $senders[ $sender ]['settings'] ?? array();
		$sender_options  = empty( $sender_settings['key'] ) ? array() : $email->get( $sender_settings['key'] );
		foreach ( is_array( $sender_options ) ? $sender_options : array() as $key => $value ) {
			if ( is_object( $value ) || ( ! is_numeric( $value ) && empty( $value ) ) ) {
				continue;
			}
			$field                          = $sender_settings['fields'][ $key ] ?? array();
			if ( is_bool( $value ) ) {
				$value = $value ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
			}
			$values           = array_map(
				static function ( $item ) use ( $field ) {
					return maybe_serialize( is_array( $field['options'] ?? null ) && is_scalar( $item ) ? ( $field['options'][ $item ] ?? $item ) : $item );
				},
				(array) $value
			);
			$sender_filters[] = array(
				'label' => $field['label'] ?? ucfirst( str_replace( '_', ' ', $key ) ),
				'value' => implode( ', ', $values ),
			);
		}
		$error       = get_post_meta( $email->id, '_bulk_email_last_error', true );
		$source      = $email->parent_id ? new Email( $email->parent_id ) : null;
		$source_post = absint( $email->get( 'source_post_id' ) );
		$stats       = array();

		foreach ( array( 'send', 'open', 'click', 'unsubscribe' ) as $activity ) {
			$method             = 'get_' . $activity . '_count';
			$stats[ $activity ] = array(
				'count' => $email->$method(),
				'url'   => $email->get_activity_url( $activity ),
			);
		}

		$revenue          = noptin_has_alk() ? (float) get_post_meta( $email->id, '_revenue', true ) : null;
		$formatter        = apply_filters( 'noptin_format_price_callback', '', $revenue );
		$description_html = '';

		if ( $type && is_array( $subtype ) ) {
			$description_html = wp_kses_post( apply_filters( 'noptin_' . $email->type . '_table_about_' . $email->get_sub_type(), '', $email ) );
		}

		$warnings = array();
		if ( null === $subtype ) {
			$warnings[] = array(
				'code'    => 'unknown-type',
				'message' => __( 'Unknown type', 'newsletter-optin-box' ),
			);
		}
		if ( $email->supports( 'supports_recipients' ) ) {
			if ( ! $sender || ( 'manual_recipients' === $sender && ! $email->get_recipients() ) ) {
				$warnings[] = array(
					'code'    => 'no-recipients',
					'message' => __( 'No recipients', 'newsletter-optin-box' ),
				);
			} elseif ( 'manual_recipients' !== $sender && ! isset( $senders[ $sender ] ) ) {
				$warnings[] = array(
					'code'    => 'unknown-sender',
					'message' => __( 'Unknown sender', 'newsletter-optin-box' ),
				);
			}
		}
		if ( ! $email->subject && $email->get_preview_url() ) {
			$warnings[] = array(
				'code'    => 'no-subject',
				'message' => __( 'No subject', 'newsletter-optin-box' ),
			);
		}

		if ( 'trash' !== $email->status ) {
			if ( is_array( $error ) && ( ( 'newsletter' === $email->type && ! $completed ) || ( 'automation' === $email->type && $email->is_published() ) ) ) {
				$warnings[]               = array(
					'code'    => 'sending-error',
					'message' => $error['message'] ?? '',
					'file'    => $error['file'] ?? '',
					'line'    => $error['line'] ?? '',
					'url'     => noptin_get_guide_url( $type->plural_label ?? 'Newsletters', 'sending-emails/how-to-fix-emails-not-sending/' ),
				);
			}

			if ( 'newsletter' === $email->type && 'publish' === $email->status && ! $completed && ! $paused && ! \Hizzle\Noptin\Tasks\Main::get_next_scheduled_task( \Hizzle\Noptin\Emails\Bulk\Main::HEALTH_CHECK_HOOK ) ) {
				$warnings[] = array(
					'code'    => 'missing-cron',
					'message' => __( 'The email sending CRON event was not scheduled. Try pausing then resuming the campaign.', 'newsletter-optin-box' ),
					'url'     => noptin_get_guide_url( $type->plural_label ?? 'Newsletters', 'sending-emails/how-to-fix-emails-not-sending/' ),
				);
			}
		}

		$data             = array(
			'warnings'           => array_values( apply_filters( 'noptin_campaign_warnings', $warnings, $email ) ),
			'row_actions'        => Admin\Browser::public_actions( $email ),
			'type'               => $email->type,
			'subtype'            => $email->get_sub_type(),
			'subject'            => $email->subject,
			'name'               => ( 'email_template' === $email->type ? $email->subject : $email->name ) ?: ( $email->name ?: $email->subject ?: ( $subtype['label'] ?? __( '(no title)', 'newsletter-optin-box' ) ) ),
			'description'        => $subtype['description'] ?? '',
			'status_label'       => $status,
			'state'              => $state,
			'description_html'   => $description_html,
			'edit_url'           => $email->get_edit_url(),
			'preview_url'        => $email->get_preview_url(),
			'activity_url'       => $email->get_activity_url(),
			'can_edit'           => $email->current_user_can_edit(),
			'can_delete'         => $email->current_user_can_delete(),
			'can_publish'        => $email->current_user_can_publish(),
			'can_send'           => $email->is_mass_mail() && ! $email->supports_timing() && ! ( 'newsletter' === $email->type && $email->is_published() ),
			'can_resend'         => 'newsletter' === $email->type && 'publish' === $email->status,
			'partial_sending'    => $email->is_mass_mail() && (bool) apply_filters( "noptin_{$sender}_email_sender_supports_partial_sending", false, $email ),
			'recipients'         => $recipients,
			'recipient_filters'  => $sender_filters,
			'delay'              => $email->sends_immediately() ? '' : $email->get_sends_after() . ' ' . $email->get_sends_after_unit( true ),
			'last_error'         => is_array( $error ) && ! $completed ? ( $error['message'] ?? '' ) : '',
			'source'             => $source && $source->exists() ? array(
				'name' => $source->name,
				'url'  => $source->get_edit_url(),
			) : null,
			'source_post'        => $source_post && get_post( $source_post ) ? array(
				'name' => get_the_title( $source_post ) ?: __( '(no title)', 'newsletter-optin-box' ),
				'url'  => get_edit_post_link( $source_post, 'raw' ),
			) : null,
			'stats'              => $stats,
			'revenue'            => $revenue,
			'revenue_label'      => null !== $revenue && is_callable( $formatter ) ? html_entity_decode( wp_strip_all_tags( call_user_func( $formatter, $revenue ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : (string) $revenue,
			'recipient_tokens'   => $recipient_tokens,
			'show_tracking'      => 'email_template' !== $email->type && (bool) get_noptin_option( 'track_campaign_stats', true ),
			'show_revenue'       => ! noptin_has_alk() || ( noptin_supports_ecommerce_tracking() && get_noptin_option( 'enable_ecommerce_tracking', true ) ),
		);
		$data             = apply_filters( 'noptin_campaign_list_data', $data, $email );
		$data['row_meta'] = $this->get_row_meta( $data, $email );
		return $data;
	}

	/**
	 * Prepare the ordered metadata shown below a campaign's name.
	 *
	 * The noptin_campaign_row_meta filter receives entries keyed by stable IDs.
	 * Each entry supports label (without a colon), value (plain text), value_html
	 * (HTML, preferred over value when non-empty), muted (boolean), and indent (boolean for nested sender filters).
	 * Labels and plain values are rendered as text; HTML is sanitized after filtering.
	 * Return an empty value to omit an entry. Numeric zero remains visible.
	 *
	 * @param array $data  Campaign list data, after noptin_campaign_list_data.
	 * @param Email $email Campaign being described.
	 * @return array Ordered metadata entries, each including its stable ID.
	 */
	private function get_row_meta( $data, $email ) {
		$meta = array(
			'description'      => array(
				'value' => $data['description'],
				'muted' => true,
			),
			'description_html' => array(
				'value_html' => $data['description_html'],
				'muted'      => true,
			),
		);

		if ( 'email_template' !== $email->type && $data['preview_url'] && $data['subject'] !== $data['name'] ) {
			$meta['subject'] = array(
				'label' => __( 'Subject', 'newsletter-optin-box' ),
				'value' => $data['subject'],
			);
		}

		if ( 'email_template' === $email->type ) {
			$meta['slug'] = array(
				'label' => __( 'Slug', 'newsletter-optin-box' ),
				'value' => get_post_field( 'post_name', $email->id ),
				'muted' => true,
			);
		}

		if ( $email->supports( 'supports_recipients' ) && ( 'manual_recipients' === $email->get_sender() || isset( get_noptin_email_senders( true )[ $email->get_sender() ] ) ) ) {
			$meta['recipients'] = array(
				'label' => __( 'Recipients', 'newsletter-optin-box' ),
				'value' => $data['recipients'],
				'muted' => true,
			);

			foreach ( $data['recipient_filters'] as $index => $filter ) {
				$meta[ 'recipient_filter_' . $index ] = array_merge(
					$filter,
					array(
						'muted'  => true,
						'indent' => true,
					)
				);
			}
		}

		if ( 'trash' !== $email->status ) {
			$meta['delay'] = array(
				'label' => __( 'Delay', 'newsletter-optin-box' ),
				'value' => $data['delay'],
			);
		}

		if ( 'newsletter' === $email->type && 'trash' !== $email->status ) {
			foreach ( array(
				'source'      => __( 'Source', 'newsletter-optin-box' ),
				'source_post' => __( 'Source post', 'newsletter-optin-box' ),
			) as $key => $label ) {
				if ( ! empty( $data[ $key ] ) ) {
					$item         = $data[ $key ];
					if ( 'source_post' === $key && empty( $item['url'] ) ) {
						continue;
					}
					$meta[ $key ] = array(
						'label' => $label,
						'value' => $item['name'],
					);
					if ( ! empty( $item['url'] ) ) {
						$meta[ $key ]['value_html'] = sprintf( '<a href="%s"%s>%s</a>', esc_url( $item['url'] ), 'source_post' === $key ? ' target="_blank" rel="noopener noreferrer"' : '', esc_html( $item['name'] ) );
					}
				}
			}
		}

		if ( 'automation' === $email->type && 'trash' !== $email->status ) {
			$meta['campaign_id'] = array(
				'label' => __( 'Campaign ID', 'newsletter-optin-box' ),
				'value' => $email->id,
				'muted' => true,
			);
		}

		$entries = array();
		foreach ( (array) apply_filters( 'noptin_campaign_row_meta', $meta, $email ) as $id => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$value = isset( $entry['value'] ) && is_scalar( $entry['value'] ) ? (string) $entry['value'] : '';
			$html  = isset( $entry['value_html'] ) && is_string( $entry['value_html'] ) ? wp_kses_post( $entry['value_html'] ) : '';
			if ( '' !== $value || '' !== $html ) {
				$entries[] = array(
					'id'         => (string) $id,
					'label'      => isset( $entry['label'] ) && is_scalar( $entry['label'] ) ? (string) $entry['label'] : '',
					'value'      => $value,
					'value_html' => $html,
					'muted'      => ! empty( $entry['muted'] ),
					'indent'     => ! empty( $entry['indent'] ),
				);
			}
		}
		return $entries;
	}

	/** Authorize the complete operation before changing any campaign or child email. */
	public function list_action_permissions_check( $request ) {
		if ( ! get_noptin_accessible_campaign_type_capability() ) {
			return new \WP_Error( 'rest_forbidden', __( 'You cannot manage campaigns.', 'newsletter-optin-box' ), array( 'status' => 403 ) );
		}
		$post = get_post( $request['id'] );
		if ( ! $post || 'noptin-campaign' !== $post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Campaign not found.', 'newsletter-optin-box' ), array( 'status' => 404 ) );
		}
		$email   = new Email( $post->ID );
		$action  = $request['action'];
		$allowed = current_user_can_manage_noptin_campaign_type( $email->type ) && $email->current_user_can_edit();
		if ( 'delete' === $action ) {
			$allowed = current_user_can_manage_noptin_campaign_type( $email->type ) && $email->current_user_can_delete();
		}
		if ( in_array( $action, array( 'publish', 'send', 'pause', 'resume', 'resend' ), true ) ) {
			$allowed = $allowed && $email->current_user_can_publish();
		}
		if ( in_array( $action, array( 'duplicate', 'delete' ), true ) ) {
			foreach ( $this->campaign_tree( $email->id ) as $id ) {
				$child   = new Email( $id );
				$allowed = $allowed && current_user_can_manage_noptin_campaign_type( $child->type ) && ( 'delete' === $action ? $child->current_user_can_delete() : $child->current_user_can_edit() );
			}
		}
		if ( ! in_array( $action, array( 'duplicate', 'restore', 'pause', 'resume', 'resend', 'send', 'publish', 'delete' ), true ) ) {
			$custom  = array_merge( Admin\Browser::actions( $email, true ), Admin\Browser::actions( $email ) );
			$command = $custom[ $action ] ?? array();
			$allowed = $allowed && isset( $command['callback'] ) && is_callable( $command['callback'] );
			if ( $allowed && isset( $command['permission_callback'] ) ) {
				$permission = call_user_func( $command['permission_callback'], $email, $request );
				if ( is_wp_error( $permission ) ) {
					return $permission;
				}
				$allowed = true === $permission;
			}
		}

		return $allowed ? true : new \WP_Error( 'rest_forbidden', __( 'You cannot perform this campaign action.', 'newsletter-optin-box' ), array( 'status' => 403 ) );
	}

	/** Return a complete, cycle-safe descendant list including trashed children. */
	private function campaign_tree( $id, &$seen = array() ) {
		if ( isset( $seen[ $id ] ) ) {
			return array();
		}
		$seen[ $id ] = true;
		$ids         = array( $id );
		$children    = get_posts(
			array(
				'post_type'      => 'noptin-campaign',
				'post_parent'    => $id,
				'post_status'    => array_keys( get_post_stati() ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $children as $child ) {
			$ids = array_merge( $ids, $this->campaign_tree( $child, $seen ) );
		}
		return $ids;
	}

	/** Execute a campaign command without redirecting away from the React list. */
	public function list_action( $request ) {
		$email  = new Email( $request['id'] );
		$action = $request['action'];
		if ( ! in_array( $action, array( 'duplicate', 'restore', 'pause', 'resume', 'resend', 'send', 'publish', 'delete' ), true ) ) {
			$commands = array_merge( Admin\Browser::actions( $email, true ), Admin\Browser::actions( $email ) );
			$result   = call_user_func( $commands[ $action ]['callback'], $email, $request );
			return is_wp_error( $result ) ? $result : rest_ensure_response(
				array(
					'id'     => $email->id,
					'result' => $result,
				)
			);
		}
		if ( 'duplicate' === $action ) {
			$args = array( 'name' => $request['name'] ?? $email->name . ' - ' . __( 'Copy', 'newsletter-optin-box' ) );
			if ( isset( $request['subject'] ) ) {
				$args['subject'] = $request['subject'];
			}
			if ( 'newsletter' === $email->type ) {
				$args['status'] = 'draft';
			}
			$result = $email->duplicate( $args );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( ! $result instanceof Email || ! $result->exists() ) {
				return new \WP_Error( 'noptin_duplicate_failed', __( 'Could not duplicate this campaign.', 'newsletter-optin-box' ), array( 'status' => 500 ) );
			}
			return rest_ensure_response(
				array(
					'id'       => $result->id,
					'campaign' => $this->get_list_data( $result ),
				)
			);
		}
		if ( 'delete' === $action ) {
			if ( 'trash' !== $email->status ) {
				return new \WP_Error( 'noptin_invalid_state', __( 'Move this campaign to Trash before deleting it permanently.', 'newsletter-optin-box' ), array( 'status' => 409 ) );
			}
			$request->set_param( 'force', true );
			return $this->delete_item( $request );
		}
		if ( 'restore' === $action ) {
			if ( 'trash' !== $email->status || ! $email->restore() ) {
				return new \WP_Error( 'noptin_restore_failed', __( 'Could not restore this campaign.', 'newsletter-optin-box' ), array( 'status' => 409 ) );
			}
		} elseif ( in_array( $action, array( 'pause', 'resume', 'resend' ), true ) ) {
			$completed = '' !== get_post_meta( $email->id, 'completed', true );
			if ( 'newsletter' !== $email->type || 'publish' !== $email->status || ( 'resend' === $action ? ! $completed : $completed ) ) {
				return new \WP_Error( 'noptin_invalid_state', __( 'This delivery action is no longer available. Refresh the list.', 'newsletter-optin-box' ), array( 'status' => 409 ) );
			}
			if ( 'pause' === $action ) {
				update_post_meta( $email->id, 'paused', 1 );
				if ( '1' !== (string) get_post_meta( $email->id, 'paused', true ) ) {
					return new \WP_Error( 'noptin_pause_failed', __( 'Could not pause sending.', 'newsletter-optin-box' ), array( 'status' => 500 ) );
				}
			} else {
				$sender = $email->get_sender();
				if ( 'new' === $request['recipients'] && ! apply_filters( "noptin_{$sender}_email_sender_supports_partial_sending", false, $email ) ) {
					return new \WP_Error( 'noptin_invalid_recipients', __( 'This sender does not support sending only to new recipients.', 'newsletter-optin-box' ), array( 'status' => 400 ) );
				}
				delete_post_meta( $email->id, 'completed' );
				delete_post_meta( $email->id, 'paused' );
				delete_post_meta( $email->id, '_bulk_email_last_error' );
				update_post_meta( $email->id, '_resend_to', $request['recipients'] );
				update_post_meta( $email->id, '_resent_on', gmdate( 'Y-m-d H:i:s e' ) );
				if ( '' !== get_post_meta( $email->id, 'completed', true ) || '' !== get_post_meta( $email->id, 'paused', true ) ) {
					return new \WP_Error( 'noptin_resume_failed', __( 'Could not resume sending.', 'newsletter-optin-box' ), array( 'status' => 500 ) );
				}
				do_action( 'noptin_newsletter_campaign_published', $email );
			}
		} elseif ( in_array( $action, array( 'publish', 'send' ), true ) ) {
			if ( 'trash' === $email->status || ( 'send' === $action && ! $this->get_list_data( $email )['can_send'] ) ) {
				return new \WP_Error( 'noptin_invalid_state', __( 'This campaign cannot be sent from the list.', 'newsletter-optin-box' ), array( 'status' => 409 ) );
			}
			if ( 'send' === $action && ! defined( 'NOPTIN_RESENDING_CAMPAIGN' ) ) {
				define( 'NOPTIN_RESENDING_CAMPAIGN', true );
			}
			$result = wp_update_post(
				array(
					'ID'          => $email->id,
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$email = new Email( $email->id );
			if ( 'send' === $action ) {
				do_action( 'noptin_send_' . $email->type, $email );
				if ( 'automation' === $email->type ) {
					do_action( 'noptin_send_' . $email->get_sub_type(), $email );
					do_action( 'noptin_send_' . $email->get_sub_type() . '_email', $email );
				}
			}
		}
		return rest_ensure_response(
			array(
				'id'       => $email->id,
				'campaign' => $this->get_list_data( new Email( $email->id ) ),
			)
		);
	}

	/**
	 * Checks if a given request has access to read a campaign.
	 *
	 * Published campaigns may be viewed through their browser links, but the
	 * REST response also includes campaign settings and metadata.
	 *
	 * @param \WP_Post $post Campaign post.
	 * @return bool Whether the current user can read the campaign.
	 */
	public function check_read_permission( $post ) {
		if ( ! parent::check_read_permission( $post ) ) {
			return false;
		}

		return current_user_can_manage_noptin_campaign_type( $this->get_stored_campaign_type( $post->ID ) );
	}

	/**
	 * Checks if the current user can delete a campaign.
	 *
	 * @param \WP_Post $post Campaign post.
	 * @return bool Whether the current user can delete the campaign.
	 */
	protected function check_delete_permission( $post ) {
		if ( ! parent::check_delete_permission( $post ) ) {
			return false;
		}

		return current_user_can_manage_noptin_campaign_type( $this->get_stored_campaign_type( $post->ID ) );
	}

	/**
	 * Checks if a given request has access to update a post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		$type = $this->get_request_campaign_type( $request );
		if ( ! $type || ! current_user_can_manage_noptin_campaign_type( $type ) ) {
			return false;
		}

		add_filter( 'is_protected_meta', '__return_false', PHP_INT_MAX );

		return parent::update_item_permissions_check( $request );
	}

	/**
	 * Checks if a request can create a campaign of the requested type.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$type = $this->get_request_campaign_type( $request );
		if ( ! $type || ! current_user_can_manage_noptin_campaign_type( $type ) ) {
			return false;
		}

		return parent::create_item_permissions_check( $request );
	}

	/**
	 * Limits collection requests to campaign types available to the user.
	 *
	 * @param array            $prepared_args Prepared query arguments.
	 * @param \WP_REST_Request $request       Request object.
	 * @return array
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$prepared_args = parent::prepare_items_query( $prepared_args, $request );
		$allowed       = get_noptin_accessible_campaign_types();

		$type_query = array(
			'key'     => 'campaign_type',
			'value'   => empty( $allowed ) ? array( '__none__' ) : $allowed,
			'compare' => 'IN',
		);

		// Older campaigns without stored type metadata are newsletters.
		if ( in_array( 'newsletter', $allowed, true ) ) {
			$type_query = array(
				'relation' => 'OR',
				$type_query,
				array(
					'key'     => 'campaign_type',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$prepared_args['meta_query'][] = $type_query;
		if ( $request ) {
			$type = $request->get_param( 'campaign_type' );
			if ( $type && 'trash' !== $type ) {
				$specific                               = array(
					'key'   => 'campaign_type',
					'value' => $type,
				);
				if ( 'newsletter' === $type ) {
					$specific = array(
						'relation' => 'OR',
						$specific,
						array(
							'key'     => 'campaign_type',
							'compare' => 'NOT EXISTS',
						),
					);
				}
				$prepared_args['meta_query'][]          = $specific;
			}
			if ( $request->get_param( 'campaign_subtype' ) && $type && 'trash' !== $type ) {
				$prepared_args['meta_query'][] = array(
					'key'   => $type . '_type',
					'value' => $request['campaign_subtype'],
				);
			}
			$delivery = $request->get_param( 'delivery_status' );
			if ( 'newsletter' === $type && $delivery ) {
				$prepared_args['post_status']  = 'publish';
				$prepared_args['meta_query'][] = array(
					'key'     => 'completed',
					'compare' => 'completed' === $delivery ? 'EXISTS' : 'NOT EXISTS',
				);
				if ( 'completed' !== $delivery ) {
					$prepared_args['meta_query'][] = array(
						'key'     => 'paused',
						'compare' => 'paused' === $delivery ? 'EXISTS' : 'NOT EXISTS',
					);
				}
			}
			$sorts = array(
				'sent'         => '_noptin_sends',
				'opened'       => '_noptin_opens',
				'clicked'      => '_noptin_clicks',
				'unsubscribed' => '_noptin_unsubscribed',
			);
			if ( isset( $sorts[ $request['orderby'] ] ) ) {
				// Keep emails without engagement counters in the results too.
				$prepared_args['meta_query'][] = array(
					'relation'          => 'OR',
					'noptin_stat_value' => array(
						'key'     => $sorts[ $request['orderby'] ],
						'compare' => 'EXISTS',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => $sorts[ $request['orderby'] ],
						'compare' => 'NOT EXISTS',
					),
				);
				$prepared_args['orderby']      = 'noptin_stat_value';
			}
			if ( ! current_user_can( get_post_type_object( 'noptin-campaign' )->cap->edit_others_posts ) ) {
				$prepared_args['author'] = get_current_user_id();
			}
		}

		return $prepared_args;
	}

	/** Authorize the requested list type before performing a collection lookup. */
	public function get_items_permissions_check( $request ) {
		$type = $request->get_param( 'campaign_type' );
		if ( ! get_noptin_accessible_campaign_type_capability() || ( $type && ! current_user_can_manage_noptin_campaign_type( $type ) ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You cannot manage this campaign type.', 'newsletter-optin-box' ), array( 'status' => 403 ) );
		}
		return parent::get_items_permissions_check( $request );
	}

	/**
	 * Gets a campaign type from a REST request or existing campaign.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return string
	 */
	private function get_request_campaign_type( $request ) {
		$type = $request->get_param( 'campaign_type' );
		$meta = $request->get_param( 'meta' );

		if ( is_object( $meta ) ) {
			$meta = (array) $meta;
		}

		if ( empty( $type ) && is_array( $meta ) ) {
			$type = $meta['campaign_type'] ?? '';
		}

		if ( empty( $type ) && $request->get_param( 'id' ) ) {
			$type = get_post_meta( (int) $request->get_param( 'id' ), 'campaign_type', true );
		}

		return sanitize_key( $type );
	}

	/**
	 * Gets the stored campaign type, including the legacy newsletter default.
	 *
	 * @param int $post_id Campaign post ID.
	 * @return string Campaign type.
	 */
	private function get_stored_campaign_type( $post_id ) {
		$type = get_post_meta( $post_id, 'campaign_type', true );
		return empty( $type ) ? 'newsletter' : sanitize_key( $type );
	}

	/**
	 * Sends a test email.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function send_test_email( $request ) {

		add_filter( 'noptin_log_email_send', '__return_false', 10000 );

		$GLOBALS['current_noptin_email'] = $request->get_param( 'email' );

		// Check if we have a recipient for the test email.
		if ( empty( $GLOBALS['current_noptin_email'] ) || ! is_email( $GLOBALS['current_noptin_email'] ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'Please provide a valid email address', 'newsletter-optin-box' ), array( 'status' => 400 ) );
		}

		$email = new Email( $request->get_param( 'id' ) );

		// Abort if the email is not found.
		if ( ! $email->exists() ) {
			return new \WP_Error( 'noptin_rest_email_invalid', 'Invalid email', array( 'status' => 404 ) );
		}

		// Ensure we have a subject.
		$subject = $email->get_subject();
		if ( empty( $subject ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', 'You need to provide a subject for your email.', array( 'status' => 404 ) );
		}

		$user = array(
			'sid'   => get_current_noptin_subscriber_id(),
			'uid'   => get_current_user_id(),
			'email' => $GLOBALS['current_noptin_email'],
		);

		// Prepare test content if needed.
		$prepare_preview = $email->prepare_preview( 'preview', $user );

		if ( is_wp_error( $prepare_preview ) ) {
			return $prepare_preview;
		}

		// Generate the preview.
		$preview = noptin_generate_email_content( $email, $user, false );

		if ( is_wp_error( $preview ) ) {
			return $preview;
		}

		// Send the email.
		$result = noptin_send_email(
			array(
				'recipients'               => $request->get_param( 'email' ),
				'subject'                  => noptin_parse_email_subject_tags( $subject ),
				'message'                  => $preview,
				'campaign_id'              => $email->id,
				'campaign'                 => $email,
				'headers'                  => array(),
				'attachments'              => array(),
				'reply_to'                 => noptin_parse_email_subject_tags( $email->get( 'reply_to' ) ),
				'from_email'               => noptin_parse_email_subject_tags( $email->get( 'from_email' ) ),
				'from_name'                => noptin_parse_email_subject_tags( $email->get( 'from_name' ) ),
				'content_type'             => $email->get_email_type() === 'plain_text' ? 'text' : 'html',
				'unsubscribe_url'          => get_noptin_action_url( 'unsubscribe', noptin_encrypt( wp_json_encode( $user ) ) ),
				'disable_template_plugins' => ! ( $email->get_email_type() === 'normal' && $email->get_template() === 'default' ),
			)
		);

		if ( empty( $result ) ) {
			$error = Main::get_phpmailer_last_error();
			return new \WP_Error(
				'noptin_rest_email_invalid',
				sprintf(
					// Translators: %s The error message.
					__( 'Unable to send test email. Error: %s', 'newsletter-optin-box' ),
					esc_html( $error )
				),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Your test email has been sent', 'newsletter-optin-box' ),
			)
		);
	}

	/**
	 * Reorders emails.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function reorder_emails( $request ) {

		$ids = $request->get_param( 'ids' );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', 'Invalid email IDs', array( 'status' => 400 ) );
		}

		$ids = array_map( 'intval', $ids );
		if ( count( array_unique( $ids ) ) !== count( $ids ) ) {
			return new \WP_Error( 'noptin_invalid_order', 'Duplicate campaign IDs.', array( 'status' => 400 ) );
		}
		$group = null;
		foreach ( $ids as $id ) {
			$email = new Email( $id );
			$type  = Main::get_email_type( $email->type );
			if ( ! $email->exists() || ! current_user_can_manage_noptin_campaign_type( $email->type ) || ! $email->current_user_can_edit() ) {
				return new \WP_Error( 'rest_forbidden', 'You cannot reorder these campaigns.', array( 'status' => 403 ) );
			}
			$identity = $email->type . ':' . $email->parent_id;
			if ( ! $type || ! $type->supports_menu_order || ( null !== $group && $group !== $identity ) ) {
				return new \WP_Error( 'noptin_invalid_order', 'Only emails in the same ordered collection can be reordered.', array( 'status' => 400 ) );
			}
			$group = $identity;
		}

		do_action( 'noptin_before_reorder_emails', $ids );

		// Update the menu order.
		foreach ( $ids as $index => $id ) {

			// Check if user can edit the email.
			if ( current_user_can( 'edit_post', $id ) ) {
				$result = wp_update_post(
					array(
						'ID'         => $id,
						'menu_order' => $index + 1 + absint( $request->get_param( 'offset' ) ),
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		do_action( 'noptin_after_reorder_emails', $ids );

		return rest_ensure_response( true );
	}

	/**
	 * Retrieves the content of an email template.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_template_content( $request ) {
		$template = sanitize_text_field( $request->get_param( 'noptin_template' ) );

		if ( empty( $template ) || 'blank' === $template ) {
			$defaults = get_noptin_email_template_defaults();
			return rest_ensure_response(
				array(
					'content' => '',
					'options' => $defaults['noptin-visual'],
				)
			);
		}

		// Check if the template is a local campaign template.
		if ( false !== strpos( $template, 'noptin_campaign_' ) ) {
			$campaign_id = (int) str_replace( 'noptin_campaign_', '', $template );
			$email       = noptin_get_email_campaign_object( $campaign_id );

			if ( empty( $email->id ) ) {
				return new \WP_Error(
					'noptin_template_not_found',
					'Template not found.',
					array( 'status' => 404 )
				);
			}

			if ( 'email_template' !== $email->type && ! $email->current_user_can_edit() ) {
				return new \WP_Error(
					'noptin_template_forbidden',
					'You cannot access this email template.',
					array( 'status' => 403 )
				);
			}

			$options = array_filter(
				array(
					'color'             => $email->get( 'color' ),
					'button_background' => $email->get( 'button_background' ),
					'button_color'      => $email->get( 'button_color' ),
					'background_color'  => $email->get( 'background_color' ),
					'custom_css'        => $email->get( 'custom_css' ),
					'font_family'       => $email->get( 'font_family' ),
					'font_size'         => $email->get( 'font_size' ),
					'font_style'        => $email->get( 'font_style' ),
					'font_weight'       => $email->get( 'font_weight' ),
					'line_height'       => $email->get( 'line_height' ),
					'link_color'        => $email->get( 'link_color' ),
					'block_css'         => $email->get( 'block_css' ),
					'background_image'  => $email->get( 'background_image' ),
				)
			);

			return rest_ensure_response(
				array(
					'content' => $email->content,
					'options' => $options,
				)
			);
		}

		// Fetch a remote template from noptin.com.
		$response = \Noptin_COM::process_api_response( wp_remote_get( "https://noptin.com/email-templates/{$template}.json" ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$template_data = json_decode( wp_json_encode( $response ), true );

		if ( ! is_array( $template_data ) || empty( $template_data['content'] ) ) {
			return new \WP_Error(
				'noptin_template_invalid',
				'Invalid template data.',
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'content' => str_ireplace(
					array( '{{DEFAULT_FOOTER_TEXT}}', '{{FOOTER_TEXT}}' ),
					get_noptin_footer_text(),
					$template_data['content']
				),
				'options' => isset( $template_data['options'] ) ? $template_data['options'] : array(),
			)
		);
	}

	protected function prepare_item_for_database( $request ) {
		$data = parent::prepare_item_for_database( $request );

		if ( ! is_wp_error( $data ) && isset( $data->post_content ) && false !== strpos( $data->post_content, '{{DEFAULT_FOOTER_TEXT}}' ) ) {
			$data->post_content = str_replace( '{{DEFAULT_FOOTER_TEXT}}', get_default_noptin_footer_text(), $data->post_content );
		}

		if ( ! is_wp_error( $data ) && $this->is_email_template_request( $request, $data ) ) {
			$post_id        = (int) $request->get_param( 'id' );
			$requested_slug = isset( $data->post_name ) ? $data->post_name : '';

			if ( empty( $requested_slug ) && isset( $data->post_title ) ) {
				$requested_slug = $data->post_title;
			}

			if ( empty( $requested_slug ) && $post_id ) {
				$requested_slug = get_post_field( 'post_name', $post_id );
			}

			$data->post_name = $this->get_unique_template_slug(
				$requested_slug,
				$post_id
			);
		}

		return $data;
	}

	/**
	 * Checks whether the request creates or updates an email template.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @param \stdClass        $data    Prepared post data.
	 * @return bool
	 */
	private function is_email_template_request( $request, $data ) {
		$campaign_type = $request->get_param( 'campaign_type' );
		$meta          = $request->get_param( 'meta' );

		if ( is_object( $meta ) ) {
			$meta = (array) $meta;
		}

		if ( empty( $campaign_type ) && is_array( $meta ) ) {
			$campaign_type = isset( $meta['campaign_type'] ) ? $meta['campaign_type'] : '';
		}

		if ( empty( $campaign_type ) && isset( $data->meta_input['campaign_type'] ) ) {
			$campaign_type = $data->meta_input['campaign_type'];
		}

		$post_id = (int) $request->get_param( 'id' );
		if ( empty( $campaign_type ) && $post_id ) {
			$campaign_type = get_post_meta( $post_id, 'campaign_type', true );
		}

		return 'email_template' === $campaign_type;
	}

	/**
	 * Returns the first available template slug, starting suffixes at 1.
	 *
	 * @param string $requested_slug Requested template slug or title.
	 * @param int    $post_id        Current post ID when updating.
	 * @return string
	 */
	private function get_unique_template_slug( $requested_slug, $post_id = 0 ) {
		global $wpdb;

		$base_slug = sanitize_title( $requested_slug );
		if ( empty( $base_slug ) ) {
			return '';
		}

		$suffix    = 0;
		$candidate = $base_slug;

		do {
			$slug_exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1",
					$candidate,
					$this->post_type,
					$post_id
				)
			);

			if ( ! $slug_exists ) {
				return $candidate;
			}

			++$suffix;
			$suffix_text = '-' . $suffix;
			$candidate   = rtrim( substr( $base_slug, 0, 200 - strlen( $suffix_text ) ), '-' ) . $suffix_text;
		} while ( true );
	}
}
