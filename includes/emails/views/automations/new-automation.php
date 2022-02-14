<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap noptin-new-automation-form" id="noptin-wrapper">
	<h1 class="title"><?php _e( 'Set-up a new automated email','newsletter-optin-box' ); ?></h1>
	<?php include plugin_dir_path( dirname( __FILE__ ) ) . 'tabs.php'; ?>
	<?php include plugin_dir_path( dirname( __FILE__ ) ) . 'new-automation-inner.php'; ?>
</div>
