<?php

	/**
	 * Admin View: Extensions page.
	 *
	 * @var array $connections
	 */

	defined( 'ABSPATH' ) || exit;

	$license     = Noptin_COM::get_active_license_key( true );
	$license_key = is_object( $license ) && ! is_wp_error( $license ) ? $license->license_key : '';

	// Addon pack features.
	$addons_pack_features = array(
		'welcome-emails'        => array(
			'title'       => __( 'Welcome Emails', 'newsletter-optin-box' ),
			'description' => __( 'Ensure new users, customers, and email subscribers are up and running by automatically sending them one or more welcome emails.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/sending-emails/welcome-emails/',
			'icon'        => 'dashicons-email',
		),
		'coupon-codes'          => array(
			'title'       => __( 'Coupon Codes', 'newsletter-optin-box' ),
			'description' => __( 'Make more money by automatically sending new subscribers, users, or customers unique coupon codes.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/blog/how-to-send-a-unique-woocommerce-coupon-code-to-new-email-subscribers/',
			'icon'        => 'dashicons-money',
		),
		'woocommerce-customers' => array(
			'title'       => __( 'WooCommerce Customers', 'newsletter-optin-box' ),
			'description' => __( 'Easily send emails to all your WooCommerce customers or only those that bought specific products.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/sending-emails/woocommerce-customers/',
			'icon'        => 'dashicons-share',
		),
		'wordpress-users'       => array(
			'title'       => __( 'WordPress Users', 'newsletter-optin-box' ),
			'description' => __( 'Manually send emails to all your WordPress users or only those that have specific roles.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/sending-emails/wordpress-users/',
			'icon'        => 'dashicons-groups',
		),
		'tag-subscribers'       => array(
			'title'       => __( 'Tag Subscribers', 'newsletter-optin-box' ),
			'description' => __( 'Tag subscribers based on their actions and send emails to subscribers with specific tags, or automatically send emails to subscribers whenever they are tagged or untagged.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/email-subscribers/tagging-subscribers/',
			'icon'        => 'dashicons-tag',
		),
		'lists'  	            => array(
			'title'       => __( 'Lists', 'newsletter-optin-box' ),
			'description' => __( 'Create multiple lists and send emails to subscribers in specific lists, or automatically send emails to subscribers whenever they are added to or removed from specific lists.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/email-subscribers/subscriber-lists/',
			'icon'        => 'dashicons-category',
		),
		'manage-preferences'    => array(
			'title'       => __( 'Manage Preferences', 'newsletter-optin-box' ),
			'description' => __( 'Allow subscribers to manage their preferences and unsubscribe from specific lists.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/email-subscribers/manage-preferences/',
			'icon'        => 'dashicons-admin-settings',
		),
		'attachments'           => array(
			'title'       => __( 'Attachments', 'newsletter-optin-box' ),
			'description' => __( 'Include attachments in your newsletters and automated email campaigns.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/sending-emails/attachments/',
			'icon'        => 'dashicons-paperclip',
		),
		'cpts'                  => array(
			'title'       => __( 'Custom Post Types', 'newsletter-optin-box' ),
			'description' => __( 'Automatically send emails to subscribers whenever a new post of a specific post type is published.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/sending-emails/new-post-notifications/',
			'icon'        => 'dashicons-admin-page',
		),
		'sync'                  => array(
			'title'       => __( 'Sync Subscribers', 'newsletter-optin-box' ),
			'description' => __( 'Sync subscribers between sites, and add newsletter subscription forms on external sites.', 'newsletter-optin-box' ),
			'guide'       => 'https://noptin.com/guide/email-subscribers/sync-subscribers/',
			'icon'        => 'dashicons-marker',
		),
	);

	// Connections.
	$connections = Noptin_COM::get_connections();

	// Installed add-ons.
	$installed_addons = wp_list_pluck( Noptin_COM::get_installed_addons(), '_filename', 'slug' );

?>

<div class="wrap noptin noptin-extensions-wrapper" id="noptin-wrapper">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Noptin Extensions', 'newsletter-optin-box' ); ?></h1>

	<?php if ( empty( $license_key ) ) : ?>

		<form class="noptin-license-form" method="POST">
			<?php wp_nonce_field( 'noptin_save_license_key', 'noptin_save_license_key_nonce' ); ?>
			<input
				type="text"
				name="noptin-license"
				value="<?php echo esc_attr( Noptin_COM::get_active_license_key() ); ?>"
				required
				placeholder="<?php esc_attr_e( 'Enter your noptin.com license key to activate extensions', 'newsletter-optin-box' ); ?>"
			>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Activate', 'newsletter-optin-box' ); ?></button>
		</form>

		<?php if ( is_wp_error( $license ) ) : ?>

			<div class="noptin-extensions-alert noptin-extensions-alert-error">
				<div><?php echo esc_html( $license->get_error_message() ); ?></div>
			</div>

		<?php else : ?>

			<div class="noptin-extensions-alert noptin-extensions-alert-success">
				<div><?php esc_html_e( 'Activate your license to get priority support and premium features.', 'newsletter-optin-box' ); ?></div>
				<a class="addons-button addons-button-installed" href="<?php echo esc_url( noptin_get_upsell_url( 'pricing', 'license', 'extensionsscreen' ) ); ?>">
					<?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?>
				</a>
			</div>

		<?php endif; ?>

	<?php else : ?>

		<div class="noptin-license-info">

			<div class="noptin-license-info__header">

				<div class="noptin-license-info__header__main">

					<div class="noptin-license-info__header__main-title">

						<?php if ( ! empty( $license->product_name ) ) : ?>
							<p class="description"><strong><?php esc_html_e( 'Plan:', 'newsletter-optin-box' ); ?></strong>&nbsp;<?php echo esc_html( $license->product_name ); ?></p>
						<?php endif; ?>

						<p class="description"><strong><?php esc_html_e( 'License Key:', 'newsletter-optin-box' ); ?></strong>&nbsp;<?php echo esc_html( $license->license_key_ast ); ?></p>
						<p class="description"><strong><?php esc_html_e( 'Email:', 'newsletter-optin-box' ); ?></strong>&nbsp;<?php echo esc_html( $license->email ); ?></p>

					</div>

					<div class="noptin-license-info__header__main-meta">

						<?php if ( ! $license->is_active || $license->has_expired ) : ?>

							<span class="renews" style="color: red;">
								<strong><?php esc_html_e( 'Expired :(', 'newsletter-optin-box' ); ?></strong>
							</span>

						<?php elseif ( empty( $license->date_expires ) ) : ?>

							<span class="renews" style="color: green;">
								<strong><?php esc_html_e( 'Lifetime License', 'newsletter-optin-box' ); ?></strong>
							</span>

						<?php else : ?>

							<span class="renews" style="color: green;">
								<strong><?php esc_html_e( 'Expires on:', 'newsletter-optin-box' ); ?></strong>
								<strong><?php echo esc_html( date_i18n( 'F jS, Y', strtotime( $license->date_expires ) ) ); ?></strong>
							</span>

						<?php endif; ?>

						<?php if ( $license->is_active && ! $license->has_expired ) : ?>
							<br/>
							<span class="subscription">

								<span>
									<?php esc_html_e( 'Activations:', 'newsletter-optin-box' ); ?>
									<?php echo esc_html( $license->the_activations ); ?>
								</span>

								<?php if ( ! empty( $license->activations ) ) : ?>
									<span title="<?php echo esc_attr( implode( ', ', array_keys( (array) $license->activations ) ) ); ?>" class="noptin-tip dashicons dashicons-info"></span>
								<?php endif; ?>

							</span>
						<?php endif; ?>

					</div>

				</div>

				<div class="noptin-license-info__header__action">
					<a
						class="button button-secondary"
						href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=noptin-addons' ), 'noptin-deactivate-license', 'noptin-deactivate-license-nonce' ) ); ?>"
						onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to deactivate this license key?', 'newsletter-optin-box' ); ?>');"
						><?php esc_html_e( 'Deactivate', 'newsletter-optin-box' ); ?></a>
				</div>
			</div>

			<?php if ( ! $license->is_active || $license->has_expired ) : ?>

				<div class="noptin-license-info__action expired">

					<p>
						<span class="dashicons dashicons-info"></span>
						<?php echo wp_kses_post( __( 'This license key has expired. Please <strong>purchase a new license key</strong> to receive updates and support.', 'newsletter-optin-box' ) ); ?>
					</p>

					<a
						class="button button-primary"
						href="<?php echo esc_url( noptin_get_upsell_url( 'pricing', 'expired', 'extensionsscreen' ) ); ?>"
					><?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?></a>

				</div>

			<?php endif; ?>

		</div>

	<?php endif; ?>

	<hr style="margin: 2em 0;" />

	<!-- WooCommerce Addon -->
	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<div class="noptin-extension-group noptin-extension-group__woocommerce">
			<h2 class="noptin-extension-group__title"><?php esc_html_e( 'WooCommerce Addon', 'newsletter-optin-box' ); ?></h2>
			<p class="noptin-extension-group__description"><?php esc_html_e( 'The WooCommerce addon provides better integration with WooCommerce and its extensions.', 'newsletter-optin-box' ); ?></p>

			<?php Noptin_COM_Helper::display_main_action_button( $license, 'noptin-woocommerce', $installed_addons, false ); ?>

			<?php if ( ! empty( $license_key ) && false !== strpos( $license->product_sku, 'connect' ) ) : ?>
				<!-- Display notice that the license key can not be used for this extension -->
				<div class="noptin-extensions-alert noptin-extensions-alert-dark">
					<div>
						<?php echo wp_kses_post( __( 'Your active license key can not be used to install the WooCommerce Addon. Please <strong>purchase a personal plan or higher</strong> to receive updates and support.', 'newsletter-optin-box' ) ); ?>
					</div>

					<a
						class="addons-button addons-button-installed"
						href="<?php echo esc_url( noptin_get_upsell_url( 'pricing', 'upgrade', 'extensionsscreen' ) ); ?>"
					><?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- Noptin Addons Pack -->
	<div class="noptin-extension-group noptin-extension-group__addons-pack">
		<h2 class="noptin-extension-group__title"><?php esc_html_e( 'Ultimate Addons Pack', 'newsletter-optin-box' ); ?>&nbsp;<?php Noptin_COM_Helper::display_main_action_button( $license, 'noptin-addons-pack', $installed_addons, false ); ?></h2>
		<p class="noptin-extension-group__description description"><?php esc_html_e( 'The ultimate addons pack is a single extension that helps your increase your revenue, get more traffic, and deliver more value to your users.', 'newsletter-optin-box' ); ?></p>

		<?php if ( ! empty( $license_key ) && false !== strpos( $license->product_sku, 'connect' ) ) : ?>
			<!-- Display notice that the license key can not be used for this extension -->
			<div class="noptin-extensions-alert noptin-extensions-alert-dark">
				<div>
					<?php echo wp_kses_post( __( 'Your active license key can not be used to install the ultimate add-ons pack. Please <strong>purchase a personal plan or higher</strong> to receive updates and support.', 'newsletter-optin-box' ) ); ?>
				</div>

				<a
					class="addons-button addons-button-installed"
					href="<?php echo esc_url( noptin_get_upsell_url( 'pricing', 'upgrade', 'extensionsscreen' ) ); ?>"
				><?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?></a>
			</div>
		<?php endif; ?>

		<ul class="noptin-extension-group__items">

			<?php foreach ( $addons_pack_features as $key => $feature ) : ?>
				<li class="noptin-extension noptin-extension__<?php echo esc_attr( $key ); ?>">

					<div class="noptin-extension__details">
						<span class="noptin-extension__img">
							<span class="dashicons <?php echo esc_attr( $feature['icon'] ); ?>"></span>
						</span>
						<h3><?php echo esc_html( $feature['title'] ); ?></h3>
						<p class="description"><?php echo esc_html( $feature['description'] ); ?></p>
					</div>

					<div class="noptin-extension__footer">
						<a
							class="button"
							href="<?php echo esc_url( noptin_get_upsell_url( $feature['guide'], $key, 'extensionsscreen' ) ); ?>"
							><?php esc_html_e( 'Learn More', 'newsletter-optin-box' ); ?></a>
					</div>
				</li>
			<?php endforeach; ?>

		</ul>
	</div>

	<hr style="margin: 2em 0;" />

	<!-- CRM connections -->
	<div class="noptin-extension-group noptin-extension-group__connections">
		<h2 class="noptin-extension-group__title"><?php esc_html_e( 'Integrations', 'newsletter-optin-box' ); ?></h2>
		<p class="noptin-extension-group__description description"><?php esc_html_e( 'These extensions allow you to connect Noptin to your favorite CRM or email software.', 'newsletter-optin-box' ); ?></p>

		<ul class="noptin-extension-group__items">

			<?php foreach ( $connections as $connection ) : ?>
				<li class="noptin-extension noptin-extension__<?php echo esc_attr( $connection->slug ); ?>">

					<div class="noptin-extension__details">
						<span class="noptin-extension__img noptin-extension__img-lg">
							<img src="<?php echo esc_url( $connection->image_url ); ?>" alt="<?php echo esc_attr( $connection->name ); ?>" />
						</span>
						<h3><?php echo esc_html( $connection->name ); ?></h3>
						<p class="description"><?php printf( /* translators: %s Integration such as Mailchimp */ esc_html__( 'Connect Noptin to %s', 'newsletter-optin-box' ), esc_html( $connection->name ) ); ?></p>
					</div>

					<div class="noptin-extension__footer">
						<a
							class="button"
							href="<?php echo esc_url( noptin_get_upsell_url( $connection->connect_url, $connection->slug, 'extensionsscreen' ) ); ?>"
							><?php esc_html_e( 'Learn More', 'newsletter-optin-box' ); ?></a>

						<?php Noptin_COM_Helper::display_main_action_button( $license, "noptin-{$connection->slug}", $installed_addons, true ); ?>

					</div>

					<?php if ( Noptin_COM_Updater::has_extension_update( 'noptin-' . $connection->slug ) ) : ?>
						<div class="noptin-extension__update"><?php esc_html_e( 'Update Available', 'newsletter-optin-box' ); ?></div>
					<?php endif; ?>

				</li>
			<?php endforeach; ?>

		</ul>
	</div>

</div>
