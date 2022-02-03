<?php
	defined( 'ABSPATH' ) || exit;

	$table = new Noptin_Email_Automations_Table();
	$table->prepare_items();

?>
<div class="wrap noptin" id="noptin-wrapper">

    <?php include plugin_dir_path( dirname( __FILE__ ) ) . 'tabs.php'; ?>

    <form id="noptin-automation-campaigns-table" method="GET" style="margin-top: 30px;">
		<input type="hidden" name="page" value="noptin-email-campaigns"/>
		<input type="hidden" name="section" value="automations"/>
		<?php $table->display(); ?>
		<p class="description"><?php _e( 'Use this page to create emails that will be automatically emailed to your subscribers', 'newsletter-optin-box' ); ?></p>
    </form>

</div>
