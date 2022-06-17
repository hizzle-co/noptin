<?php defined( 'ABSPATH' ) || exit; ?>

<h2><?php echo esc_html__( 'Smart Tags', 'newsletter-optin-box' ); ?></h2>

<p>
	<?php echo esc_html__( 'You can use the following smart tags in your form messages and content to personalize your form.', 'newsletter-optin-box' ); ?>
</p>

<table class="widefat striped">
	<?php
	foreach ( noptin()->forms->get_tags() as $form_tag => $config ) {
		$form_tag = ! empty( $config['example'] ) ? $config['example'] : $form_tag;
		?>
		<tr>
			<td>
				<input type="text" class="widefat" value="<?php echo esc_attr( sprintf( '{%s}', $form_tag ) ); ?>" readonly="readonly" onfocus="this.select();" />
				<p class="description" style="margin-bottom:0;"><?php echo wp_kses_post( $config['description'] ); ?></p>
			</td>
		</tr>
		<?php
	}
	?>
</table>
