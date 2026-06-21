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
			get_noptin_capability(),
			'noptin-automation-rules',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		if ( current_user_can_manage_noptin() ) {
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
						'isTest'       => defined( 'NOPTIN_IS_TESTING' ),
						'integrations' => apply_filters( 'noptin_get_all_known_integrations', array() ),
						'ai'           => array(
							'disabled' => (bool) $disable_ai,
						),
						'license'      => self::prepare_license_for_editor(),
						'data'         => array(
							'add_new'       => add_query_arg(
								array(
									'page'          => 'noptin-automation-rules',
									'hizzlewp_path' => '/edit/0',
								),
								admin_url( 'admin.php' )
							),
							'triggers'      => self::prepare_triggers_for_editor( \Hizzle\Noptin\Automation_Rules\Triggers\Main::all() ),
							'actions'       => self::prepare_triggers_for_editor( \Hizzle\Noptin\Automation_Rules\Actions\Main::all() ),
							'add_new_cards' => self::prepare_add_new_cards( ! $disable_ai ),
							'app'           => $map,
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
			}

			self::preload_overview_api();
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
	 * Prepares add-new wizard cards.
	 *
	 * @param bool $ai_enabled Whether AI is enabled.
	 * @return array
	 */
	private static function prepare_add_new_cards( $ai_enabled ) {
		$cards = array();

		if ( $ai_enabled ) {
			$cards[] = array(
				'id'          => 'ai',
				'step'        => 'ai',
				'category'    => __( 'Create', 'newsletter-optin-box' ),
				'image'       => 'superhero',
				'title'       => __( 'Generate with AI', 'newsletter-optin-box' ),
				'description' => __( 'Describe the automation you want and let Noptin draft the first version.', 'newsletter-optin-box' ),
				'keywords'    => array( 'ai', 'generate', 'assistant' ),
			);
		}

		$cards[] = array(
			'id'          => 'manual',
			'step'        => 'manual',
			'category'    => __( 'Create', 'newsletter-optin-box' ),
			'image'       => 'admin-tools',
			'title'       => __( 'Start from scratch', 'newsletter-optin-box' ),
			'description' => __( 'Choose what starts the rule, then choose what Noptin should do.', 'newsletter-optin-box' ),
			'keywords'    => array( 'manual', 'custom', 'blank' ),
		);

		$cards = array_merge( $cards, self::prepare_example_cards() );

		/**
		 * Filters add-new automation rule wizard cards.
		 *
		 * Cards support:
		 * - step: ai|manual|example
		 * - image: ImageOrIcon-compatible image config
		 * - title
		 * - description
		 * - tree: required when step is example
		 * - keywords: optional search keywords
		 *
		 * @param array $cards Add-new cards.
		 */
		return apply_filters( 'noptin_automation_rules_add_new_cards', $cards );
	}

	/**
	 * Prepares example automation rule cards.
	 *
	 * @return array
	 */
	private static function prepare_example_cards() {
		$file = __DIR__ . '/examples.json';

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return array();
		}

		$examples = wp_json_file_decode( $file, array( 'associative' => true ) );

		if ( ! is_array( $examples ) ) {
			return array();
		}

		$cards = array();

		foreach ( $examples as $example ) {
			$card = self::prepare_example_card( $example );

			if ( $card ) {
				$cards[] = $card;
			}
		}

		return $cards;
	}

	/**
	 * Prepares a single example automation rule card.
	 *
	 * @param array $example Example data.
	 * @return array|null
	 */
	private static function prepare_example_card( $example ) {
		if ( ! is_array( $example ) || empty( $example['trigger_id'] ) || empty( $example['action_id'] ) ) {
			return null;
		}

		$trigger_id = sanitize_key( $example['trigger_id'] );
		$action_id  = sanitize_key( $example['action_id'] );

		if (
			! \Hizzle\Noptin\Automation_Rules\Triggers\Main::exists( $trigger_id ) ||
			! \Hizzle\Noptin\Automation_Rules\Actions\Main::exists( $action_id )
		) {
			return null;
		}

		$title = empty( $example['title'] ) ? '' : sanitize_text_field( $example['title'] );

		if ( '' === $title ) {
			return null;
		}

		$uuid = wp_generate_uuid4();
		$rule = array(
			'id'               => 0,
			'action_id'        => $action_id,
			'trigger_id'       => $trigger_id,
			'action_settings'  => empty( $example['action_settings'] ) || ! is_array( $example['action_settings'] ) ? array() : $example['action_settings'],
			'status'           => true,
			'trigger_settings' => empty( $example['trigger_settings'] ) || ! is_array( $example['trigger_settings'] ) ? array() : $example['trigger_settings'],
			'times_run'        => 0,
			'created_at'       => '',
			'updated_at'       => '',
			'delay'            => isset( $example['delay'] ) ? absint( $example['delay'] ) : 0,
			'workflow_name'    => empty( $example['workflow_name'] ) ? $title : sanitize_text_field( $example['workflow_name'] ),
			'parent_id'        => 0,
			'priority'         => 0,
			'metadata'         => array(
				'permanent_key' => $uuid,
				'parent_key'    => '',
			),
		);

		return array(
			'id'          => empty( $example['id'] ) ? $uuid : sanitize_key( $example['id'] ),
			'step'        => 'example',
			'category'    => empty( $example['category'] ) ? __( 'Quickstart examples', 'newsletter-optin-box' ) : sanitize_text_field( $example['category'] ),
			'image'       => isset( $example['image'] ) ? $example['image'] : 'welcome-widgets-menus',
			'title'       => $title,
			'description' => empty( $example['description'] ) ? '' : sanitize_text_field( $example['description'] ),
			'keywords'    => empty( $example['keywords'] ) || ! is_array( $example['keywords'] ) ? array() : array_values( array_map( 'sanitize_text_field', $example['keywords'] ) ),
			'tree'        => array(
				$uuid => array(
					'id'        => 0,
					'uuid'      => $uuid,
					'parent_id' => 0,
					'children'  => array(),
					'action_id' => $action_id,
					'data'      => $rule,
				),
			),
		);
	}

	/**
	 * Prepares license details for the editor.
	 *
	 * @return array
	 */
	private static function prepare_license_for_editor() {
		return array(
			'key'          => class_exists( '\Noptin_COM' ) ? \Noptin_COM::get_active_license_key() : '',
			'activate_url' => admin_url( 'admin.php?page=noptin-addons' ),
			'upgrade_url'  => function_exists( 'noptin_get_upsell_url' ) ? noptin_get_upsell_url( 'pricing', 'license', 'automationrules' ) : 'https://noptin.com/pricing/',
		);
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
		// Preload paths.
		$preload_paths = array(
			'/noptin/v1/automation_rules/collection_schema',
		);

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
