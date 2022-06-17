<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title><?php echo esc_html( $email_heading ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow" />
	<?php require plugin_dir_path( __FILE__ ) . 'styles.php'; ?>
</head>
<body>

	<table id="backgroundTable" cellpadding="0" cellspacing="0" border="0">
    	<tbody>
			<tr>
        		<td>
					<?php require plugin_dir_path( __FILE__ ) . 'logo.php'; ?>

					<table class="body-wrap">
						<tbody>
							<tr>
								<td></td>
								<td class="container" bgcolor="#FFFFFF" valign="top">
									<!-- content -->
									<div class="content">
										<table>
											<tbody>
												<tr>
													<td>

														<?php if ( ! empty( $email_heading ) ) : ?>

															<!-- start hero -->
																<h1 style="margin-bottom: 20px;"><?php echo esc_html( $email_heading ); ?></h1>
															<!-- end hero -->

														<?php endif; ?>
