const globals = require('globals');
const wordpress = require('@wordpress/eslint-plugin');

module.exports = [
	...wordpress.configs.recommended,
	{
		ignores: ['**/build-types/**'],
	},
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.jquery,
				...globals.node,
				wp: 'writable',
			},
		},
		rules: {
			camelcase: 'warn',
			eqeqeq: 'warn',
			'no-console': 'warn',
			'@wordpress/no-unused-vars-before-return': 'off',
			'@wordpress/no-unsafe-wp-apis': 'off',
			'react/react-in-jsx-scope': 'off',
		},
	},
	{
		files: ['**/*.ts', '**/*.tsx'],
		rules: {
			'@typescript-eslint/explicit-module-boundary-types': 'off',
			'@typescript-eslint/no-explicit-any': 'off',
		},
	},
];
