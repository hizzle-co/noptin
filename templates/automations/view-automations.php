<div class="wrap noptin" id="noptin-wrapper">

    <?php
        get_noptin_template(
            'newsletters/tabs.php',
            array(
                'tabs' => $tabs,
                'tab'  => 'newsletters',
            )
        );
    ?>

    <form id="noptin-automation-campaigns-table" method="GET" style="margin-top: 30px;">
		<input type="hidden" name="page" value="noptin-email-campaigns"/>
		<input type="hidden" name="section" value="automations"/>
		<?php $table->display(); ?>
		<p class="description"><?php _e( 'Use this page to create emails that will be automatically emailed to your subscribers', 'newsletter-optin-box' ); ?></p>
    </form>
    
    <div id="noptin-create-automation" style="display:none;">
		<?php get_noptin_template( 'new-email-automations-popup.php', compact( 'triggers' ) ); ?>
	</div>
</div>
