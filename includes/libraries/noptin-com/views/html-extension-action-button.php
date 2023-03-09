<?php

	/**
	 * Admin View: Extension action button.
	 *
	 * @var object|WP_Error|false $license The active license
	 * @var string $slug The extension slug.
	 * @var array $installed_addons The installed addons.
	 * @var bool $is_connection
	 */

	defined( 'ABSPATH' ) || exit;

	$has_license = $license && ! is_wp_error( $license ) && $license->is_active && ! $license->has_expired;
	$can_install = $has_license;

	if ( $has_license && ! $is_connection ) {
		$can_install = false === strpos( $license->product_sku, 'connect' );
	}
?>

<?php if ( $can_install ) : ?>

	<?php if ( isset( $installed_addons[ $slug ] ) ) : ?>

		<!-- Installed -->
		<?php $installed_plugin = $installed_addons[ $slug ]; ?>

		<?php if ( is_plugin_active( $installed_plugin ) ) : ?>

			<!-- Installed and Active -->
			<?php if ( $is_connection ) : ?>
				<a
					class="addons-button addons-button-installed"
					href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-settings&tab=integrations#noptin-settings-section-settings_section_' . $slug ) ); ?>"
				>
					<?php esc_html_e( 'Settings', 'newsletter-optin-box' ); ?>
				</a>
			<?php else : ?>
				<small class="addons-button addons-button-outline-green">
					<?php esc_html_e( 'Active', 'newsletter-optin-box' ); ?>
				</small>
			<?php endif; ?>

		<?php elseif ( ! is_plugin_active( $installed_plugin ) ) : ?>

			<!-- Installed but Not Active -->
			<a
				class="addons-button addons-button-outline-green"
				href="<?php echo esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $installed_plugin ), 'activate-plugin_' . $installed_plugin ) ); ?>"
			>
				<?php esc_html_e( 'Activate', 'newsletter-optin-box' ); ?>
			</a>

		<?php endif; ?>

	<?php else : ?>

		<!-- Not Installed -->
		<a
			class="addons-button addons-button-solid"
			href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=noptin-plugin-with-slug-' . $slug ), 'install-plugin_noptin-plugin-with-slug-' . $slug ) ); ?>"
		>
			<?php esc_html_e( 'Install Now', 'newsletter-optin-box' ); ?>
		</a>
	<?php endif; ?>

<?php elseif ( ! $has_license ) : ?>
	<a class="addons-button addons-button-solid" href="<?php echo esc_url( noptin_get_upsell_url( 'pricing', str_replace( 'noptin-', '', $slug ), 'extensionsscreen' ) ); ?>">
		<?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?>
	</a>
<?php endif; ?>
