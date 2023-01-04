<?php defined( 'ABSPATH' ) || exit; ?>

<div class="noptin-text-wrapper">
	<label>
		<strong><?php esc_html_e( 'Shortcode', 'newsletter-optin-box' ); ?></strong>
		<input type="text" class="code" value="[noptin form=<?php echo (int) $GLOBALS['post_ID']; ?>]" readonly="readonly" onclick="this.select()" style="width: 100%;">
        <p class="description"><?php esc_html_e( 'Use this shortcode to display the form inside your post, page, or text widget content.', 'newsletter-optin-box' ); ?></p>
	</label>
</div>

<div class="noptin-text-wrapper">
	<label>
		<strong><?php esc_html_e( 'PHP Snippet', 'newsletter-optin-box' ); ?></strong>
		<input type="text" class="code" value="<?php echo esc_attr( sprintf( '<?php show_noptin_form(%d); ?>', (int) $GLOBALS['post_ID'] ) ); ?>" readonly="readonly" onclick="this.select()" style="width: 100%;">
        <p class="description"><?php esc_html_e( 'Use this PHP snippet to display the form inside your theme template files.', 'newsletter-optin-box' ); ?></p>
	</label>
</div>

<?php do_action( 'noptin_form_editor_embed_metabox', $GLOBALS['post_ID'] ); ?>
