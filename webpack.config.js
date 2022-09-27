const path = require('path');
const webpack = require('webpack');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

module.exports = {
	mode: "production",

	entry: {
		admin: "./includes/assets/js/src/admin.js",
		settings: "./includes/assets/js/src/settings.js",
		"newsletter-editor": "./includes/assets/js/src/newsletter-editor.js",
		"edit-automation-rule": "./includes/assets/js/src/edit-automation-rule.js",
		"create-automation-rule": "./includes/assets/js/src/create-automation-rule.js",
		frontend: "./includes/assets/js/src/frontend.js",
		blocks: "./includes/assets/js/src/blocks.js",
		"blocks-new": "./includes/assets/js/src/blocks-new.js",
		"blocks-woocommerce-backend": "./includes/assets/js/src/wc/index.js",
		"blocks-woocommerce-frontend": "./includes/assets/js/src/wc/frontend.js",
		subscribers: "./includes/assets/js/src/subscribers.js",
		"subscribers-import": "./includes/assets/js/src/subscribers-import.js",
		"form-scripts": "./includes/assets/js/src/form-scripts.js",
		"popups": "./includes/assets/js/src/popups.js",
		"form-editor": "./includes/assets/js/src/form-editor.js",
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
	},
	plugins: [
		new webpack.DefinePlugin({
			__VUE_OPTIONS_API__: true,
			__VUE_PROD_DEVTOOLS__: false,
		}),
		new WooCommerceDependencyExtractionWebpackPlugin()
	],
	externals: {
		jquery: 'jQuery'
	},
	resolve: {
		alias: {
			vue: "vue/dist/vue.esm-bundler.js"
		},
	},
};