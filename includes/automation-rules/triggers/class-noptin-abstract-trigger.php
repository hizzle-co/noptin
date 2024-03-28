<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base triggers class.
 *
 * @since 1.2.8
 */
abstract class Noptin_Abstract_Trigger extends Noptin_Abstract_Trigger_Action {

	/**
	 * Whether or not this trigger deals with a user.
	 *
	 * @var bool
	 */
	public $is_user_based = false;

	/**
	 * Custom mail configuration.
	 *
	 * @var array
	 */
	public $mail_config = array();

	/**
	 * Prepares email test data.
	 *
	 * @since 1.11.0
	 * @param Noptin_Automation_Rule $rule
	 * @return Noptin_Automation_Rules_Smart_Tags
	 * @throws Exception
	 */
	public function get_test_smart_tags( $rule ) {
		throw new Exception( 'No test data available for the trigger ' . esc_html( $this->get_name() ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$action            = $rule->get_action();
		$conditional_logic = noptin_prepare_conditional_logic_for_display( $rule->get_conditional_logic(), $this->get_known_smart_tags() );

		if ( empty( $conditional_logic ) || empty( $action ) ) {
			return '';
		}

		if ( 'allow' === $conditional_logic['action'] ) {
			return sprintf( $action->run_if(), $conditional_logic['rules'] );
		}

		return sprintf( $action->skip_if(), $conditional_logic['rules'] );
	}

	/**
	 * Groups rule triggers into a single string.
	 *
	 * @since 1.11.9
	 * @param array $meta
	 * @param Noptin_Automation_Rule $rule
	 * @return string
	 */
	public function rule_trigger_meta( $meta, $rule ) {

		$meta = apply_filters( 'noptin_rule_trigger_meta_' . $this->get_id(), $meta, $rule, $this );
		$meta = apply_filters( 'noptin_rule_trigger_meta', $meta, $rule, $this );

		return $this->prepare_rule_meta( $meta, $rule );
	}

	/**
	 * Returns an array of known smart tags with callbacks removed.
	 *
	 * @since 1.12.0
	 * @return array
	 */
	public function get_known_smart_tags_for_js() {
		return noptin_prepare_merge_tags_for_js( $this->get_known_smart_tags() );
	}

	/**
	 * Returns an array of known smart tags.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	public function get_known_smart_tags() {
		/** @var \WP_Locale $wp_locale */
		global $wp_locale;

		$smart_tags = array(

			'cookie'  => array(
				'description' => __( 'Data from a cookie.', 'newsletter-optin-box' ),
				'callback'    => 'Noptin_Dynamic_Content_Tags::get_cookie',
				'example'     => "cookie name='my_cookie' default='Default Value'",
			),

			'date'    => array(
				'description'       => __( 'The current date', 'newsletter-optin-box' ),
				'callback'          => 'Noptin_Dynamic_Content_Tags::get_date',
				'example'           => 'date format="j, F Y" localized=1',
				'conditional_logic' => 'date',
				'placeholder'       => current_time( 'Y-m-d' ),
			),

			'year'    => array(
				'description'       => __( 'The current year', 'newsletter-optin-box' ),
				'replacement'       => current_time( 'Y' ),
				'example'           => 'year',
				'conditional_logic' => 'number',
				'placeholder'       => current_time( 'Y' ),
			),

			'month'   => array(
				'description'       => __( 'The current month', 'newsletter-optin-box' ),
				'replacement'       => current_time( 'm' ),
				'example'           => 'month',
				'conditional_logic' => 'number',
				'placeholder'       => current_time( 'm' ),
				'options'           => $wp_locale->month,
			),

			'day'     => array(
				'description'       => __( 'The day of the month', 'newsletter-optin-box' ),
				'replacement'       => current_time( 'j' ),
				'example'           => 'day',
				'conditional_logic' => 'number',
				'placeholder'       => current_time( 'j' ),
				'options'           => array_combine( range( 1, 31 ), range( 1, 31 ) ),
			),

			'weekday' => array(
				'description'       => __( 'The day of the week', 'newsletter-optin-box' ),
				'replacement'       => (int) current_time( 'w' ),
				'placeholder'       => (int) current_time( 'w' ),
				'example'           => 'weekday',
				'conditional_logic' => 'number',
				'options'           => $wp_locale->weekday,
			),

			'time'    => array(
				'description' => __( 'The current time', 'newsletter-optin-box' ),
				'callback'    => 'Noptin_Dynamic_Content_Tags::get_time',
				'example'     => 'time format="g:i a" localized=1',
			),

		);

		if ( ! $this->is_user_based ) {
			$smart_tags['user_logged_in'] = array(
				'description'       => __( 'Log-in status', 'newsletter-optin-box' ),
				'example'           => 'user_logged_in',
				'conditional_logic' => 'string',
				'callback'          => 'noptin_get_user_logged_in_status',
				'options'           => array(
					'yes' => __( 'Logged in', 'newsletter-optin-box' ),
					'no'  => __( 'Logged out', 'newsletter-optin-box' ),
				),
				'group'             => __( 'User', 'newsletter-optin-box' ),
			);
		} else {
			$smart_tags = array_replace(
				$smart_tags,
				array(

					'user_id'     => array(
						'description'       => __( 'User ID', 'newsletter-optin-box' ),
						'conditional_logic' => 'number',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'user_role'   => array(
						'description'       => __( 'User Role', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'options'           => wp_roles()->get_names(),
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'user_locale' => array(
						'description'       => __( 'User Locale', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'example'           => 'user_locale default="en_US"',
						'options'           => noptin_get_available_languages(),
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'email'       => array(
						'description'       => __( 'Email Address', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'name'        => array(
						'description'       => __( 'Display Name', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'first_name'  => array(
						'description'       => __( 'First Name', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'last_name'   => array(
						'description'       => __( 'Last Name', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'user_login'  => array(
						'description'       => __( 'Login Name', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'user_url'    => array(
						'description'       => __( 'User URL', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),

					'user_bio'    => array(
						'description'       => __( 'User Bio', 'newsletter-optin-box' ),
						'conditional_logic' => 'string',
						'group'             => __( 'User', 'newsletter-optin-box' ),
					),
				)
			);
		}

		return apply_filters( 'noptin_automation_trigger_known_smart_tags', $smart_tags, $this );
	}

	/**
	 * Prepare smart tags.
	 *
	 * @param Noptin_Subscriber|\Hizzle\Noptin\DB\Subscriber|WP_User|WC_Customer $subject
	 * @since 1.9.0
	 * @return array
	 */
	public function prepare_known_smart_tags( $subject ) {

		// Subscribers.
		if ( $subject instanceof Noptin_Subscriber ) {
			$subject = noptin_get_subscriber( $subject->id );
		}

		if ( $subject instanceof \Hizzle\Noptin\DB\Subscriber ) {
			return $subject->get_data();
		}

		return array();
	}

	/**
	 * Returns an array of post smart tags.
	 *
	 * @since 1.11.0
	 * @return array
	 */
	public function get_post_smart_tags() {

		return array(
			'post_id'            => array(
				'description'       => __( 'The post ID', 'newsletter-optin-box' ),
				'example'           => 'post_id',
				'conditional_logic' => 'number',
			),
			'post_author_id'     => array(
				'description'       => __( 'The post author ID', 'newsletter-optin-box' ),
				'example'           => 'post_author_id',
				'conditional_logic' => 'number',
			),
			'post_author_name'   => array(
				'description'       => __( 'The post author name', 'newsletter-optin-box' ),
				'example'           => 'post_author_name',
				'conditional_logic' => 'string',
			),
			'post_author_email'  => array(
				'description'       => __( 'The post author email', 'newsletter-optin-box' ),
				'example'           => 'post_author_email',
				'conditional_logic' => 'string',
			),
			'post_date'          => array(
				'description'       => __( 'The post date', 'newsletter-optin-box' ),
				'example'           => 'post_date',
				'conditional_logic' => 'date',
			),
			'post_title'         => array(
				'description'       => __( 'The post title', 'newsletter-optin-box' ),
				'example'           => 'post_title',
				'conditional_logic' => 'string',
			),
			'post_url'           => array(
				'description'       => __( 'The post URL', 'newsletter-optin-box' ),
				'example'           => 'post_url',
				'conditional_logic' => 'string',
			),
			'post_excerpt'       => array(
				'description'       => __( 'The post excerpt', 'newsletter-optin-box' ),
				'example'           => 'post_excerpt',
				'conditional_logic' => 'string',
			),
			'post_content'       => array(
				'description'       => __( 'The post content', 'newsletter-optin-box' ),
				'example'           => 'post_content',
				'conditional_logic' => 'string',
			),
			'post_status'        => array(
				'description'       => __( 'The post status', 'newsletter-optin-box' ),
				'example'           => 'post_status',
				'conditional_logic' => 'string',
				'options'           => get_post_stati(),
			),
			'post_password'      => array(
				'description'       => __( 'The post password', 'newsletter-optin-box' ),
				'example'           => 'post_password',
				'conditional_logic' => 'string',
			),
			'post_name'          => array(
				'description'       => __( 'The post slug', 'newsletter-optin-box' ),
				'example'           => 'post_name',
				'conditional_logic' => 'string',
			),
			'post_modified'      => array(
				'description'       => __( 'The post modified date', 'newsletter-optin-box' ),
				'example'           => 'post_modified',
				'conditional_logic' => 'date',
			),
			'post_type'          => array(
				'description'       => __( 'The post type', 'newsletter-optin-box' ),
				'example'           => 'post_type',
				'conditional_logic' => 'string',
				'options'           => get_post_types(),
			),
			'post_comment_count' => array(
				'description'       => __( 'The post comment count', 'newsletter-optin-box' ),
				'example'           => 'post_comment_count',
				'conditional_logic' => 'number',
			),
		);
	}

	/**
	 * Prepare post smart tags.
	 *
	 * @param WP_Post $post The post object.
	 * @since 1.11.1
	 * @return array
	 */
	public function prepare_post_smart_tags( $post ) {
		$post_author = get_user_by( 'id', $post->post_author );

		return array(
			'post_id'            => $post->ID,
			'post_author_id'     => $post->post_author,
			'post_author_name'   => empty( $post_author->display_name ) ? '' : $post_author->display_name,
			'post_author_email'  => empty( $post_author->user_email ) ? '' : $post_author->user_email,
			'post_date'          => $post->post_date,
			'post_title'         => $post->post_title,
			'post_url'           => get_permalink( $post->ID ),
			'post_excerpt'       => get_the_excerpt( $post ),
			'post_content'       => $post->post_content,
			'post_status'        => $post->post_status,
			'post_password'      => $post->post_password,
			'post_name'          => $post->post_name,
			'post_modified'      => $post->post_modified,
			'post_type'          => $post->post_type,
			'post_comment_count' => $post->comment_count,
		);
	}

	/**
	 * Checks if conditional logic if met.
	 *
	 * @since 1.2.8
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule The rule to check for.
	 * @param mixed $args Extra args for the action.
	 * @param mixed $subject The subject.
	 * @param Noptin_Abstract_Action $action The action to run.
	 * @return bool
	 */
	public function is_rule_valid_for_args( $rule, $args, $subject, $action ) {

		// Set the current email.
		$GLOBALS['current_noptin_email'] = $this->get_subject_email( $subject, $this, $args );

		$conditional_logic = $rule->get_conditional_logic();
		// Abort if conditional logic is not set.
		if ( empty( $conditional_logic['enabled'] ) || empty( $args['smart_tags'] ) || empty( $conditional_logic['rules'] ) || ! is_array( $conditional_logic['rules'] ) ) {
			return true;
		}

		// Retrieve the conditional logic.
		$action      = $conditional_logic['action']; // allow or prevent.
		$type        = $conditional_logic['type']; // all or any.
		$rules_met   = 0;
		$rules_total = count( $conditional_logic['rules'] );

		/** @var Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		// Loop through each rule.
		foreach ( $conditional_logic['rules'] as $rule ) {

			$current_value = $smart_tags->replace_in_text_field( '[[' . $rule['type'] . ']]' );
			$compare_value = noptin_clean( $rule['value'] );
			$comparison    = $rule['condition'];

			// If the rule is met.
			if ( ! $smart_tags->get( $rule['type'] ) || noptin_is_conditional_logic_met( $current_value, $compare_value, $comparison ) ) {

				// Increment the number of rules met.
				++ $rules_met;

				// If we're using the "any" condition, we can stop here.
				if ( 'any' === $type ) {
					break;
				}
			} elseif ( 'all' === $type ) {

				// If we're using the "all" condition, we can stop here.
				break;
			}
		}

		// Check if the conditions are met.
		if ( 'all' === $type ) {
			$is_condition_met = $rules_met === $rules_total;
		} else {
			$is_condition_met = $rules_met > 0;
		}

		// Return the result.
		return 'allow' === $action ? $is_condition_met : ! $is_condition_met;
	}

	/**
	 * Prepares user args for user based triggers.
	 *
	 * @since 1.10.0
	 * @param WP_User $user The user.
	 * @return array
	 */
	public function prepare_user_args( $user ) {

		// Abort if not a user object.
		if ( ! $user instanceof WP_User ) {
			return array();
		}

		$args = array(
			'user_id'     => $user->ID,
			'email'       => $user->user_email,
			'name'        => $user->display_name,
			'first_name'  => $user->first_name,
			'last_name'   => $user->last_name,
			'user_bio'    => $user->description,
			'user_url'    => $user->user_url,
			'user_login'  => $user->user_login,
			'user_role'   => current( $user->roles ),
			'user_locale' => get_user_locale( $user ),
		);

		// Add meta data.
		$meta_data = get_user_meta( $user->ID );
		foreach ( $meta_data as $key => $value ) {
			if ( ! isset( $args[ $key ] ) ) {
				$args[ 'user_cf_' . $key ] = $value[0];
			}
		}

		return $args;
	}

	/**
	 * Prepares trigger args.
	 *
	 * @since 1.11.0
	 * @param mixed $subject The subject.
	 * @param array $args Extra arguments passed to the action.
	 * @return array
	 */
	public function prepare_trigger_args( $subject, $args ) {

		if ( ! is_array( $args ) ) {
			$args = array();
		}

		if ( isset( $args['subject'] ) ) {
			$args['subject_orig'] = $args['subject'];
		}

		// Add user args.
		if ( $subject instanceof WP_User ) {
			$args = array_replace( $args, $this->prepare_user_args( $subject ) );
		}

		if ( is_string( $subject ) && is_email( $subject ) && empty( $args['email'] ) ) {
			$args['email'] = $subject;
		}

		$args['subject']    = $subject;
		$args               = apply_filters( 'noptin_automation_trigger_args', $args, $this );
		$args['smart_tags'] = new Noptin_Automation_Rules_Smart_Tags( $this, $subject, $args );

		return $args;
	}

	/**
	 * Triggers action callbacks.
	 *
	 * @since 1.2.8
	 * @param mixed $subject The subject.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function trigger( $subject, $args ) {

		$args = $this->prepare_trigger_args( $subject, $args );

		$GLOBALS['noptin_current_trigger_args'] = $args;

		if ( isset( $args['rule_id'] ) ) {
			$rules = array( noptin_get_automation_rules( $args['rule_id'] ) );
		} else {
			$rules = $this->get_rules();
		}

		foreach ( $rules as $rule ) {

			// Abort if the rule is not found.
			if ( is_wp_error( $rule ) || ! $rule->exists() || ! $rule->get_status() ) {
				continue;
			}

			// Retrieve the action.
			$action = $rule->get_action();
			if ( ! empty( $action ) ) {
				$rule->maybe_run( $subject, $this, $action, $args );
			}
		}
	}

	/**
	 * Serializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		unset( $args['smart_tags'] );

		// In case the subject is a subscriber, we need to store the email address.
		if ( $args['subject'] instanceof Noptin_Subscriber ) {
			$args['noptin_subject_subscriber'] = $args['subject']->id;
			unset( $args['subject'] );
		} elseif ( $args['subject'] instanceof \Hizzle\Noptin\DB\Subscriber ) {
			$args['noptin_subject_subscriber'] = $args['subject']->get_id();
			unset( $args['subject'] );
		} elseif ( $args['subject'] instanceof WP_User ) { // In case the subject is a user, we need to store the user id.
			$args['noptin_subject_user'] = $args['subject']->ID;
			unset( $args['subject'] );
		}

		return $args;
	}

	/**
	 * Unserializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {
		$subject = isset( $args['subject'] ) ? $args['subject'] : false;

		// Maybe fetch the subscriber.
		if ( empty( $subject ) && ! empty( $args['noptin_subject_subscriber'] ) ) {
			$subject = noptin_get_subscriber( $args['noptin_subject_subscriber'] );

			if ( $subject->exists() ) {
				$args['subject'] = $subject;
			} else {
				throw new Exception( 'Subscriber not found' );
			}
		}

		// Maybe fetch the user.
		if ( empty( $subject ) && ! empty( $args['noptin_subject_user'] ) ) {
			$subject = get_user_by( 'id', $args['noptin_subject_user'] );

			if ( $subject instanceof WP_User ) {
				$args['subject'] = $subject;
			} else {
				throw new Exception( 'User not found' );
			}
		}

		return $this->prepare_trigger_args( $subject, $args );
	}
}
