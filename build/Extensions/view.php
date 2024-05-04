<?php

defined( 'ABSPATH' ) || exit;

?>
<style>
    .noptin-components-button__green {
        --wp-components-color-accent: #4caf50;
        --wp-components-color-accent-darker-10: #3d9541;
        --wp-components-color-accent-darker-20: #368737;
    }
    .noptin-components-button__pink {
        --wp-components-color-accent: #e91e63;
        --wp-components-color-accent-darker-10: #d81b60;
        --wp-components-color-accent-darker-20: #c2185b;
    }
    .noptin-components-button__update {
        --wp-components-color-accent: #d63638;
        --wp-components-color-accent-darker-10: #c92f31;
        --wp-components-color-accent-darker-20: #b82a2c;
    }
</style>
<div class="wrap noptin-list">
	<h1><?php esc_html( get_admin_page_title() ); ?></h1>
	<div id="noptin-misc__lists_app">
		<!-- spinner -->
		<span class="spinner" style="visibility: visible; float: none;"></span>
		<!-- /spinner -->
	</div>
</div>
