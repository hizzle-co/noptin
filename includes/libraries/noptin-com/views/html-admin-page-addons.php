<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap noptin noptin_addons_wrap" id="noptin-wrapper">

	<nav class="nav-tab-wrapper noptin-nav-tab-wrapper">

		<?php if ( $sections && 3 > count( $sections ) ) : ?>

			<?php foreach ( $sections as $section ) : ?>
				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-addons&section=' . esc_attr( $section->slug ) ) ); ?>"
					class="nav-tab <?php echo $current_section === $section->slug ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $section->label ); ?>
				</a>
			<?php endforeach; ?>

		<?php else : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-addons' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Browse Extensions', 'newsletter-optin-box' ); ?></a>
		<?php endif; ?>

		<?php
			$count_html = Noptin_COM_Updater::get_updates_count_html();
			// translators: Count of updates for Noptin.com subscriptions.
			$menu_title = sprintf( __( 'Noptin.com Helper %s', 'newsletter-optin-box' ), $count_html );
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-addons&section=helper' ) ); ?>" class="nav-tab"><?php echo wp_kses_post( $menu_title ); ?></a>
	</nav>

	<h1 class="screen-reader-text"><?php esc_html_e( 'Noptin Extensions', 'newsletter-optin-box' ); ?></h1>

	<?php if ( $sections ) : ?>

		<?php if ( 2 < count( $sections ) ) : ?>
			<ul class="subsubsub">
				<?php foreach ( $sections as $section ) : ?>
					<li>
						<a
							class="<?php echo $current_section === $section->slug ? 'current' : ''; ?>"
							href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-addons&section=' . esc_attr( $section->slug ) ) ); ?>">
							<?php echo esc_html( $section->label ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $_GET['search'] ) ) : ?>
			<h1 class="search-form-title" >
				<?php // translators: search keyword. ?>
				<?php printf( esc_html__( 'Showing search results for: %s', 'newsletter-optin-box' ), '<strong>' . esc_html( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) . '</strong>' ); ?>
			</h1>
		<?php endif; ?>

		<form class="search-form" method="GET">
			<button type="submit">
				<span class="dashicons dashicons-search"></span>
			</button>
			<input
				type="text"
				name="search"
				value="<?php echo esc_attr( isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '' ); ?>"
				placeholder="<?php esc_attr_e( 'Enter a search term and press enter', 'newsletter-optin-box' ); ?>">
			<input type="hidden" name="page" value="noptin-addons">
			<?php $page_section = ( isset( $_GET['section'] ) && '_featured' !== $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_all'; ?>
			<input type="hidden" name="section" value="<?php echo esc_attr( $page_section ); ?>">
		</form>

		<?php if ( Noptin_COM::has_active_membership() ) : ?>
			<div class="membership-alert">
				<div><?php _e( 'You have an active membership that allows you to install any of our extensions for free.', 'newsletter-optin-box' ); ?></div>
				<a
					class="addons-button addons-button-installed noptin-helper-deactivate-license-modal"
					href="#"
					data-license_key="<?php echo esc_attr( Noptin_COM::get( 'membership_key' ) ); ?>"
				>
					<?php _e( 'Deactivate', 'newsletter-optin-box' ); ?>
				</a>
			</div>
		<?php else: ?>
			<div class="membership-alert membership-alert-dark">
				<div><?php _e( 'A Noptin.com membership allows you to install any of our extensions or integrations for free.', 'newsletter-optin-box' ); ?></div>
				<a class="addons-button addons-button-installed" href="https://noptin.com/pricing/?utm_source=extensionsscreen&utm_medium=product&utm_campaign=noptinaddons">
					<?php _e( 'View Pricing', 'newsletter-optin-box' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<ul class="products">
			<?php if ( ! empty( $addons ) ) : ?>
				<?php foreach ( $addons as $addon ) : ?>
					<li class="product">
						<a href="<?php echo esc_attr( Noptin_Addons::add_in_app_purchase_url_params( $addon->href ) ); ?>"><h2><?php echo esc_html( $addon->title ); ?></h2></a>
						<div class="product-description"><?php echo wpautop( wp_kses_post( $addon->description ) ); ?></div>
						<div class="footer <?php echo Noptin_COM::has_active_membership() ? 'has-membership' : '' ?>">

							<?php if ( Noptin_COM::has_active_license( $addon->id ) ) : ?>

								<?php if ( ! Noptin_COM::has_active_membership() ) : ?>
									<a
										class="addons-button noptin-helper-deactivate-license-modal"
										href="#"
										data-license_key="<?php echo esc_attr( Noptin_COM::get_active_license( $addon->id )->get_license_key() ); ?>"
									>
										<?php _e( 'De-activate license', 'newsletter-optin-box' ); ?>
									</a>
								<?php endif; ?>

								<?php $plugin = Noptin_COM_Helper::get_plugin_file_from_id( $addon->id ); ?>

								<?php if ( empty( $plugin ) ) : ?>

									<!-- Not installed -->
									<a
										class="addons-button addons-button-solid"
										href="<?php echo wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=noptin-product-with-id-' . $addon->id ), 'install-plugin_noptin-product-with-id-' . $addon->id ); ?>"
									>
										<?php _e( 'Install Now', 'newsletter-optin-box' ); ?>
									</a>

								<?php else: ?>

									<!-- Installed -->
									<?php if ( is_plugin_active( $plugin ) ) : ?>

										<!-- Active -->
										<a
											class="addons-button addons-button-installed"
											href="<?php echo esc_attr( $addon->href ); ?>"
											target="_blank"
										>
											<?php _e( 'Active', 'newsletter-optin-box' ); ?>
										</a>

									<?php else: ?>

										<!-- Not Active -->
										<a
											class="addons-button-outline-green"
											href="<?php echo wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin='.$plugin ), 'activate-plugin_'.$plugin ); ?>"
										>
											<?php _e( 'Activate', 'newsletter-optin-box' ); ?>
										</a>

									<?php endif; ?>

								<?php endif; ?>

							<?php else: ?>
								<a
									class="addons-button noptin-helper-activate-license-modal"
									href="#"
									data-id="<?php echo esc_attr( $addon->id ); ?>"
									data-activating="<?php echo esc_attr( sprintf( __( 'Activating %s', 'newsletter-optin-box' ), $addon->title ) ); ?>">
									<?php _e( 'Activate license', 'newsletter-optin-box' ); ?>
								</a>
								<a class="addons-button addons-button-solid" href="<?php echo esc_attr( Noptin_Addons::add_in_app_purchase_url_params( $addon->href ) ); ?>" target="_blank"><?php _e( 'Get it', 'newsletter-optin-box' ); ?></a>
							<?php endif; ?>

						</div>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>

	<?php else : ?>
		<?php /* translators: a url */ ?>
		<p><?php printf( wp_kses_post( __( 'Our catalog of Noptin Extensions can be found on noptin.com here: <a href="%s">Noptin Extensions Catalog</a>', 'newsletter-optin-box' ) ), 'https://noptin.com/product-category/plugins/' ); ?></p>
	<?php endif; ?>

</div>
