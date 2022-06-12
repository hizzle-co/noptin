<?php
/**
 * Forms API: Displays the newsletter form editor.
 *
 * Displays the newsletter form editor
 *
 * @var Noptin_Form $form
 * @since   1.6.2
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Setting tabs.
$form_tabs = array(
	'form'         => __( 'Form', 'newsletter-optin-box' ),
	'messages'     => __( 'Messages', 'newsletter-optin-box' ),
	'email'        => __( 'Welcome Email', 'newsletter-optin-box' ),
	'integrations' => __( 'Integrations', 'newsletter-optin-box' ),
	'settings'     => __( 'Advanced', 'newsletter-optin-box' ),
);

$form_tabs   = apply_filters( 'noptin_form_editor_tabs', $form_tabs );
$current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $form_tabs ) ? noptin_clean( $_GET['tab'] ) : 'form'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

add_thickbox();

?>

<div class="noptin-form-editor" id="noptin-wrapper">

	<div id="noptin-form-editor-app">
		<?php wp_nonce_field( 'noptin-save-form', 'noptin-save-form-nonce' ); ?>

		<div id="noptin-form-editor-container">

			<ul class="noptin-tab-list">
				<?php

					foreach ( $form_tabs as $form_tab => $label ) :

						printf(
							'<li class="noptin-form-tab-%s"><a href="%s" data-id="%s" class="noptin-tab-button">%s</a></li>',
							esc_attr( $form_tab ) . ( $current_tab === $form_tab ? ' active' : '' ),
							esc_url( add_query_arg( 'tab', $form_tab ) ),
							esc_attr( $form_tab ),
							esc_html( $label )
						);

					endforeach;

				?>
			</ul>

			<?php

				foreach ( array_keys( $form_tabs ) as $form_tab ) :

					printf(
						'<div data-id="%s" class="noptin-form-tab-content noptin-form-tab-content-%s %s">',
						esc_attr( $form_tab ),
						esc_attr( $form_tab ),
						$current_tab === $form_tab ? 'noptin-form-tab-content-active' : ''
					);

					if ( file_exists( plugin_dir_path( __FILE__ ) . "tab-$form_tab.php" ) ) {
						include plugin_dir_path( __FILE__ ) . "tab-$form_tab.php";
					}

					do_action( "noptin_form_editor_tab_$form_tab", $form );

					echo '</div>';
				endforeach;

			?>

		</div><!-- #noptin-form-editor-container -->

	</div>

</div><!-- .wrap -->

<?php // Content for Thickboxes ?>
<div id="noptin-form-variables" style="display: none;">
	<?php require plugin_dir_path( __FILE__ ) . 'dynamic-content-tags.php'; ?>
</div>
