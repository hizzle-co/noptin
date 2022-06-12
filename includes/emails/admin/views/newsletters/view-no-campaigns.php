<?php defined( 'ABSPATH' ) || exit; ?>

<h1 style='font-size: 2.5em; font-weight: bold; margin-top: 20px; margin-bottom: 20px;'><?php esc_html_e( 'Send your first email', 'newsletter-optin-box' ); ?> <?php echo wp_staticize_emoji( "🙂" ); ?></h1>
<?php include plugin_dir_path( __FILE__ ) . 'view-new-campaign.php'; ?>
