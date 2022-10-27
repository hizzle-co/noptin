<?php

	defined( 'ABSPATH' ) || exit;

	$params               = array(
        'page'        => 'noptin-email-campaigns',
        'section'     => 'newsletters',
        'sub_section' => 'edit_campaign',
    );
    $add_new_campaign_url = add_query_arg( $params, admin_url( '/admin.php' ) );
?>

<div class="noptin-email-types">

	<?php foreach ( get_noptin_email_senders( true ) as $key => $email_sender ) : ?>

		<div class="card noptin-email-type">

			<div class="noptin-email-type-image"><?php noptin_kses_post_e( $email_sender['image'] ); ?></div>

			<div class="noptin-email-type-content">

				<h3><?php echo esc_html( $email_sender['label'] ); ?></h3>

				<p><?php echo wp_kses_post( $email_sender['description'] ); ?></p>

				<?php if ( ! $email_sender['is_installed'] ) : ?>
					<p style="color: #a00;"><em><?php esc_html_e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
				<?php endif; ?>

				<div class="noptin-email-type-action">

					<?php if ( ! $email_sender['is_installed'] ) : ?>
						<a href="https://noptin.com/ultimate-addons-pack/?utm_medium=plugin-dashboard&utm_campaign=newsletter-emails&utm_source=<?php echo sanitize_key( $key ); ?>" class="button" target="_blank"><?php esc_html_e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( 'campaign', $key, $add_new_campaign_url ) ); ?>" class="button button-primary"><?php esc_html_e( 'Send email', 'newsletter-optin-box' ); ?></a>
					<?php endif; ?>

				</div>
			</div>

		</div>

	<?php endforeach; ?>

</div>
