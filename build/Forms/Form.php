<?php
/**
 * Forms API: Form Class.
 *
 * @since   3.8.7
 * @package Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Form class.
 *
 * All properties are run through the noptin_form_{$property} filter hook
 *
 * @see   noptin_get_form
 * @since 3.8.7
 */
class Form {

	/**
	 * Form id
	 *
	 * @since 1.0.5
	 * @var int
	 */
	protected $id = null;

	/**
	 * Form information
	 *
	 * @since 1.0.5
	 * @var array
	 */
	protected $data = array();

	/**
	 * Class constructor. Loads form data.
	 *
	 * @param array|int|Form $form Form ID, array, or Form instance.
	 */
	public function __construct( $form = false ) {

		// If this is an instance of the class...
		if ( $form instanceof Form ) {
			$this->init( $form->get_all_data() );
			return;
		}

		// ... or an array of form properties.
		if ( is_array( $form ) ) {
			$this->init( $form );
			return;
		}

		// Try fetching the form by its post id.
		if ( ! empty( $form ) && is_numeric( $form ) ) {
			$form = absint( $form );

			if ( ! is_legacy_noptin_form( $form ) ) {
				$data = Compat::convert( $form );
			} else {
				$data = $this->get_data_by( 'id', $form );
			}

			if ( $data ) {
				$this->init( $data );
				return;
			}
		}

		// If we are here then the form does not exist.
		$this->init( $this->get_defaults() );
	}

	/**
	 * Sets up object properties
	 *
	 * @param array $data contains form details.
	 */
	public function init( $data ) {

		$data       = $this->sanitize_form_data( $data );
		$data       = $this->convert_classic_vars( $data );
		$this->data = apply_filters( 'noptin_get_form_data', $data, $this );
		$this->id   = $data['id'];
	}

	/**
	 * Fetch a form from the db/cache
	 *
	 * @param string     $field The field to query against: At the moment only ID is allowed.
	 * @param string|int $value The field value.
	 * @return array|false array of form details on success. False otherwise.
	 */
	public function get_data_by( $field, $value ) {

		// 'ID' is an alias of 'id'...
		if ( 'id' === strtolower( $field ) ) {

			// Make sure the value is numeric to avoid casting objects, for example, to int 1.
			if ( ! is_numeric( $value ) ) {
				return false;
			}

			// Ensure this is a valid form id.
			$value = intval( $value );
			if ( $value < 1 ) {
				return false;
			}
		} else {
			return false;
		}

		// Fetch the post object from the db.
		$post = get_post( $value );
		if ( ! $post || 'noptin-form' !== $post->post_type ) {
			return false;
		}

		// Init the form.
		$form = array(
			'optinName'   => $post->post_title,
			'optinStatus' => ( 'publish' === $post->post_status ),
			'id'          => $post->ID,
			'optinType'   => get_post_meta( $post->ID, '_noptin_optin_type', true ),
		);

		$state = get_post_meta( $post->ID, '_noptin_state', true );

		if ( is_object( $state ) ) {
			$state = (array) $state;
		}

		if ( ! is_array( $state ) ) {
			$state = array();
		}

		$form = array_merge( $state, $form );

		return $this->sanitize_form_data( $form );
	}

	/**
	 * Return default object properties
	 *
	 * @return array
	 */
	public function get_defaults() {

		$defaults = array_merge(
			include plugin_dir_path( __FILE__ ) . 'Admin/default-form.php',
			array(
				'optinName'            => '',
				'optinStatus'          => true,
				'id'                   => null,
				'hideSeconds'          => WEEK_IN_SECONDS,

				// Opt in options.
				'formRadius'           => '0px',

				// Form Design.
				'noptinFormBgImg'      => '',
				'noptinFormBgVideo'    => '',

				// Overlay.
				'noptinOverlayBgImg'   => '',
				'noptinOverlayBgVideo' => '',
				'noptinOverlayBg'      => 'rgba(96, 125, 139, 0.6)',
			)
		);

		// Add filters for all known taxonomies.
		foreach ( array_keys( noptin_get_post_types() ) as $post_type ) {
			$taxonomies = wp_list_pluck(
				wp_list_filter(
					get_object_taxonomies( $post_type, 'objects' ),
					array(
						'public' => true,
					)
				),
				'name'
			);

			foreach ( $taxonomies as $taxonomy ) {
				$defaults[ 'showTaxonomy_' . $taxonomy ] = '';
			}
		}

		// Loop through all custom fields.
		foreach ( get_noptin_multicheck_custom_fields() as $field ) {

			// Skip if no options.
			if ( empty( $field['options'] ) ) {
				continue;
			}

			$default_value                   = ! isset( $field['default_value'] ) ? array() : $field['default_value'];
			$defaults[ $field['merge_tag'] ] = noptin_parse_list( $default_value, true );
		}

		return apply_filters( 'noptin_optin_form_default_form_state', $defaults, $this );
	}

	/**
	 * Converts classic form variables.
	 */
	public function convert_classic_vars( $data ) {

		// Convert the borders.
		if ( empty( $data['formBorder'] ) || ! is_array( $data['formBorder'] ) ) {
			$data['formBorder'] = array(
				'style'         => 'solid',
				'border_radius' => intval( $data['formRadius'] ),
				'border_width'  => intval( $data['borderSize'] ),
				'border_color'  => $data['noptinFormBorderColor'],
			);

			extract( $data['formBorder'] ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			$data['formBorder']['generated'] = "border-style: solid; border-radius: {$border_radius}px; border-width: {$border_width}px; border-color: {$border_color};";
		}

		// Image position.
		if ( empty( $data['imageMainPos'] ) ) {
			$data['imageMainPos'] = 'right';
		}

		if ( empty( $data['imageMain'] ) ) {
			$data['imageMain'] = '';
		}

		if ( ! empty( $data['showPostTypes'] ) ) {
			$data['showPlaces']    = array_unique( array_merge( $data['showPlaces'], $data['showPostTypes'] ) );
			$data['showPostTypes'] = array();
		}

		return $data;
	}

	/**
	 * Sanitizes form data
	 *
	 * @since 1.0.5
	 * @access public
	 * @param  array $data the unsanitized data.
	 * @return array the sanitized data
	 */
	public function sanitize_form_data( $data ) {

		// Backwards compatibility.
		// Previously, Noptin supported lists as a standalone component.
		// We've since removed the component and now users can use custom fields to create lists.
		// This code converts the old data to the new data.
		$data     = wp_json_encode( $data );
		$data     = empty( $data ) ? '{}' : $data;
		$data     = str_replace( 'noptin_lists', 'lists', $data );
		$data     = json_decode( $data, true );
		$defaults = $this->get_defaults();

		// Arrays only please.
		if ( ! is_array( $data ) ) {
			return $defaults;
		}

		// Merge the defaults with the data.
		$data   = array_merge( $defaults, $data );
		$return = array();

		foreach ( $data as $key => $value ) {
			// Backwards compatibility.
			// Previously, Noptin supported tags as a standalone component.
			// We've since removed the component and now users can use custom fields to create tags.
			// This code converts the old data to the new data.
			if ( 'apTags' === $key ) {
				$key = 'tags';
			}

			// convert 'true' to a boolean true.
			if ( 'false' === $value ) {
				$return[ $key ] = false;
				continue;
			}

			// convert 'false' to a boolean false.
			if ( 'true' === $value ) {
				$return[ $key ] = true;
				continue;
			}

			if ( ! isset( $defaults[ $key ] ) || ! is_array( $defaults[ $key ] ) ) {
				$return[ $key ] = $value;
				continue;
			}

			// Ensure props that expect array always receive arrays.
			if ( is_scalar( $data[ $key ] ) ) {
				$return[ $key ] = noptin_parse_list( $data[ $key ] );
				continue;
			}

			if ( ! is_array( $data[ $key ] ) ) {
				$return[ $key ] = $defaults[ $key ];
				continue;
			}

			$return[ $key ] = $value;
		}

		if ( empty( $return['optinType'] ) ) {
			$return['optinType'] = 'inpost';
		}

		return $return;
	}

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param string $key The key to check for.
	 * @return bool Whether the given form field is set.
	 */
	public function __isset( $key ) {

		if ( 'id' === strtolower( $key ) ) {
			return null !== $this->id;
		}
		return isset( $this->data[ $key ] ) && null !== $this->data[ $key ];
	}

	/**
	 * Magic method for accessing custom fields.
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @param string $key form property to retrieve.
	 * @return mixed Value of the given form property
	 */
	public function __get( $key ) {

		if ( 'id' === strtolower( $key ) ) {
			return apply_filters( 'noptin_form_id', $this->id, $this );
		}

		if ( isset( $this->data[ $key ] ) ) {
			$value = $this->data[ $key ];
		} else {
			$value = null;
		}

		return apply_filters( "noptin_form_{$key}", $value, $this );
	}

	/**
	 * Magic method for setting custom form fields.
	 *
	 * This method does not update custom fields in the database. It only stores
	 * the value on the Noptin_Form instance.
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The new value for the key.
	 * @since 1.0.5
	 * @access public
	 */
	public function __set( $key, $value ) {

		if ( 'id' === strtolower( $key ) ) {
			$this->id         = $value;
			$this->data['id'] = $value;
			return;
		}

		$this->data[ $key ] = $value;
	}

	/**
	 * Saves the current form to the database
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function save( $status = false ) {

		if ( isset( $this->id ) ) {
			$id = $this->update( $status );
		} else {
			$id = $this->create( $status );
		}

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return true;
	}

	/**
	 * Creates a new form
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return mixed True on success. WP_Error on failure
	 */
	public function create( $status = false ) {

		// Prepare the args...
		$args = $this->get_post_array();
		unset( $args['ID'] );

		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}

		// ... then create the form.
		$id = wp_insert_post( $args, true );

		// If an error occured, return it.
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		// Set the new id.
		$this->id         = $id;
		$this->data['id'] = $id;

		$state = $this->data;
		unset( $state['optinType'] );
		unset( $state['id'] );
		update_post_meta( $id, '_noptin_state', $this->data );
		update_post_meta( $id, '_noptin_optin_type', $this->optinType );
		return true;
	}

	/**
	 * Updates the form in the db
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return mixed True on success. WP_Error on failure
	 */
	private function update( $status = false ) {

		// Prepare the args...
		$args = $this->get_post_array();

		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}

		// ... then update the form.
		$id = wp_update_post( $args, true );

		// If an error occured, return it.
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$state = $this->data;
		unset( $state['optinType'] );
		unset( $state['id'] );
		update_post_meta( $id, '_noptin_state', $this->data );
		update_post_meta( $id, '_noptin_optin_type', $this->optinType );
		return true;
	}

	/**
	 * Returns post creation/update args
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return mixed
	 */
	private function get_post_array() {
		$data = array(
			'post_title'   => empty( $this->optinName ) ? '' : $this->optinName,
			'ID'           => $this->id,
			'post_content' => 'Noptin Form',
			'post_status'  => empty( $this->optinStatus ) ? 'draft' : 'publish',
			'post_type'    => 'noptin-form',
		);

		foreach ( $data as $key => $val ) {
			if ( empty( $val ) ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * Duplicates the form
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return mixed
	 */
	public function duplicate( $append = '(duplicate)' ) {
		$this->optinName = trim( $this->optinName . ' ' . $append );
		$this->id        = null;
		return $this->save( 'draft' );
	}

	/**
	 * Determine whether the form exists in the database.
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return bool True if form exists in the database, false if not.
	 */
	public function exists() {
		return null !== $this->id;
	}

	/**
	 * Determines whether this form has been published
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return bool True if form is published, false if not.
	 */
	public function is_published() {
		return $this->optinStatus;
	}

	/**
	 * Checks whether this is a real form and is saved to the database
	 *
	 * @return bool
	 */
	public function is_form() {
		$is_form = ( $this->exists() && get_post_type( $this->id ) === 'noptin-form' );
		return apply_filters( 'noptin_is_form', $is_form, $this );
	}

	/**
	 * Checks whether this form can be displayed on the current page
	 *
	 * @return bool
	 */
	public function can_show() {
		return apply_filters( 'noptin_can_show_form', $this->check_can_show(), $this );
	}

	/**
	 * Contains the logic for Noptin_Form::can_show()
	 *
	 * @internal
	 * @return bool
	 */
	protected function check_can_show() {

		// Abort early if the form does not exist.
		if ( ! $this->exists() ) {
			return false;
		}

		// Always show if we're previewing the form.
		if ( defined( 'IS_NOPTIN_PREVIEW' ) && $this->id === IS_NOPTIN_PREVIEW ) {
			return true;
		}

		// Abort if the form is not published, unless we're on a preview.
		if ( ! noptin_is_preview() && ! $this->is_published() ) {
			return false;
		}

		// Always display click triggered popups.
		if ( 'popup' === $this->optinType && 'after_click' === $this->triggerPopup ) {
			return true;
		}

		// ... or the user wants to hide all forms.
		if ( ! noptin_should_show_optins() ) {
			return false;
		}

		// Maybe hide on mobile.
		if ( $this->hideSmallScreens && wp_is_mobile() ) {
			return false;
		}

		// Maybe hide on desktops.
		if ( $this->hideLargeScreens && ! wp_is_mobile() ) {
			return false;
		}

		// User roles.
		if ( 'users' === $this->whoCanSee && ! is_user_logged_in() ) {
			return false;
		}

		if ( 'guests' === $this->whoCanSee && is_user_logged_in() ) {
			return false;
		}

		if ( 'roles' === $this->whoCanSee ) {
			$role = $this->get_user_role();

			if ( empty( $role ) || ! is_array( $this->userRoles ) || ! in_array( $role, $this->userRoles, true ) ) {
				return false;
			}
		}

		// Has the user restricted this to a few posts?
		if ( ! empty( $this->onlyShowOn ) ) {
			return self::page_is( $this->onlyShowOn );
		}

		// or maybe forbidden it on this post?
		if ( ! empty( $this->neverShowOn ) && self::page_is( $this->neverShowOn ) ) {
			return false;
		}

		// Is this form set to be shown everywhere?
		if ( $this->showEverywhere ) {
			return true;
		}

		// Check taxonomy restrictions.
		foreach ( array_keys( noptin_get_post_types() ) as $post_type ) {
			$taxonomies = wp_list_pluck(
				wp_list_filter(
					get_object_taxonomies( $post_type, 'objects' ),
					array(
						'public' => true,
					)
				),
				'label',
				'name'
			);

			foreach ( $taxonomies as $taxonomy => $taxonomy_label ) {
				if ( ! empty( $this->data[ 'showTaxonomy_' . $taxonomy ] ) ) {
					$terms = noptin_parse_list( $this->data[ 'showTaxonomy_' . $taxonomy ], true );

					return is_tax( $taxonomy, $terms ) || ( is_singular( $post_type ) && has_term( $terms, $taxonomy ) );
				}
			}
		}

		$places = is_array( $this->showPlaces ) ? $this->showPlaces : array();

		// frontpage.
		if ( is_front_page() ) {
			return ( ! empty( $this->showHome ) || in_array( 'showHome', $places, true ) );
		}

		// blog page.
		if ( is_home() ) {
			return ( ! empty( $this->showBlog ) || in_array( 'showBlog', $places, true ) );
		}

		// search.
		if ( is_search() ) {
			return ( ! empty( $this->showSearch ) || in_array( 'showSearch', $places, true ) );
		}

		// other archive pages.
		if ( is_archive() ) {
			return ( ! empty( $this->showArchives ) || in_array( 'showArchives', $places, true ) );
		}

		// Single posts.
		return is_singular( $places );
	}

	/**
	 * Checks if the current page matches an array.
	 */
	public static function page_is( $check ) {
		if ( empty( $check ) ) {
			return false;
		}

		$check = noptin_parse_list( $check, true );

		// frontpage.
		if ( is_front_page() ) {
			return in_array( 'frontpage', $check, true ) || in_array( 'showHome', $check, true );
		}

		// blog page.
		if ( is_home() ) {
			return in_array( 'blogpage', $check, true ) || in_array( 'showBlog', $check, true );
		}

		// search.
		if ( is_search() ) {
			return in_array( 'searchpage', $check, true ) || in_array( 'showSearch', $check, true );
		}

		// other archive pages.
		if ( is_archive() ) {
			return in_array( 'archives', $check, true ) || in_array( 'showArchives', $check, true );
		}

		// Post types.
		$post_types = array_keys( noptin_get_post_types() );
		$selected   = array_intersect( $post_types, $check );
		if ( ! empty( $selected ) && is_singular( $selected ) ) {
			return true;
		}

		// URLs and Post IDs.
		$remaining = array_intersect( $check, $post_types );
		return ! empty( $remaining ) && noptin_is_singular( $remaining );
	}

	/**
	 * Returns the current user's role.
	 *
	 * @return string
	 */
	public function get_user_role() {
		$user = wp_get_current_user();
		return empty( $user ) ? '' : current( $user->roles );
	}

	/**
	 * Returns the html required to display the form
	 *
	 * @return string html
	 */
	public function get_html() {
		return show_noptin_form( $this->id, false );
	}

	/**
	 * Displays the form.
	 *
	 */
	public function display() {
		return show_noptin_form( $this->id, true );
	}

	/**
	 * Returns all form data
	 *
	 * @return array an array of form data
	 */
	public function get_all_data() {
		return $this->data;
	}

	/**
	 * Checks if we can embed this form to the current post/page.
	 */
	public function can_embed() {
		return apply_filters( 'noptin_can_embed_form', 'inpost' === $this->optinType && $this->can_show(), $this );
	}

	/**
	 * Check if this is a slide in form.
	 */
	public function is_slide_in() {
		return 'slide_in' === $this->optinType;
	}

	/**
	 * Check if this is a popup form.
	 */
	public function is_popup() {
		return 'popup' === $this->optinType;
	}
}
