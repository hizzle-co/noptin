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
	 * Retrieves or creates a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return int|\WP_Error $user The user.
	 */
	public static function get_or_create_from_email( $email ) {
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			return $user->ID;
		}

		// Create the user.
		$user_info['user_email'] = $email;

		if ( empty( $user_info['user_pass'] ) ) {
			$user_info['user_pass'] = wp_generate_password();
		}

		$user_info['user_login'] = sanitize_user( self::generate_username( $user_info['user_email'], $user_info ) );

		return wp_insert_user( $user_info );
	}

	/**
	 * Create a unique username for a new user.
	 *
	 * @since 1.0.0
	 * @param string $email New user email address.
	 * @param array  $new_user_args Array of new user args, maybe including first and last names.
	 * @param string $suffix Append string to username to make it unique.
	 * @return string Generated username.
	 */
	private static function generate_username( $email, $new_user_args = array(), $suffix = '' ) {
		$username_parts = array();

		if ( ! empty( $new_user_args['first_name'] ) ) {
			$username_parts[] = sanitize_user( $new_user_args['first_name'], true );
		}

		if ( ! empty( $new_user_args['last_name'] ) ) {
			$username_parts[] = sanitize_user( $new_user_args['last_name'], true );
		}

		// Remove empty parts.
		$username_parts = array_filter( $username_parts );

		// If there are no parts, e.g. name had unicode chars, or was not provided, fallback to email.
		if ( empty( $username_parts ) ) {
			$email_parts    = explode( '@', $email );
			$email_username = $email_parts[0];

			// Exclude common prefixes.
			if ( in_array(
				$email_username,
				array(
					'sales',
					'hello',
					'mail',
					'contact',
					'info',
					'admin',
				),
				true
			) ) {
				// Get the domain part.
				$email_username = $email_parts[1];
			}

			$username_parts[] = sanitize_user( $email_username, true );
		}

		$username = strtolower( implode( '.', $username_parts ) );

		if ( $suffix ) {
			$username .= $suffix;
		}

		/**
		 * WordPress 4.4 - filters the list of blocked usernames.
		 *
		 * @since 3.7.0
		 * @param array $usernames Array of blocked usernames.
		 */
		$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

		// Stop illegal logins and generate a new random username.
		if ( in_array( strtolower( $username ), array_map( 'strtolower', $illegal_logins ), true ) ) {
			$new_args = array();

			$new_args['first_name'] = apply_filters(
				'noptin_generated_user_username',
				'noptin_user_' . zeroise( wp_rand( 0, 9999 ), 4 ),
				$email,
				$new_user_args,
				$suffix
			);

			return self::generate_username( $email, $new_args, $suffix );
		}

		if ( username_exists( $username ) ) {
			// Generate something unique to append to the username in case of a conflict with another user.
			$suffix = '-' . zeroise( wp_rand( 0, 9999 ), 4 );
			return self::generate_username( $email, $new_user_args, $suffix );
		}

		return apply_filters( 'noptin_generated_user_username', $username, $email, $new_user_args, $suffix );
	}

	/**
	 * Retrieves the manual recipients.
	 */
	public function get_manual_recipients() {
		return array(
			$this->field_to_merge_tag( 'email' ) => 'current_user' === $this->type ? __( 'Logged in user', 'newsletter-optin-box' ) : $this->singular_label,
		);
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
				'label' => __( 'Registration date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'meta'         => $this->meta_key_tag_config(),
		);

		if ( 'post_author' === $this->type ) {
			$fields['id']['deprecated']           = 'author_id';
			$fields['email']['deprecated']        = 'author_email';
			$fields['display_name']['deprecated'] = array( 'author_name', 'post_author' );
			$fields['first_name']['deprecated']   = 'author_first_name';
			$fields['last_name']['deprecated']    = 'author_last_name';
			$fields['login']['deprecated']        = 'author_login';
		}

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
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
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
