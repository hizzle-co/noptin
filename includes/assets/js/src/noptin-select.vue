<template>
	<select style="width: 100%;">
		<slot></slot>
	</select>
</template>

<script>

module.exports = {
	props: ['value'],

	mounted () {

			$(this.$el)

				// init select2
				.select2({ width: 'resolve' })

				//Sync the current value
				.val(this.value)

				//Then trigger a change event
				.trigger('change')

				// emit input event on change.
				.on('change', ( e ) => {
					this.$emit('input', $(e.currentTarget).val() )
				})


	},

	watch: {
		value (value) {
			// update value
			$(this.$el).val(value).trigger('change')
		},
	},

	destroyed () {
		$(this.$el).off().select2('destroy')
	}

}
</script>
