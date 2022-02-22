<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap noptin" id="noptin-wrapper">

	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<hr class="wp-header-end">

	<?php noptin()->admin->show_notices(); ?>

    <?php include plugin_dir_path( dirname( __FILE__ ) ) . 'tabs.php'; ?>

    <form id="noptin-email-campaigns-table" method="GET" style="margin-top: 30px;">
		<input type="hidden" name="page" value="noptin-email-campaigns"/>
		<input type="hidden" name="section" value="automations"/>
		<?php $table->display(); ?>
		<p class="description"><?php _e( 'Use this page to create emails that will be automatically emailed to your subscribers', 'newsletter-optin-box' ); ?></p>
    </form>

</div>
