<?php
/**
 * Displays the settings tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$all_settings = $form->settings;
$places       = array_merge(
	array(
		'frontpage'  => __( 'Front page', 'newsletter-optin-box' ),
		'blogpage'   => __( 'Blog page', 'newsletter-optin-box' ),
		'searchpage' => __( 'Search page', 'newsletter-optin-box' ),
		'archives'   => __( 'Archive pages', 'newsletter-optin-box' ),
	),
	noptin_get_post_types()
);
$hide         = empty( $form->settings['hide'] ) ? array() : $form->settings['hide'];
?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Advanced Settings', 'newsletter-optin-box' ); ?></h2>

<div class="noptin-text-wrapper form-settings-header-text">
	<label for="noptin-form-header-text" class="noptin-field-label">
		<?php esc_html_e( 'Header Text (Optional)', 'newsletter-optin-box' ); ?>
	</label>
	<textarea
		name="noptin_form[settings][before_fields]"
		class="regular-text"
		rows="4"
		id="noptin-form-header-text"
		placeholder="<?php esc_attr_e( "Example: <h2>Free Newsletter</h2>\n<p>Join {subscriber_count} other subscribers already on our newsletter</p>", 'newsletter-optin-box' ); ?>"
	><?php echo empty( $form->settings['before_fields'] ) ? '' : esc_textarea( $form->settings['before_fields'] ); ?></textarea>
	<p class="description">
		<?php
			printf(
				// translators: %1 & 2, opening and closing link.
				esc_html__( 'Shown above the form fields. HTML and %1$sSmart tags%2$s are allowed.', 'newsletter-optin-box' ),
				'<a href="#TB_inline?width=0&height=550&inlineId=noptin-form-variables" class="thickbox">',
				'</a>'
			);
		?>
	</p>
</div>

<div class="noptin-text-wrapper form-settings-footer-text">
	<label for="noptin-form-footer-text" class="noptin-field-label">
		<?php esc_html_e( 'Footer Text (Optional)', 'newsletter-optin-box' ); ?>
	</label>
	<textarea
		name="noptin_form[settings][after_fields]"
		class="regular-text"
		id="noptin-form-footer-text"
		rows="4"
		placeholder="<?php esc_attr_e( 'Example: We do not spam!', 'newsletter-optin-box' ); ?>"
		><?php echo empty( $form->settings['after_fields'] ) ? '' : esc_textarea( $form->settings['after_fields'] ); ?></textarea>
	<p class="description">
		<?php
			printf(
				// translators: %1 & 2, opening and closing link.
				esc_html__( 'Shown below the form fields. HTML and %1$sSmart tags%2$s are allowed.', 'newsletter-optin-box' ),
				'<a href="#TB_inline?width=0&height=550&inlineId=noptin-form-variables" class="thickbox">',
				'</a>'
			);
		?>
	</p>
</div>

<div class="noptin-text-wrapper form-settings-hide-on">
	<label for="noptin-form-hide-on" class="noptin-field-label">
		<?php esc_html_e( 'Hide on (Optional)', 'newsletter-optin-box' ); ?>
	</label>
	<select id="noptin-form-hide-on" class="noptin-select2" name="noptin_form[settings][hide][]" multiple="multiple" style="width: 25em;">
		<?php foreach ( $places as $key => $place ) : ?>
			<option
				value="<?php echo esc_attr( $key ); ?>"
				<?php selected( in_array( $key, $hide, true ) ); ?>
			><?php echo esc_html( $place ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Optional. Select the places where this form should be hidden.', 'newsletter-optin-box' ); ?></p>
</div>

<div class="noptin-text-wrapper form-settings-show-on">
	<label for="noptin-form-show-on" class="noptin-field-label">
		<?php esc_html_e( 'Only show on: (Optional)', 'newsletter-optin-box' ); ?>
	</label>
	<input type="text" class="regular-text" id="noptin-form-show-on" name="noptin_form[settings][only_show]" value="<?php echo isset( $all_settings['only_show'] ) ? esc_attr( $all_settings['only_show'] ) : ''; ?>" placeholder="<?php echo sprintf( /* translators: %s examples*/ esc_attr__( 'Example: %s', 'newsletter-optin-box' ), '3,14,5,' . esc_attr( noptin_clean_url( home_url( 'newsletter' ) ) ) ); ?>" />
	<p class="description"><?php esc_html_e( 'Optional. Enter a comma separated list of URLs or post ids. If set, the form will only show if a user is viewing those pages.', 'newsletter-optin-box' ); ?></p>
</div>

<?php do_action( 'noptin_form_advanced_settings_editor', $form ); ?>
