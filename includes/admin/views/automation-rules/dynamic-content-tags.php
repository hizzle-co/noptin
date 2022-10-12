<?php defined( 'ABSPATH' ) || exit; ?>

<h2><?php echo esc_html__( 'Smart Tags', 'newsletter-optin-box' ); ?></h2>

<p>
	<?php echo esc_html__( 'You can use the following smart tags to generate dynamic values.', 'newsletter-optin-box' ); ?>
</p>

<table class="widefat striped">
	<tr v-for="smartTag in availableSmartTags">
		<td>
			<input type="text" class="widefat" :value="fetchSmartTagExample(smartTag)" readonly="readonly" onfocus="this.select();" />
			<p class="description" style="margin-bottom:0;" v-if="smartTag.label" v-html="smartTag.label"></p>
		</td>
	</tr>
</table>
