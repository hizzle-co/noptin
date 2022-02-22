<?php

	defined( 'ABSPATH' ) || exit;

	$params               = array(
        'page'        => 'noptin-email-campaigns',
        'section'     => 'newsletters',
        'sub_section' => 'new_campaign',
    );
    $add_new_campaign_url = add_query_arg( $params, admin_url( '/admin.php' ) );
?>

<div class="wrap noptin" id="noptin-wrapper">

    <?php include plugin_dir_path( dirname( __FILE__ ) ) . 'tabs.php'; ?>

    <div style='min-height: 320px; margin-top: 20px; display: flex; align-items: center; justify-content: center; flex-flow: column;'>
        <h1 style='font-size: 2.5em; font-weight: bold; margin-bottom: 20px;'><?php _e( 'You are yet to send any email', 'newsletter-optin-box' ); ?> 🙂</h1>
        <img src="<?php echo esc_url( plugin_dir_url( Noptin::$file ) . 'includes/assets/images/envelope.png' ); ?>" style="max-width: 100%; width: 400px;height: auto;">
        <p style="margin-bottom: 20px;">
            <?php _e( 'Noptin allows you to send your subscribers one-time emails. Click on the button below to send an email.', 'newsletter-optin-box' ); ?>
        </p>
        <a href='<?php echo esc_url( $add_new_campaign_url ); ?>' class='button button-primary'><?php _e( 'Send your First Email', 'newsletter-optin-box' ); ?></a>
        <p class='description'><a style='color: #616161; text-decoration: underline;' href='https://noptin.com/guide/sending-emails' target='_blank'><?php _e( 'Learn more', 'newsletter-optin-box' ); ?></a></p>
    </div>
</div>
