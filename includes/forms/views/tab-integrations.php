<?php
/**
 * Displays the integrations tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$all_settings          = $form->settings;
$available_connections = get_noptin_connection_providers();

printf(
	'<h2 class="screen-reader-text">%s</h2>',
	esc_html__( 'Form Integrations', 'newsletter-optin-box' )
);

printf(
	'<p class="description">%s</p>',
	__( 'Noptin also allows you to add new subscribers to an external email service provider.', 'newsletter-optin-box' )
);

if ( empty( $available_connections ) ) {

	$all_integrations = Noptin_COM::get_integrations();

	if ( is_array( $all_integrations ) ) {

		echo '<div>';
		foreach ( Noptin_COM::get_integrations() as $slug => $data ) {

			?>

			<fieldset id="noptin-form-integrations-panel-<?php echo esc_attr( $slug ); ?>" class="noptin-settings-panel noptin-settings-panel__compact noptin-settings-panel__hidden">
				<button
					aria-expanded="false"
					aria-controls="noptin-form-integrations-panel-<?php echo esc_attr( $slug ); ?>-content"
					type="button"
					class="noptin-accordion-trigger"
					><span class="title"><?php echo esc_html( $data->title ); ?></span>
					<span class="badge orange"><?php esc_html_e( 'Not Installed', 'newsletter-optin-box' ); ?></span>
					<span class="icon"></span>
				</button>

				<div class="noptin-settings-panel__content" id="noptin-form-integrations-panel-<?php echo esc_attr( $slug ); ?>-content">
					<span class="dashicons dashicons-info" style="margin-right: 10px; color: #03a9f4; "></span>
					<?php
						printf(
							esc_html__( 'Install the %s to add new subscribers to %s.', 'newsletter-optin-box' ),
							sprintf(
								'<a target="_blank" href="%s">%s</a>',
								esc_url( $data->href ),
								sprintf(
									__( '%s addon', 'newsletter-optin-box' ),
									esc_html( $data->title )
								)
							),
							esc_html( $data->title )
						);
					?>
				</div>

			</fieldset>

			<?php
		}
		echo '</div>';

	}

} else {

	// Display connections.
	foreach ( $available_connections as $key => $connection ) {

		?>

		<fieldset id="noptin-form-integrations-panel-<?php echo esc_attr( $key ); ?>" class="noptin-settings-panel">
			<button
				aria-expanded="true"
				aria-controls="noptin-form-integrations-panel-<?php echo esc_attr( $key ); ?>-content"
				type="button"
				class="noptin-accordion-trigger"
				><span class="title"><?php echo esc_html( $connection->name ); ?></span>
				<span class="icon"></span>
			</button>

			<div class="noptin-settings-panel__content" id="noptin-form-integrations-panel-<?php echo esc_attr( $key ); ?>-content">

				<?php if ( ! $connection->is_connected() ) : ?>
					<p style="color:#F44336;"><?php
						printf(
							'Error: %s',
							! empty( $connection->last_error ) ? esc_html( $connection->last_error ) : sprintf( __( 'You are not connected to %s', 'newsletter-optin-box' ), $connection->name )
						);
					?></p>
				<?php elseif ( ! empty( $connection->list_providers ) ): ?>

					<table class="form-table noptin-form-settings noptin-integration-settings">

						<tr valign="top" class="form-field-row form-field-row-list">
							<th scope="row">
								<label for="noptin-form-<?php echo esc_attr( $key ); ?>-list"><?php printf( '%s %s', esc_html( $connection->name ), esc_html( $connection->list_providers->get_name() ) ); ?></label>
							</th>
							<td>
								<?php $list_id = isset( $all_settings[ $key . '_list' ] ) ? $all_settings[ $key . '_list' ] : $connection->get_default_list_id(); ?>
								<select class="regular-text" id="noptin-form-<?php echo esc_attr( $key ); ?>-list" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>_list]">
									<option value="-1" <?php selected( '-1', $list_id ); ?>><?php printf ( __( 'Do not add to %s', 'newsletter-optin-box' ), esc_html( $connection->name ) ); ?></option>
									<?php foreach ( $connection->list_providers->get_dropdown_lists() as $list_key => $list_label ) : ?>
										<option value="<?php echo esc_attr( $list_key ); ?>" <?php selected( $list_key, $list_id ); ?>><?php echo esc_attr( $list_label ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php printf( __( 'People who subscribe via this form will be added to the %s you select here', 'newsletter-optin-box' ), esc_html( $connection->list_providers->get_name() ) ); ?></p>
							</td>
						</tr>

						<?php if ( $connection->supports( 'tags' ) ) : ?>
							<tr valign="top" class="form-field-row form-field-row-tags">
								<th scope="row">
									<label for="noptin-form-<?php echo esc_attr( $key ); ?>-tags"><?php printf( __( '%s tags', 'newsletter-optin-box' ), esc_html( $connection->name ) ); ?></label>
								</th>
								<td>
									<?php $tags = isset( $all_settings[ $key . '_tags' ] ) ? $all_settings[ $key . '_tags' ] : get_noptin_option( "noptin_{$key}_default_tags", '' ); ?>
									<input
										class="regular-text"
										id="noptin-form-<?php echo esc_attr( $key ); ?>-tags"
										name="noptin_form[settings][<?php echo esc_attr( $key ); ?>_tags]"
										value="<?php echo esc_attr( $tags ); ?>"
									/>
									<p class="description"><?php _e( 'Enter a comma separated list of tags to assign new suscribers.', 'newsletter-optin-box' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>

					</table>

				<?php endif; ?>

			</div>

		</fieldset>

		<?php
	}

}
