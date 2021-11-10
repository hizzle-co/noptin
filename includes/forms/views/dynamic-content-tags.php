<?php
defined( 'ABSPATH' ) or exit;

$tags = noptin()->forms->get_tags();
?>
<h2><?php echo esc_html__( 'Smart Tags', 'newsletter-optin-box' ); ?></h2>

<p>
	<?php echo esc_html__( 'You can use the following smart tags in your form messages and content to personalize your form.', 'newsletter-optin-box' ); ?>
</p>

<table class="widefat striped">
	<?php
	foreach ( $tags as $tag => $config ) {
		$tag = ! empty( $config['example'] ) ? $config['example'] : $tag;
		?>
		<tr>
			<td>
				<input type="text" class="widefat" value="<?php echo esc_attr( sprintf( '{%s}', $tag ) ); ?>" readonly="readonly" onfocus="this.select();" />
				<p class="description" style="margin-bottom:0;"><?php echo strip_tags( $config['description'], '<strong><b><em><i><a><code>' ); ?></p>
			</td>
		</tr>
		<?php
	}
	?>
</table>
