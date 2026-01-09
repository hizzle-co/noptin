<?php

namespace Hizzle\Noptin\Subscribers;

defined( 'ABSPATH' ) || exit;

/**
 * The subscribers' DB schema class.
 */
class Schema {

	/**
	 * Loads the class.
	 *
	 */
	public static function init() {
		add_filter( 'noptin_db_schema', array( __CLASS__, 'add_to_schema' ) );
	}

	/**
	 * Adds the subscribers table to the schema.
	 *
	 * @param array $schema The database schema.
	 * @return array
	 */
	public static function add_to_schema( $schema ) {

		// Basic props.
		$props = array(

			'id'    => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'extra'       => 'AUTO_INCREMENT',
				'description' => __( 'Unique identifier for this resource.', 'newsletter-optin-box' ),
			),

			'name'  => array(
				'type'        => 'VARCHAR',
				'length'      => 200,
				'description' => __( "The subscriber's name.", 'newsletter-optin-box' ),
				'is_dynamic'  => true,
			),

			'email' => array(
				'type'        => 'VARCHAR',
				'length'      => 255,
				'description' => __( "The subscriber's email address.", 'newsletter-optin-box' ),
				'nullable'    => false,
			),
		);

		// Custom fields.
		foreach ( get_noptin_custom_fields() as $custom_field ) {

			// Skip first name, last name and email.
			if ( ! in_array( $custom_field['merge_tag'], array( 'email' ), true ) ) {
				$props = array_merge( $props, noptin_convert_custom_field_to_schema( $custom_field ) );
			}
		}

		return array_merge(
			$schema,
			array(

				// Subscribers.
				'subscribers' => array(
					'object'         => '\Hizzle\Noptin\Subscribers\Subscriber',
					'singular_name'  => 'subscriber',
					'use_meta_table' => true,
					'labels'         => array(
						'name'          => __( 'Subscribers', 'newsletter-optin-box' ),
						'singular_name' => __( 'Subscriber', 'newsletter-optin-box' ),
						'add_new'       => __( 'Add New', 'newsletter-optin-box' ),
						'add_new_item'  => __( 'Add New Subscriber', 'newsletter-optin-box' ),
						'edit_item'     => __( 'Edit Subscriber', 'newsletter-optin-box' ),
						'new_item'      => __( 'New Subscriber', 'newsletter-optin-box' ),
						'view_item'     => __( 'View Subscriber', 'newsletter-optin-box' ),
						'view_items'    => __( 'View Subscribers', 'newsletter-optin-box' ),
						'search_items'  => __( 'Search Subscribers', 'newsletter-optin-box' ),
						'not_found'     => __( 'No subscribers found.', 'newsletter-optin-box' ),
						'import'        => __( 'Import Subscribers', 'newsletter-optin-box' ),
					),
					'props'          => array_merge(
						$props,
						array(
							'tags'                     => array(
								'type'                 => 'TEXT',
								'is_tokens'            => true,
								'is_meta_key'          => true,
								'is_meta_key_multiple' => true,
								'description'          => __( "The subscriber's tags.", 'newsletter-optin-box' ),
							),

							'status'                   => array(
								'type'        => 'VARCHAR',
								'length'      => 12,
								'nullable'    => false,
								'default'     => 'subscribed',
								'description' => __( "The subscriber's status.", 'newsletter-optin-box' ),
								'enum'        => 'noptin_get_subscriber_statuses',
							),

							'source'                   => array(
								'type'        => 'VARCHAR',
								'length'      => 100,
								'description' => __( 'The subscription source.', 'newsletter-optin-box' ),
								'enum'        => 'noptin_get_subscription_sources',
								'nullable'    => true,
							),

							'ip_address'               => array(
								'type'        => 'VARCHAR',
								'length'      => 46,
								'description' => __( 'The IP address of the subscriber.', 'newsletter-optin-box' ),
								'nullable'    => true,
							),

							'conversion_page'          => array(
								'type'        => 'VARCHAR',
								'length'      => 255,
								'description' => __( 'The page the subscriber converted on.', 'newsletter-optin-box' ),
								'nullable'    => true,
							),

							'confirmed'                => array(
								'type'        => 'TINYINT',
								'length'      => 1,
								'nullable'    => false,
								'default'     => 0,
								'description' => __( 'Whether the subscriber has confirmed their email address.', 'newsletter-optin-box' ),
							),

							'confirm_key'              => array(
								'type'        => 'VARCHAR',
								'length'      => 32,
								'description' => __( 'The confirmation key.', 'newsletter-optin-box' ),
								'nullable'    => false,
								'readonly'    => true,
							),

							'date_created'             => array(
								'type'        => 'DATETIME',
								'nullable'    => false,
								'description' => __( 'Creation date for this subscriber.', 'newsletter-optin-box' ),
								'readonly'    => true,
							),

							'date_modified'            => array(
								'type'        => 'DATETIME',
								'nullable'    => false,
								'description' => __( 'Last modification date for this subscriber.', 'newsletter-optin-box' ),
								'readonly'    => true,
							),

							'activity'                 => array(
								'type'              => 'TEXT',
								'description'       => __( 'Subscriber activity', 'newsletter-optin-box' ),
								'sanitize_callback' => 'wp_kses_post_deep',
							),

							'total_emails_sent'        => array(
								'type'        => 'BIGINT',
								'length'      => 20,
								'nullable'    => false,
								'readonly'    => true,
								'default'     => 0,
								'description' => __( 'Total number of emails sent to this subscriber.', 'newsletter-optin-box' ),
							),

							'total_emails_opened'      => array(
								'type'        => 'BIGINT',
								'length'      => 20,
								'nullable'    => false,
								'readonly'    => true,
								'default'     => 0,
								'description' => __( 'Total number of emails opened by this subscriber.', 'newsletter-optin-box' ),
							),

							'total_links_clicked'      => array(
								'type'        => 'BIGINT',
								'length'      => 20,
								'nullable'    => false,
								'readonly'    => true,
								'default'     => 0,
								'description' => __( 'Total number of links clicked by this subscriber.', 'newsletter-optin-box' ),
							),

							'last_email_sent_date'     => array(
								'type'        => 'DATETIME',
								'description' => __( 'Date when subscriber was last sent an email.', 'newsletter-optin-box' ),
								'nullable'    => true,
								'readonly'    => true,
							),

							'last_email_opened_date'   => array(
								'type'        => 'DATETIME',
								'description' => __( 'Date when subscriber last opened an email.', 'newsletter-optin-box' ),
								'nullable'    => true,
								'readonly'    => true,
							),

							'last_email_clicked_date'  => array(
								'type'        => 'DATETIME',
								'description' => __( 'Date when subscriber last clicked a link in an email.', 'newsletter-optin-box' ),
								'nullable'    => true,
								'readonly'    => true,
							),

							'email_engagement_score'   => array(
								'type'        => 'DECIMAL',
								'length'      => '3,2',
								'nullable'    => false,
								'readonly'    => true,
								'default'     => 0.00,
								'description' => __( 'Engagement score (0.00 to 1.00).', 'newsletter-optin-box' ),
							),

							'edit_url'                 => array(
								'type'        => 'TEXT',
								'description' => __( "The subscriber's edit URL.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),

							'unsubscribe_url'          => array(
								'type'        => 'TEXT',
								'description' => __( "The subscriber's unsubscribe URL.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),

							'resubscribe_url'          => array(
								'type'        => 'TEXT',
								'description' => __( "The subscriber's resubscribe URL.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),

							'confirm_subscription_url' => array(
								'type'        => 'TEXT',
								'description' => __( "The subscriber's confirm subscription URL.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),

							'manage_preferences_url'   => array(
								'type'        => 'TEXT',
								'description' => __( "The subscriber's manage preferences URL.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),

							'send_email_url'           => array(
								'type'        => 'TEXT',
								'description' => __( "The subscriber's send email URL.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),

							'avatar_url'               => array(
								'type'        => 'TEXT',
								'description' => __( "The subscriber's avatar URL.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
								'length'      => 200,
							),

							'wp_user_id'               => array(
								'type'        => 'BIGINT',
								'description' => __( "The subscriber's WordPress user ID.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),
						)
					),

					'keys'           => array(
						'primary' => array( 'id' ),
						'unique'  => array( 'confirm_key', 'email' ),
					),
				),
			)
		);
	}
}
