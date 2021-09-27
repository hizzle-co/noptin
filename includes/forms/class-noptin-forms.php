<?php
/**
 * Forms API: Forms Controller.
 *
 * Contains main class for manipulating Noptin forms
 *
 * @since             1.6.1
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Noptin_Forms' ) ) :

	/**
	 * Forms controller class.
	 *
	 * @since 1.6.1
	 * @internal
	 * @ignore
	 */
	class Noptin_Forms extends Noptin_Forms_Legacy {

		/**
		 * Class Constructor.
		 */
		public function __construct() {

			add_action( 'admin_init', array( $this, 'maybe_redirect_form_url' ) );
			add_action( 'noptin_after_register_menus', array( $this, 'add_editor_page' ) );
			do_action( 'noptin_forms_load', $this );

		}

		/**
		 * Retrieves a given form's object.
		 *
		 * @param int $form_id
		 * @since  1.6.1
		 * @return Noptin_Form
		 */
		public function get_form( $form_id ) {
			return new Noptin_Form( $form_id );
		}

		/**
		 * Filters post row actions.
		 *
		 * @since  1.6.1
		 */
		public function maybe_redirect_form_url() {
			global $pagenow;

			if ( ! is_admin() || empty( $pagenow ) ) {
				return;
			}

			if ( 'post.php' === $pagenow && isset( $_GET['post'] ) && 'noptin-form' === get_post_type( (int) $_GET['post'] ) ) {
				wp_redirect( $this->edit_form_url( (int) $_GET['post'] ) );
				exit;
			}

			if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'noptin-form' === $_GET['post_type'] ) {
				wp_redirect( $this->new_form_url() );
				exit;
			}

		}

		/**
		 * Retrieves the URL to the forms creation page
		 *
		 * @since  1.6.1
		 * @return string
		 */
		public function new_form_url() {
			return admin_url( 'admin.php?page=noptin-form-editor' );
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
			return add_query_arg( 'form_id', $form_id, $this->new_form_url() );
		}

		/**
		 * Registers the editor page.
		 *
		 * @since  1.6.1
		 */
		public function add_editor_page() {

			add_submenu_page( 
				null,
				'Noptin Forms Editor',
				'Noptin Forms Editor',
				'manage_options',
				'noptin-form-editor',
				array( $this, 'display_form_editor_page' )
			);

		}

		/**
		 * Displays form editing page.
		 *
		 * @since  1.6.1
		 */
		public function display_form_editor_page() {
			$post    = null;
			$post_id = 'new';

			if ( isset( $_GET['form_id'] ) ) {
				$post = get_post( $_GET['form_id'] );
			}

			if ( ! empty( $post ) ) {
				$post_id = $post->ID;
			}

			require_once plugin_dir_path( __FILE__ ) . 'editor.php';
		}

	}

endif;
