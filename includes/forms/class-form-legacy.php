<?php

defined( 'ABSPATH' ) || exit;

/**
 * Optin Class.
 *
 * All properties are run through the noptin_form_{$property} filter hook
 *
 * @see noptin_get_optin_form
 *
 * @class    Noptin_Form
 * @version  1.0.5
 */
class Noptin_Form_Legacy {

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
			$form = absint( $form );

			$data = $this->get_data_by( 'id', $form );
			if ( $data ) {
				$this->init( $data );
				return;
			}
		}

		// If we are here then the form does not.
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
			'optinStatus' => ( 'draft' !== $post->post_status ),
			'id'          => $post->ID,
			'optinHTML'   => $post->post_content,
			'optinType'   => get_post_meta( $post->ID, '_noptin_optin_type', true ),
		);

		$state = get_post_meta( $post->ID, '_noptin_state', true );
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

		$defaults = array(
			'optinName'             => '',
			'optinStatus'           => true,
			'id'                    => null,
			'optinHTML'             => __( 'This form is incorrectly configured', 'newsletter-optin-box' ),
			'optinType'             => 'inpost',

			// Opt in options.
			'formRadius'            => '0px',

			'singleLine'            => false,
			'gdprCheckbox'          => false,
			'gdprConsentText'       => __( 'I consent to receive promotional emails about your products and services.', 'newsletter-optin-box' ),
			'fields'                => array(
				array(
					'type'    => array(
						'label' => __( 'Email Address', 'newsletter-optin-box' ),
						'name'  => 'email',
						'type'  => 'email',
					),
					'require' => 'true',
					'key'     => 'noptin_email_key',
				),
			),
			'hideFields'            => false,
			'inject'                => '0',
			'buttonPosition'        => 'block',
			'subscribeAction'       => 'message', // redirect.
			'successMessage'        => get_noptin_option( 'success_message' ),
			'redirectUrl'           => '',

			// Form Design.
			'noptinFormBgImg'       => '',
			'noptinFormBgVideo'     => '',
			'noptinFormBg'          => '#eeeeee',
			'noptinFormBorderColor' => '#eeeeee',
			'borderSize'            => '4px',
			'formWidth'             => '620px',
			'formHeight'            => '280px',

			// Overlay.
			'noptinOverlayBgImg'    => '',
			'noptinOverlayBgVideo'  => '',
			'noptinOverlayBg'       => 'rgba(96, 125, 139, 0.6)',

			// image Design.
			'image'                 => '',
			'imagePos'              => 'right',
			'imageMain'             => '',
			'imageMainPos'          => 'right',

			// Button designs.
			'noptinButtonBg'        => '#313131',
			'noptinButtonColor'     => '#fefefe',
			'noptinButtonLabel'     => __( 'Subscribe Now', 'newsletter-optin-box' ),

			// Title design.
			'hideTitle'             => false,
			'title'                 => __( 'JOIN OUR NEWSLETTER', 'newsletter-optin-box' ),
			'titleColor'            => '#313131',
			'titleTypography'       => array(
				'font_size'   => '30',
				'font_weight' => '700',
				'line_height' => '1.5',
				'decoration'  => '',
				'style'       => '',
				'generated'   => 'font-size: 30px; font-weight: 700; line-height: 1.5;',
			),
			'titleAdvanced'         => array(
				'margin'    => new stdClass(),
				'padding'   => array(
					'top' => '4',
				),
				'generated' => 'padding-top: 4px;',
				'classes'   => '',
			),

			// Title design.
			'hidePrefix'            => true,
			'prefix'                => __( 'Prefix', 'newsletter-optin-box' ),
			'prefixColor'           => '#313131',
			'prefixTypography'      => array(
				'font_size'   => '20',
				'font_weight' => '500',
				'line_height' => '1.3',
				'decoration'  => '',
				'style'       => '',
				'generated'   => 'font-size: 20px; font-weight: 500; line-height: 1.3;',
			),
			'prefixAdvanced'        => array(
				'margin'    => new stdClass(),
				'padding'   => array(
					'top' => '4',
				),
				'generated' => 'padding-top: 4px;',
				'classes'   => '',
			),

			// Description design.
			'hideDescription'       => false,
			'description'           => __( 'And get notified everytime we publish a new blog post.', 'newsletter-optin-box' ),
			'descriptionColor'      => '#32373c',
			'descriptionTypography' => array(
				'font_size'   => '16',
				'font_weight' => '500',
				'line_height' => '1.3',
				'decoration'  => '',
				'style'       => '',
				'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.3;',
			),
			'descriptionAdvanced'   => array(
				'padding'   => new stdClass(),
				'margin'    => array(
					'top' => '18',
				),
				'generated' => 'margin-top: 18px;',
				'classes'   => '',
			),

			// Note design.
			'hideNote'              => false,
			'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
			'noteColor'             => '#607D8B',
			'hideOnNoteClick'       => false,
			'noteTypography'        => array(
				'font_size'   => '14',
				'font_weight' => '400',
				'line_height' => '1',
				'decoration'  => '',
				'style'       => '',
				'generated'   => 'font-size: 14px; font-weight: 400; line-height: 1;',
			),
			'noteAdvanced'          => array(
				'padding'   => new stdClass(),
				'margin'    => array(
					'top' => '10',
				),
				'generated' => 'margin-top: 10px;',
				'classes'   => '',
			),

			// Trigger Options.
			'timeDelayDuration'     => 4,
			'scrollDepthPercentage' => 25,
			'cssClassOfClick'       => '#id .class',
			'triggerPopup'          => 'immeadiate',
			'slideDirection'        => 'bottom_right',

			// Restriction Options.
			'showEverywhere'        => true,
			'showPlaces'            => array(
				'showHome',
				'showBlog',
				'post',
			),
			'neverShowOn'           => '',
			'onlyShowOn'            => '',
			'whoCanSee'             => 'all',
			'userRoles'             => array(),
			'hideSmallScreens'      => false,
			'hideLargeScreens'      => false,
			'showPostTypes'         => array(),

			// custom css.
			'CSS'                   => '.noptin-optin-form-wrapper *{}',
			'tags'                  => '',

		);

		if ( empty( $defaults['successMessage'] ) ) {
			$defaults['successMessage'] = esc_html__( 'Thanks for subscribing to the newsletter', 'newsletter-optin-box' );
		}

		// Loop through all custom fields.
		foreach ( get_noptin_multicheck_custom_fields() as $field ) {

			// Skip if no options.
			if ( empty( $field['options'] ) ) {
				continue;
			}

			$default_value = ! isset( $field['default_value'] ) ? array() : $field['default_value'];
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
		$data     = wp_json_encode( $data );
		$data     = empty( $data ) ? '{}' : $data;
		$data     = str_replace( 'noptin_lists', 'lists', $data );
		$data     = json_decode( $data, true );
		$defaults = $this->get_defaults();

		// Arrays only please.
		if ( ! is_array( $data ) ) {
			return $defaults;
		}

		$data   = array_merge( $defaults, $data );
		$return = array();

		foreach ( $data as $key => $value ) {

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
		unset( $state['optinHTML'] );
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
		unset( $state['optinHTML'] );
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
			'post_content' => empty( $this->optinHTML ) ? __( 'This form is incorrectly configured', 'newsletter-optin-box' ) : $this->optinHTML,
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

		// or not published...
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
			return noptin_is_singular( $this->onlyShowOn );
		}

		// or maybe forbidden it on this post?
		if ( ! empty( $this->neverShowOn ) && noptin_is_singular( $this->neverShowOn ) ) {
			return false;
		}

		// Is this form set to be shown everywhere?
		if ( $this->showEverywhere ) {
			return true;
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
		ob_start();
		$this->display();
		return ob_get_clean();
	}

	/**
	 * Returns the html required to display the form
	 *
	 * @return string html
	 */
	public function display() {
		$type       = esc_attr( $this->optinType );
		$id         = $this->id;
		$id_class   = "noptin-form-id-$id";
		$type_class = "noptin-$type-main-wrapper";

		if ( 'popup' !== $type ) {

			$count = (int) get_post_meta( $id, '_noptin_form_views', true );
			update_post_meta( $id, '_noptin_form_views', $count + 1 );

		}

		?>
			<div class="<?php echo esc_attr( "$type_class $id_class" ); ?> noptin-optin-main-wrapper">

				<?php if ( 'popup' === $type ) : ?>
					<div class="noptin-popup-optin-inner-wrapper"></div>
				<?php endif; ?>

				<?php if ( ! empty( $this->CSS ) ) : ?>
					<style><?php echo esc_html( str_ireplace( '.noptin-optin-form-wrapper', ".$id_class .noptin-optin-form-wrapper", str_ireplace( ".$type_class", ".$type_class.$id_class", $this->CSS ) ) ); ?></style>
				<?php endif; ?>

				<?php $this->render_form(); ?>

				<?php if ( 'popup' === $type ) : ?>
					</div>
				<?php endif; ?>

			</div>
		<?php

	}

	/**
	 * Generates HTML
	 *
	 * @return string
	 */
	protected function render_form() {
		$data         = $this->data;
		$data['data'] = $data;
		$data['form'] = $this;
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include plugin_dir_path( __FILE__ ) . 'views/legacy/frontend-optin-form.php';
	}

	/**
	 * Returns all form data
	 *
	 * @return array an array of form data
	 */
	public function get_all_data() {
		return $this->data;
	}

}
