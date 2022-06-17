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

$url = add_query_arg(
	array(
		'utm_medium'   => 'plugin-dashboard',
		'utm_campaign' => 'form-builder',
		'utm_source'   => rawurlencode( esc_url( get_home_url() ) ),
	),
	'https://noptin.com/product-tag/integrations/'
);

?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Form Integrations', 'newsletter-optin-box' ); ?></h2>

<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'Noptin also allows you to add new subscribers to an external email service provider.', 'newsletter-optin-box' ); ?></p>

<?php if ( empty( $available_connections ) ) : ?>
	<div class="card">
		<h3><?php esc_html_e( 'No integration installed', 'newsletter-optin-box' ); ?></h3>
		<p><?php esc_html_e( 'Please install the appropriate integration to automatically add new subscribers to an external email provider such as ConvertKit or Mailchimp.', 'newsletter-optin-box' ); ?></p>
		<p><a href="<?php echo esc_url( $url ); ?>" class="button noptin-button-standout" target="_blank"><?php esc_html_e( 'View Integrations', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt"></i></a></p>
	</div>
<?php endif; ?>

<?php
	if ( ! empty( $available_connections ) ) {

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
						<p style="color:#F44336;">
							<?php
								printf(
									'Error: %s',
									! empty( $connection->last_error ) ? esc_html( $connection->last_error ) : sprintf( /* translators: %s integration name */ esc_html__( 'You are not connected to %s', 'newsletter-optin-box' ), esc_html( $connection->name ) )
								);
							?>
						</p>
					<?php elseif ( ! empty( $connection->list_providers ) ) : ?>

						<table class="form-table noptin-form-settings noptin-integration-settings">

							<tr valign="top" class="form-field-row form-field-row-list">
								<th scope="row">
									<label for="noptin-form-<?php echo esc_attr( $key ); ?>-list"><?php printf( '%s %s', esc_html( $connection->name ), esc_html( $connection->list_providers->get_name() ) ); ?></label>
								</th>
								<td>
									<?php $list_id = isset( $all_settings[ $key . '_list' ] ) ? $all_settings[ $key . '_list' ] : $connection->get_default_list_id(); ?>
									<select class="regular-text" id="noptin-form-<?php echo esc_attr( $key ); ?>-list" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>_list]">
										<option value="-1" <?php selected( '-1', $list_id ); ?>><?php printf( /* translators: %s integration name */ esc_html__( 'Do not add to %s', 'newsletter-optin-box' ), esc_html( $connection->name ) ); ?></option>
										<?php foreach ( $connection->list_providers->get_dropdown_lists() as $list_key => $list_label ) : ?>
											<option value="<?php echo esc_attr( $list_key ); ?>" <?php selected( $list_key, $list_id ); ?>><?php echo esc_attr( $list_label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php printf( /* translators: %s type of list */ esc_html__( 'People who subscribe via this form will be added to the %s you select here', 'newsletter-optin-box' ), esc_html( $connection->list_providers->get_name() ) ); ?></p>
								</td>
							</tr>

							<?php if ( $connection->supports( 'tags' ) ) : ?>
								<tr valign="top" class="form-field-row form-field-row-tags">
									<th scope="row">
										<label for="noptin-form-<?php echo esc_attr( $key ); ?>-tags"><?php printf( /* translators: %s integration name */ esc_html__( '%s tags', 'newsletter-optin-box' ), esc_html( $connection->name ) ); ?></label>
									</th>
									<td>
										<?php $tags = isset( $all_settings[ $key . '_tags' ] ) ? $all_settings[ $key . '_tags' ] : get_noptin_option( "noptin_{$key}_default_tags", '' ); ?>
										<input
											class="regular-text"
											id="noptin-form-<?php echo esc_attr( $key ); ?>-tags"
											name="noptin_form[settings][<?php echo esc_attr( $key ); ?>_tags]"
											value="<?php echo esc_attr( $tags ); ?>"
										/>
										<p class="description"><?php esc_html_e( 'Enter a comma separated list of tags to assign new suscribers.', 'newsletter-optin-box' ); ?></p>
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
