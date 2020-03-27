<!DOCTYPE html>
<html>

<head>

	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title><?php echo $title; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style type="text/css">
		/**
   	     * Avoid browser level font resizing.
   		 * 1. Windows Mobile
   		 * 2. iOS / OSX
   		 */
		body,
		table,
		td,
		p,
		a {
			-ms-text-size-adjust: 100%;
			/* 1 */
			-webkit-text-size-adjust: 100%;
			/* 2 */
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
		}

		/**
   		 * Remove extra space added to tables and cells in Outlook.
   		 */
		table,
		td {
			mso-table-rspace: 0pt;
			mso-table-lspace: 0pt;
		}

		/**
  		 * Better fluid images in Internet Explorer.
   		 */
		img {
			-ms-interpolation-mode: bicubic;
		}

		/**
   		 * Remove blue links for iOS devices.
   		 */
		a[x-apple-data-detectors] {
			font-family: inherit !important;
			font-size: inherit !important;
			font-weight: inherit !important;
			line-height: inherit !important;
			color: inherit !important;
			text-decoration: none !important;
		}

		/**
   		 * Fix centering issues in Android 4.4.
   		 */
		div[style*="margin: 16px 0;"] {
			margin: 0 !important;
		}

		body {
			width: 100% !important;
			height: 100% !important;
			padding: 0 !important;
			margin: 0 !important;
		}

		/**
   	     * Collapse table borders to avoid space between cells.
         */
		table {
			border-collapse: collapse !important;
		}

		a {
			color: #1a82e2;
		}

		img {
			height: auto;
			line-height: 100%;
			text-decoration: none;
			border: 0;
			outline: none;
		}

		.noptin-round {
			border-radius: 6px;
		}
	</style>

</head>

<body style="background-color: #e9ecef;">

	<!-- start preheader -->
	<div class="preheader"
		style="display: none; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">
		<?php _e( 'Please confirm your subscription to our newsletter.', 'newsletter-optin-box' ); ?>
	</div>
	<!-- end preheader -->

	<!-- start body -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">

		<!-- start hero -->
		<tr>
			<td align="center" bgcolor="#e9ecef">
				<!--[if (gte mso 9)|(IE)]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <tr>
        <td align="center" valign="top" width="600">
        <![endif]-->
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
					<tr>
						<td align="left" bgcolor="#ffffff" style="padding: 36px 24px 0; border-top: 3px solid #d4dadf;">
							<h1
								style="margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -1px; line-height: 48px;">
								<?php _e( 'Please confirm your subscription', 'newsletter-optin-box' ); ?></h1>
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

		<!-- start copy block -->
		<tr>
			<td align="center" bgcolor="#e9ecef">
				<!--[if (gte mso 9)|(IE)]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <tr>
        <td align="center" valign="top" width="600">
        <![endif]-->
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">

					<!-- start copy -->
					<tr>
						<td align="left" bgcolor="#ffffff" style="padding: 24px; font-size: 16px; line-height: 24px;">
							<p style="margin: 0;"><?php _e( 'Tap the button below to confirm your subscription to our newsletter.', 'newsletter-optin-box' ); ?> <?php _e( 'If you have received this email by mistake, you can safely delete it.', 'newsletter-optin-box' ); ?> <?php _e( "You won't be subscribed if you don't click on the button below.</p>", 'newsletter-optin-box' ); ?>
						</td>
					</tr>
					<!-- end copy -->

					<!-- start button -->
					<tr>
						<td align="left" bgcolor="#ffffff">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td align="center" bgcolor="#ffffff" style="padding: 12px;">
										<table border="0" cellpadding="0" cellspacing="0">
											<tr>
												<td align="center" style="border-radius: 6px;">
													<div style='text-align: left; padding: 20px;' align='left'>
														<a href="[[confirmation_link]]" class='noptin-round'
															target="_blank"
															style="background: #1a82e2; display: inline-block; padding: 16px 36px; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px;"><?php _e( 'Confirm your subscription', 'newsletter-optin-box' ); ?></a>
													</div>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<!-- end button -->

					<!-- start copy -->
					<tr>
						<td align="left" bgcolor="#ffffff" style="padding: 24px; font-size: 16px; line-height: 24px;">
							<p style="margin: 0;">
								<?php _e( "If that doesn't work, copy and paste the following link in your browser:", 'newsletter-optin-box'); ?>
							</p>
							<p style="margin: 0;"><a href="[[confirmation_link]]"
									target="_blank">[[confirmation_link]]</a></p>
						</td>
					</tr>
					<!-- end copy -->

					<!-- start copy -->
					<tr>
						<td align="left" bgcolor="#ffffff"
							style="padding: 24px; font-size: 16px; line-height: 24px; border-bottom: 3px solid #d4dadf">
							<p style="margin: 0;"><?php _e( 'Cheers,', 'newsletter-optin-box'); ?><br> [[noptin_company]]</p>
						</td>
					</tr>
					<!-- end copy -->

				</table>
				<!--[if (gte mso 9)|(IE)]>
        </td>
        </tr>
        </table>
        <![endif]-->
			</td>
		</tr>
		<!-- end copy block -->

		<!-- start footer -->
		<tr>
			<td align="center" bgcolor="#e9ecef" style="padding: 24px;">
				<!--[if (gte mso 9)|(IE)]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <tr>
        <td align="center" valign="top" width="600">
        <![endif]-->
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">

					<!-- start permission -->
					<tr>
						<td align="center" bgcolor="#e9ecef"
							style="padding: 12px 24px; font-size: 14px; line-height: 20px; color: #666;">
							<p style="margin: 0;">
								<?php _e( "You are receiving this email because we got your request to subscribe to our newsletter. If you don't want to join the newsletter, you can safely delete this email.", 'newsletter-optin-box' ); ?>
							</p>
						</td>
					</tr>
					<!-- end permission -->

					<!-- start unsubscribe -->
					<tr>
						<td align="center" bgcolor="#e9ecef"
							style="padding: 12px 24px; font-size: 14px; line-height: 20px; color: #666;">
							<p style="margin: 0;">[[noptin_company]] <br /> [[noptin_address]] <br /> [[noptin_city]],
								[[noptin_state]], [[noptin_country]]</p>
						</td>
					</tr>
					<!-- end unsubscribe -->

				</table>
				<!--[if (gte mso 9)|(IE)]>
        </td>
        </tr>
        </table>
        <![endif]-->
			</td>
		</tr>
		<!-- end footer -->

	</table>
	<!-- end body -->

</body>

</html>