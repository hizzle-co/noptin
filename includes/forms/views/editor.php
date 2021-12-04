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
$tabs = array(
	'form'         => __( 'Form', 'newsletter-optin-box' ),
	'messages'     => __( 'Messages', 'newsletter-optin-box' ),
	'email'        => __( 'Welcome Email', 'newsletter-optin-box' ),
	'integrations' => __( 'Integrations', 'newsletter-optin-box' ),
	'settings'     => __( 'Advanced', 'newsletter-optin-box' ),
);

$tabs = apply_filters( 'noptin_form_editor_tabs', $tabs );
$tab  = isset( $_GET['tab'] )  && array_key_exists( $_GET['tab'], $tabs ) ? noptin_clean( $_GET['tab'] ) : 'form';

add_thickbox();

?>

<div class="noptin-form-editor" id="noptin-wrapper">

	<div id="noptin-form-editor-app">
		<?php wp_nonce_field( 'noptin-save-form', 'noptin-save-form-nonce' ); ?>

		<div id="noptin-form-editor-container">

			<ul class="noptin-tab-list">
				<?php

					foreach ( $tabs as $id => $label ) :

						printf(
							'<li class="noptin-form-tab-%s"><a href="%s" data-id="%s" class="noptin-tab-button">%s</a></li>',
							esc_attr( $id ) . ( $tab == $id ? ' active' : '' ),
							esc_url( add_query_arg( 'tab', $id ) ),
							esc_attr( $id ),
							esc_html( $label )
						);

					endforeach;

				?>
			</ul>

			<?php

				foreach ( array_keys( $tabs ) as $id ) :

					printf(
						'<div data-id="%s" class="noptin-form-tab-content noptin-form-tab-content-%s %s">',
						esc_attr( $id ),
						esc_attr( $id ),
						$tab == $id ? 'noptin-form-tab-content-active' : ''
					);

					if ( file_exists( plugin_dir_path( __FILE__ ) . "tab-$id.php" ) ) {
						include plugin_dir_path( __FILE__ ) . "tab-$id.php";
					}

					do_action( "noptin_form_editor_tab_$id", $form );

					echo '</div>';
				endforeach;

			?>

		</div><!-- #noptin-form-editor-container -->

	</div>

</div><!-- .wrap -->

<?php // Content for Thickboxes ?>
<div id="noptin-form-variables" style="display: none;">
	<?php include plugin_dir_path( __FILE__ ) . 'dynamic-content-tags.php'; ?>
</div>
