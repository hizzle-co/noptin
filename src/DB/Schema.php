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
							'type'        => 'TEXT',
							'description' => __( 'Action settings JSON', 'newsletter-optin-box' ),
						),

						'trigger_id'       => array(
							'type'        => 'VARCHAR',
							'length'      => 200,
							'description' => __( 'The trigger ID.', 'newsletter-optin-box' ),
							'nullable'    => false,
						),

						'trigger_settings' => array(
							'type'        => 'TEXT',
							'description' => __( 'Trigger settings JSON', 'newsletter-optin-box' ),
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
							'description' => __( 'The number of times this rule has run.', 'newsletter-optin-box' ),
						),

						'delay'            => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'description' => __( 'The number of seconds to wait before firing the action.', 'newsletter-optin-box' ),
						),

						'created_at'       => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'description' => __( 'The date this rule was created.', 'newsletter-optin-box' ),
						),

						'updated_at'       => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
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

}
