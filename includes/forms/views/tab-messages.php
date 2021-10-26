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

<p class="description" style="margin-bottom: 16px;"><?php
	printf(
		esc_html__( 'Edit the messages shown when someone submits this form. %sSmart tags%s are allowed in the message fields.', 'newsletter-optin-box' ),
		'<a href="#TB_inline?width=0&height=550&inlineId=noptin-form-variables" class="thickbox">',
		'</a>'
	);
?></p>

<table class="form-table noptin-form-messages">

    <?php foreach ( get_default_noptin_form_messages() as $message_id => $message_details ) : ?>
		<tr valign="top" class="form-field-row form-field-row-message-<?php echo sanitize_html_class( $message_id ); ?>">
			<th scope="row">
				<label for="noptin-form-message-<?php echo sanitize_html_class( $message_id ); ?>"><?php echo esc_html( $message_details['label'] ); ?></label>
			</th>
			<td>
                <input type="text" class="regular-text" id="noptin-form-message-<?php echo sanitize_html_class( $message_id ); ?>" name="noptin_form[messages][<?php echo esc_attr( $message_id ); ?>]" value="<?php echo esc_attr( isset( $all_messages[ $message_id ] ) ? $all_messages[ $message_id ] : $message_details['default'] ); ?>" placeholder="<?php echo esc_attr( $message_details['default'] ); ?>" />
			    <p class="description"><?php echo esc_html( $message_details['description'] ); ?></p>
			</td>
		</tr>
	<?php endforeach; ?>

	<?php do_action( 'noptin_form_messages_editor', $form ); ?>
</table>
