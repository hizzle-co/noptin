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

	<body style="background-color: #D2C7BA;">

		<!-- start body -->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 10px;">

			<?php require plugin_dir_path( __FILE__ ) . 'logo.php'; ?>

			<?php if ( ! empty( $email_heading ) ) : ?>

				<!-- start hero -->
				<tr>
					<td align="center" bgcolor="#D2C7BA">
						<!--[if (gte mso 9)|(IE)]>
						<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
						<tr>
						<td align="center" valign="top" width="600">
						<![endif]-->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
							<tr>
								<td align="left" bgcolor="#ffffff" style="padding: 36px 24px 0; font-family: 'Merriweather Bold', serif; border-top: 5px solid #69BCB1;">
									<h1 style="margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -1px; line-height: 48px;"><?php echo esc_html( $email_heading ); ?></h1>
								</td>
							</tr>
						</table>
						<!--[if (gte mso 9)|(IE)]>
						</td>
						</tr>
						</table>
						<![endif]-->
					</td>
				</tr>
				<!-- end hero -->

			<?php endif; ?>
