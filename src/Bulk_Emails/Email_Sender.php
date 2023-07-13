<?php

namespace Hizzle\Noptin\Bulk_Emails;

/**
 * Bulk Emails API: Email Sender.
 *
 * Contains the main email sender class.
 *
 * @since   1.12.0
 * @package Noptin
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main email sender class.
 */
abstract class Email_Sender {

	/**
	 * The email sender.
	 * @var string
	 */
	protected $sender = 'noptin';

	/**
	 * Initiates new non-blocking asynchronous request.
	 *
	 * @ignore
	 */
	public function __construct() {

		// Displays sender options.
		add_action( 'noptin_sender_options_' . $this->sender, array( $this, 'display_sending_options' ) );

		// Prepares a recipient.
		add_filter( "noptin_{$this->sender}_email_recipient", array( $this, 'filter_recipient' ), 10, 2 );
	}

	/**
	 * Fetch the next recipient.
	 *
	 * @param \Noptin_Newsletter_Email $campaign
	 *
	 * @return int[]|string[]
	 */
	abstract public function get_recipients( $campaign );

	/**
	 * Sends the actual email.
	 *
	 * @param @param \Noptin_Newsletter_Email $campaign
	 * @param int|string $recipient
	 *
	 * @return bool
	 */
	abstract public function send( $campaign, $recipient );

	/**
	 * Fired after a campaign is done sending.
	 *
	 * @param @param \Noptin_Newsletter_Email $campaign
	 *
	 */
	abstract public function done_sending( $campaign );

	/**
	 * Displays newsletter sending options.
	 *
	 * @param Noptin_Newsletter_Email|Noptin_automated_Email $campaign
	 *
	 * @return bool
	 */
	abstract public function display_sending_options( $campaign );

	/**
	 * Displays setting fields.
	 *
	 * @param Noptin_Newsletter_Email|Noptin_automated_Email $campaign
	 * @param string $key
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function display_sending_fields( $campaign, $key, $fields ) {

		if ( empty( $fields ) ) {
			return;
		}

		// Render sender options.
		$options = $campaign->get( $key );
		$options = is_array( $options ) ? $options : array();

		foreach ( $fields as $field_id => $data ) {

			$data['name']  = "noptin_email[$key][$field_id]";
			$data['value'] = isset( $options[ $field_id ] ) ? $options[ $field_id ] : '';
			$description   = '';

			// Backwards compatibility.
			if ( empty( $data['value'] ) && 'source' === $field_id ) {
				$data['value'] = isset( $options['_subscriber_via'] ) ? $options['_subscriber_via'] : '';
			}

			if ( ! empty( $data['description'] ) ) {
				$description = '<span class="noptin-help-text">' . wp_kses_post( $data['description'] ) . '</span>';
			}

			$data['description'] = $description;

			$method = "display_sending_field_{$data['type']}";

			if ( method_exists( $this, $method ) ) {
				call_user_func( array( $this, $method ), $data );
			}
		}
	}

	/**
	 * Displays a select setting field.
	 *
	 * @param array $field
	 */
	public function display_sending_field_select( $field ) {
		$class       = empty( $field['select2'] ) ? 'widefat' : 'widefat noptin-select2';
		$is_multiple = ! empty( $field['multiple'] );
		$placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : __( 'Select an option', 'newsletter-optin-box' );

		if ( $is_multiple ) {
			$field['value'] = noptin_parse_list( $field['value'], true );
		}

		?>
			<p>
				<label>
					<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
					<select name="<?php echo esc_attr( $field['name'] ); ?><?php echo $is_multiple ? '[]' : ''; ?>" class="<?php echo esc_attr( $class ); ?>" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo $is_multiple ? 'multiple="multiple"' : ''; ?>>
						<?php foreach ( $field['options'] as $option_key => $option_label ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $is_multiple ? in_array( $option_key, $field['value'] ) : $option_key == $field['value'] ); ?>><?php echo esc_html( $option_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Displays a token input field.
	 *
	 * @param array $field
	 */
	public function display_sending_field_token( $field ) {
		$field['placeholder'] = ! empty( $field['placeholder'] ) ? $field['placeholder'] : __( 'Enter a comma-separated list of tags', 'newsletter-optin-box' );
		$messages             = array(
			'noResults' => __( 'Enter a comma separated list of tags', 'newsletter-optin-box' ),
		);

		$field['value'] = noptin_parse_list( $field['value'], true );

		?>
		<p>
			<label>
				<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
				<select name="<?php echo esc_attr( $field['name'] ); ?>[]" data-placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" class="widefat noptin-select2" data-tags="true" data-token-separators="[',']" data-messages="<?php echo esc_attr( wp_json_encode( $messages ) ); ?>" multiple="multiple">
					<?php foreach ( $field['value'] as $token ) : ?>
						<option value="<?php echo esc_attr( $token ); ?>" selected="selected"><?php echo esc_html( $token ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php echo wp_kses_post( $field['description'] ); ?>
		</p>
	<?php
	}

	/**
	 * Displays multi select setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_multi_checkbox( $field ) {

		$value = is_array( $field['value'] ) ? $field['value'] : array();
		?>
			<?php echo wp_kses_post( $field['description'] ); ?>
			<ul style="overflow: auto; min-height: 42px; max-height: 200px; padding: 0 .9em; border: solid 1px #dfdfdf; background-color: #fdfdfd; margin-bottom: 1rem;">
				<?php foreach ( $field['options'] as $option_key => $option_label ) : ?>
					<li>
						<label>
							<input
								name='<?php echo esc_attr( $field['name'] ); ?>[]'
								type='checkbox'
								value='<?php echo esc_attr( $option_key ); ?>'
								<?php checked( in_array( $option_key, $value, true ) ); ?>
							>
							<span><?php echo esc_html( $option_label ); ?></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php

	}

	/**
	 * Displays a checkbox setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_checkbox( $field ) {

		?>
			<p>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $field['name'] ); ?>" value="1" <?php checked( ! empty( $field['value'] ), true ); ?>>
					<?php echo wp_kses_post( $field['label'] ); ?>
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Displays a text setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_text( $field ) {

		?>
			<p>
				<label>
					<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
					<input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" placeholder="<?php echo empty( $field['placeholder'] ) ? '' : esc_attr( $field['placeholder'] ); ?>" class="widefat">
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Displays a textarea setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_textarea( $field ) {

		?>
			<p>
				<label>
					<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
					<textarea name="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo empty( $data['placeholder'] ) ? '' : esc_attr( $data['placeholder'] ); ?>" class="widefat"><?php echo esc_textarea( $field['value'] ); ?></textarea>
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Filters a recipient.
	 *
	 * @param false|array $recipient
	 * @param int $recipient_id
	 *
	 * @return array
	 */
	public function filter_recipient( $recipient, $recipient_id ) {

		if ( ! is_array( $recipient ) ) {
			$recipient = array();
		}

		return $recipient;
	}
}
