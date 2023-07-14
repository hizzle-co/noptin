<?php

namespace Hizzle\Noptin\DB;

/**
 * Contains the main DB schema class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main DB schema class.
 */
class Schema {

	/**
	 * @var string The database schema.
	 */
	protected $schema;

	/**
	 * Loads the class.
	 *
	 */
	public function __construct() {
		add_filter( 'noptin_db_schema', array( $this, 'add_automation_rules_table' ) );
		add_filter( 'noptin_db_schema', array( $this, 'add_subscribers_table' ) );
	}

	/**
	 * Retrieves the database schema.
	 *
	 * @return array
	 */
	public function get_schema() {

		if ( ! empty( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = apply_filters( 'noptin_db_schema', array() );
		return $this->schema;
	}

	/**
	 * Adds the automation rules table to the schema.
	 *
	 * @param array $schema The database schema.
	 * @return array
	 */
	public function add_automation_rules_table( $schema ) {

		return array_merge(
			$schema,
			array(

				// Automation rules.
				'automation_rules' => array(
					'object'        => '\Hizzle\Noptin\DB\Automation_Rule',
					'singular_name' => 'automation_rule',
					'props'         => array(

						'id'               => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => false,
							'extra'       => 'AUTO_INCREMENT',
							'description' => __( 'Unique identifier for this resource.', 'newsletter-optin-box' ),
						),

						'action_id'        => array(
							'type'        => 'VARCHAR',
							'length'      => 200,
							'description' => __( 'The action ID.', 'newsletter-optin-box' ),
							'nullable'    => false,
						),

						'action_settings'  => array(
							'type'              => 'TEXT',
							'description'       => __( 'Action settings JSON', 'newsletter-optin-box' ),
							'extra_rest_schema' => array(
								'type' => array( 'object', 'array', 'null', 'string' ),
							),
						),

						'trigger_id'       => array(
							'type'        => 'VARCHAR',
							'length'      => 200,
							'description' => __( 'The trigger ID.', 'newsletter-optin-box' ),
							'nullable'    => false,
						),

						'trigger_settings' => array(
							'type'              => 'TEXT',
							'description'       => __( 'Trigger settings JSON', 'newsletter-optin-box' ),
							'extra_rest_schema' => array(
								'type' => array( 'object', 'array', 'null', 'string' ),
							),
						),

						'status'           => array(
							'type'        => 'TINYINT',
							'length'      => 1,
							'nullable'    => false,
							'default'     => 1, // 1 === active, 0 === inactive.
							'description' => __( 'The rule status', 'newsletter-optin-box' ),
						),

						'times_run'        => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => false,
							'default'     => 0,
							'readonly'    => true,
							'description' => __( 'The number of times this rule has run.', 'newsletter-optin-box' ),
						),

						'delay'            => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'default'     => 0,
							'description' => __( 'The number of seconds to wait before firing the action.', 'newsletter-optin-box' ),
						),

						'created_at'       => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'readonly'    => true,
							'description' => __( 'The date this rule was created.', 'newsletter-optin-box' ),
						),

						'updated_at'       => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'readonly'    => true,
							'description' => __( 'The date this rule was last modified.', 'newsletter-optin-box' ),
						),

						'metadata'         => array(
							'type'        => 'TEXT',
							'description' => __( 'A key value array of additional metadata about this rule', 'newsletter-optin-box' ),
						),
					),

					'keys'          => array(
						'primary'    => array( 'id' ),
						'action_id'  => array( 'action_id' ),
						'trigger_id' => array( 'trigger_id' ),
					),
				),
			)
		);
	}

	/**
	 * Adds the subscribers table to the schema.
	 *
	 * @param array $schema The database schema.
	 * @return array
	 */
	public function add_subscribers_table( $schema ) {

		// Basic props.
		$props = array(

			'id'         => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'extra'       => 'AUTO_INCREMENT',
				'description' => __( 'Unique identifier for this resource.', 'newsletter-optin-box' ),
			),

			'name'       => array(
				'type'        => 'VARCHAR',
				'length'      => 200,
				'description' => __( "The subscriber's name.", 'newsletter-optin-box' ),
				'is_dynamic'  => true,
			),

			'first_name' => array(
				'type'        => 'VARCHAR',
				'length'      => 100,
				'description' => __( "The subscriber's first name.", 'newsletter-optin-box' ),
				'nullable'    => false,
				'default'     => '',
			),

			'last_name'  => array(
				'type'        => 'VARCHAR',
				'length'      => 100,
				'description' => __( "The subscriber's last name.", 'newsletter-optin-box' ),
				'nullable'    => false,
				'default'     => '',
			),

			'email'      => array(
				'type'        => 'VARCHAR',
				'length'      => 255,
				'description' => __( "The subscriber's email address.", 'newsletter-optin-box' ),
				'nullable'    => false,
			),
		);

		// Custom fields.
		foreach ( get_noptin_custom_fields() as $custom_field ) {

			// Skip first name, last name and email.
			if ( ! in_array( $custom_field['merge_tag'], array( 'first_name', 'last_name', 'email' ), true ) ) {
				$props = array_merge( $props, noptin_convert_custom_field_to_schema( $custom_field ) );
			}
		}

		return array_merge(
			$schema,
			array(

				// Subscribers.
				'subscribers' => array(
					'object'         => '\Hizzle\Noptin\DB\Subscriber',
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
								'type'        => 'TEXT',
								'description' => __( 'Subscriber activity', 'newsletter-optin-box' ),
							),

							'sent_campaigns'           => array(
								'type'        => 'TEXT',
								'description' => __( 'An array of sent campaigns', 'newsletter-optin-box' ),
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
							),

							'wp_user_id'               => array(
								'type'        => 'BIGINT',
								'description' => __( "The subscriber's WordPress user ID.", 'newsletter-optin-box' ),
								'is_dynamic'  => true,
								'readonly'    => true,
							),
						)
					),

					'keys'          => array(
						'primary' => array( 'id' ),
						'unique'  => array( 'confirm_key', 'email' ),
					),
				),
			)
		);
	}

}
