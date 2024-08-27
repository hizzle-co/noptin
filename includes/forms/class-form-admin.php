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

		if ( ! is_legacy_noptin_form( $post->ID ) ) {

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
		wp_enqueue_script( 'noptin-form-editor', plugin_dir_url( Noptin::$file ) . 'includes/assets/js/dist/form-editor.js', array( 'jquery' ), $version, true );
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

		$preview_form = sprintf(
			' <a href="%s">%s</a>',
			esc_url( get_noptin_preview_form_url( $post_ID ) ),
			__( 'Preview form', 'newsletter-optin-box' )
		);

		$messages['noptin-form'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Form updated.', 'newsletter-optin-box' ) . $preview_form,
			2  => __( 'Custom field updated.', 'newsletter-optin-box' ),
			3  => __( 'Custom field deleted.', 'newsletter-optin-box' ),
			4  => __( 'Form updated.', 'newsletter-optin-box' ) . $preview_form,
			5  => __( 'Form restored.', 'newsletter-optin-box' ),
			6  => __( 'Form published.', 'newsletter-optin-box' ) . $preview_form,
			7  => __( 'Form saved.', 'newsletter-optin-box' ) . $preview_form,
			8  => __( 'Form submitted.', 'newsletter-optin-box' ) . $preview_form,
			9  => __( 'Form scheduled.', 'newsletter-optin-box' ) . $preview_form,
			10 => __( 'Form draft updated.', 'newsletter-optin-box' ) . $preview_form,
		);

		return $messages;
	}
}
