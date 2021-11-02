<?php
/**
 * Forms API: Forms Admin.
 *
 * Contains the main admin class for Noptin forms
 *
 * @since   1.6.2
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin forms.
 *
 * @since 1.6.2
 * @internal
 * @ignore
 */
class Noptin_Form_Admin {

	/**
	 * Add hooks
	 *
	 * @since  1.6.2
	 */
	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'maybe_redirect_form_url' ) );
		add_action( 'noptin_after_register_menus', array( $this, 'add_editor_page' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'noptin_editor_save_form', array( $this, 'save_edited_form' ) );
	}

	/**
	 * Filters post row actions.
	 *
	 * @since  1.6.2
	 */
	public function maybe_redirect_form_url() {
		global $pagenow;

		if ( ! is_admin() || empty( $pagenow ) ) {
			return;
		}

		$to_keep = array( 'from_post', 'new_lang', 'trid' );
		$args    = array();

		foreach ( $to_keep as $key ) {
			if ( isset( $_GET[ $key ] ) ) {
				$args[ $key ] = $_GET[ $key ];
			}
		}

		// Form edits.
		if ( 'post.php' === $pagenow && ( empty( $_GET['action'] ) || 'edit' === $_GET['action'] ) && isset( $_GET['post'] ) && 'noptin-form' === get_post_type( (int) $_GET['post'] ) ) {

			// Only redirect if we're using the new forms editor.
			if ( ! is_legacy_noptin_form( (int) $_GET['post'] ) ) {
				$args['form_id'] = (int) $_GET['post'];
				wp_redirect( add_query_arg( $args, admin_url( 'admin.php?page=noptin-form-editor' ) ) );
				exit;
			}

		}

		// Form creates.
		if ( is_using_new_noptin_forms() && 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'noptin-form' === $_GET['post_type'] ) {
			wp_redirect( add_query_arg( $args, get_noptin_new_form_url() ) );
			exit;
		}

	}

	/**
	 * Registers the editor page.
	 *
	 * @since  1.6.2
	 */
	public function add_editor_page() {

		add_submenu_page(
			'noptin',
			'Forms Editor - Noptin',
			'Noptin Forms Editor',
			'manage_options',
			'noptin-form-editor',
			array( $this, 'display_form_editor_page' )
		);

	}

	/**
	 * Displays form editing page.
	 *
	 * @since  1.6.2
	 */
	public function display_form_editor_page() {
		global $post, $post_ID;

		if ( ! empty( $_POST['noptin_form'] ) ) {
			$form = new Noptin_Form( wp_kses_post_deep( wp_unslash( $_POST['noptin_form'] ) ) );
		} elseif ( isset( $_GET['form_id'] ) ) {
			$form = new Noptin_Form( (int) $_GET['form_id'] );
		} else {
			$form = new Noptin_Form();
		}

		if ( $form->exists() ) {
			$post    = get_post( $form->id );
			$post_ID = $form->id;
		}

		require_once plugin_dir_path( __FILE__ ) . 'views/editor.php';

		// Custom admin scripts.
		$version = filemtime( plugin_dir_path( Noptin::$file ) . 'includes/assets/js/dist/form-editor.js' );
		wp_enqueue_script( 'select2', plugin_dir_url( Noptin::$file ) . 'includes/assets/vendor/select2/select2.full.min.js', array( 'jquery' ), '4.0.12', true );
		wp_enqueue_script( 'noptin-form-editor', plugin_dir_url( Noptin::$file ) . 'includes/assets/js/dist/form-editor.js', array( 'jquery', 'select2' ), $version, true );
	}

	/**
	 * Registers the legacy form editing metabox.
	 *
	 * @since       1.6.2
	 * @param string $post_type
	 */
	public function add_meta_boxes( $post_type ) {

		if ( 'noptin-form' === $post_type ) {
			add_meta_box(
				'noptin_form_editor',
				__( 'Form Editor', 'newsletter-optin-box' ),
				array( $this, 'display_legacy_form_editor' ),
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
	 * @since  1.6.2
	 */
	public function display_legacy_form_editor( $post ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-legacy-form-editor.php';
		$editor = new Noptin_Legacy_Form_Editor( $post->ID, true );
		$editor->output();
	}

	/**
	 * Saves a submitted form (only handles forms created by the new editor).
	 *
	 * @param Noptin_Admin $admin
	 * @since  1.6.2
	 */
	public function save_edited_form( $admin ) {

		// Security checks.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( 'Access Denied' );
		}

		if ( empty( $_POST['noptin-save-form-nonce'] ) || ! wp_verify_nonce( $_POST['noptin-save-form-nonce'], 'noptin-save-form' ) ) {
			wp_die( 'Invalid Nonce' );
		}

		// Removes slashes from submitted data and loads the form.
		$form   = new Noptin_Form( wp_kses_post_deep( wp_unslash( $_POST['noptin_form'] ) ) );
		$is_new = ! $form->exists();

		// Create/update the form.
		$form->save();

		if ( ! $form->exists() ) {
			return $admin->show_error( __( 'An error ocurred while saving your changes. Please try again later.', 'newsletter-optin-box' ) );
		}

		if ( $is_new ) {

			$admin->show_success(
				sprintf(
					__( 'Form created successfully. %sPreview%s', 'newsletter-optin-box' ),
					sprintf( '<a href="%s">', esc_url_raw( get_noptin_preview_form_url( $form->id ) ) ),
					'</a>'
				)
			);

		} else {

			$admin->show_success(
				sprintf(
					__( 'Form updated successfully. %sPreview%s', 'newsletter-optin-box' ),
					sprintf( '<a href="%s">', esc_url_raw( get_noptin_preview_form_url( $form->id ) ) ),
					'</a>'
				)
			);

		}

		do_action( 'after_save_edited_noptin_form', $form );

		// Redirect to the form's edit page.
		wp_redirect( esc_url_raw( get_noptin_edit_form_url( $form->id ) ) );
		exit;
	}

}
