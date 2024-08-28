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

$popup_types = array(

	'popup' => array(
		__( 'Popup Settings', 'newsletter-optin-box' ),
		__( 'Display this form in a popup', 'newsletter-optin-box' ),
		__( 'Show the popup', 'newsletter-optin-box' ),
	),

	'slide' => array(
		__( 'Sliding Settings', 'newsletter-optin-box' ),
		__( 'Slide this form into view', 'newsletter-optin-box' ),
		__( 'Slide this form', 'newsletter-optin-box' ),
	),

);

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
	<select id="noptin-form-hide-on" name="noptin_form[settings][hide][]" multiple="multiple" style="width: 25em;">
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
	<input type="text" class="regular-text" id="noptin-form-show-on" name="noptin_form[settings][only_show]" value="<?php echo isset( $all_settings['only_show'] ) ? esc_attr( $all_settings['only_show'] ) : ''; ?>" placeholder="<?php printf( /* translators: %s: The Example */ esc_attr__( 'For example, %s', 'newsletter-optin-box' ), '3,14,5,' . esc_attr( noptin_clean_url( home_url( 'newsletter' ) ) ) ); ?>" />
	<p class="description"><?php esc_html_e( 'Optional. Enter a comma separated list of URLs or post ids. If set, the form will only show if a user is viewing those pages.', 'newsletter-optin-box' ); ?></p>
</div>

<?php
	foreach ( $popup_types as $key => $labels ) {
		$settings = isset( $form->settings[ $key ] ) ? $form->settings[ $key ] : array();

		$triggers = array(
			'immeadiate'   => __( 'Immediately', 'newsletter-optin-box' ),
			'before_leave' => __( 'Before the user leaves', 'newsletter-optin-box' ),
			'on_scroll'    => __( 'After the user starts scrolling', 'newsletter-optin-box' ),
			'after_click'  => __( 'After clicking on something', 'newsletter-optin-box' ),
			'after_delay'  => __( 'After a time delay', 'newsletter-optin-box' ),
		);
		$trigger  = isset( $settings['trigger'] ) ? $settings['trigger'] : 'immeadiate';

		$directions = array(
			'top_left'     => __( 'Top Left', 'newsletter-optin-box' ),
			'left_top'     => __( 'Top Left Alt', 'newsletter-optin-box' ),
			'top_right'    => __( 'Top Right', 'newsletter-optin-box' ),
			'right_top'    => __( 'Top Right Alt', 'newsletter-optin-box' ),
			'center_left'  => __( 'Center Left', 'newsletter-optin-box' ),
			'center_right' => __( 'Center Right', 'newsletter-optin-box' ),
			'bottom_left'  => __( 'Bottom Left', 'newsletter-optin-box' ),
			'left_bottom'  => __( 'Bottom Left Alt', 'newsletter-optin-box' ),
			'bottom_right' => __( 'Bottom right', 'newsletter-optin-box' ),
			'right_bottom' => __( 'Bottom right Alt', 'newsletter-optin-box' ),
		);
		$direction  = isset( $settings['direction'] ) ? $settings['direction'] : 'bottom_right';

		$positions = array(
			'top'    => __( 'Top of the page', 'newsletter-optin-box' ),
			'bottom' => __( 'Bottom of the page', 'newsletter-optin-box' ),
		);
		$position  = isset( $settings['position'] ) ? $settings['position'] : 'top';

		?>

		<div class="noptin-text-wrapper form-settings-<?php echo esc_attr( $key ); ?>" style="display: none;">
			<label for="noptin-form-enable-<?php echo esc_attr( $key ); ?>" class="noptin-field-label"><?php echo esc_html( $labels[0] ); ?></label>

			<?php noptin_hidden_field( 'noptin_form[settings][' . $key . '][enable]', 0 ); ?>
			<label>
				<input type="checkbox" id="noptin-form-enable-<?php echo esc_attr( $key ); ?>" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>][enable]" value="1" <?php checked( ! empty( $settings['enable'] ) ); ?>/>
				<span class="description"><?php echo esc_html( $labels[1] ); ?></span>
			</label>

			<?php if ( 'slide' === $key ) : ?>
				<label><span style="vertical-align: middle;"><?php esc_html_e( 'from the', 'newsletter-optin-box' ); ?></span>
					<select id="noptin-form-slide-direction" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>][direction]">
						<?php foreach ( $directions as $_key => $label ) : ?>
							<option value="<?php echo esc_attr( $_key ); ?>" <?php selected( $_key, $direction ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>

			<?php if ( 'bar' === $key ) : ?>
				<label><span style="vertical-align: middle;"><?php esc_html_e( 'at the', 'newsletter-optin-box' ); ?></span>
					<select id="noptin-form-bar-position" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>][position]">
						<?php foreach ( $positions as $_key => $label ) : ?>
							<option value="<?php echo esc_attr( $_key ); ?>" <?php selected( $_key, $position ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>

			<select id="noptin-form-trigger-<?php echo esc_attr( $key ); ?>" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>][trigger]">
				<?php foreach ( $triggers as $_key => $label ) : ?>
					<option value="<?php echo esc_attr( $_key ); ?>" <?php selected( $_key, $trigger ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="noptin-<?php echo esc_attr( $key ); ?>-trigger-value on_scroll" style="<?php echo 'on_scroll' === $trigger ? '' : 'display: none;'; ?>"><input type="number" style="width: 50px;" max="100" min="0" step="0.1" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>][on_scroll]" value="<?php echo isset( $settings['on_scroll'] ) ? floatval( $settings['on_scroll'] ) : '25'; ?>" /><?php esc_html_e( '% of the page', 'noptin-addons-pack' ); ?></label>
			<label class="noptin-<?php echo esc_attr( $key ); ?>-trigger-value after_click" style="<?php echo 'after_click' === $trigger ? '' : 'display: none;'; ?>"><input type="text" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>][after_click]" value="<?php echo isset( $settings['after_click'] ) ? esc_attr( $settings['after_click'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'Enter CSS selector', 'noptin-addons-pack' ); ?>"/></label>
			<label class="noptin-<?php echo esc_attr( $key ); ?>-trigger-value after_delay" style="<?php echo 'after_delay' === $trigger ? '' : 'display: none;'; ?>"><input type="number" style="width: 80px;" name="noptin_form[settings][<?php echo esc_attr( $key ); ?>][after_delay]" value="<?php echo isset( $settings['after_delay'] ) ? floatval( $settings['after_delay'] ) : '5'; ?>" /><?php esc_html_e( 'seconds', 'noptin-addons-pack' ); ?></label>

			<script>
				jQuery( '#noptin-form-trigger-<?php echo esc_js( $key ); ?>' ).on( 'change', function() {
					jQuery( '.noptin-<?php echo esc_js( $key ); ?>-trigger-value' ).hide();
					jQuery( '.noptin-<?php echo esc_js( $key ); ?>-trigger-value.' + jQuery( this ).val() ).show();
				});
			</script>
		</div>

		<?php
	}
?>

<?php do_action( 'noptin_form_advanced_settings_editor', $form ); ?>
