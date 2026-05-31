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

		if ( isset( $_GET['noptin_edit_automation_rule'] ) ) {
			$title = __( 'Edit Automation Rule', 'newsletter-optin-box' );
		} else {
			$title = __( 'Automation Rules', 'newsletter-optin-box' );
		}

		self::$hook_suffix = add_submenu_page(
			'noptin',
			$title,
			esc_html__( 'Automation Rules', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-automation-rules',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		if ( isset( $_GET['noptin_edit_automation_rule'] ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/edit.php';
		} else {
			include plugin_dir_path( __FILE__ ) . 'views/list.php';
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

		if ( isset( $_GET['noptin_edit_automation_rule'] ) ) {
			$script = 'automation-rule-editor';
		} else {
			$script = 'automation-rules';
		}

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
			$map = 'automation-rule-editor' === $script ? self::prepare_app() : array();
			wp_localize_script(
				'noptin-' . $script,
				'noptinEmailSettingsMisc',
				apply_filters(
					'noptin_email_settings_misc',
					array(
						'isTest'       => defined( 'NOPTIN_IS_TESTING' ),
						'integrations' => apply_filters( 'noptin_get_all_known_integrations', array() ),
						'ai'           => array(
							'disabled' => (bool) $disable_ai,
						),
						'data'         => array(
							'add_new'  => add_query_arg(
								array(
									'page' => 'noptin-automation-rules',
									'noptin_edit_automation_rule' => 0,
								),
								admin_url( 'admin.php' )
							),
							'triggers' => self::prepare_triggers_for_editor( \Hizzle\Noptin\Automation_Rules\Triggers\Main::all() ),
							'actions'  => self::prepare_triggers_for_editor( \Hizzle\Noptin\Automation_Rules\Actions\Main::all() ),
							'app'      => 'automation-rule-editor' === $script ? $map : array(),
						),
						'brand'        => noptin()->white_label->get_details(),
						'comparisons'  => noptin_get_conditional_logic_comparisons(),
					),
					$script
				)
			);

			wp_set_script_translations( 'noptin-' . $script, 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

			// Preload the automation rule being edited into wp.apiFetch cache.
			if ( 'automation-rule-editor' === $script ) {
				self::preload_current_rule_rest_api( $map['treeMap'] ?? array() );
			}
		}

		// Load the css.
		wp_enqueue_style( 'wp-components' );

		if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $script . '.css' ) ) {
			$version = empty( $config ) ? filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $script . '.css' ) : $config['version'];
			wp_enqueue_style(
				'noptin-' . $script,
				plugins_url( 'assets/css/style-' . $script . '.css', __DIR__ ),
				array(),
				$version
			);
		}
	}

	/**
	 * Preloads the current automation rule being edited into wp.apiFetch cache to speed up loading in the editor.
	 *
	 * @param array $tree_map The tree map of the current rule, used to preload all rules in the workflow.
	 */
	private static function preload_current_rule_rest_api( $tree_map = array() ) {
		$rule = noptin_get_current_automation_rule();

		// Preload paths.
		$preload_paths = array(
			'/noptin/v1/automation_rules/collection_schema',
		);

		if ( ! is_wp_error( $rule ) ) {
			if ( $rule->exists() ) {
				foreach ( $tree_map as $tree_rule ) {
					if ( empty( $tree_rule['id'] ) ) {
						continue;
					}

					$preload_paths[] = sprintf( '/noptin/v1/automation_rules/%d?context=view', $tree_rule['id'] );
				}
			}

			if ( ! empty( $rule->get_trigger_id() ) ) {
				$preload_paths[] = sprintf( '/noptin/v1/automation-rule-settings?trigger_id=%s&noptin_raw=true', $rule->get_trigger_id() );
			}

			if ( ! empty( $rule->get_action_id() ) ) {
				$preload_paths[] = sprintf( '/noptin/v1/automation-rule-settings?action_id=%s&noptin_raw=true', $rule->get_action_id() );
			}
		}

		$preload_data = array_reduce(
			$preload_paths,
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
	 * Fetches triggers for the automation rule editor.
	 *
	 * @param \Noptin_Abstract_Trigger_Action[] $triggers The triggers.
	 * @since 3.1.0
	 */
	private static function prepare_triggers_for_editor( $triggers ) {

		$triggers_data = array();

		foreach ( $triggers as $trigger ) {
			if ( ! empty( $trigger->depricated ) ) {
				continue;
			}

			$triggers_data[ $trigger->get_id() ] = array(
				'name'        => $trigger->get_id(),
				'label'       => $trigger->get_name(),
				'description' => $trigger->get_description(),
				'image'       => $trigger->get_image(),
				'category'    => $trigger->depricated ? '' : $trigger->category,
				'featured'    => $trigger->featured,
			);
		}

		return $triggers_data;
	}

	/**
	 * Fetches app data for the automation rule editor.
	 *
	 * @since 3.1.0
	 */
	private static function prepare_app() {
		$rule = noptin_get_current_automation_rule();

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
