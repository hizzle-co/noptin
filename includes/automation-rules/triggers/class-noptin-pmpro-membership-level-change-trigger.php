<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a Paid Memberships Pro member level changes.
 *
 * @since 1.10.0
 */
class Noptin_PMPro_Membership_Level_Change_Trigger extends Noptin_Abstract_Trigger {

    /**
	 * Whether or not this trigger deals with a user.
	 *
	 * @var bool
	 */
	public $is_user_based = true;

    /**
	 * @var string
	 */
	public $category = 'Paid Memberships Pro';

	/**
	 * @var string
	 */
	public $integration = 'paid-memberships-pro';

    /**
	 * Constructor.
	 *
	 * @since 1.10.0
	 */
	public function __construct() {
		add_action( 'pmpro_after_change_membership_level', array( $this, 'init_trigger' ), 100, 3 );
        add_action( 'pmpro_checkout_before_change_membership_level', array( $this, 'remove_trigger' ) );
        add_action( 'pmpro_after_checkout', array( $this, 'after_checkout' ), 15 );
	}

    /**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'pmpro_membership_level_change';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'After Change Membership Level (PMPro)', 'newsletter-optin-box' );
	}

    /**
	 * @inheritdoc
	 */
	public function get_description() {
        return __( "When a user's membership level changes", 'newsletter-optin-box' );
    }

    /**
	 * @inheritdoc
	 */
    public function get_known_smart_tags() {

		$smart_tags = array_merge(
			parent::get_known_smart_tags(),
			array(
                'cancel_level'            => array(
					'description'       => __( 'ID of the level being cancelled if specified', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
				),
				'level_id'                => array(
					'description'       => __( 'Level ID', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
				),
                'level_name'              => array(
                    'description'       => __( 'Level Name', 'newsletter-optin-box' ),
                    'conditional_logic' => 'text',
                ),
                'level_description'       => array(
                    'description'       => __( 'Level Description', 'newsletter-optin-box' ),
                    'conditional_logic' => 'text',
                ),
                'level_confirmation'      => array(
                    'description'       => __( 'Level Confirmation Message', 'newsletter-optin-box' ),
                    'conditional_logic' => 'text',
                ),
                'level_initial_payment'   => array(
                    'description'       => __( 'Level Initial Payment', 'newsletter-optin-box' ),
                    'conditional_logic' => 'number',
                ),
                'level_billing_amount'    => array(
                    'description'       => __( 'Level Billing Amount', 'newsletter-optin-box' ),
                    'conditional_logic' => 'number',
                ),
                'level_cycle_number'      => array(
                    'description'       => __( 'Level Cycle Number', 'newsletter-optin-box' ),
                    'conditional_logic' => 'number',
                ),
                'level_cycle_period'      => array(
                    'description'       => __( 'Level Cycle Period', 'newsletter-optin-box' ),
                    'conditional_logic' => 'text',
                    'options'           => array(
                        'Day'   => __( 'Day', 'newsletter-optin-box' ),
                        'Week'  => __( 'Week', 'newsletter-optin-box' ),
                        'Month' => __( 'Month', 'newsletter-optin-box' ),
                        'Year'  => __( 'Year', 'newsletter-optin-box' ),
                    ),
                ),
                'level_billing_limit'     => array(
                    'description'       => __( 'Level Billing Limit', 'newsletter-optin-box' ),
                    'conditional_logic' => 'number',
                ),
                'level_trial_amount'      => array(
                    'description'       => __( 'Level Trial Amount', 'newsletter-optin-box' ),
                    'conditional_logic' => 'number',
                ),
                'level_trial_limit'       => array(
                    'description'       => __( 'Level Trial Limit', 'newsletter-optin-box' ),
                    'conditional_logic' => 'number',
                ),
                'level_allow_signups'     => array(
                    'description'       => __( 'Level Allow Signups', 'newsletter-optin-box' ),
                    'conditional_logic' => 'text',
                    'options'           => array(
                        'yes' => __( 'Yes', 'newsletter-optin-box' ),
                        'no'  => __( 'No', 'newsletter-optin-box' ),
                    ),
                ),
                'level_expiration_number' => array(
                    'description'       => __( 'Level Expiration Number', 'newsletter-optin-box' ),
                    'conditional_logic' => 'number',
                ),
                'level_expiration_period' => array(
                    'description'       => __( 'Level Expiration Period', 'newsletter-optin-box' ),
                    'conditional_logic' => 'text',
                    'options'           => array(
                        'Day'   => __( 'Day', 'newsletter-optin-box' ),
                        'Week'  => __( 'Week', 'newsletter-optin-box' ),
                        'Month' => __( 'Month', 'newsletter-optin-box' ),
                        'Year'  => __( 'Year', 'newsletter-optin-box' ),
                    ),
                ),
			)
		);

		return $smart_tags;
    }

    /**
     * Delay the call to $this->init_trigger() during checkout until
     * after usermeta is saved. Function call re-added in $this->after_checkout().
     */
    public function remove_trigger() {
        remove_action( 'pmpro_after_change_membership_level', array( $this, 'init_trigger' ), 100 );
    }

    /**
     * Fires on checkout after usermeta is saved.
     *
     * @param int $user_id of user who checked out.
     */
    public function after_checkout( $user_id ) {
        $this->init_trigger( $_REQUEST['level'], $user_id );
    }

    /**
     *
     * @param int $level_id of the level the user is changing to.
     * @param int $user_id of the user changing levels.
     * @param int $cancel_level_id of the level the user is changing from.
     */
    public function init_trigger( $level_id, $user_id, $cancel_level_id = 0 ) {

        $level   = pmpro_getLevel( $level_id );
        $subject = get_userdata( $user_id );
        $args    = array(
            'cancel_level' => (int) $cancel_level_id,
            'user_id'      => (int) $user_id,
            'level_id'     => (int) $level_id,
        );

        if ( is_object( $level ) ) {

            foreach ( (array) $level as $key => $value ) {

                if ( 'id' === $key ) {
                    $value = absint( $value );
                }

                if ( 'allow_signups' === $key ) {
                    $value = empty( $value ) ? 'no' : 'yes';
                }

                $args[ 'level_' . $key ] = $value;
            }
        }

        $this->trigger( $subject, $args );
    }

    /**
	 * Serializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		return array(
            'cancel_level' => $args['cancel_level'],
            'user_id'      => $args['user_id'],
            'level_id'     => $args['level_id'],
        );
	}

    /**
	 * Unserializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {

        $level = pmpro_getLevel( $args['level_id'] );
        $user  = get_userdata( $args['user_id'] );

		if ( empty( $level ) ) {
			throw new Exception( 'The membership level no longer exists' );
		}

		if ( empty( $user ) ) {
			throw new Exception( 'The user no longer exists' );
		}

        // Check if the user is still a member of the level.
        if ( ! pmpro_hasMembershipLevel( $args['level_id'], $args['user_id'] ) ) {
            throw new Exception( 'The user is no longer a member of the level' );
        }

		foreach ( (array) $level as $key => $value ) {

            if ( 'id' === $key ) {
                $value = absint( $value );
            }

            if ( 'allow_signups' === $key ) {
                $value = empty( $value ) ? 'no' : 'yes';
            }

            $args[ 'level_' . $key ] = $value;
        }

		return $this->prepare_trigger_args( $user, $args );
	}
}
