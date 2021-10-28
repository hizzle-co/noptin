<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Polylang.
 *
 * @since       1.6.2
 */
class Noptin_Polylang {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'pll_get_post_types', array( $this, 'filter_post_types' ), 10, 2 );
		add_action( 'after_save_edited_noptin_form', array( $this, 'edit_form' ) );
		add_action( 'admin_init', array( $this, 'maybe_translate' ), 9 );
		add_action( 'noptin_form_editor_side_metabox', array( $this, 'add_language_meta_box' ) );
		add_filter( 'translate_noptin_form_id', array( $this, 'translate_form_id' ) );
		add_filter( 'noptin_is_multilingual', '__return_true', 5 );
		add_filter( 'noptin_form_scripts_params', array( $this, 'filter_ajax_params' ), 5 );

		if ( isset( $_GET['page'] ) && 'noptin-form-editor' == $_GET['page'] ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

	}

	/**
	 * Filters editable post types.
	 *
	 * @param array  $post_types
	 * @param bool   $is_settings
	 *
	 * @return array
	 */
	public function filter_post_types( $post_types, $is_settings = false ) {

		if ( ! $is_settings ) {
			$post_types['noptin-form'] = 'noptin-form';
		}

		return $post_types;
	}

	/**
	 * Find the right form and return it in the current language.
	 *
	 * @param int $form_id The form ID being displayed.
	 *
	 * @return int
	 */
	public function translate_form_id( $form_id ) {

		if ( function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $form_id );
	
			if ( ! empty( $translated ) ) {
				$form_id = $translated;
			}
		}

		return $form_id;
	}

	/**
	 * Add the Polylang meta box when editing forms.
	 *
	 * @param Noptin_Form $form The subscription form.
	 */
	public function add_language_meta_box( $form ) {
		$pll = PLL();

		if ( empty( $pll ) || empty( $pll->classic_editor ) ) {
			return;
		}

		if ( $form->exists() ) {
			?>

			<div class="postbox">
				<h3><?php echo esc_html( __( 'Languages', 'newsletter-optin-box' ) ); ?></h3>
				<div>
					<div>
						<div id="icl_div">
							<div class="inside"><?php $pll->classic_editor->post_language(); ?></div>
						</div>
					</div>
				</div>
			</div>

			<?php

		}

	}

	/**
	 * Add language info to REST links.
	 *
	 * @param array $params
	 * @return array $params
	 */
	public function filter_ajax_params( $params ) {
		$params['resturl'] = $params['ajaxurl'];
		return $params;
	}

	/**
	 * Clones a given form for translation.
	 *
	 * @param int $form_id
	 * @param PLL_Language $lang
	 * @return int|false
	 */
	public function translate_to( $form_id, $lang ) {

		// Retrieve the form.
		$form = noptin_get_optin_form( $form_id );

		if ( ! $form->exists() ) {
			return false;
		}

		// Duplicate the form.
		$form->duplicate( '(' . sanitize_text_field( $lang->name ) . ')' );

		if ( ! $form->exists() ) {
			return false;
		}

		// Save translation details.
		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $form->id, $lang->slug );

			$translations = pll_get_post_translations( $form_id );

			$translations[ $lang->slug ] = $form->id;
			pll_save_post_translations( $translations );
			PLL()->model->post->save_translations( $form->id, $translations );
		}

		return $form->id;
	}

	/**
	 * Creates a new translation.
	 *
	 * @since 1.6.2
	 *
	 */
	public function maybe_translate() {

		// Abort if not our request...
		if ( ! isset( $GLOBALS['pagenow'], $_GET['post_type'], $_GET['from_post'], $_GET['new_lang'] ) ) {
			return;
		}

		// ... or post type.
		if ( 'post-new.php' !== $GLOBALS['pagenow'] || 'noptin-form' !== $_GET['post_type'] ) {
			return;
		}
	
		// Security check.
		check_admin_referer( 'new-post-translation' );

		// Capability check.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( 'Not authorized' );
		}

		// Prepare args.
		$from_post_id = (int) $_GET['from_post'];
		$lang         = PLL()->model->get_language( sanitize_text_field( urldecode( $_GET['new_lang'] ) ) );

		if ( ! $from_post_id || ! $lang ) {
			wp_die( 'Invalid language or post' );
		}

		// Translate.
		$new_form = $this->translate_to( $from_post_id, $lang );

		if ( ! $new_form ) {
			wp_die( 'An error occured while translating the form' );
		}

		wp_redirect( get_noptin_edit_form_url( $new_form ) );
		exit;
	}

	/**
	 * Save language and translation when editing a form
	 *
	 * @since 1.6.2
	 * @param Noptin_Form $form
	 * @return void
	 */
	public function edit_form( $form ) {

		// Ensure the languages metabox was shown.
		if ( function_exists( 'pll_set_post_language' ) && isset( $_POST['post_lang_choice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification

			// Check nonce.
			check_admin_referer( 'pll_language', '_pll_nonce' );

			// Set language.
			pll_set_post_language(
				$form->id,
				PLL()->model->get_language( sanitize_key( $_POST['post_lang_choice'] ) )
			);

			// Save translations.
			if ( isset( $_POST['post_tr_lang'] ) ) {
				PLL()->model->post->save_translations( $form->id, array_map( 'absint', $_POST['post_tr_lang'] ) );
			}

		}

	}

	/**
	 * Setup js scripts & css styles ( only on the relevant pages )
	 *
	 * @since 0.6
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {

		// Ensure that PLL is set-up and that we are currently editing a saved form.
		if ( empty( $_GET['form_id'] ) || ! defined( 'POLYLANG_VERSION' ) ) {
			return;
		}

		// Need for PLL to properly set footer scripts.
		$GLOBALS['post_ID'] = (int) $_GET['form_id'];

		// Load the classic-editor JS.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'pll_classic-editor',
			plugins_url( '/js/build/classic-editor' . $suffix . '.js', POLYLANG_ROOT_FILE ),
			array( 'jquery', 'wp-ajax-response', 'post', 'jquery-ui-dialog', 'wp-i18n' ),
			POLYLANG_VERSION,
			true
		);

		wp_set_script_translations( 'pll_classic-editor', 'polylang' );
	}

}
