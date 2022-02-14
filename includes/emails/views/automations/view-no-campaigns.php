<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap noptin" id="noptin-wrapper">

	<?php include plugin_dir_path( dirname( __FILE__ ) ) . 'tabs.php'; ?>

	<h1 style='font-size: 2.5em; font-weight: bold; margin-top: 20px; margin-bottom: 20px;'><?php _e( 'Set-up your first automated email', 'newsletter-optin-box' ); ?> 🙂</h1>

	<?php include plugin_dir_path( __FILE__ ) . 'new-automation-inner.php'; ?>
</div>
