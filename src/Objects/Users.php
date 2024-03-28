<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for WordPress users.
 *
 * @since 3.0.0
 */
class Users extends People {

	public static $user_types = array();

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Objects\User';

	/**
	 * @var string integration.
	 */
	public $integration = 'wordpress'; // phpcs:ignore

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct( $type, $label, $singular_label ) {
		$this->label          = $label;
		$this->singular_label = $singular_label;
		$this->type           = $type;
		$this->icon           = array(
			'icon' => 'admin-users',
			'fill' => '#404040',
		);

		if ( 'user' === $type && noptin_has_active_license_key() ) {
			$this->email_sender   = 'wp_users';
			$this->is_stand_alone = false;
			$this->can_list       = true;
		}

		self::$user_types[] = $type;
		parent::__construct();
	}

	/**
	 * Retrieves several users.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $users The user IDs.
	 */
	public function get_all( $filters ) {
		return get_users(
			array(
				'number' => -1,
				'fields' => 'ID',
			)
		);
	}

	/**
	 * Retrieves a single person from a WordPress user.
	 *
	 * @param \WP_User $user The user.
	 * @return User $person The person.
	 */
	public function get_from_user( $user ) {
		return new User( $user );
	}

	/**
	 * Retrieves a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return User $person The person.
	 */
	public function get_from_email( $email ) {
		return new User( get_user_by( 'email', $email ) );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$fields = array(
			'id'           => array(
				'label'      => __( 'ID', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'user_id',
			),
			'role'         => array(
				'label'      => __( 'Role', 'newsletter-optin-box' ),
				'type'       => 'string',
				'options'    => wp_roles()->get_names(),
				'deprecated' => 'user_role',
				'actions'    => array( 'add_user', 'add_user_role', 'remove_user_role', 'set_user_role' ),
				'required'   => true,
				'default'    => get_option( 'default_role', 'subscriber' ),
			),
			'locale'       => array(
				'label'      => __( 'Locale', 'newsletter-optin-box' ),
				'type'       => 'string',
				'options'    => noptin_get_available_languages(),
				'default'    => get_locale(),
				'deprecated' => 'user_locale',
			),
			'email'        => array(
				'label'        => __( 'Email', 'newsletter-optin-box' ),
				'type'         => 'string',
				'actions'      => array( 'add_user', 'add_user_role', 'remove_user_role', 'set_user_role', 'delete_user' ),
				'required'     => true,
				'default'      => '[[email]]',
				'action_label' => __( 'User ID or email address', 'newsletter-optin-box' ),
			),
			'display_name' => array(
				'label'      => __( 'Display name', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'name',
			),
			'first_name'   => array(
				'label'      => __( 'First name', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'first_name',
				'actions'    => array( 'add_user' ),
			),
			'last_name'    => array(
				'label'      => __( 'Last name', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'last_name',
				'actions'    => array( 'add_user' ),
			),
			'login'        => array(
				'label'      => __( 'Username', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'user_login',
			),
			'url'          => array(
				'label'      => __( 'URL', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'user_url',
			),
			'description'  => array(
				'label'          => __( 'BIO', 'newsletter-optin-box' ),
				'type'           => 'string',
				'skip_smart_tag' => true,
				'deprecated'     => 'user_description',
				'actions'        => array( 'add_user' ),
			),
			'registered'   => array(
				'label'   => __( 'Registration date', 'newsletter-optin-box' ),
				'type'    => 'date',
				'example' => "format='datetime'",
			),
			'meta'         => $this->meta_key_tag_config(),
		);

		// Add known custom fields.
		foreach ( noptin_get_user_custom_fields() as $field_name => $field ) {
			$field['actions']                   = array( 'add_user' );
			$fields[ 'user_cf_' . $field_name ] = $field;
		}

		// Add provided fields.
		$fields = $this->add_provided( $fields );

		$fields = apply_filters( 'noptin_wp_user_fields', $fields, $this );

		if ( 'current_user' === $this->type ) {
			$fields['logged_in'] = array(
				'label'      => __( 'Log-in status', 'newsletter-optin-box' ),
				'type'       => 'string',
				'options'    => array(
					'yes' => __( 'Logged in', 'newsletter-optin-box' ),
					'no'  => __( 'Logged out', 'newsletter-optin-box' ),
				),
				'example'    => "format='label'",
				'deprecated' => 'user_logged_in',
			);
		}

		return $fields;
	}

	/**
	 * Adds the current user.
	 *
	 */
	public static function add_default() {
		Store::add( new Users( 'user', __( 'WordPress Users', 'newsletter-optin-box' ), __( 'WordPress User', 'newsletter-optin-box' ) ) );
		Store::add( new Users( 'current_user', __( 'Current User', 'newsletter-optin-box' ), __( 'Current User', 'newsletter-optin-box' ) ) );
	}

	/**
	 * Retrieves a test object args.
	 *
	 * @since 3.0.0
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule
	 * @throws \Exception
	 * @return array
	 */
	public function get_test_args( $rule ) {

		if ( get_current_user_id() > 0 ) {
			$user = get_user_by( 'id', get_current_user_id() );
		} else {
			$user = current(
				get_users(
					array(
						'number' => 1,
					)
				)
			);
		}

		if ( empty( $user ) ) {
			throw new \Exception( 'No user found.' );
		}

		$args = array(
			'object_id'  => $user->ID,
			'subject_id' => $user->ID,
		);

		if ( 'add_user_role' === $rule->get_trigger_id() ) {
			$args['extra_args'] = array(
				'user.added_role' => current( $user->roles ),
			);
		}

		if ( 'remove_user_role' === $rule->get_trigger_id() ) {
			$args['extra_args'] = array(
				'user.removed_role' => current( $user->roles ),
			);
		}

		if ( 'after_password_reset' === $rule->get_trigger_id() ) {
			$args['extra_args'] = array(
				'user.new_password' => '{{NEW_PASSWORD}}',
			);
		}

		return $args;
	}

	/**
	 * Retrieves a test ID.
	 *
	 */
	public function get_test_id() {
		return get_current_user_id();
	}
}
