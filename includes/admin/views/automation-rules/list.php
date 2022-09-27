<?php

/**
 * View automations
 */

defined( 'ABSPATH' ) || exit;

$table = new Noptin_Automation_Rules_Table();
$table->prepare_items();
?>

<div class="wrap" id="noptin-wrapper">

    <h1 class="wp-heading-inline">
		<span><?php echo esc_html( get_admin_page_title() ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( 'noptin_create_automation_rule', '1', admin_url( 'admin.php?page=noptin-automation-rules' ) ) ); ?>" class="page-title-action noptin-add-automation-rule"><?php esc_html_e( 'Add New', 'newsletter-optin-box' ); ?></a>
	</h1>

    <?php noptin()->admin->show_notices(); ?>

    <?php do_action( 'noptin_before_automation_rules_overview_page' ); ?>
    <form id="noptin-automation-rules-table" method="POST">
		<?php $table->display(); ?>
	</form>
    <?php do_action( 'noptin_after_automation_rules_overview_page' ); ?>

    <p class="description"><a href="https://noptin.com/guide/automation-rules" target="_blank"><?php esc_html_e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>

</div>
