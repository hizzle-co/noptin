<?php
/**
 * Displays a list of all automation rules
 */

defined( 'ABSPATH' ) || exit;

/**
 * Automation rules table class.
 */
class Noptin_Automation_Rules_Table extends \Hizzle\Store\List_Table {

	/**
	 * Constructor function.
	 *
	 */
	public function __construct() {
		parent::__construct( \Hizzle\Store\Collection::instance( 'noptin_automation_rules' ) );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.2.8
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $item The current item.
	 */
	public function single_row( $item ) {

		echo '<tr class="noptin_automation_rule_' . esc_attr( $item->get_id() ) . '" data-id="' . esc_attr( $item->get_id() ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';

	}

	/**
	 * Displays available actions.
	 *
	 * @param  \Hizzle\Noptin\DB\Automation_Rule $item item.
	 * @return string
	 */
	public function column_actions( $item ) {

		$actions = array(
			'edit'   => array(
				'url'   => $item->get_edit_url(),
				'label' => __( 'Edit', 'newsletter-optin-box' ),
				'icon'  => 'dashicons dashicons-edit',
			),
			'delete' => array(
				'label' => __( 'Delete', 'newsletter-optin-box' ),
				'icon'  => 'dashicons dashicons-trash',
			),
		);

		$html = '';

		foreach ( $actions as $action => $data ) {

			$html .= sprintf(
				'<a href="%s" title="%s" class="noptin-tip noptin-automation-rule-action noptin-automation-rule-action__%s">%s</a>',
				empty( $data['url'] ) ? '#' : esc_url( $data['url'] ),
				empty( $data['label'] ) ? '' : esc_attr( $data['label'] ),
				esc_attr( $action ),
				sprintf(
					'<span class="%s" aria-label="%s"></span>',
					empty( $data['icon'] ) ? 'dashicons dashicons-admin-generic' : esc_attr( $data['icon'] ),
					empty( $data['label'] ) ? '' : esc_attr( $data['label'] )
				)
			);

		}

		$status = sprintf(
			'<label class="noptin-automation-rule-action__switch-wrapper noptin-tip" title="%s">
				<input type="checkbox" class="noptin-toggle-automation-rule" %s>
				<span class="noptin-automation-rule-action__switch"></span>
			</label>',
			esc_attr( __( 'Enable or disable this automation rule', 'newsletter-optin-box' ) ),
			checked( ! empty( $item->get_status() ), true, false )
		);

		return '<div class="noptin-automation-rule-actions">' . $status . $html . '</div>';

	}

	/**
	 * Returns the icon image.
	 *
	 * @param  string|array $image image.
	 * @param string       $title title.
	 * @return string
	 */
	public function get_icon_image( $image, $title ) {

		// URLs.
		if ( is_string( $image ) && 0 === strpos( $image, 'http' ) ) {
			return sprintf(
				'<img src="%s" alt="%s" />',
				esc_url( $image ),
				esc_attr( $title )
			);
		}

		// Dashicons.
		if ( empty( $image ) ) {
			$image = 'email';
		}

		if ( is_string( $image ) ) {
			return sprintf(
				'<span class="dashicons dashicons-%s"></span>',
				esc_attr( $image )
			);
		}

		// SVG or Dashicons with fill color.
		if ( is_array( $image ) ) {
			$fill    = isset( $image['fill'] ) ? $image['fill'] : '#008000';
			$path    = isset( $image['path'] ) ? $image['path'] : '';
			$viewbox = isset( $image['viewBox'] ) ? $image['viewBox'] : '0 0 64 64';
			$icon    = isset( $image['icon'] ) ? $image['icon'] : 'email';

			if ( ! empty( $path ) ) {
				return sprintf(
					'<svg viewbox="%s" xmlns="http://www.w3.org/2000/svg"><path fill="%s" d="%s"></path></svg>',
					esc_attr( $viewbox ),
					esc_attr( $fill ),
					esc_attr( $path )
				);
			}

			return sprintf(
				'<span class="dashicons dashicons-%s" style="color: %s"></span>',
				esc_attr( $icon ),
				esc_attr( $fill )
			);
		}

		return '<span class="dashicons dashicons-email"></span>';
	}

	/**
	 * Displays the trigger column.
	 *
	 * @param  \Hizzle\Noptin\DB\Automation_Rule $item item.
	 * @return string
	 */
	public function column_trigger( $item ) {

		// Fetch trigger.
		$trigger = $item->get_trigger();

		// Abort if the trigger is invalid.
		if ( empty( $trigger ) ) {
			return sprintf(
				'%s<div class="noptin-rule-error">%s</div>',
				esc_html( $item->get_trigger_id() ),
				__( 'Your site does not support this trigger.', 'newsletter-optin-box' )
			);
		}

		// Prepare the texts.
		$title       = $trigger->get_rule_description( $item->get_deprecated_rule() );
		$description = $trigger->get_rule_table_description( $item->get_deprecated_rule() );
		$image       = $trigger->get_image();

		return sprintf(
			'<div class="noptin-rule-trigger">
				<div class="noptin-rule-image">%s</div>
				<div class="noptin-rule-name">
					<div class="row-title">
						<a href="%s">%s</a>
					</div>
					%s
				</div>
			</div>',
			$this->get_icon_image( $image, $title ),
			esc_url( $item->get_edit_url() ),
			esc_html( $title ),
			empty( $description ) ? '' : "<div class='noptin-rule-description'>$description</div>"
		);

	}

	/**
	 * Displays the action column.
	 *
	 * @param  \Hizzle\Noptin\DB\Automation_Rule $item item.
	 * @return string
	 */
	public function column_action( $item ) {

		// Fetch action.
		$action = $item->get_action();

		// Abort if the action is invalid.
		if ( empty( $action ) ) {
			return sprintf(
				'%s<div class="noptin-rule-error">%s</div>',
				esc_html( $item->get_action_id() ),
				__( 'Your site does not support this action.', 'newsletter-optin-box' )
			);
		}

		// Prepare the text.
		$title       = $action->get_rule_description( $item->get_deprecated_rule() );
		$description = $action->get_rule_table_description( $item->get_deprecated_rule() );
		$image       = $action->get_image();

		return sprintf(
			'<div class="noptin-rule-action">
				<div class="noptin-rule-image">%s</div>
				<div class="noptin-rule-name">%s%s</div>
			</div>',
			$this->get_icon_image( $image, $title ),
			esc_html( $title ),
			empty( $description ) ? '' : "<div class='noptin-rule-description'>$description</div>"
		);

	}

	/**
	 * [OPTIONAL] Return array of bulk actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array();
	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'trigger'    => __( 'Trigger', 'newsletter-optin-box' ),
			'action'     => __( 'Action', 'newsletter-optin-box' ),
			'times_run'  => __( 'Times Run', 'newsletter-optin-box' ),
			'created_at' => __( 'Created', 'newsletter-optin-box' ),
			'updated_at' => __( 'Updated', 'newsletter-optin-box' ),
			'actions'    => __( 'Actions', 'newsletter-optin-box' ),
		);

		/**
		 * Filters the columns shown in an automation rules table.
		 *
		 * @param array $columns Automations rules table columns.
		 */
		return apply_filters( 'manage_noptin_automation_rules_table_columns', $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'id'         => array( 'id', true ),
			'trigger'    => array( 'trigger_id', true ),
			'action'     => array( 'action_id', true ),
			'times_run'  => array( 'times_run', true ),
			'created_at' => array( 'created_at', true ),
			'updated_at' => array( 'updated_at', true ),
		);

		/**
		 * Filters the sortable columns in the automation rules table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_noptin_automation_rules_sortable_table_columns', $sortable );
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {

		echo "<div style='min-height: 320px; display: flex; align-items: center; justify-content: center; flex-flow: column;'>";
		echo '<svg width="100" height="100" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path style="fill: #039be5;" d="M6.72 20.492c1.532.956 3.342 1.508 5.28 1.508 1.934 0 3.741-.55 5.272-1.503l1.24 1.582c-1.876 1.215-4.112 1.921-6.512 1.921-2.403 0-4.642-.708-6.52-1.926l1.24-1.582zm17.28-1.492h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm-18 0h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm6-11c-3.037 0-5.5 2.462-5.5 5.5 0 3.037 2.463 5.5 5.5 5.5s5.5-2.463 5.5-5.5c0-3.038-2.463-5.5-5.5-5.5zm.306 1.833h-.612v.652c-1.188.164-1.823.909-1.823 1.742 0 1.49 1.74 1.717 2.309 1.982.776.347.632 1.069-.07 1.229-.609.137-1.387-.103-1.971-.33l-.278 1.005c.546.282 1.201.433 1.833.444v.61h.612v-.644c1.012-.142 1.834-.7 1.833-1.75 0-1.311-1.364-1.676-2.41-2.167-.635-.33-.555-1.118.355-1.171.505-.031 1.024.119 1.493.284l.221-1.007c-.554-.168-1.05-.245-1.492-.257v-.622zm8.694 2.167c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25zm-18 0c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25zm5-11.316v2.149c-2.938 1.285-5.141 3.942-5.798 7.158l-2.034-.003c.732-4.328 3.785-7.872 7.832-9.304zm8 0c4.047 1.432 7.1 4.976 7.832 9.304l-2.034.003c-.657-3.216-2.86-5.873-5.798-7.158v-2.149zm-1 6.316h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm-3-7c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25z"/></svg>';

		echo '<div style="margin-top: 40px; text-align: center;"><p class="description" style="font-size: 16px;">';
		esc_html_e( 'Automation rules are simple "if this, then that" commands. Trigger an action when a product is purchased, a user creates an account, someone is tagged, etc.', 'newsletter-optin-box' );
		echo '</p>';

		printf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			esc_html__( '%1$sCreate your first automation rule%2$s', 'newsletter-optin-box' ),
			"<p><a style='margin: 20px auto;' class='no-rule-create-new-automation-rule button button-primary' href='" . esc_url( add_query_arg( 'noptin_create_automation_rule', '1' ) ) . "'>",
			'</a></p>'
		);

		echo "<p class='description'><a style='color: #616161; text-decoration: underline;' href='" . esc_html( noptin_get_upsell_url( '/guide/automation-rules/', 'learn-more', 'automation-rules' ) ) . "' target='_blank'>" . esc_html__( 'Or Learn more', 'newsletter-optin-box' ) . '</a></p>';
		echo '</div></div>';
	}


}


