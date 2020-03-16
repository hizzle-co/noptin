<template>
	<select style="width: 100%;">
		<slot></slot>
	</select>
</template>

<script>

module.exports = {
	props: ['value','tags'],

	mounted () {

			let tags = this.tags == 'yes'

			jQuery(this.$el)

				// init select2
				.select2(
					{ 
						width: 'resolve',
						tags
					}
				)

				//Sync the current value
				.val(this.value)

				//Then trigger a change event
				.trigger('change.select2')

				// emit input event on change.
				.on('change', ( e ) => {
					this.$emit('input',jQuery(e.currentTarget).val() )
				})


	},

	watch: {
		value (value) {
			// update value
			jQuery(this.$el).val(value).trigger('change.select2')
		},
	},

	destroyed () {
		jQuery(this.$el).off().select2('destroy')
	}

}
</script>
