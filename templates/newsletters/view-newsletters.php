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

    <form id="noptin-newsletter-campaigns-table" method="GET" style="margin-top: 30px;">
		<input type="hidden" name="page" value="noptin-email-campaigns"/>
		<input type="hidden" name="section" value="newsletters"/>
		<?php $table->display(); ?>
		<p class="description"><?php _e( 'Use this page to send one-time emails to your email subscribers', 'newsletter-optin-box' ); ?></p>
	</form>
</div>
