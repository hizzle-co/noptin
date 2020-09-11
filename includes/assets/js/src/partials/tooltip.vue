<template>
	<v-tooltip top v-if="tooltip" max-width="400px" open-on-click open-on-focus>
    	<template v-slot:activator="{ on, attrs }">
        	<v-icon color="grey darken" v-bind="attrs" v-on="on">{{mdiInformation}}</v-icon>
      	</template>
		<slot></slot>
    	<span v-html="tooltip"></span>
    </v-tooltip>
</template>

<script>

	import { mdiInformation } from '@mdi/js'

  	export default {
		props: ['tooltip'],

		data: () => ({
			mdiInformation
		}),

	}
	  
</script>
