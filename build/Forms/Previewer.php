<?php
/**
 * Forms API: Forms Previewer.
 *
 * Contains main class for previewing Noptin forms
 *
 * @since             1.6.2
 * @package           Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Forms previewing class.
 *
 * @since 1.6.2
 * @internal
 * @ignore
 */
class Previewer {

	private static $form_id = null;

	public static function init() {
		add_action( 'parse_request', array( __CLASS__, 'listen' ) );
	}

	public static function listen() {
		if ( empty( $_GET['noptin_preview_form'] ) || ! current_user_can( get_noptin_capability() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( 'new' !== $_GET['noptin_preview_form'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$form = noptin_get_optin_form( absint( $_GET['noptin_preview_form'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $form->exists() ) {
				return;
			}

			$GLOBALS['noptin_showing_popup'] = $form->is_popup() || $form->is_slide_in();

			self::$form_id = $form->id;
		}

		define( 'IS_NOPTIN_PREVIEW', 1 );
		show_admin_bar( false );
		add_filter( 'pre_handle_404', '__return_true' );
		remove_all_actions( 'template_redirect' );
		add_action( 'template_redirect', array( __CLASS__, 'load_preview' ), 1 );
	}

	public static function load_preview() {
		// Clear all output buffers
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$form_id = empty( self::$form_id ) ? array() : (int) $_GET['noptin_preview_form'];
		status_header( 200 );

		// fake post to prevent notices in wp_enqueue_scripts call
		if ( empty( $form_id ) ) {
			$post = new \WP_Post( (object) array( 'filter' => 'raw' ) );
		} else {
			$post = get_post( $form_id );
		}

		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! empty( $GLOBALS['noptin_showing_popup'] ) ) {
			Popups::enqueue_scripts();
		}

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<link rel="profile" href="http://gmpg.org/xfn/11">
			<meta name="robots" content="noindex, nofollow" />
			<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
			<?php
				wp_enqueue_scripts();
				wp_print_styles();
				wp_print_head_scripts();
				wp_custom_css_cb();
				wp_site_icon();
			?>
			<style type="text/css">
				body{ 
					background: white;
					width: 100%;
					max-width: 100%;
					text-align: left;
				}

				html, body, #page, #content {
					padding: 0 !important;
					margin: 0 !important;
				}

				/* hide all other elements */
				body::before,
				body::after,
				body > *:not(#noptin-form-preview) { 
					display:none !important; 
				}

				#noptin-form-preview {
					display: block !important;
					width: 100%;
					height: 100%;
					padding: 20px;
					border: 0;
					margin: 0;
					box-sizing: border-box;
				}

				#noptin-form-preview p.description{
					font-size: 14px;
					margin: 2px 0 5px;
					color: #646970;
					text-align: center;
				}
			</style>
		</head>
		<body class="page-template-default page">
			<div id="noptin-form-preview" class="page type-page status-publish hentry post post-content">
				<p class="description"><?php esc_html_e( 'The form may look slightly different than this when shown in a post, page or widget area.', 'newsletter-optin-box' ); ?></p>
				<div style="max-width: 720px; margin: 0 auto;">
					<?php show_noptin_form( $form_id ); ?>
				</div>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
		exit;
	}
}
