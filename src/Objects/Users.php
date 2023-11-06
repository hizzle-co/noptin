<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for WordPress users.
 *
 * @since 2.2.0
 */
class Users extends People {

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
		$this->label		  = $label;
		$this->singular_label = $singular_label;
		$this->type			  = $type;
		$this->can_email      = false;

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
			'id'         => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'role'       => array(
				'label'   => __( 'Role', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => wp_roles()->get_names(),
			),
			'locale'     => array(
				'label'   => __( 'Locale', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => noptin_get_available_languages(),
				'default' => get_locale(),
			),
			'email'      => array(
				'label' => __( 'Email', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'name'       => array(
				'label' => __( 'Display name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'first_name' => array(
				'label' => __( 'First name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'last_name'  => array(
				'label' => __( 'Last name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'login'      => array(
				'label' => __( 'Username', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'url'        => array(
				'label' => __( 'URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'bio'        => array(
				'label'          => __( 'BIO', 'newsletter-optin-box' ),
				'type'           => 'string',
				'skip_smart_tag' => true,
			),
			'registered' => array(
				'label'   => __( 'Registration date', 'newsletter-optin-box' ),
				'type'    => 'date',
				'example' => "format='datetime'",
			),
			'meta'       => array(
				'label'          => __( 'Meta Value', 'newsletter-optin-box' ),
				'type'           => 'string',
				'example'        => 'key="my_key"',
				'skip_smart_tag' => true,
			),
		);

		// Add provided fields.
		foreach ( $this->get_related_collections() as $collection ) {

			/** @var People $collection */
			$provides = $collection->provides();

			if ( empty( $provides ) || 'wordpress' === $collection->integration ) { // phpcs:ignore
				continue;
			}

			$all_fields = $collection->get_all_fields();
			foreach ( $provides as $key ) {
				if ( isset( $all_fields[ $key ] ) ) {
					$all_fields[ $key ]['label'] = $collection->singular_label . ' >> ' . $all_fields[ $key ]['label'];

					$fields[ "{$collection->type}.{$key}" ] = $all_fields[ $key ];
				}
			}
		}

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
	 * Retrieves a test object ID.
	 *
	 * @since 2.2.0
	 * @param \Noptin_Automation_Rule $rule
	 * @return int
	 */
	public function get_test_object_id( $rule ) {

		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}

		// Get last user ID.
		return current(
			get_users(
				array(
					'number' => 1,
					'fields' => 'ID',
				)
			)
		);
	}
}