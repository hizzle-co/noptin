<?php
/**
 * Displays a list of all automation rules
 */
namespace Hizzle\Noptin\Automation_Rules\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Rules list table class.
 */
class Table extends \Hizzle\Store\List_Table {

	/**
	 * Constructor function.
	 *
	 */
	public function __construct() {
		parent::__construct( \Hizzle\Store\Collection::instance( 'noptin_automation_rules' ) );
	}

	/**
	 *  Returns the query args.
	 */
	public function get_query_args() {
		return array_merge(
			parent::get_query_args(),
			array(
				'action_id_not' => 'email',
			)
		);
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

		$props = array(
			'editUrl' => $item->get_edit_url(),
			'ruleId'  => $item->get_id(),
			'status'  => $item->get_status(),
		);

		?>
			<div class="noptin-automation-rule-actions__app" data-app="<?php echo esc_attr( wp_json_encode( $props ) ); ?>">
				<!-- spinner -->
				<span class="spinner" style="visibility: visible; float: none;"></span>
				<!-- /spinner -->
			</div>
		<?php
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
		$title       = $trigger->get_rule_description( $item );
		$description = $trigger->get_rule_table_description( $item );
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
		$title       = $action->get_rule_description( $item );
		$description = $action->get_rule_table_description( $item );
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
     * Extra controls to be displayed between bulk actions and pagination
     *
     * @since 3.1.0
     * @access protected
     */
    public function extra_tablenav( $which ) {

		// Only show bottom button if items > 5.
		// Show top if items > 1.
		$show = 'top' === $which ? $this->has_items() : $this->has_items() && $this->total > 5;

		if ( $show ) {
			echo '<span class="noptin-automation-rules__editor--add-new__button"><span class="spinner" style="visibility: visible; float: none;"></span></span>';
		}
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {
		?>
			<div id="noptin-automation-rules__editor--add-new__in-table">
				<?php parent::no_items(); ?>
				<!-- spinner -->
				<span class="spinner" style="visibility: visible; float: none;"></span>
				<!-- /spinner -->
			</div>
		<?php
	}
}
