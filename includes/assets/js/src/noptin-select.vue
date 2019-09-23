<template>
	<select style="width: 100%;">
		<slot></slot>
	</select>
</template>

<script>

module.exports = {
	props: ['value'],
	mounted: function () {
			var that = this

			$(this.$el)

				// init select2
				.select2({ width: 'resolve' })
				.val(this.value)
				.trigger('change')

				// emit event on change.
				.on('change', function () {
					that.$emit('input', this.value)
				})


		},
		watch: {
			value: function (value) {
				// update value
				$(this.$el)
					.val(value)
					.trigger('change')
			},
		},
		destroyed: function () {
			$(this.$el).off().select2('destroy')
		}

}
</script>
