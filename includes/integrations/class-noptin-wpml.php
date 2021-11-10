<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with WPML.
 *
 * @since       1.6.2
 */
class Noptin_WPML {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'translate_noptin_form_id', array( $this, 'translate_form_id' ), 10, 1 );
		add_filter( 'icl_job_elements', array( $this, 'remove_body_from_translation_job' ), 10, 2 );
		add_filter( 'wpml_document_view_item_link', array( $this, 'document_view_item_link' ), 10, 5 );
		add_filter( 'wpml_document_edit_item_link', array( $this, 'document_edit_item_link' ), 10, 5 );
		add_action( 'noptin_form_editor_side_metabox', array( $this, 'add_language_meta_box' ) );
		add_filter( 'wpml_link_to_translation', array( $this, 'link_to_translation' ), 10, 4 );
		add_filter( 'noptin_is_multilingual', '__return_true', 5 );
		add_filter( 'noptin_form_scripts_params', array( $this, 'filter_ajax_params' ), 5 );

		if ( isset( $_GET['page'] ) && 'noptin-form-editor' == $_GET['page'] ) {
			add_filter( 'wpml_enable_language_meta_box', '__return_true' );
			add_filter( 'wpml_admin_language_switcher_items', array( $this, 'admin_language_switcher_items' ) );
		}

	}

	/**
	 * Find the right form and return it in the current language.
	 *
	 * @param int $form_id The form ID being displayed.
	 *
	 * @return int
	 */
	public function translate_form_id( $form_id ) {
		return apply_filters( 'wpml_object_id', $form_id, 'noptin-form', true );
	}

	/**
	 * Don't translate the post_content of subscription forms.
	 *
	 * @param array $elements Translation job elements.
	 * @param int   $post_id  The post ID.
	 *
	 * @return array
	 */
	public function remove_body_from_translation_job( $elements, $post_id ) {

		// Bail out early if its not a noptin form.
		if ( 'noptin-form' !== get_post_type( $post_id ) ) {
			return $elements;
		}

		// Search for the body element and empty it so that it's not displayed in the TE.
		$field_types = wp_list_pluck( $elements, 'field_type' );
		$index       = array_search( 'body', $field_types, true );
		if ( false !== $index ) {
			$elements[ $index ]->field_data            = '';
			$elements[ $index ]->field_data_translated = '';
		}

		return $elements;
	}

	/**
	 * Remove the 'View' link from translation jobs because Subscription
	 * Forms don't have a link to 'View' them.
	 *
	 * @param string $link   The complete link.
	 * @param string $text   The text to link.
	 * @param object $job    The corresponding translation job.
	 * @param string $prefix The prefix of the element type.
	 * @param string $type   The element type.
	 *
	 * @return string
	 */
	public function document_view_item_link( $link, $text, $job, $prefix, $type ) {
		if ( 'noptin-form' === $type ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Adjust the 'Edit' link from translation jobs because Subscription
	 * Forms have a different URL for editing.
	 *
	 * @param string $link             The complete link.
	 * @param string $text             The text to link.
	 * @param object $current_document The document to translate.
	 * @param string $prefix           The prefix of the element type.
	 * @param string $type             The element type.
	 *
	 * @return string
	 */
	public function document_edit_item_link( $link, $text, $current_document, $prefix, $type ) {

		if ( 'noptin-form' === $type && ! is_legacy_noptin_form( $current_document->ID ) ) {
			$url  = add_query_arg( 'form_id', (int) $current_document->ID, admin_url( 'admin.php?page=noptin-form-editor' ) );
			$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $text ) );
		}

		return $link;
	}

	/**
	 * Add the WPML meta box when editing forms.
	 *
	 * @param Noptin_Form $form The subscription form.
	 */
	public function add_language_meta_box( $form ) {
		global $sitepress;

		$trid = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT );

		if ( $form->exists() ) {
			add_filter( 'wpml_post_edit_can_translate', '__return_true' );
			?>

			<div class="postbox">
				<h3><?php echo esc_html( __( 'Language', 'newsletter-optin-box' ) ); ?></h3>
				<div>
					<div>
						<div id="icl_div">
							<div class="inside"><?php $sitepress->meta_box( get_post( $form->id ) ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<?php

		} else if ( $trid ) {
			// Used by WPML for connecting new manual translations to their originals.
			echo '<input type="hidden" name="icl_trid" value="' . esc_attr( $trid ) . '" />';
		}
	}

	/**
	 * Filters links to translations in language metabox.
	 *
	 * @param string $link
	 * @param int    $post_id
	 * @param string $lang
	 * @param int    $trid
	 * @return string
	 */
	public function link_to_translation( $link, $post_id, $lang, $trid ) {

		if ( 'noptin-form' === get_post_type( $post_id ) ) {
			$link = $this->get_link_to_translation( $post_id, $lang );
		}

		return $link;
	}

	/**
	 * Works out the correct link to a translation
	 *
	 * @param int    $post_id The post_id being edited.
	 * @param string $lang    The target language.
	 * @return string
	 */
	protected function get_link_to_translation( $post_id, $lang ) {
		global $wpml_post_translations;

		$translated_post_id = $wpml_post_translations->element_id_in( $post_id, $lang );
		if ( $translated_post_id ) {

			// Rewrite link to edit subscription form translation.
			$args = array(
				'lang'   => $lang,
				'form_id' => $translated_post_id,
			);

		} else {
			// Rewrite link to create subscription form translation.
			$trid                 = $wpml_post_translations->get_element_trid( $post_id, 'noptin-form' );
			$source_language_code = $wpml_post_translations->get_element_lang_code( $post_id );

			$args = array(
				'lang'        => $lang,
				'trid'        => $trid,
				'source_lang' => $source_language_code,
			);
		}

		return add_query_arg( $args, get_noptin_new_form_url() );
	}

	/**
	 * Filters the top bar admin language switcher links.
	 *
	 * @param array $links
	 * @return array $links
	 */
	public function admin_language_switcher_items( $links ) {
		global $wpml_post_translations;

		$post_id       = filter_input( INPUT_GET, 'form_id', FILTER_SANITIZE_NUMBER_INT );
		$trid          = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT );

		if ( $trid || $post_id ) {
			// If we are adding a post, get the post_id from the trid and source_lang.
			if ( ! $post_id ) {
				$source_lang = filter_input( INPUT_GET, 'source_lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				$post_id     = $wpml_post_translations->get_element_id( $source_lang, $trid );
				unset( $links['all'] );
				// We shouldn't get here, but just in case.
				if ( ! $post_id ) {
					return $links;
				}
			}

			foreach ( $links as $lang => & $link ) {
				if ( 'all' !== $lang && ! $link['current'] ) {
					$link['url'] = $this->get_link_to_translation( $post_id, $lang );
				}
			}
		}

		return $links;
	}

	/**
	 * Add language info to REST links.
	 *
	 * @param array $params
	 * @return array $params
	 */
	public function filter_ajax_params( $params ) {
		$params['resturl'] = apply_filters( 'wpml_permalink', $params['resturl'], null );
		return $params;
	}

}
