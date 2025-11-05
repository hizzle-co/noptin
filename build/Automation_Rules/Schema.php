<?php

namespace Hizzle\Noptin\Automation_Rules;

defined( 'ABSPATH' ) || exit;

/**
 * The automation rules DB schema class.
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
	 * Adds the automation rules table to the schema.
	 *
	 * @param array $schema The database schema.
	 * @return array
	 */
	public static function add_to_schema( $schema ) {

		return array_merge(
			$schema,
			array(

				// Automation rules.
				'automation_rules' => array(
					'object'        => '\Hizzle\Noptin\Automation_Rules\Automation_Rule',
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
}
