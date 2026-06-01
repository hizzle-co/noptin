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
							'description' => 'Unique identifier for this resource.',
						),

						'action_id'        => array(
							'type'        => 'VARCHAR',
							'length'      => 200,
							'description' => 'The action ID.',
							'nullable'    => false,
						),

						'action_settings'  => array(
							'type'              => 'TEXT',
							'description'       => 'Action settings JSON',
							'extra_rest_schema' => array(
								'type' => array( 'object', 'array', 'null', 'string' ),
							),
						),

						'trigger_id'       => array(
							'type'        => 'VARCHAR',
							'length'      => 200,
							'description' => 'The trigger ID.',
							'nullable'    => false,
						),

						'trigger_settings' => array(
							'type'              => 'TEXT',
							'description'       => 'Trigger settings JSON',
							'extra_rest_schema' => array(
								'type' => array( 'object', 'array', 'null', 'string' ),
							),
						),

						'status'           => array(
							'type'        => 'TINYINT',
							'length'      => 1,
							'nullable'    => false,
							'default'     => 1, // 1 === active, 0 === inactive.
							'description' => 'The rule status',
						),

						'times_run'        => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => false,
							'default'     => 0,
							'readonly'    => true,
							'description' => 'The number of times this rule has run.',
						),

						'delay'            => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'default'     => 0,
							'description' => 'The number of seconds to wait before firing the action.',
						),

						'created_at'       => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'readonly'    => true,
							'description' => 'The date this rule was created.',
						),

						'updated_at'       => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'readonly'    => true,
							'description' => 'The date this rule was last modified.',
						),

						'workflow_name'    => array(
							'type'        => 'VARCHAR',
							'length'      => 200,
							'description' => 'The workflow name.',
						),

						'parent_id'        => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'default'     => 0,
							'description' => 'The parent rule ID. Defaults to 0. If set, this rule fires after the parent rule fires.',
						),

						'priority'         => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'default'     => 0,
							'description' => 'The execution priority. Lower values run first.',
						),

						'workflow_tree'    => array(
							'type'        => 'TEXT',
							'description' => "The rule\'s workflow tree.",
							'is_dynamic'  => true,
							'readonly'    => true,
						),

						'trigger_info'     => array(
							'type'        => 'TEXT',
							'description' => "The rule\'s trigger info.",
							'is_dynamic'  => true,
							'readonly'    => true,
						),

						'action_info'      => array(
							'type'        => 'TEXT',
							'description' => "The rule\'s action info.",
							'is_dynamic'  => true,
							'readonly'    => true,
						),

						'metadata'         => array(
							'type'        => 'TEXT',
							'description' => 'A key value array of additional metadata about this rule',
						),
					),

					'keys'          => array(
						'primary'    => array( 'id' ),
						'action_id'  => array( 'action_id' ),
						'trigger_id' => array( 'trigger_id' ),
						'parent_id'  => array( 'parent_id' ),
						'priority'   => array( 'priority' ),
					),
				),
			)
		);
	}
}
