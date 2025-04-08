<?php

namespace Hizzle\Noptin\Integrations\PMPro;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with PMPro
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * Class constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		add_action( 'noptin_load', __CLASS__ . '::register_custom_objects' );
		add_filter( 'noptin_user_collection_triggers', __CLASS__ . '::load_triggers', 5 );
		add_filter( 'noptin_user_test_args', __CLASS__ . '::add_trigger_test_args', 10, 3 );
		add_filter( 'noptin_user_collection_actions', __CLASS__ . '::load_actions', 5 );
		add_action( 'pmpro_after_change_membership_level', __CLASS__ . '::after_change_membership_level', 100, 3 );
		add_action( 'pmpro_checkout_before_change_membership_level', __CLASS__ . '::remove_trigger' );
		add_action( 'pmpro_after_checkout', __CLASS__ . '::after_checkout', 100, 2 );
		add_filter( 'noptin_get_settings', array( __CLASS__, 'add_options' ) );

		if ( get_noptin_option( 'pmpro_manage_preferences' ) ) {
			add_action( 'pmpro_show_user_profile', array( __CLASS__, 'display_pmpro_manage_subscription_form' ) );
		}
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public static function register_custom_objects() {
		\Hizzle\Noptin\Objects\Store::add( new Membership_Levels() );
	}

	/**
	 * Loads automation rule triggers.
	 *
	 * @param array $triggers
	 */
	public static function load_triggers( $triggers ) {

		$triggers['pmpro_membership_level_change'] = array(
			'id'          => 'pmpro_membership_level_change',
			'label'       => __( 'PMPro > Change Membership Level', 'newsletter-optin-box' ),
			'category'    => __( 'Paid Memberships Pro', 'newsletter-optin-box' ),
			'description' => __( "When a user's membership level changes", 'newsletter-optin-box' ),
			'subject'     => 'user',
			'provides'    => array( 'pmpro_membership_level' ),
			'extra_args'  => array(
				'cancel_level' => array(
					'description' => __( 'ID of the level being cancelled if specified', 'newsletter-optin-box' ),
					'type'        => 'number',
				),
			),
		);

		return $triggers;
	}

	/**
	 * Add automation rule test trigger args.
	 *
	 * @param array $args
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @param string $trigger_id
	 */
	public static function add_trigger_test_args( $args, $rule, $trigger_id ) {

		if ( 0 !== strpos( $trigger_id, 'pmpro_' ) || ! get_current_user_id() ) {
			return $args;
		}

		// Fetch current user level.
		$user   = wp_get_current_user();
		$levels = pmpro_getMembershipLevelsForUser( $user->ID );

		if ( empty( $levels ) ) {
			throw new \Exception( 'Current user has no active membership level' );
		}

		return array(
			'email'      => $user->user_email,
			'object_id'  => $user->ID,
			'subject_id' => $user->ID,
			'provides'   => array(
				'pmpro_membership_level' => current( wp_list_pluck( $levels, 'id' ) ),
			),
		);
	}

	/**
	 *
	 * @param int $level_id of the level the user is changing to.
	 * @param int $user_id of the user changing levels.
	 * @param int $cancel_level_id of the level the user is changing from.
	 */
	public static function after_change_membership_level( $level_id, $user_id, $cancel_level_id = 0 ) {

		$collection = \Hizzle\Noptin\Objects\Store::get( 'user' );
		$user       = get_userdata( $user_id );

		// Abort if the action is not known.
		if ( ! $collection || ! $user ) {
			return;
		}

		if ( empty( $level_id ) && ! empty( $cancel_level_id ) ) {
			$collection->trigger(
				'pmpro_membership_level_canceled',
				array(
					'email'      => $user->user_email,
					'object_id'  => $user->ID,
					'subject_id' => $user->ID,
					'provides'   => array(
						'pmpro_membership_level' => $cancel_level_id,
					),
				)
			);
		} elseif ( ! empty( $level_id ) ) {
			$collection->trigger(
				'pmpro_membership_level_change',
				array(
					'email'      => $user->user_email,
					'object_id'  => $user->ID,
					'subject_id' => $user->ID,
					'provides'   => array(
						'pmpro_membership_level' => $level_id,
					),
					'extra_args' => array(
						'user.cancel_level' => $cancel_level_id,
					),
				)
			);
		}
	}

	/**
	 * Delay the call to $this->init_trigger() during checkout until
	 * after usermeta is saved. Function call re-added in $this->after_checkout().
	 */
	public static function remove_trigger() {
		remove_action( 'pmpro_after_change_membership_level', __CLASS__ . '::after_change_membership_level', 100 );
	}

	/**
	 * Fires on checkout after usermeta is saved.
	 *
	 * @param int $user_id of user who checked out.
	 * @param \MemberOrder $order — The order to complete the checkout for.
	 */
	public static function after_checkout( $user_id, $order = false ) {
		global $pmpro_level;

		if ( ! $pmpro_level && ! $order ) {
			$order = new \MemberOrder();
			$order = new \MemberOrder( $order->getLastMemberOrder( $user_id ) );
		}

		$membership_level = $pmpro_level ? $pmpro_level : $order->getMembershipLevel();

		if ( ! empty( $membership_level ) ) {
			self::after_change_membership_level( $membership_level->id, $user_id );
		}
	}

	/**
	 * Loads automation rule actions.
	 *
	 * @param array $actions
	 */
	public static function load_actions( $actions ) {

		return array_merge(
			$actions,
			array(
				'pmpro_change_membership_level' => array(
					'id'             => 'pmpro_change_membership_level',
					'label'          => __( 'PMPro > Add to Membership Level', 'noptin-addons-pack' ),
					'category'       => __( 'Paid Memberships Pro', 'newsletter-optin-box' ),
					'description'    => __( "Updates the user's membership level", 'noptin-addons-pack' ),
					'callback'       => __CLASS__ . '::change_membership_level',
					'action_fields'  => array( 'email' ),
					'extra_settings' => array(
						'level' => array(
							'label'       => __( 'New Level', 'noptin-addons-pack' ),
							'description' => __( 'Enter the level id or name', 'noptin-addons-pack' ),
							'type'        => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Changes a user's membership level.
	 *
	 * @param array $args
	 */
	public static function change_membership_level( $args ) {

		if ( empty( $args['level'] ) ) {
			return new \WP_Error( 'noptin_invalid_level', 'Invalid membership level.' );
		}

		if ( empty( $args['email'] ) ) {
			return new \WP_Error( 'noptin_invalid_email', __( 'Invalid email address or user ID.', 'noptin-addons-pack' ) );
		}

		if ( is_email( $args['email'] ) ) {
			$user = get_user_by( 'email', $args['email'] );
		} elseif ( is_numeric( $args['email'] ) ) {
			$user = get_userdata( $args['email'] );
		}

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( empty( $user ) ) {
			return new \WP_Error( 'noptin_invalid_email', __( 'Invalid email address or user ID.', 'noptin-addons-pack' ) );
		}

		$level = pmpro_getLevel( $args['level'] );

		if ( empty( $level ) ) {
			return new \WP_Error( 'noptin_invalid_level', 'Invalid membership level.' );
		}

		$custom_level = array(
			'user_id'         => $user->ID,
			'membership_id'   => $level->id,
			'code_id'         => 0,
			'initial_payment' => $level->initial_payment,
			'billing_amount'  => $level->billing_amount,
			'cycle_number'    => $level->cycle_number,
			'cycle_period'    => $level->cycle_period,
			'billing_limit'   => $level->billing_limit,
			'trial_amount'    => $level->trial_amount,
			'trial_limit'     => $level->trial_limit,
			'startdate'       => current_time( 'mysql' ),
			'enddate'         => '0000-00-00 00:00:00',
		);

		if ( ! pmpro_changeMembershipLevel( $custom_level, $user->ID, 'changed' ) && ! empty( $GLOBALS['pmpro_error'] ) ) {
			return new \WP_Error( 'noptin_pmpro_error', $GLOBALS['pmpro_error'] );
		}
	}

	/**
	 * Add PMPro manage options to Noptin settings.
	 *
	 * @param array $options
	 * @return array
	 */
	public static function add_options( $options ) {

		if ( isset( $options['subscription_info'] ) ) {
			$options['subscription_info']['settings']['pmpro_manage_preferences'] = array(
				'el'          => 'input',
				'type'        => 'checkbox',
				'label'       => __( 'PMPro Newsletter Subscription Management', 'newsletter-optin-box' ),
				'description' => __( 'If enabled, subscribers will be able to manage their newsletter subscription from their PMPro profile edit page.', 'newsletter-optin-box' ),
			);
		}

		return $options;
	}

	/**
	 * Display PMPro manage subscription form.
	 */
	public static function display_pmpro_manage_subscription_form() {

		$user       = get_userdata( get_current_user_id() );
		$subscriber = noptin_get_subscriber( $user->user_email );
		$subscribed = 'subscribed' === $subscriber->get_status();
		$defaults   = array(
			'email'      => $user->user_email,
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
		);

		if ( ! $subscriber->exists() ) {
			$subscriber = false;
		}

		?>
			<style>
				textarea.noptin-text {
				font-family: inherit;
				width: 100%;
			}
			</style>
			<fieldset id="pmpro_member_profile_edit-noptin-information" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_member_profile_edit-noptin-information' ) ); ?>">
				<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Newsletter', 'paid-memberships-pro' ); ?></h2>
				</legend>
				<div style="margin-bottom: 16px;">
					<label>
						<input type="hidden" name="noptin_fields[status]" value="unsubscribed" />
						<input type="checkbox" name="noptin_fields[status]" value="subscribed" <?php checked( $subscribed ); ?> />
						<span>
							<?php esc_html_e( 'Subscribe to our newsletter', 'noptin-addons-pack' ); ?>
						</span>
					</label>
				</div>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields pmpro_cols-2' ) ); ?>">
					<?php

					foreach ( get_noptin_custom_fields( true ) as $custom_field ) {

						if ( isset( $defaults[ $custom_field['merge_tag'] ] ) ) {
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

						?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-' . $custom_field['merge_tag'], 'pmpro_form_field-' . $custom_field['merge_tag'] ) ); ?>">
								<?php display_noptin_custom_field_input( $custom_field, $subscriber ); ?>
								</div>
							<?php
					}

						wp_nonce_field( 'noptin-manage-subscription-nonce', 'noptin-manage-subscription-nonce' );
					?>
				</div>
			</fieldset>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					const labels = document.querySelectorAll('#pmpro_member_profile_edit-noptin-information .noptin-label');
					labels.forEach(function(label) {
						if (!label.classList.contains('pmpro_form_label')) {
							label.classList.add('pmpro_form_label');
						}
					});

					const inputs = document.querySelectorAll('#pmpro_member_profile_edit-noptin-information .noptin-text');
					inputs.forEach(function(input) {
						if (!input.classList.contains('pmpro_form_input')) {
							input.classList.add('pmpro_form_input');
						}
						if (!input.classList.contains('pmpro_form_input-text')) {
							input.classList.add('pmpro_form_input-text');
						}
					});
				});
			</script>
		<?php
	}
}
