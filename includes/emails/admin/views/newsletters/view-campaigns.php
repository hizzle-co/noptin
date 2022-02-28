<?php defined( 'ABSPATH' ) || exit; ?>
<form id="noptin-email-campaigns-table" method="post" style="margin-top: 30px;">
	<input type="hidden" name="page" value="noptin-email-campaigns"/>
	<input type="hidden" name="section" value="newsletters"/>
	<?php $table->display(); ?>
</form>
