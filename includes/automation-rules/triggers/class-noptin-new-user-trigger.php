<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fired when there is a new WordPress user.
 *
 * @since 1.9.0
 */
class Noptin_New_User_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * Constructor.
	 *
	 * @since 1.9.0
	 * @return string
	 */
	public function __construct() {
		add_action( 'user_register', array( $this, 'maybe_trigger' ), 1000, 2 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'new_user';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'New User Account', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Fired when there is a new user account', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {
		return __( 'When someone creates a new account', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_image() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'noptin',
			'user',
			'new',
		);
	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.9.0
     * @return array
     */
    public function get_known_smart_tags() {

		$smart_tags = array_merge(
			parent::get_known_smart_tags(),
			array(
				'user_id'    => array(
					'description'       => __( 'User ID', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
				),
				'email'      => array(
					'description'       => __( 'Email Address', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'name'       => array(
					'description'       => __( 'Display Name', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'first_name' => array(
					'description'       => __( 'First Name', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'last_name'  => array(
					'description'       => __( 'Last Name', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'user_login' => array(
					'description'       => __( 'Login Name', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'user_url'   => array(
					'description'       => __( 'User URL', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'user_bio'   => array(
					'description'       => __( 'User Bio', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
			)
		);

		return $smart_tags;
    }

	/**
	 * Called when someone creates a new account.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $userdata The raw array of data passed to wp_insert_user().
	 */
	public function maybe_trigger( $user_id, $userdata ) {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$args = array(
			'user_id'    => $user_id,
			'email'      => $user->user_email,
			'name'       => $user->display_name,
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'user_bio'   => $user->description,
			'user_url'   => $user->user_url,
			'user_login' => $user->user_login,
		);

		// Add the user's meta.
		$meta = get_user_meta( $user_id );
		foreach ( $meta as $key => $value ) {
			$args[ $key ] = $value[0];
		}

		$this->trigger( $user, $args );
	}

}
