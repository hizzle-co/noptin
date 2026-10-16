<?php
/**
 * Automation Rules API: Automation Rules Admin.
 *
 * Contains the main admin class for Noptin automation rules
 *
 * @since   3.1.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin automation rules.
 *
 * @since 3.1.0
 * @internal
 * @ignore
 */
class Main {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		add_action( 'admin_menu', array( __CLASS__, 'automation_rules_menu' ), 40 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( __CLASS__, 'migrate_automation_rule_triggers' ) );
	}

	/**
	 * Automation rules menu.
	 */
	public static function automation_rules_menu() {

		$title = __( 'Automation Rules', 'newsletter-optin-box' );

		self::$hook_suffix = add_submenu_page(
			'noptin',
			$title,
			esc_html__( 'Automation Rules', 'newsletter-optin-box' ),
			get_noptin_collection_capability( 'automation_rules' ),
			'noptin-automation-rules',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		if ( current_user_can( get_noptin_collection_capability( 'automation_rules' ) ) ) {
			?>
			<div id="noptin-wrapper">
				<div id="noptin-automation-rules__app">
					<span class="spinner" style="visibility: visible; float: none;"></span>
				</div>
			</div>
			<?php
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

		$script            = 'automation-rules';
		$edited_rule_id    = self::get_edited_rule_id();
		$is_editor_context = null !== $edited_rule_id;

		$disable_ai = get_noptin_option( 'disable_ai', false );

		// Load the js.
		if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/js/' . $script . '.js' ) ) {
			$config = include plugin_dir_path( __DIR__ ) . 'assets/js/' . $script . '.asset.php';

			if ( ! $disable_ai ) {
				\Hizzle\Noptin\Emails\Admin\Main::load_ai_script();
				$config['dependencies'][] = 'noptin-ai';
			}

			wp_enqueue_script(
				'noptin-' . $script,
				plugins_url( 'assets/js/' . $script . '.js', __DIR__ ),
				$config['dependencies'],
				$config['version'],
				true
			);

			// Localize the script.
			$map = $is_editor_context ? self::prepare_app( $edited_rule_id ) : array();
			wp_localize_script(
				'noptin-' . $script,
				'noptinEmailSettingsMisc',
				apply_filters(
					'noptin_email_settings_misc',
					array(
						'config'       => \Hizzle\Noptin\Extensions\Catalog::config(),
						'isTest'       => defined( 'NOPTIN_IS_TESTING' ),
						'ai'           => array(
							'disabled' => (bool) $disable_ai,
						),
						'data'         => array(
							'add_new'  => add_query_arg(
								array(
									'page'          => 'noptin-automation-rules',
									'hizzlewp_path' => '/edit/0',
								),
								admin_url( 'admin.php' )
							),
							'app'      => $map,
						),
						'brand'        => noptin()->white_label->get_details(),
						'comparisons'  => noptin_get_conditional_logic_comparisons(),
					),
					$script
				)
			);

			wp_set_script_translations( 'noptin-' . $script, 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

			// Preload the automation rule being edited into wp.apiFetch cache.
			if ( $is_editor_context ) {
				self::preload_current_rule_rest_api( $edited_rule_id, $map['treeMap'] ?? array() );
			} else {
				self::preload_overview_api();
			}
		}

		// Load the css.
		wp_enqueue_style( 'wp-components' );

		foreach ( array_unique( array( $script ) ) as $style ) {
			if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $style . '.css' ) ) {
				$version = empty( $config ) ? filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $style . '.css' ) : $config['version'];
				wp_enqueue_style(
					'noptin-' . $style,
					plugins_url( 'assets/css/style-' . $style . '.css', __DIR__ ),
					array(),
					$version
				);
			}
		}
	}

	/**
	 * Fetches the rule id being edited from the current HizzleWP path.
	 *
	 * @return int|null
	 */
	private static function get_edited_rule_id() {
		$path = isset( $_GET['hizzlewp_path'] ) ? sanitize_text_field( wp_unslash( $_GET['hizzlewp_path'] ) ) : '';

		if ( ! preg_match( '#^/edit/(\d+)$#', $path, $matches ) ) {
			return null;
		}

		return absint( $matches[1] );
	}

	/**
	 * Preloads the current automation rule being edited into wp.apiFetch cache to speed up loading in the editor.
	 *
	 * @param int   $rule_id  The current rule id.
	 * @param array $tree_map The tree map of the current rule, used to preload all rules in the workflow.
	 */
	private static function preload_current_rule_rest_api( $rule_id, $tree_map = array() ) {
		$rule = noptin_get_automation_rule( $rule_id );

		// Preload paths.
		$preload_paths = array(
			'/noptin/v1/automation_rules/collection_schema',
		);

		if ( ! is_wp_error( $rule ) ) {
			$unique_id = \Hizzle\WordPress\ScriptManager::$request_uuid;

			if ( $rule->exists() && ! empty( $unique_id ) ) {
				foreach ( $tree_map as $tree_rule ) {
					if ( empty( $tree_rule['id'] ) ) {
						continue;
					}

					$preload_paths[] = sprintf(
						'/noptin/v1/automation_rules/%d?context=view&uniqid=%s',
						$tree_rule['id'],
						$unique_id
					);
				}
			}

			if ( ! empty( $rule->get_trigger_id() ) ) {
				$preload_paths[] = sprintf( '/noptin/v1/automation-rule-settings?trigger_id=%s&noptin_raw=true', $rule->get_trigger_id() );
			}

			// Each step has their own action.
			foreach ( $tree_map as $tree_rule ) {
				if ( empty( $tree_rule['action_id'] ) ) {
					continue;
				}

				$preload_paths[] = sprintf(
					'/noptin/v1/automation-rule-settings?action_id=%s&noptin_raw=true',
					$tree_rule['action_id']
				);
			}
		}

		$preload_data = array_reduce(
			array_unique( $preload_paths ),
			'rest_preload_api_request',
			array()
		);

		wp_add_inline_script(
			'wp-api-fetch',
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
				wp_json_encode( $preload_data )
			),
			'after'
		);
	}

	/**
	 * Preloads the overview api routes.
	 */
	private static function preload_overview_api() {
		$unique_id = \Hizzle\WordPress\ScriptManager::$request_uuid;

		// Preload paths.
		$preload_paths = array(
			'/noptin/v1/automation_rules/collection_schema',
			sprintf( '/noptin/v1/automation_rules?context=view&uniqid=%s&per_page=1&parent_id=0&action_id_not=email', $unique_id ),
			sprintf( '/noptin/v1/automation_rules?context=view&uniqid=%s&per_page=1&parent_id=0&action_id_not=email&status=true', $unique_id ),
			sprintf( '/noptin/v1/automation_rules?context=view&uniqid=%s&per_page=1&parent_id=0&action_id_not=email&status=false', $unique_id ),
		);

		foreach ( self::get_top_rule_column_values( 'trigger_id' ) as $trigger_id ) {
			$preload_paths[] = sprintf( '/noptin/v1/automation-rule-settings?trigger_id=%s&noptin_raw=true', $trigger_id );
		}

		foreach ( self::get_top_rule_column_values( 'action_id' ) as $action_id ) {
			$preload_paths[] = sprintf( '/noptin/v1/automation-rule-settings?action_id=%s&noptin_raw=true', $action_id );
		}

		$preload_data = array_reduce(
			array_unique( $preload_paths ),
			'rest_preload_api_request',
			array()
		);

		wp_add_inline_script(
			'wp-api-fetch',
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
				wp_json_encode( $preload_data )
			),
			'after'
		);
	}

	/**
	 * Fetches the most used automation rule values for a given column.
	 *
	 * @param string $column The column to query.
	 * @param int    $limit  The maximum number of values to return.
	 * @return string[]
	 */
	private static function get_top_rule_column_values( $column, $limit = 10 ) {
		if ( ! in_array( $column, array( 'trigger_id', 'action_id' ), true ) || ! did_action( 'noptin_db_init' ) ) {
			return array();
		}

		$limit = absint( $limit );

		if ( empty( $limit ) ) {
			return array();
		}

		$values = noptin()->db()->query(
			'automation_rules',
			array(
				'aggregate' => array(
					'id' => array( 'COUNT' ),
				),
				'groupby'   => array( $column ),
				'orderby'   => array(
					'count_id' => 'DESC',
					$column    => 'ASC',
				),
				'per_page'  => $limit * 2,
			),
			'aggregate'
		);

		if ( is_wp_error( $values ) || empty( $values ) ) {
			return array();
		}

		$values = wp_list_pluck( $values, $column );
		$values = array_filter( array_unique( array_map( 'sanitize_text_field', (array) $values ) ) );

		return array_slice( array_values( $values ), 0, $limit );
	}

	/**
	 * Fetches app data for the automation rule editor.
	 *
	 * @since 3.1.0
	 */
	private static function prepare_app( $rule_id ) {
		$rule = noptin_get_automation_rule( $rule_id );

		if ( is_wp_error( $rule ) ) {
			return array();
		}

		return array(
			'treeMap' => $rule->to_tree_map(),
		);
	}

	/**
	 * Migrates automation rule triggers.
	 *
	 * @since 3.0.0
	 */
	public static function migrate_automation_rule_triggers() {
		$migrators = apply_filters( 'noptin_automation_rule_migrate_triggers', array() );

		// Nothing to migrate.
		if ( empty( $migrators ) || absint( get_option( 'noptin_db_version', 0 ) ) < noptin()->db_version ) {
			return;
		}

		$migrated = (array) get_option( 'noptin_automation_rule_migrated_triggers', array() );

		foreach ( $migrators as $migrator ) {
			if ( empty( $migrator['trigger_id'] ) || empty( $migrator['id'] ) || in_array( $migrator['id'], $migrated, true ) ) {
				continue;
			}

			/** @var \Hizzle\Noptin\DB\Automation_Rule[] $rules */
			$rules = noptin_get_automation_rules(
				array(
					'trigger_id' => $migrator['trigger_id'],
				)
			);

			foreach ( $rules as $rule ) {

				// Abort if trigger id does not match.
				if ( $rule->get_trigger_id() !== $migrator['trigger_id'] ) {
					continue;
				}

				$previous_trigger = $rule->get_trigger_id();
				call_user_func_array( $migrator['callback'], array( &$rule ) );
				$rule->save();

				if ( $previous_trigger !== $rule->get_trigger_id() && 'email' === $rule->get_action_id() ) {
					$email_id = $rule->get_action_setting( 'automated_email_id' );

					if ( ! empty( $email_id ) ) {
						update_post_meta( $email_id, 'automation_type', 'automation_rule_' . $rule->get_trigger_id() );
					}
				}
			}

			$migrated[] = $migrator['id'];
		}

		update_option( 'noptin_automation_rule_migrated_triggers', $migrated );
	}
}
