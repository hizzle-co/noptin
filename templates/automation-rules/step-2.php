<?php

/**
 * Second step: Configure the trigger
 */

$triggers = noptin()->automation_rules->get_triggers();
$trigger  = trim( $_GET['trigger'] );
$trigger  = noptin()->automation_rules->get_trigger( $trigger );

?>

<div id="noptin-automation-rule-editor">
    <p><?php _e( 'Next, configure your automation rule.', 'newsletter-optin-box' ); ?></p>
    <div class="trigger">
        <div>
            <h2><?php echo esc_html( $trigger->get_name() ); ?></h2>
            <p class="description"><?php echo esc_html( $trigger->get_description() ); ?></p>
            <?php foreach ( $trigger->get_settings() as $id => $args ) { ?>
				<?php Noptin_Vue::render_el( $id, $args ); ?>
			<?php } ?>
        </div>
    </div>
</div>
