<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $settings
	 * @var string $email_heading
	 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" style="height: 100%">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $email_heading ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
	<meta name="x-apple-disable-message-reformatting">
	<meta name="robots" content="noindex, nofollow" />
	<!--[if mso]>
		<noscript>
			<xml>
				<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
				</o:OfficeDocumentSettings>
			</xml>
		</noscript>
    <![endif]-->
	<?php require plugin_dir_path( __FILE__ ) . 'styles.php'; ?>
</head>
<body class="body" xml:lang="en">

	<!--[if mso]>
		<style type=”text/css”>
			table,
			td,
			div,
			p,
			a {
				font-family: Arial, sans-serif;
			}
		</style>
	<![endif]-->
	<!-- start body -->
	<div role="article" aria-label="<?php echo esc_attr( $email_heading ); ?>" <?php language_attributes(); ?> id="noptin-email-content" class="wrapper-div content">

	<?php if ( is_array( $settings['background_image'] ) && ! empty( $settings['background_image']['url'] ) ) : ?>
		<!--[if gte mso 9]>
		<v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="t">
			<v:fill type="tile" src="<?php echo esc_url( $settings['background_image']['url'] ); ?>" color="<?php echo esc_attr( $settings['background_color'] ); ?>"/>
		</v:background>
		<![endif]-->
	<?php endif; ?>
