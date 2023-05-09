<?php

function noptin_welcome_wizard_page() {
    ?>
    <div id="noptin-welcome-wizard></div>
    <?php wp_enqueue_script( 'noptin_welcome_wizard_script', plugins_url( 'includes/assets/js/dist/welcome-wizard.js', __FILE__ ), array( 'jquery' ) ); ?>
    <?php
}

