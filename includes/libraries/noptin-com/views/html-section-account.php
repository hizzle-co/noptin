<?php defined( 'ABSPATH' ) or exit(); ?>

<a class="button button-update" href="<?php echo esc_url( $refresh_url ); ?>"><span class="dashicons dashicons-image-rotate"></span> <?php _e( 'Update', 'newsletter-optin-box' ); ?></a>
<div class="user-info">
	<header>
		<p><?php printf( __( 'Connected to Noptin.com', 'newsletter-optin-box' ) ); ?> <span class="chevron dashicons dashicons-arrow-down-alt2"></span></p>
	</header>
	<section>
		<p><?php echo get_avatar( $auth_user_data['email'], 48 ); ?> <?php echo esc_html( $auth_user_data['email'] ); ?></p>
		<div class="actions">
			<a class="" href="https://noptin.com/my-account/downloads/" target="_blank"><span class="dashicons dashicons-download"></span> <?php _e( 'My Downloads', 'newsletter-optin-box' ); ?></a>
			<a class="" href="<?php echo esc_url( $disconnect_url ); ?>"><span class="dashicons dashicons-no"></span> <?php _e( 'Disconnect', 'newsletter-optin-box' ); ?></a>
		</div>
	</section>
</div>
