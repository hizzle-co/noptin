import Vue from 'vue'
import App from './optin-editor-app.vue'
import vuetify from './vuetify';
import contenteditable from 'vue-contenteditable'

var editor = jQuery('#noptin_form_editor').clone().removeClass('postbox')
jQuery('.post-type-noptin-form #post').replaceWith( editor )

Vue.filter('capitalize', function (value) {
	if (!value) return ''
	value = value.toString()
	return value.charAt(0).toUpperCase() + value.slice(1)
})

Vue.use(contenteditable)

export default new Vue({
	vuetify,
	render: h => h(App),
	data: {},
}).$mount('#noptin-form-editor')
