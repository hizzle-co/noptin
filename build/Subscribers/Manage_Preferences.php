<?php

namespace Hizzle\Noptin\Subscribers;

defined( 'ABSPATH' ) || exit;

// Backwards compatibility.
define( 'NOPTIN_MANAGE_PREFERENCES_FILE', __FILE__ );

/**
 * Allows subscribers to manage their subscription preferences.
 *
 */
class Manage_Preferences {

	/**
	 * Error in current request.
	 */
	protected static $error_message;

	/**
	 * Success in current request.
	 */
	protected static $success_message;

	/**
	 * Init variables.
	 *
	 * @since       1.0.0
	 */
	public static function init() {
		add_shortcode( 'noptin_manage_subscription', array( __CLASS__, 'get_form' ) );
		add_action( 'noptin_init', array( __CLASS__, 'maybe_add_update_subscriber' ), 100 );
	}

	/**
	 * Generates manage subscriptions HTML.
	 *
	 * @return string
	 */
	public static function get_form() {

		// Generate mark-up.
		ob_start();
		self::display_form();
		return ob_get_clean();
	}

	/**
	 * Displays the manage subscriptions form.
	 *
	 * @return string
	 */
	public static function display_form() {

		// Prepare subscriber details.
		$subscriber = noptin_get_subscriber( get_current_noptin_subscriber_id() );
		$subscribed = 'subscribed' === $subscriber->get_status();
		$defaults   = array();

		if ( get_current_user_id() && ! $subscriber->exists() ) {
			$user = get_userdata( get_current_user_id() );

			if ( ! $subscriber->exists() ) {
				$subscriber = noptin_get_subscriber( $user->user_email );
				$subscribed = 'subscribed' === $subscriber->get_status();
			}

			$defaults = array(
				'email'      => $user->user_email,
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
			);
		}

		if ( ! $subscriber->exists() ) {
			$subscriber = false;
		}

		?>
			<style>
				.noptin-manage-subscriptions p,
				.noptin-field-wrapper {
					padding: 0.1875em;
					margin: 0 0 0.375em;
				}

				.noptin-actions-page-inner .noptin-manage-subscriptions p {
					padding: 0;
					margin: 0 0 16px;
				}

				.noptin-manage-subscriptions .noptin-label {
					font-weight: 700;
					display: block;
				}

				.noptin-manage-subscriptions .noptin-text {
					margin: 0;
					min-height: 32px;
					width: 100%;
					max-width: 610px;
					display: block;
					box-sizing: border-box;
					height: auto;
					padding: 12px;
				}

				.noptin-manage-subscription-success-div p {
					color: green;
				}

				.noptin-manage-subscription-error-div p {
					color: red;
				}

				.noptin-actions-page-inner .button {
					box-sizing: border-box;
					padding: 12px;
				}

				.screen-reader-text {
					border: 0;
					clip: rect(1px,1px,1px,1px);
					-webkit-clip-path: inset(50%);
					clip-path: inset(50%);
					height: 1px;
					margin: -1px;
					overflow: hidden;
					padding: 0;
					position: absolute;
					width: 1px;
					word-wrap: normal!important;
				}
			</style>
			<form class="noptin-manage-subscriptions" method="POST" action="<?php echo esc_url_raw( add_query_arg( array() ) ); ?>">

				<?php if ( ! empty( self::$error_message ) ) : ?>
					<div class="noptin-manage-subscription-error-div">
						<p><?php echo esc_html( self::$error_message ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( self::$success_message ) ) : ?>
					<div class="noptin-manage-subscription-success-div">
						<p><?php echo esc_html( self::$success_message ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( apply_filters( 'noptin_manage_subscriptions_show_status_field', true ) ) : ?>
					<div class="noptin-field-wrapper noptin-field-wrapper--status">
						<input type="hidden" name="noptin_fields[status]" value="unsubscribed" />
						<input type="checkbox" name="noptin_fields[status]" value="subscribed" <?php checked( $subscribed ); ?> />
						<span>
							<?php esc_html_e( 'Subscribe to our newsletter', 'noptin-addons-pack' ); ?>
						</span>
					</div>
				<?php endif; ?>

				<?php

				foreach ( get_noptin_custom_fields( true ) as $custom_field ) {

					if ( ! empty( $defaults[ $custom_field['merge_tag'] ] ) ) {
						printf(
							'<input type="hidden" name="noptin_fields[%s]" value="%s" />',
							esc_attr( $custom_field['merge_tag'] ),
							esc_attr( $defaults[ $custom_field['merge_tag'] ] )
						);

						continue;
					}

					// Display the field.
					$custom_field['wrap_name'] = true;
					$custom_field['show_id']   = true;

					echo '<div class="noptin-field-wrapper noptin-field-wrapper--' . esc_attr( $custom_field['merge_tag'] ) . '">';
					display_noptin_custom_field_input( $custom_field, $subscriber );
					echo '</div>';
				}

					wp_nonce_field( 'noptin-manage-subscription-nonce', 'noptin-manage-subscription-nonce' );
				?>

				<input type="hidden" name="noptin-subscriber-key" value="<?php echo $subscriber ? esc_attr( $subscriber->get_confirm_key() ) : ''; ?>" />

				<div class="noptin-field-wrapper noptin-field-wrapper--submit">
					<input type="submit" name="submit" class="btn button wp-element-button" value="<?php esc_attr_e( 'Update Preferences', 'noptin-addons-pack' ); ?>" />
				</div>
			</form>
		<?php
	}

	/**
	 * Adds / Updates a subscriber when they submit the update preferences form.
	 *
	 * @return string
	 */
	public static function maybe_add_update_subscriber() {

		// Abort if no submission was made.
		if ( empty( $_POST['noptin-manage-subscription-nonce'] ) || empty( $_POST['noptin_fields'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['noptin-manage-subscription-nonce'], 'noptin-manage-subscription-nonce' ) ) {
			self::$error_message = __( 'Could not verify nonce. Please try again.', 'noptin-addons-pack' );
			return;
		}

		$posted = wp_unslash( $_POST['noptin_fields'] );
		if ( empty( $posted['email'] ) || ! is_email( $posted['email'] ) ) {
			self::$error_message = __( 'Missing or invalid email address.', 'noptin-addons-pack' );
			return;
		}

		$prepared = array();

		foreach ( get_noptin_custom_fields( true ) as $custom_field ) {
			if ( isset( $posted[ $custom_field['merge_tag'] ] ) ) {
				$prepared[ $custom_field['merge_tag'] ] = $posted[ $custom_field['merge_tag'] ];
			}
		}

		// If status was not set, set it to unsubscribed.
		if ( isset( $posted['status'] ) ) {
			$prepared['status'] = $posted['status'];
		}

		// Retrieve subscriber by key.
		if ( ! empty( $_POST['noptin-subscriber-key'] ) ) {
			$subscriber = noptin_get_subscriber( $_POST['noptin-subscriber-key'] );

			if ( ! $subscriber->exists() || $subscriber->get( 'confirm_key' ) !== $_POST['noptin-subscriber-key'] ) {
				$subscriber = false;
			}
		}

		// Retrieve subscriber by email.
		if ( empty( $subscriber ) ) {
			$subscriber = noptin_get_subscriber( $prepared['email'] );

			if ( ! $subscriber->exists() || $subscriber->get( 'email' ) !== $prepared['email'] ) {
				$subscriber = false;
			}
		}

		// Create or update subscriber.
		if ( empty( $subscriber ) ) {
			$result = add_noptin_subscriber(
				array_merge(
					array(
						'source' => 'Manage Preferences',
					),
					$prepared
				)
			);
		} else {
			$result = update_noptin_subscriber( $subscriber, $prepared );
		}

		if ( is_string( $result ) ) {
			self::$error_message = $result;
		} elseif ( is_wp_error( $result ) ) {
			self::$error_message = $result->get_error_message();
		} else {
			self::$success_message = __( 'Your changes have been saved', 'noptin-addons-pack' );
		}
	}
}
