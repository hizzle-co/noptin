const path = require('path');

module.exports = {
	mode: "production",

	entry: {
		admin: "./includes/assets/js/src/admin.js",
		settings: "./includes/assets/js/src/settings.js",
		"newsletter-editor": "./includes/assets/js/src/newsletter-editor.js",
		"automation-rules": "./includes/assets/js/src/automation-rules.js",
		helper: "./includes/assets/js/src/helper.js",
		blocks: "./includes/assets/js/src/blocks.js",
		subscribers: "./includes/assets/js/src/subscribers.js",
		"subscribers-import": "./includes/assets/js/src/subscribers-import.js",
		"form-scripts": "./includes/assets/js/src/form-scripts.js",
	},
	output: {
		filename: "[name].js",
		path: path.resolve(__dirname, "./includes/assets/js/dist")
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env', '@wordpress/babel-preset-default'],
						cacheDirectory: true
					}
				}
			}
		]
	}
};
