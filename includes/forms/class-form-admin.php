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
	 * Ensure we save metaboxes once.
	 *
	 * @var bool
	 */
	protected $saved_meta_box = false;

	/**
	 * Add hooks
	 *
	 * @since  1.6.2
	 */
	public function add_hooks() {
		add_action( 'add_meta_boxes_noptin-form', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_edited_form' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
	}

	/**
	 * Registers the form editing metabox.
	 *
	 * @since       1.6.2
	 * @param WP_Post $post
	 */
	public function add_meta_boxes( $post ) {

		if ( is_legacy_noptin_form( $post->ID ) ) {

			add_meta_box(
				'noptin_form_editor',
				__( 'Form Editor', 'newsletter-optin-box' ),
				array( $this, 'display_legacy_form_editor' ),
				null,
				'normal',
				'high'
			);

		} else {

			add_meta_box(
				'noptin-form-editor-new',
				__( 'Form Editor', 'newsletter-optin-box' ),
				array( $this, 'display_new_form_editor' ),
				null,
				'normal',
				'high'
			);

			add_meta_box(
				'noptin-form-editor-embed',
				__( 'Embed', 'newsletter-optin-box' ),
				array( $this, 'display_editor_embed' ),
				null,
				'side',
				'low'
			);

			add_meta_box(
				'noptin-form-editor-tips',
				__( 'Do you need help?', 'newsletter-optin-box' ),
				array( $this, 'display_editor_tips' ),
				null,
				'side',
				'low'
			);

		}

	}

	/**
	 * Displays the legacy form editing metabox.
	 *
	 * @param WP_Post $post
	 * @since  1.6.2
	 */
	public function display_legacy_form_editor( $post ) {

		$version = filemtime( plugin_dir_path( Noptin::$file ) . 'includes/assets/js/dist/optin-editor.js' );
		wp_enqueue_script( 'noptin-modules', plugin_dir_url( Noptin::$file ) . 'includes/assets/js/dist/modules.js', array(), $version, true );
		wp_enqueue_script( 'noptin-optin-editor', plugin_dir_url( Noptin::$file ) . 'includes/assets/js/dist/optin-editor.js', array( 'vue', 'select2', 'sweetalert2', 'noptin-modules' ), $version, true );

		require_once plugin_dir_path( __FILE__ ) . 'class-legacy-form-editor.php';
		$editor = new Noptin_Legacy_Form_Editor( $post->ID, true );
		$editor->output();
	}

	/**
	 * Displays new form editing metabox.
	 *
	 * @param WP_Post $post
	 * @since  1.6.4
	 */
	public function display_new_form_editor( $post ) {
		$form = new Noptin_Form( $post->ID );

		require_once plugin_dir_path( __FILE__ ) . 'views/editor.php';

		// Custom admin scripts.
		$version = filemtime( plugin_dir_path( Noptin::$file ) . 'includes/assets/js/dist/form-editor.js' );
		wp_enqueue_script( 'select2', plugin_dir_url( Noptin::$file ) . 'includes/assets/vendor/select2/select2.full.min.js', array( 'jquery' ), '4.0.12', true );
		wp_enqueue_script( 'noptin-form-editor', plugin_dir_url( Noptin::$file ) . 'includes/assets/js/dist/form-editor.js', array( 'jquery', 'select2' ), $version, true );
	}

	/**
	 * Displays editor embed metabox.
	 *
	 * @param WP_Post $post
	 * @since  1.6.4
	 */
	public function display_editor_embed( $post ) {
		include plugin_dir_path( __FILE__ ) . 'views/metabox-embed.php';
	}

	/**
	 * Displays editor tips.
	 *
	 * @param WP_Post $post
	 * @since  1.6.4
	 */
	public function display_editor_tips( $post ) {
		include plugin_dir_path( __FILE__ ) . 'views/metabox-tips.php';
	}

	/**
	 * Saves a submitted form (only handles forms created by the new editor).
	 *
	 * @param  int    $post_id Post ID.
	 * @param  WP_Post $post Post object.
	 * @since  1.6.2
	 */
	public function save_edited_form( $post_id, $post ) {

		// Do not save for ajax requests.
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || $this->saved_meta_box ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce.
		if ( empty( $_POST['noptin-save-form-nonce'] ) || ! wp_verify_nonce( $_POST['noptin-save-form-nonce'], 'noptin-save-form' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['noptin_form'] ) || empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Prepare the form.
		$form = new Noptin_Form( $post_id );

		// Abort if it does not exist.
		if ( ! $form->exists() ) {
			return;
		}

		// Prepare data being saved.
		$data = wp_kses_post_deep( wp_unslash( $_POST['noptin_form'] ) );

		foreach ( $form->get_form_properties() as $prop ) {

			if ( ! in_array( $prop, array( 'id', 'title', 'status' ), true ) ) {

				if ( isset( $data[ $prop ] ) && ( ! empty( $data[ $prop ] ) || '0' === $data[ $prop ] ) ) {
					update_post_meta( $post_id, "form_$prop", $data[ $prop ] );
				} else {
					delete_post_meta( $post_id, "form_$prop" );
				}
			}
		}

		delete_transient( 'noptin_subscription_sources' );
		do_action( 'after_save_edited_noptin_form', $form );
	}

	/**
	 * Filter our updated/trashed post messages
	 *
	 * @access public
	 * @since 1.6.2
	 * @return array $messages
	 */
	public function post_updated_messages( $messages ) {
		global $post_ID;

		$messages['noptin-form'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( /* Translators: %s URL to preview the form. */ __( 'Form updated. <a href="%s">Preview form</a>', 'newsletter-optin-box' ), esc_url( get_noptin_preview_form_url( $post_ID ) ) ),
			2  => __( 'Custom field updated.', 'newsletter-optin-box' ),
			3  => __( 'Custom field deleted.', 'newsletter-optin-box' ),
			4  => sprintf( /* Translators: %s URL to preview the form. */ __( 'Form updated. <a href="%s">Preview form</a>', 'newsletter-optin-box' ), esc_url( get_noptin_preview_form_url( $post_ID ) ) ),
			5  => __( 'Form restored.', 'newsletter-optin-box' ),
			6  => sprintf( /* Translators: %s URL to preview the form. */ __( 'Form published. <a href="%s">Preview form</a>', 'newsletter-optin-box' ), esc_url( get_noptin_preview_form_url( $post_ID ) ) ),
			7  => sprintf( /* Translators: %s URL to preview the form. */ __( 'Form saved. <a href="%s">Preview form</a>', 'newsletter-optin-box' ), esc_url( get_noptin_preview_form_url( $post_ID ) ) ),
			8  => sprintf( /* Translators: %s URL to preview the form. */ __( 'Form submitted. <a href="%s">Preview form</a>', 'newsletter-optin-box' ), esc_url( get_noptin_preview_form_url( $post_ID ) ) ),
			9  => sprintf( /* Translators: %s URL to preview the form. */ __( 'Form scheduled. <a href="%s">Preview form</a>', 'newsletter-optin-box' ), esc_url( get_noptin_preview_form_url( $post_ID ) ) ),
			10 => sprintf( /* Translators: %s URL to preview the form. */ __( 'Form draft updated. <a href="%s">Preview form</a>', 'newsletter-optin-box' ), esc_url( get_noptin_preview_form_url( $post_ID ) ) ),
		);

		return $messages;
	}

}
