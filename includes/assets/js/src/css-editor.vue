<template>
	<textarea :id="id">
		<slot></slot>
	</textarea>
</template>

<script>

var editorInstances = {}

module.exports = {
	props: ['value', 'id'],
	mounted: function () {
		var vmEditor = this
		var el = jQuery(this.$el)
		var editor = wp.codeEditor.initialize(el)
		editor.codemirror.on('change', function (cm, change) {
			vmEditor.$emit('input', cm.getValue())
		})
		editorInstances[this.id] = editor
		editorInstances[this.id].codemirror.getDoc().setValue(this.value);
	},
	watch: {
		value: function (value) {
			if (editorInstances[this.id]) {

				var editor = editorInstances[this.id].codemirror.getDoc()

				//set if values are not ===
				if (value != editor.getValue()) {
					editor.setValue(value)
				}

			}
		},
	}

}
</script>
