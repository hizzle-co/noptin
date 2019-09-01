<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
class Noptin_Form {

	//Form id
	protected $id = null;

	/**
	 * Form information
	 * @since 1.0.5
	 * @var array
	 */
	protected $data = array();

	/**
	 * Class constructor. Loads form data.
	 * @param mixed $form Form ID, array, or Noptin_Form instance
	 */
	public function __construct( $form = false ) {

        //If this is an instance of the class...
		if ( $form instanceof Noptin_Form ) {
			$this->init( $form->data );
			return;
        }

        //... or an array of form properties
        if ( is_array( $form ) ) {
			$this->init( $form );
			return;
		}

		//Try fetching the form by its post id
		if ( ! empty( $form ) && is_numeric( $form ) ) {
			$form = absint( $form );

			if ( $data = $this->get_data_by( 'id', $form ) ) {
				$this->init( $data );
				return;
			}
		}


		//If we are here then the form does not exist
		$this->init( $this->get_defaults() );
	}

	/**
	 * Sets up object properties
	 *
	 * @param array $data contains form details
	 */
	public function init( $data ) {

		$data 				= $this->sanitize_form_data( $data );
		$this->data 		= apply_filters( "noptin_get_form_data", $data, $this );
		$this->id 			= $data['id'];

	}

	/**
	 * Fetch a form from the db/cache
	 *
	 *
	 * @param string $field The field to query against: At the moment only ID is allowed
	 * @param string|int $value The field value
	 * @return array|false array of form details on success. False otherwise.
	 */
	public function get_data_by( $field, $value ) {

		// 'ID' is an alias of 'id'...
		if ( 'id' == strtolower($field) ) {

			// Make sure the value is numeric to avoid casting objects, for example, to int 1.
			if ( ! is_numeric( $value ) ) {
                return false;
            }

            //Ensure this is a valid form id
			$value = intval( $value );
			if ( $value < 1 ) {
                return false;
            }

		} else {
			return false;
		}

		//Maybe fetch from cache
		if ( $form = wp_cache_get( $value, 'noptin_forms' ) ) {
            return $form;
        }

		//Fetch the post object from the db
		$post = get_post( $value );
        if(! $post || $post->post_type != 'noptin-form' ) {
            return false;
		}

        //Init the form
        $form = array(
            'optinName'     => $post->post_title,
            'optinStatus'   => ( $post->post_status == 'publish' ),
            'id'            => $post->ID,
            'optinHTML'     => $post->post_content,
            'optinType'     => get_post_meta( $post->ID, '_noptin_optin_type', true ),
        );

        $state = get_post_meta( $post->ID, '_noptin_state', true );
        if(! is_array( $state ) ) {
            $state = array();
        }

        $form = array_replace( $state, $form );

		//Update the cache with out data
		wp_cache_add( $post->ID, $form, 'noptin_forms' );

		return $this->sanitize_form_data( $form );
    }

    /**
	 * Return default object properties
	 *
	 * @param array $data contains form props
	 */
	public function get_defaults() {

		$noptin   = noptin();
		$defaults = array(
			'optinName'                     => '',
			'optinStatus'                   => false,
			'id'                            => null,
            'optinHTML'                     => 'This form is incorrectly configured',
            'optinType'                     => 'popup',

            //Opt in options
            'formRadius'                    => '10px',
            'hideCloseButton'               => false,
            'closeButtonPos'                => 'along',

			'singleLine'                    => true,
			'fields'						=> array(
				array(
					'type'   => array(
						'label' => 'Email Address',
						'name' => 'email',
						'type' => 'email',
					),
					'require'=> 'true',
					'key'	 => 'noptin_email_key',
				)
			),
			'inject'						=> '0',
            'buttonPosition'                => 'block',
            'subscribeAction'               => 'message', //redirect
            'successMessage'                => 'Thank you for subscribing to our newsletter',
            'redirectUrl'                   => '',


			//Form Design
			'noptinFormBgImg'				=> '',
			'noptinFormBgVideo'				=> '',
            'noptinFormBg'                  => '#fafafa',
            'noptinFormBorderColor'         => '#009688',
            'noptinFormBorderRound'         => true,
            'formWidth'                     => '520px',
			'formHeight'                    => '280px',

			//Overlay
			'noptinOverlayBgImg'			=> '',
			'noptinOverlayBgVideo'			=> '',
            'noptinOverlayBg'               => 'rgba(96, 125, 139, 0.6)',

            //image Design
            'image'                         => $noptin->plugin_url . 'includes/assets/images/email-icon.png',
			'imagePos'                      => 'right',
			'imageMain'						=> '',
			'imageMainPos'					=> '',

            //Button designs
            'noptinButtonBg'                => '#009688',
            'noptinButtonColor'             => '#fefefe',
            'noptinButtonLabel'             => 'Subscribe Now',

            //Title design
            'hideTitle'                     => false,
            'title'                         => 'Subscribe To Our Newsletter',
            'titleColor'                    => '#191919',

            //Description design
            'hideDescription'               => false,
            'description'                   => 'Enter your email to receive a weekly round-up of our best posts. <a href="https://noptin.com/guide">Learn more!</a>',
            'descriptionColor'              => '#666666',

            //Note design
            'hideNote'                      => true,
            'note'                          => "We do not spam people",
            'noteColor'                     => '#607D8B',
            'hideOnNoteClick'               => false,

            //Trigger Options
            'timeDelayDuration'             => 4,
            'scrollDepthPercentage'         => 25,
            'DisplayOncePerSession'         => true,
            'cssClassOfClick'               => '#id .class',
			'triggerPopup'					=> 'immeadiate',

            //Restriction Options
            'showEverywhere'                   	=> true,
            'showHome'              			=> true,
            'showBlog'                   		=> true,
            'showSearch'                   		=> false,
			'showArchives'              		=> false,
			'neverShowOn'              			=> '',
			'onlyShowOn'              			=> '',
			'whoCanSee'              			=> 'all',
			'userRoles'              			=> array(),
			'hideSmallScreens'              	=> false,
			'hideLargeScreens'              	=> false,
			'showPostTypes'						=> array('post'),

            //custom css
            'CSS'                           => '.noptin-optin-form-wrapper *{}',

		);

		return apply_filters( 'noptin_optin_form_default_form_state', $defaults, $this );

	}

	/**
	 * Sanitizes form data
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return array the sanitized data
	 */
	public function sanitize_form_data( $data ) {

		$defaults = $this->get_defaults();

		//Arrays only please
		if (! is_array( $data ) )
			return $defaults;

        $data   = array_replace( $defaults, $data );
        $return = array();

        foreach( $data as $key => $value ){

			//convert 'true' to a boolean true
            if( 'false' === $value ) {
                $return[$key] = false;
                continue;
            }

			//convert 'false' to a boolean false
            if( 'true' === $value ) {
                $return[$key] = true;
                continue;
			}

			if( empty( $defaults[$key] ) || !is_array( $defaults[$key] ) ) {
                $return[$key] = $value;
                continue;
			}

			//Ensure props that expect array always receive arrays
			if(  !is_array( $data[$key] ) ) {
				$return[$key] = $defaults[$key];
                continue;
			}

            $return[$key] = $value;
        }

        if( empty( $return['optinType'] ) ) {
            $return['optinType'] = 'popup';
        }

		return $return;
	}

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 * @return bool Whether the given form field is set.
	 */
	public function __isset( $key ) {

		if ( 'id' == strtolower( $key ) ) {
			return $this->id != null;
        }
		return isset( $this->data[$key] ) && $this->data[$key] != null;

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

		if ( 'id' == strtolower( $key ) ) {
			return apply_filters( "noptin_form_id", $this->id, $this );
		}

		if( isset( $this->data[$key] ) ) {
			$value = $this->data[$key];
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
	 * @since 1.0.5
	 * @access public
	 *
	 */
	public function __set( $key, $value ) {

		if ( 'id' == strtolower( $key ) ) {

			$this->id = $value;
			$this->data['id'] = $value;
			return;

		}

		$this->data[$key] = $value;

	}

	/**
	 * Saves the current form to the database
	 *
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 */
	public function save() {

		if( isset( $this->id ) ) {
            $id = $this->update();
        } else {
            $id = $this->create();
        }

        if( is_wp_error( $id ) ) {
            return $id;
        }


        //Update the cache with our new data
        wp_cache_delete( $id, 'noptin_forms' );
        wp_cache_add($id, $this->data, 'noptin_forms' );
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
	private function create() {

        //Prepare the args...
        $args = $this->get_post_array();
		unset( $args['ID'] );

        //... then create the form
        $id = wp_insert_post( $args, true );

        //If an error occured, return it
        if( is_wp_error($id) ) {
            return $id;
        }

		//Set the new id
		$this->id = $id;

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
	private function update() {

        //Prepare the args...
        $args = $this->get_post_array();

        //... then update the form
        $id = wp_update_post( $args, true );

        //If an error occured, return it
        if( is_wp_error($id) ) {
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
            'post_title'        => empty( $this->optinName ) ? '' : $this->optinName,
            'ID'                => $this->id,
            'post_content'      => empty( $this->optinHTML ) ? 'This form is incorrectly configured' : $this->optinHTML,
			'post_status'       => empty( $this->optinStatus ) ? 'draft' : 'publish',
			'post_type'         => 'noptin-form',
		);

		foreach( $data as $key => $val ) {
			if( empty( $val ) ) {
				unset( $data[$key] );
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
	public function duplicate() {
        $this->optinName = $this->optinName . " (duplicate)";
        $this->id = null;
        return $this->save();
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
		return null != $this->id;
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
		$is_form = ( $this->exists() && get_post_type( $this->id ) == 'noptin-form' );
		return apply_filters( "noptin_is_form", $is_form, $this );
	}

	/**
	 * Checks whether this form can be displayed on the current page
	 *
	 *
	 * @return bool
	 */
	public function can_show(){

		//Abort early if the form is not published...
		if( !$this->exists() || !$this->is_published() ) {
			return false;
		}

		//... or the user wants to hide all forms
		if( !noptin_should_show_optins() ) {
			return false;
		}

		//Maybe hide on mobile
		if( $this->hideSmallScreens && wp_is_mobile() ) {
			return false;
		}

		//Maybe hide on desktops
		if( $this->hideLargeScreens && !wp_is_mobile() ) {
			return false;
		}

		//Get current global post
		$post = get_post();

		//Has the user restricted this to a few posts?
		if(! empty( $this->onlyShowOn ) ) {
			return is_object( $post ) && in_array( $post->ID, explode( ',', $this->onlyShowOn ) );
		}


		//or maybe forbidden it on this post?
		if( is_object( $post ) && in_array( $post->ID, explode( ',', $this->neverShowOn ) ) ) {
			return false;
		}

		//Is this form set to be shown everywhere?
		if( $this->showEverywhere ) {
			return true;
		}

		//frontpage
		if ( is_front_page() ) {
			return $this->showHome;
		}

		//blog page
		if ( is_home() ) {
			return $this->showBlog;
		}

		//search
		if ( is_search() ) {
			return $this->showSearch;
		}

		//other archive pages
		if ( is_archive() ) {
			return $this->showArchives;
		}

		//Single posts
		$post_types = $this->showPostTypes;

		if( empty( $post_types ) ) {
			return false;
		}

		return is_singular( $post_types );

	}

	/**
	 * Returns the html required to display the form
	 *
	 * @return string html
	 */
	public function get_html(){
		$type       = esc_attr( $this->optinType );
		$id         = $this->id;
		$id_class   = "noptin-form-id-$id";
		$type_class = "noptin-$type-main-wrapper";
		$style		= '';

		if( $type == 'popup' ){

			//Background color
			if( $this->noptinOverlayBg ) {
				$color = esc_attr( $this->noptinOverlayBg );
				$style = "background-color: $color;";
			}

			//Background image
			if( $this->noptinOverlayBgImg ) {
				$image = esc_url( $this->noptinOverlayBgImg );
				$style .= "background-image: url($image);";
			}

		}
		$html  = "<div class='$type_class $id_class' style='$style'>";

		//Maybe print custom css
		if(! empty( $this->CSS ) ) {

			//Our best attempt at scoping styles
			$wrapper = '.noptin-optin-form-wrapper';
			$css     = str_ireplace( ".$type_class", ".$type_class.$id_class", $this->CSS);
			$css     = str_ireplace( $wrapper, ".$id_class $wrapper", $css);
			$html   .= "<style>$css</style>";
		}

		//print main form html
		return $html . $this->optinHTML . '</div>';
	}

	/**
	 * Returns all form data
	 *
	 * @return array an array of form data
	 */
	public function get_all_data(){
		return $this->data;
	}

}
