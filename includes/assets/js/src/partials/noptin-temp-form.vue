<template>
	<form @submit.prevent=''>
		<slot></slot>
	</form>
</template>

<script>

module.exports = {
	data() {
		return {}
	}
}
</script>
