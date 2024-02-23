<?php

	namespace Hizzle\Noptin\Automation_Rules\Admin;

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $query_args
	 */

	// Prepare items.
	$table = new Table();
	$table->prepare_items();

?>

<div class="wrap noptin noptin-automation-rules noptin-automation-rules-main" id="noptin-wrapper">

	<h1 class="wp-heading-inline">
		<span><?php echo esc_html( get_admin_page_title() ); ?></span>
	</h1>

	<hr class="wp-header-end">

	<?php noptin()->admin->show_notices(); ?>
	<?php do_action( 'noptin_before_automation_rules_overview_page' ); ?>

	<!-- Display actual content -->
	<div class="noptin-automation-rules-tab-content">
		<form id="noptin-automation-rules-table" method="post" style="margin-top: 30px;">
			<?php $table->display(); ?>
		</form>
		<p class="description">
			<a href="https://noptin.com/guide/automation-rules" target="_blank">
				<?php esc_html_e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?>
			</a>
		</p>
	</div>

</div>
