<?php defined( 'ABSPATH' ) || exit; ?>

<h1 style='font-size: 2.5em; font-weight: bold; margin-top: 20px; margin-bottom: 20px;'><?php esc_html_e( 'Set-up your first automated email', 'newsletter-optin-box' ); ?> <?php echo wp_kses_post( wp_staticize_emoji( '🙂' ) ); ?></h1>
<?php require plugin_dir_path( __FILE__ ) . 'view-new-campaign.php'; ?>
