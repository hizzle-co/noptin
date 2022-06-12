<?php
/**
 * Displays the messages tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$all_messages = $form->messages;
?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Form Messages', 'newsletter-optin-box' ); ?></h2>

<p class="description" style="margin-bottom: 16px;">
	<?php
		printf(
			// translators: %1 & 2, opening and closing link.
			esc_html__( 'Edit the messages shown when someone submits this form. %1$sSmart tags%2$s are allowed in the message fields.', 'newsletter-optin-box' ),
			'<a href="#TB_inline?width=0&height=550&inlineId=noptin-form-variables" class="thickbox">',
			'</a>'
		);
	?>
</p>

<?php foreach ( get_default_noptin_form_messages() as $message_id => $message_details ) : ?>
	<div class="noptin-text-wrapper noptin-form-field-message-<?php echo sanitize_html_class( $message_id ); ?>">
		<label for="noptin-form-message-<?php echo sanitize_html_class( $message_id ); ?>" class="noptin-field-label">
			<?php echo esc_html( $message_details['label'] ); ?>
			<span title="<?php echo esc_attr( $message_details['description'] ); ?>" class="noptin-tip dashicons dashicons-info"></span>
		</label>
		<input type="text" class="noptin-text" id="noptin-form-message-<?php echo sanitize_html_class( $message_id ); ?>" name="noptin_form[messages][<?php echo esc_attr( $message_id ); ?>]" value="<?php echo esc_attr( ! empty( $all_messages[ $message_id ] ) ? $all_messages[ $message_id ] : '' ); ?>" placeholder="<?php echo esc_attr( $message_details['default'] ); ?>" />
	</div>
<?php endforeach; ?>
