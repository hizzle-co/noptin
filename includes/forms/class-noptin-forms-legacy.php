<?php
/**
 * Forms API: Forms Controller.
 *
 * Contains main class for manipulating legacy Noptin forms
 *
 * @since   1.6.1
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Noptin_Forms_Legacy' ) ) :

	/**
	 * Legacy forms controller class.
	 *
	 * @since 1.6.1
	 * @internal
	 * @ignore
	 */
	class Noptin_Forms_Legacy {

		/**
		 * Class Constructor.
		 */
		public function __construct() {

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_shortcode( 'noptin', array( $this, 'handle_noptin_shortcode' ) );
			do_action( 'noptin_forms_load', $this );

		}

		/**
		 * Callback for the `[noptin]` shortcode.
		 *
		 * @param array $atts Shortcode attributes.
		 * @since 1.6.0
		 * @return string
		 */
		public function handle_noptin_shortcode( $atts ) {

			$default_atts = $this->get_default_shortcode_atts();

			foreach ( get_noptin_connection_providers() as $key => $connection ) {

				if ( empty( $connection->list_providers ) ) {
					continue;
				}

				$default_atts["{$key}_list"] = $connection->get_default_list_id();

				if ( $connection->supports( 'tags' ) ) {
					$default_atts["{$key}_tags"] = get_noptin_option( "noptin_{$key}_default_tags", '' );
				}

				// Secondary fields.
				foreach ( array_keys( $connection->list_providers->get_secondary() ) as $secondary ) {
					$default_atts["{$key}_$secondary"] = get_noptin_option( "noptin_{$key}_default_{$secondary}", '' );
				}

			}

			$default_atts = apply_filters( 'default_noptin_shortcode_atts', $default_atts, $atts );
			$atts         = shortcode_atts( $default_atts, $atts, 'noptin' );
			return get_noptin_subscription_form_html( $atts );
		}

		/**
		 * Returns the default `[noptin]` shortcode attributes.
		 *
		 * @since 1.6.0
		 * @return array
		 */
		public function get_default_shortcode_atts() {

			return array(
				'fields'      => 'email', // Comma separated array of fields, or all
				'source'      => 'shortcode', // Manual source of the subscriber. Can also be a form id.
				'labels'      => 'hide', // Whether or not to show the field label.
				'wrap'        => 'p', // Which element to wrap field values in.
				'styles'      => 'basic', // Set to inherit to inherit theme styles.
				'title'       => '', // Form title.
				'description' => '', // Form description.
				'note'        => '', // Privacy note.
				'html_id'     => '', // ID of the form.
				'html_name'   => '', // HTML name of the form.
				'html_class'  => '', // HTML class of the form.
				'redirect'    => '', // An optional URL to redirect users after successful subscriptions.
				'success_msg' => '', // Overide the success message shwon to users after successful subscriptions.
				'submit'      => __( 'Subscribe', 'noptin-newsletter' ),
				'template'    => 'normal',
			);

		}

		/**
		 * Registers form editing metabox.
		 *
		 * @since       1.6.1
		 * @param string $post_type
		 */
		public function add_meta_boxes( $post_type ) {

			if ( 'noptin-form' === $post_type ) {
				add_meta_box(
					'noptin_form_editor',
					__( 'Form Editor', 'newsletter-optin-box' ),
					array( $this, 'display_form_editor' ),
					$post_type,
					'normal',
					'high'
				);
			}

		}

		/**
		 * Displays form editing metabox.
		 *
		 * @param WP_Post $post
		 * @since  1.6.1
		 */
		public function display_form_editor( $post ) {
			require_once plugin_dir_path( __FILE__ ) . 'legacy/class-noptin-form-editor.php';
			$editor = new Noptin_Form_Editor( $post->ID, true );
			$editor->output();
		}

		/**
		 * Retrieves the URL to the forms creation page
		 *
		 * @since  1.6.1
		 * @return string
		 */
		public function new_form_url() {
			return admin_url( 'post-new.php?post_type=noptin-form' );
		}

		/**
		 * Retrieves the URL to a form's edit page
		 *
		 * @access public
		 * @param int $form_id
		 * @since  1.6.1
		 * @return string
		 */
		public function edit_form_url( $form_id ) {
			return get_edit_post_link( $form_id, 'edit' );
		}

		/**
		 * Retrieves the URL to a forms overview page
		 *
		 * @access public
		 * @since  1.6.1
		 * @return string
		 */
		public function view_forms_url() {
			return admin_url( 'edit.php?post_type=noptin-form' );
		}

		/**
		 * Retrieves a given form's object.
		 *
		 * @param int $form_id
		 * @since  1.6.1
		 * @return Noptin_Form_Legacy
		 */
		public function get_form( $form_id ) {
			return new Noptin_Form_Legacy( $form_id );
		}

		/**
		 * Creates a new form.
		 *
		 * @param array $form_data
		 * @since  1.6.1
		 * @return WP_Error|int
		 */
		public function create_form( $form_data ) {
			$form    = new Noptin_Form_Legacy( $form_data );
			$created = $form->save();

			if ( is_wp_error( $created ) ) {
				return $created;
			}

			return $form->id;
		}

	}

endif;
