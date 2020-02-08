<!DOCTYPE html>
<html>
<head>

  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title><?php echo $title; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style type="text/css">
  <meta name="robots" content="noindex, nofollow" />


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
	-ms-text-size-adjust: 100%; /* 1 */
	-webkit-text-size-adjust: 100%; /* 2 */
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
  }

  p{
		margin: 1em 0;
		  padding: 5px 0px 5px 0px;
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

  .wp-caption {
	margin-bottom: 1.5em;
	max-width: 100%;
}

.wp-caption img[class*="wp-image-"] {
	display: block;
	margin-left: auto;
	margin-right: auto;
}

.wp-caption .wp-caption-text {
	margin: 0.8075em 0;
}

.wp-caption-text {
	text-align: center;
}

.gallery {
	margin-bottom: 1.5em;
}

.gallery-item {
	display: inline-block;
	text-align: center;
	vertical-align: top;
	width: 100%;
}

.gallery-columns-2 .gallery-item {
	max-width: 50%;
}

.gallery-columns-3 .gallery-item {
	max-width: 33.33%;
}

.gallery-columns-4 .gallery-item {
	max-width: 25%;
}

.gallery-columns-5 .gallery-item {
	max-width: 20%;
}

.gallery-columns-6 .gallery-item {
	max-width: 16.66%;
}

.gallery-columns-7 .gallery-item {
	max-width: 14.28%;
}

.gallery-columns-8 .gallery-item {
	max-width: 12.5%;
}

.gallery-columns-9 .gallery-item {
	max-width: 11.11%;
}

.gallery-caption {
	display: block;
}

.alignleft {
	display: inline;
	float: left;
	margin-right: 1.5em;
}

.alignright {
	display: inline;
	float: right;
	margin-left: 1.5em;
}

.aligncenter {
	clear: both;
	display: block;
	margin-left: auto;
	margin-right: auto;
}

.noptin-round {
	border-radius: 6px;
}

  </style>

</head>
<body style="background-color: #e9ecef;">

  <?php
	echo $preview;
	echo $tracker;
	?>

  <!-- start body -->
  <table border="0" cellpadding="0" cellspacing="0" width="100%">

	<?php echo $logo; ?>

	<!-- start copy block -->
	<tr>
	  <td align="center" bgcolor="#e9ecef">
		<!--[if (gte mso 9)|(IE)]>
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
		<tr>
		<td align="center" valign="top" width="600">
		<![endif]-->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin-top: 4px;">
			<?php echo $main_content; ?>
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
		<?php echo $footer; ?>
	<!-- end footer -->

  </table>
  <!-- end body -->

</body>
</html>
