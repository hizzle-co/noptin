<?php defined( 'ABSPATH' ) || exit; ?>
<form id="noptin-email-campaigns-table" method="post" style="margin-top: 30px;">
	<input type="hidden" name="page" value="noptin-email-campaigns"/>
	<input type="hidden" name="section" value="automations"/>
	<?php $table->display(); ?>
	<p class="description"><?php _e( 'Use this page to create emails that will be automatically emailed to your subscribers', 'newsletter-optin-box' ); ?></p>
</form>