const path = require('path');
const webpack = require('webpack');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

module.exports = {

	entry: {
		admin: "./includes/assets/js/src/admin.js",
		"blocks-woocommerce-backend": "./includes/assets/js/src/wc/index.js",
		"blocks-woocommerce-frontend": "./includes/assets/js/src/wc/frontend.js",
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
			},
			{
				test: /\.css$/,
				use: ['style-loader', 'css-loader'],
			},
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
};