const path = require('path');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

module.exports = {
	mode: "development",
	entry: {
		admin: "./includes/assets/js/src/admin.js",
		settings: "./includes/assets/js/src/settings.js",
		"newsletter-editor": "./includes/assets/js/src/newsletter-editor.js",
		"optin-editor": "./includes/assets/js/src/optin-editor.js",
		"automation-rules": "./includes/assets/js/src/automation-rules.js",
		frontend: "./includes/assets/js/src/frontend.js",
		blocks: "./includes/assets/js/src/blocks.js",
		subscribers: "./includes/assets/js/src/subscribers.js"
	},
	output: {
		filename: "[name].js",
		path: path.resolve(__dirname, "./includes/assets/js/dist")
	},
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},

			{
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env'],
						cacheDirectory: true
					}
				}
			}
		]
	},
	plugins: [
		new VueLoaderPlugin()
	]
};
