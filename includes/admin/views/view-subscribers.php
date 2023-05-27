<?php
/**
 * Displays the subscribers page.
 *
 * @since 1.2.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$components = array(
	'/'      => array(
		'title'      => esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
		'singular'   => esc_html__( 'Email Subscriber', 'newsletter-optin-box' ),
		'component'  => 'list-records',
		'collection' => 'subscribers',
		'namespace'  => 'noptin',
		'icon'       => 'admin-users',
	),
	'add'    => array(
		'title'      => esc_html__( 'Add New Subscriber', 'newsletter-optin-box' ),
		'singular'   => esc_html__( 'Add New', 'newsletter-optin-box' ),
		'component'  => 'create-record',
		'collection' => 'subscribers',
		'namespace'  => 'noptin',
	),
	'update' => array(
		'title'      => esc_html__( 'Update Subscriber', 'newsletter-optin-box' ),
		'singular'   => esc_html__( 'Update', 'newsletter-optin-box' ),
		'component'  => 'update-record',
		'collection' => 'subscribers',
		'namespace'  => 'noptin',
		'hide'       => true,
	),
	'import' => array(
		'title'      => esc_html__( 'Import Subscribers', 'newsletter-optin-box' ),
		'singular'   => esc_html__( 'Import', 'newsletter-optin-box' ),
		'component'  => 'import',
		'collection' => 'subscribers',
		'namespace'  => 'noptin',
	),
	'export' => array(
		'title'      => esc_html__( 'Export Subscribers', 'newsletter-optin-box' ),
		'singular'   => esc_html__( 'Export', 'newsletter-optin-box' ),
		'component'  => 'export',
		'collection' => 'subscribers',
		'namespace'  => 'noptin',
	),
);

$config = apply_filters(
	'noptin_admin_subscribers_page_config',
	array(
		'components' => $components,
		'namespace'  => 'noptin',
		'collection' => 'subscribers',
	)
);

?>

<style>.notice{display:none !important;}</style>

<div class="wrap noptin-subscribers-page" id="noptin-wrapper">

	<?php noptin()->admin->show_notices(); ?>

	<div id="noptin-collection__overview-app" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
		<!-- Display a loading animation while the app is loading -->
		<div class="loading">
			<?php esc_html_e( 'Loading...', 'newsletter-optin-box' ); ?>
			<span class="spinner"></span>
		</div>
	</div>

	<p class="description">
		<?php
			printf(
				// translators: %1$s Opening link tag, %2$s Closing link tag.
				esc_html__( 'Store more information about your subscribers by %1$screating custom fields%2$s.', 'newsletter-optin-box' ),
				'<a href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
				'</a>'
			);
		?>
	</p>

</div>
