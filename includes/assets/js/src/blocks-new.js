(function (registerBlockType, SelectControl, __, forms) {

	registerBlockType('noptin/form', {
		title: __('Newsletter Form', 'newsletter-optin-box'),
		description: __('Displays a newsletter subscription form', 'newsletter-optin-box'),
		category: 'widgets',
		attributes: {
			form: {
				type: 'int',
				default: 0,
			}
		},
		icon: 'forms',
		supports: {
			html: false
		},

		edit: function (props) {

			return (
				<div style={{ backgroundColor: '#f8f9f9', padding: '14px' }}>
					<SelectControl
						label={__( 'Select Newsletter Form', 'newsletter-optin-box' )}
						value={props.attributes.form}
						options={forms}
						onChange={value => {
							props.setAttributes({ form: value })
						}}
					/>
				</div>
			)
		},

		// Render nothing in the saved content, because we render in PHP
		save: function (props) {
			return null
		}
	})
})(
	window.wp.blocks.registerBlockType,
	window.wp.components.SelectControl,
	window.wp.i18n.__,
	window.noptin_forms
);
