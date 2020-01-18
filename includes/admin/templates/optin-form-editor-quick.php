<style id="formCustomCSS"></style>
<div class="noptin-form-designer-loader"><div class="noptin-spinner"></div></div>
<div class="noptin-popup-designer">
	<div id="noptin-quick-form-editor">
		<div class="noptin-popup-editor-header" tabindex="-1">
			<div class="noptin-popup-editor-title">{{headerTitle}} &mdash; {{optinName}} </div>
		</div>
		<div class="noptin-divider"></div>
		<div class="noptin-popup-editor-body">
			<div class="noptin-popup-editor-main">
				<div class="noptin-popup-editor-main-preview">
					<?php foreach ( $steps as $step => $fields ) { ?>
						<div v-show="currentStep=='<?php echo $step; ?>'" class="noptin-form-editor-step">
							<?php
							foreach ( $fields as $id => $field ) {
								Noptin_Vue::render_el( $id, $field, $step );
							}
							?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/x-template" id="noptinFormTemplate">
	<?php include 'optin-form.php'; ?>
</script>
<script type="text/x-template" id="noptinFieldEditorTemplate">
	<?php include 'fields-editor.php'; ?>
</script>
