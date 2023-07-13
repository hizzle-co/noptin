<?php
/**
 * Forms API: Form Class.
 *
 * Represents a single opt-in form.
 *
 * @since   1.0.5
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Represents a single opt-in form.
 *
 * @see noptin_get_optin_form()
 * @version  1.0.5
 */
class Noptin_Form {

	/**
	 * Form id
	 *
	 * @since 1.0.5
	 * @var int
	 */
	public $id = null;

	/**
	 * Form title
	 *
	 * @since 1.0.5
	 * @var string
	 */
	public $title = 'untitled';

	/**
	 * Form status
	 *
	 * @since 1.0.5
	 * @var string
	 */
	public $status = 'publish';

	/**
	 * Form settings.
	 *
	 * @since 1.6.2
	 * @var array
	 */
	public $settings = array();

	/**
	 * Message settings.
	 *
	 * @since 1.6.2
	 * @var array
	 */
	public $messages = array();

	/**
	 * Email settings.
	 *
	 * @since 1.6.2
	 * @var array
	 */
	public $email = array();

	/**
	 * Class constructor. Loads form data.
	 *
	 * @param mixed $form Form ID, array, or Noptin_Form instance.
	 */
	public function __construct( $form = false ) {

		// If this is an instance of the class...
		if ( $form instanceof Noptin_Form ) {
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

			$data = $this->get_data_by_id( absint( $form ) );
			if ( $data ) {
				$this->init( $data );
				return;
			}
		}

	}

	/**
	 * Sets up object properties
	 *
	 * @param array $data contains form details.
	 */
	public function init( $data ) {

		foreach ( $this->get_form_properties() as $prop ) {

			if ( isset( $data[ $prop ] ) ) {
				$this->$prop = wp_kses_post_deep( $data[ $prop ] );
			}
		}

		if ( isset( $this->settings['noptin_tags'] ) ) {
			$this->settings['tags'] = implode( ', ', array_filter( noptin_parse_list( $this->settings['noptin_tags'], true ) ) );
			unset( $this->settings['noptin_tags'] );
		}

		if ( isset( $this->settings['noptin_lists'] ) ) {
			$this->settings['lists'] = array_filter( noptin_parse_list( $this->settings['noptin_lists'], true ) );
			unset( $this->settings['noptin_lists'] );
		}

		$settings = wp_json_encode( $this->settings );

		if ( $settings ) {
			$settings       = str_replace( 'noptin_lists', 'lists', $settings );
			$this->settings = json_decode( $settings, true );
		}
	}

	/**
	 * Fetch a form from the db/cache
	 *
	 * @param int $value The form id.
	 * @return array|false array of form details on success. False otherwise.
	 */
	protected function get_data_by_id( $value ) {

		// Ensure the post object exists in the db.
		$post = get_post( $value );
		if ( ! $post || 'noptin-form' !== $post->post_type ) {
			return false;
		}

		// Prepare form data.
		$data = array(
			'id'     => $post->ID,
			'title'  => $post->post_title,
			'status' => $post->post_status,
		);

		// Add meta properties.
		foreach ( $this->get_form_properties() as $prop ) {

			if ( ! in_array( $prop, array( 'id', 'title', 'status' ), true ) ) {
				$value = get_post_meta( $post->ID, "form_$prop", true );

				if ( '' !== $value ) {
					$data[ $prop ] = $value;
				}
			}
		}

		return $data;
	}

	/**
	 * Magic method for accessing form properties.
	 *
	 * @since 1.0.5
	 *
	 * @param string $key form property to retrieve.
	 * @return mixed Value of the given form property
	 */
	public function __get( $key ) {

		if ( 'id' === strtolower( $key ) ) {
			return apply_filters( 'noptin_form_id', $this->id, $this );
		}

		if ( isset( $this->$key ) || ! empty( $this->$key ) ) {
			$value = $this->$key;
		} else {
			$value = null;
		}

		return apply_filters( "noptin_form_{$key}", $value, $this );
	}

	/**
	 * Magic method for setting form properties.
	 *
	 * This method does not update property in the database. It only stores
	 * the value on the Noptin_Form instance.
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The new value for the key.
	 * @since 1.0.5
	 * @access public
	 */
	public function __set( $key, $value ) {

		if ( 'id' === strtolower( $key ) ) {
			$this->id = (int) $value;
			return;
		}

		$this->$key = $value;

	}

	/**
	 * Saves the current form to the database
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function save( $status = false ) {

		// Create or update.
		if ( $this->exists() ) {
			$id = $this->update( $status );
		} else {
			$id = $this->create( $status );
		}

		// Did an error occur?
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$this->id = $id;

		// Save meta properties.
		foreach ( $this->get_form_properties() as $prop ) {

			if ( ! in_array( $prop, array( 'id', 'title', 'status' ), true ) ) {

				if ( isset( $this->$prop ) || ! empty( $this->$prop ) ) {
					update_post_meta( $id, "form_$prop", $this->$prop );
				} else {
					delete_post_meta( $id, "form_$prop" );
				}
			}
		}

		delete_transient( 'noptin_forms_to_append' );

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
		return wp_insert_post( $args, true );
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
		return wp_update_post( $args, true );
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
			'post_title'   => $this->title,
			'ID'           => $this->id,
			'post_content' => $this->title . current_time( 'mysql' ),
			'post_status'  => $this->status,
			'post_type'    => 'noptin-form',
		);

		return array_filter( $data );
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
		$this->title = trim( $this->title . ' ' . $append );
		$this->id    = null;
		return $this->save();
	}

	/**
	 * Determine whether or not the form exists in the database.
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
	 * Determines whether or not this form has been published
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return bool True if form is published, false if not.
	 */
	public function is_published() {
		return 'publish' === $this->status;
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
		return apply_filters( 'noptin_can_show_form', $this->_can_show(), $this );
	}

	/**
	 * Contains the logic for Noptin_Form::can_show()
	 *
	 * @internal
	 * @return bool
	 */
	protected function _can_show() {

		// Abort early if the form does not exist.
		if ( ! $this->exists() ) {
			return false;
		}

		// Show on preview pages.
		if ( noptin_is_preview() ) {
			return true;
		}

		// Abort if not published or the user wants to hide all forms.
		if ( ! $this->is_published() || ! noptin_should_show_optins() ) {
			return false;
		}

		// Has the user restricted this to a few posts?
		if ( ! empty( $this->settings['only_show'] ) ) {
			return noptin_is_singular( $this->settings['only_show'] );
		}

		if ( empty( $this->settings['hide'] ) ) {
			return true;
		}
		$hide = $this->settings['hide'];

		// frontpage.
		if ( is_front_page() ) {
			return ! in_array( 'frontpage', $hide, true );
		}

		// blog page.
		if ( is_home() ) {
			return ! in_array( 'blogpage', $hide, true );
		}

		// search.
		if ( is_search() ) {
			return ! in_array( 'searchpage', $hide, true );
		}

		// other archive pages.
		if ( is_archive() ) {
			return ! in_array( 'archives', $hide, true );
		}

		// Single posts.
		return ! is_singular( $hide );

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

		$data = array();

		foreach ( $this->get_form_properties() as $prop ) {

			if ( isset( $this->$prop ) || ! empty( $this->$prop ) ) {
				$data[ $prop ] = $this->$prop;
			}
		}

		return $data;

	}

	/**
	 * Returns an array of known form properties.
	 *
	 * @return array an array of known form properties.
	 */
	public function get_form_properties() {
		$properties = array( 'id', 'settings', 'messages', 'email', 'title', 'status' );

		/**
		 * Filters the list of known form properties.
		 *
		 * @since 1.6.2
		 *
		 * @param array $properties an array of known form properties.
		 * @param Noptin_Form $form Current form object.
		 */
		return apply_filters( 'known_noptin_form_properties', $properties, $this );

	}

}
