<?php
/**
 * Handles birthday inputs.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles birthday inputs.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Birthday extends Noptin_Custom_Field_Type {

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function output( $args, $subscriber ) {

		$day   = '';
		$month = '';

		if ( is_array( $args['value'] ) ) {
			$day   = empty( $args['value']['day'] ) ? $day : $args['value']['day'];
			$month = empty( $args['value']['month'] ) ? $month : $args['value']['month'];
		}

		?>

			<label class="noptin-label" for="<?php echo esc_attr( $args['id'] ); ?>"><?php echo empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>

			<div class="noptin-birthday-div" style="display: flex;">
				<label style="flex: 0 0 80px; margin-right: 10px;">
					<span class="noptin-day screen-reader-text"><?php esc_html_e( 'Day', 'newsletter-optin-box' ); ?></span>
					<select
						name="<?php echo esc_attr( $args['name'] ); ?>[day]"
						id="<?php echo esc_attr( $args['id'] ); ?>"
						class="noptin-text noptin-form-field"
						<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
					>
						<option <?php selected( empty( $day ) ); ?> disabled><?php esc_html_e( 'Day', 'newsletter-optin-box' ); ?></option>
						<?php for( $i =1; $i < 32; $i++ ) : ?>
							<option value="<?php echo $i; ?>" <?php selected( (int) $day, $i ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>		
					</select>
				</label>
				<label style="flex: 1;">
					<span class="noptin-month screen-reader-text"><?php esc_html_e( 'Month', 'newsletter-optin-box' ); ?></span>
					<select
						name="<?php echo esc_attr( $args['name'] ); ?>[month]"
						id="<?php echo esc_attr( $args['id'] ); ?>-month"
						class="noptin-text noptin-form-field"
						<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
					>
						<option <?php selected( empty( $month ) ); ?> disabled><?php esc_html_e( 'Month', 'newsletter-optin-box' ); ?></option>
						<?php for( $i =1; $i < 13; $i++ ) : ?>
							<option value="<?php echo $i; ?>" <?php selected( (int) $month, $i ); ?>><?php echo date_i18n( 'F', strtotime( "2020-$i-15" ) ); ?></option>
						<?php endfor; ?>		
					</select>
				</label>
			</div>

		<?php

	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.5.5
	 * @param mixed $value Submitted value
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function sanitize_value( $value, $subscriber ) {

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$sanitized = array();

		if ( ! empty( $value['day'] ) ) {
			$sanitized['day'] = (int) $value['day'];
		}

		if ( ! empty( $value['month'] ) ) {
			$sanitized['month'] = (int) $value['month'];
		}

		return $sanitized;
	}

	/**
	 * Formats a value for display.
	 *
	 * @since 1.5.5
	 * @param mixed $value Sanitized value
	 * @param Noptin_Subscriber $subscriber
	 */
	public function format_value( $value, $subscriber ) {

		$value = $this->sanitize_value( $value, $subscriber );

		if ( empty( $value['day'] ) || empty( $value['month'] ) ) {
			return "&mdash;";
		}

		$day   = (int) $value['day'];
		$month = (int) $value['month'];
		return date_i18n( 'jS F', strtotime( "2020-$month-$day" ) );

	}

}
