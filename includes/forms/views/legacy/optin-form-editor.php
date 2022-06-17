<?php defined( 'ABSPATH' ) || exit; ?>
<style id="formCustomCSS"></style>
<style id="generatedCustomCSS"></style>
<div class="noptin-form-designer-loader">
	<div class="noptin-spinner"></div>
</div>
<div class="noptin-popup-designer">
	<style>

		.wp-admin .v-expansion-panel-header {
			font-weight: 500;
		}

		.v-input__control input[type="text"] {
			background-color: transparent;
    		border-style: none;
		}

		.v-input__control input[type="text"]:focus,
		.v-input__control textarea:focus {
			background-color: transparent;
    		border-style: none;
			outline: none;
			box-shadow: none;
		}

		.noptin-sidebar-expansion-panels.theme--light.v-expansion-panels.v-expansion-panels--focusable .v-expansion-panel-header--active::before {
    		opacity: 0;
		}

		.wp-admin .v-input{
			font-size: 14px;
		}

		.noptin-field-editor .v-expansion-panel::before {
			box-shadow: none;
		}

		.noptin-field-editor .v-expansion-panel,
		.noptin-field-editor .v-expansion-panels:not(.v-expansion-panels--accordion):not(.v-expansion-panels--tile) > .v-expansion-panel--active + .v-expansion-panel,
		.noptin-field-editor .v-expansion-panels:not(.v-expansion-panels--accordion):not(.v-expansion-panels--tile) > .v-expansion-panel--active {
			box-shadow: none;
			border: 1px solid #ccc;
			border-radius: 0;
		}

		.noptin-field-editor .v-expansion-panel:not(.v-expansion-panel--active):not(:first-child) {
			border-top: 0;
		}

	</style>
	<div id="noptin-form-editor"></div>
</div>

<script type="text/x-template" id="noptinFieldEditorTemplate">
	<?php require plugin_dir_path( __FILE__ ) . 'fields-editor.php'; ?>
</script>

<script type="text/x-template" id="noptinOptinFormTemplate">
	<?php require plugin_dir_path( __FILE__ ) . 'optin-form.php'; ?>
</script>
