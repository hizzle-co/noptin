<?php if(! empty( $logo_url ) ) { ?>
<!-- start logo -->
<tr>
      <td align="center" bgcolor="#e9ecef">
        <!--[if (gte mso 9)|(IE)]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <tr>
        <td align="center" valign="top" width="600">
        <![endif]-->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
          <tr>
            <td align="center" valign="top" style="padding: 36px 24px;">
             	<a href="[[home_url]]" target="_blank" style="display: inline-block;">
                	<img src="<?php echo esc_url($logo_url);?>" alt="Logo" border="0" width="48" style="display: block; width: 48px; max-width: 48px; min-width: 48px;">
              	</a>
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
    <!-- end logo -->
<?php } ?>
