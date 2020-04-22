<?php

/**
 * First step: Select the trigger
 */

$triggers = noptin()->automation_rules->get_triggers();

$icons  = array( 'post-status', 'email', 'email-alt', 'email-alt2' );
$colors = array( '#f44336', '#e91e63', '#9c27b0', '#673ab7', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a' );
?>

<div class="noptin-automation-rule-editor-loader">
	<div class="spinner"></div>
</div>

<div id="noptin-automation-rule-editor">
    <p><?php _e( 'Start by selecting a trigger for your rule below.', 'newsletter-optin-box' ); ?></p>
    <?php foreach( $triggers as $trigger ) {?>
        <a href='<?php echo esc_url( add_query_arg( 'create', '2', add_query_arg( 'trigger', $trigger->get_id() ) ) ); ?>'>
        <div class="trigger">
            <div class="trigger-icon">
                <?php if ( '' != $trigger->get_image() ) { ?>
                    <img height="32px" width="32px" src="<?php echo esc_url( $trigger->get_image() );?>" />
                <?php

                    } else {
                        $color = wp_rand( 1, count( $colors ) ) - 1;
                        $icon  = wp_rand( 1, count( $icons ) ) - 1;
                
                ?>
                    <div style="background-color: <?php echo $colors[ $color ]; ?>;">
                        <span class="dashicons dashicons-<?php echo $icons[ $icon ]; ?>"></span>
                    </div>

                <?php } ?>
            </div> 
            
            <div class="trigger-text">
                
                    <h4><?php echo $trigger->get_name(); ?></h4>
                    <p><?php echo $trigger->get_description(); ?></p>
                
            </div>

            <span class="dashicons dashicons-arrow-right-alt"></span>
        </div>
        </a>
    <?php } ?>
</div>
