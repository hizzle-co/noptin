<template>
	<select style="width: 100%;">
		<slot></slot>
	</select>
</template>

<script>

module.exports = {
	props: ['value'],
	mounted () {
			var that = this

			$(this.$el)

				// init select2
				.select2({ width: 'resolve' })
				.val(this.value)
				.trigger('change')

				// emit event on change.
				.on('change', () => {
					that.$emit('input', this.value)
				})


		},
		watch: {
			value (value) {
				// update value
				$(this.$el)
					.val(value)
					.trigger('change')
			},
		},
		destroyed () {
			$(this.$el).off().select2('destroy')
		}

}
</script>
