const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const VuetifyLoaderPlugin = require('vuetify-loader/lib/plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
	mode: "production",

	optimization: {
		splitChunks: {
			cacheGroups: {
				styles: {
					name: 'modules',
					test: /\.s(c|a)ss$/,
					chunks: 'all',
					enforce: true,
				},
			},
		},
	},

	entry: {
		admin: "./includes/assets/js/src/admin.js",
		settings: "./includes/assets/js/src/settings.js",
		"newsletter-editor": "./includes/assets/js/src/newsletter-editor.js",
		//"optin-editor": "./includes/assets/js/src/optin-editor.js",
		"automation-rules": "./includes/assets/js/src/automation-rules.js",
		frontend: "./includes/assets/js/src/frontend.js",
		helper: "./includes/assets/js/src/helper.js",
		blocks: "./includes/assets/js/src/blocks.js",
		"blocks-new": "./includes/assets/js/src/blocks-new.js",
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
				test: /\.vue$/,
				loader: 'vue-loader'
			},

			{
				test: /\.css$/i,
				use: [
					'style-loader',
					'css-loader'
				],
			},

			{
				test: /\.s(c|a)ss$/,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					{
						loader: 'sass-loader',

						// Requires sass-loader@^8.0.0
						options: {
							implementation: require('sass'),

							// This is the path to your variables
							additionalData: "@import './includes/assets/css/postcss/variables.scss'",

							sassOptions: {
								fiber: require('fibers'),
								indentedSyntax: true // optional
							},
						},
					},
				],
			},

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
		new VueLoaderPlugin(),
		new VuetifyLoaderPlugin(),
		new MiniCssExtractPlugin()
	],
	externals: {
		jquery: 'jQuery',
		vue: 'Vue'
	}
};