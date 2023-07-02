<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'FrmFormAction' ) ) {
	die;
}

/**
 * Handles integrations with Formidable Forms
 * @link http://localhost/wpi/wp-admin/plugins.php?plugin_status=all&paged=1&s
 *
 * @since       1.5.5
 */
class Noptin_Formidable_Forms extends FrmFormAction {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Prepare action options...
        $action_ops = array(
            'classes'  => 'dashicons dashicons-forms',
            'active'   => true,
            'limit'    => 99,
            'priority' => 50,
            'event'    => array( 'create', 'update' ),
			'color'    => 'var(--orange)',
        );

		// ... then init the action.
        $this->FrmFormAction( 'noptin', 'Noptin', $action_ops );

    }

	/**
	 * Get the HTML for your action settings
	 *
	 * @param WP_Post $form_action Action oject.
	 * @param array $args Action args.
	 */
	public function form( $form_action, $args = array() ) {

		$post_content  = $form_action->post_content;
		$fields_name   = $this->get_field_name( 'noptin_custom_fields' );
		$form_fields   = FrmField::getAll( 'fi.form_id=' . (int) $args['form']->id . " and fi.type not in ('break', 'divider', 'end_divider', 'html', 'captcha', 'form')", 'field_order' );
		$custom_fields = wp_list_pluck( get_noptin_custom_fields(), 'label', 'merge_tag' );
		?>
			<p class="description"><?php __( 'Map form fields to Noptin if you would like to use the form as a newsletter subscription form.', 'newsletter-optin-box' ); ?></p>
			<table class="form-table frm-no-margin">
				<tbody>
					<?php foreach ( $custom_fields as $key => $value ) : ?>
					<tr>
						<th scope="row">
							<label for="noptin_map_<?php echo sanitize_html_class( $key ); ?>"><?php echo esc_html( $value ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( $fields_name ); ?>[<?php esc_attr( $key ); ?>]" id="noptin_map_<?php echo sanitize_html_class( $key ); ?>">
                        		<option value=""><?php esc_html_e( 'Map Field', 'newsletter-optin-box' ); ?></option>
								<?php foreach ( $form_fields as $form_field ) : ?>
									<option
										value="<?php echo esc_attr( $form_field->id ); ?>"
										<?php selected( isset( $post_content['noptin_custom_fields'][ $key ] ) && $post_content['noptin_custom_fields'][ $key ] === $form_field->id ); ?>
										><?php echo esc_html( FrmAppHelper::truncate( $form_field->name, 40 ) ); ?></option>
								<?php endforeach; ?>
							</select>

						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php
	}

	/**
	 * Processes the newsletter optin action.
	 *
	 * @param WP_Post $action
	 */
    public static function process_form( $action, $entry ) {

		// Retrieve subscriber details.
        $subscriber = self::get_custom_field_values( $entry, $action->post_content );

		// Set source.
		$subscriber['source'] = 'Formidable Forms';

		// And maybe the conversion page.
		if ( isset( $_REQUEST['referrer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $subscriber['conversion_page'] = esc_url_raw( $_REQUEST['referrer'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

		// Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

        // We need an email.
		if ( empty( $subscriber['email'] ) ) {
			return;
		}

		// Filter the subscriber fields.
		$subscriber = apply_filters( 'noptin_formidable_forms_integration_new_subscriber_fields', $subscriber );

		// Register the subscriber.
		add_noptin_subscriber( $subscriber );

    }

	/**
	 * Retrieves custom field values.
	 *
	 * @return array
	 */
	protected static function get_custom_field_values( $entry, $settings ) {

		// Abort if no custom fields were mapped.
		if ( empty( $settings['noptin_custom_fields'] ) ) {
			return array();
		}

		$vars = array();

		// Loop through each custom field...
		foreach ( $settings['noptin_custom_fields'] as $field_tag => $field_id ) {

			// ... Abort if it was not mapped.
			if ( empty( $field_id ) ) {
				continue;
			}

			// Retrieve value from the processed entry.
			if ( ! empty( $entry ) && isset( $entry->metas[ $field_id ] ) ) {
				$vars[ $field_tag ] = $entry->metas[ $field_id ];
			} elseif ( isset( $_POST['item_meta'][ $field_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				// Or the posted data.
				$vars[ $field_tag ] = $_POST['item_meta'][ $field_id ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}

        return $vars;
    }

}
