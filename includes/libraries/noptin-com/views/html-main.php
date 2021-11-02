<?php
/**
 * @var Noptin_COM_License[] $licenses
 */

defined( 'ABSPATH' ) or exit();

?>

<div class="wrap noptin noptin_addons_wrap noptin-helper" id="noptin-wrapper">
	<?php require Noptin_COM_Helper::get_view_filename( 'html-section-nav.php' ); ?>
	<h1 class="screen-reader-text"><?php _e( 'Noptin Extensions', 'newsletter-optin-box' ); ?></h1>

	<?php require Noptin_COM_Helper::get_view_filename( 'html-section-notices.php' ); ?>

	<div class="subscriptions-header">
		<h2><?php _e( 'Licenses', 'newsletter-optin-box' ); ?></h2>
		<?php require Noptin_COM_Helper::get_view_filename( 'html-section-account.php' ); ?>
		<p><?php printf( __( 'Below is a list of licenses available on your noptin.com account. To receive extension updates, please make sure the extension is installed, and its license activated and connected to your noptin.com account. Extensions can be activated from the <a href="%s">Plugins</a> screen.', 'newsletter-optin-box' ), admin_url( 'plugins.php' ) ); ?></p>
	</div>

	<table class="wp-list-table widefat fixed striped">
		<?php if ( ! empty( $licenses ) ) : ?>
			<?php foreach ( $licenses as $license ) : ?>
				<tbody>
					<tr class="wp-list-table__row is-ext-header">
						<td class="wp-list-table__ext-details">
							<div class="wp-list-table__ext-title">
								<a href="<?php echo esc_url( $license->get_product_url() ); ?>" target="_blank">
									<?php echo esc_html( $license->get_product_name() ); ?>
								</a>
								<p class="description"><?php $license->is_membership() ? _e( 'Membership Key: ', 'newsletter-optin-box' ) : _e( 'License Key: ', 'newsletter-optin-box' ); ?><?php echo esc_html( $license->get_license_key() ); ?></p>
							</div>

							<div class="wp-list-table__ext-description">
								<?php if ( ! $license->is_active() ) : ?>
									<span class="renews" style="color: red;">
										<strong><?php _e( 'Expired :(', 'newsletter-optin-box' ); ?></strong>
									</span>
								<?php elseif ( $license->is_lifetime() ) : ?>
									<span class="renews" style="color: green;">
										<strong><?php _e( 'Lifetime License', 'newsletter-optin-box' ); ?></strong>
									</span>
								<?php else : ?>
									<span class="renews" style="color: green;">
										<strong><?php _e( 'Expires on:', 'newsletter-optin-box' ); ?></strong>
										<strong><?php echo date_i18n( 'F jS, Y', strtotime( $license->get_expiration() ) ); ?></strong>
									</span>
								<?php endif; ?>

								<?php if ( $license->is_active() ) : ?>
								<br/>
								<span class="subscription">
									<?php
									if ( ! $license->is_activated_on_site() && $license->is_maxed() ) {
										echo '<span style="color: red;">';
										/* translators: %1$d: sites active, %2$d max sites active */
										printf( __( 'Not available for this site - %1$d of %2$d already in use', 'newsletter-optin-box' ), absint( $license->get_activations() ), absint( $license->get_max_activations() ) );
										echo '</span>';
									} else if ( ! $license->is_unlimited() ) {
										/* translators: %1$d: sites active, %2$d max sites active */
										printf( __( 'Using %1$d of %2$d sites available', 'newsletter-optin-box' ), absint( $license->get_activations() ), absint( $license->get_max_activations() ) );
									} else {
										_e( 'Usable on unlimited sites', 'newsletter-optin-box' );
									}

										$sites  = $license->get_activated_on();

										if ( ! empty( $sites ) ) {
											$_sites = '';
											$id     = 'noptin-license-activations-' . sanitize_key( $license->get_license_key() );

											foreach ( $sites as $url => $activation_date ) {
												$url             = esc_url_raw( $url );
												$activation_date = date_i18n( get_option( 'date_format' ), strtotime( $activation_date ) );
												$_sites          = "\n\t<li>$url &mdash; $activation_date</li>";
											}

											echo "&nbsp;<a href='#TB_inline?&width=400&height=550&inlineId=$id' class='thickbox'><span class='dashicons dashicons-info-outline'></span></a>";
											echo "<div id='$id' style='display:none;'><ul>$_sites</ul></div>";
										}

									?>
								</span>
								<?php endif; ?>
							</div>
						</td>
						<td class="wp-list-table__ext-actions">
							<?php if ( ! $license->is_active() || ( ! $license->is_activated_on_site() && $license->is_maxed() ) ) : ?>
								<a class="button" href="<?php echo esc_url( $license->get_product_url() ); ?>" target="_blank"><?php _e( 'Buy New', 'newsletter-optin-box' ); ?></a>
							<?php elseif ( ! $license->is_activated_on_site() ) : ?>
								<a class="button button-activate" href="<?php echo esc_url( $license->get_activation_url() ); ?>"><?php _e( 'Activate', 'newsletter-optin-box' ); ?></a>
							<?php else : ?>
								<a class="button button-secondary" href="<?php echo esc_url( $license->get_deactivation_url() ); ?>"><?php _e( 'Deactivate', 'newsletter-optin-box' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>

				<?php foreach ( $license['actions'] as $action ) : ?>
				<tr class="wp-list-table__row wp-list-table__ext-updates">
					<td class="wp-list-table__ext-status <?php echo sanitize_html_class( $action['status'] ); ?>">
						<p><span class="dashicons <?php echo sanitize_html_class( $action['icon'] ); ?>"></span>
							<?php echo $action['message']; ?>
						</p>
					</td>
					<td class="wp-list-table__ext-actions">
						<?php if ( ! empty( $action['button_label'] ) && ! empty( $action['button_url'] ) ) : ?>
						<a class="button <?php echo empty( $action['primary'] ) ? 'button-secondary' : ''; ?>" href="<?php echo esc_url( $action['button_url'] ); ?>"><?php echo esc_html( $action['button_label'] ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>

				</tbody>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="3"><em><?php _e( 'Could not find any licenses on your noptin.com account', 'newsletter-optin-box' ); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $no_licenses ) ) : ?>
		<h2><?php _e( 'Installed Extensions without an active license key', 'newsletter-optin-box' ); ?></h2>
		<p><?php _e( 'Below is a list of Noptin.com products available on your site - but their license keys are either out-dated or missing.', 'newsletter-optin-box' ); ?></p>

		<table class="wp-list-table widefat fixed striped">
			<?php /* Extensions without a license key. */ ?>
			<?php foreach ( $no_licenses as $filename => $data ) : ?>
				<tbody>
					<tr class="wp-list-table__row is-ext-header">
						<td class="wp-list-table__ext-details color-bar autorenews">
							<div class="wp-list-table__ext-title">
								<a href="<?php echo esc_url( $data['_product_url'] ); ?>" target="_blank"><?php echo esc_html( $data['Name'] ); ?></a>
							</div>
							<div class="wp-list-table__ext-description">
							</div>
						</td>
						<td class="wp-list-table__ext-actions">
							<span class="button button-secondary"><?php _e( 'INACTIVE', 'newsletter-optin-box' ); ?></span>
						</td>
					</tr>

					<?php foreach ( $data['_actions'] as $action ) : ?>
					<tr class="wp-list-table__row wp-list-table__ext-updates">
						<td class="wp-list-table__ext-status <?php echo sanitize_html_class( $action['status'] ); ?>">
							<p><span class="dashicons <?php echo sanitize_html_class( $action['icon'] ); ?>"></span>
								<?php echo $action['message']; ?>
							</p>
						</td>
						<td class="wp-list-table__ext-actions">
							<a class="button" href="<?php echo esc_url( $action['button_url'] ); ?>" target="_blank"><?php echo esc_html( $action['button_label'] ); ?></a>
						</td>
					</tr>
					<?php endforeach; ?>

				</tbody>

			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>
