(function (blocks, editor, i18n, element, components, _) {
	var el = element.createElement;
	var RichText = editor.RichText;
	var MediaUpload = editor.MediaUpload;
	var InspectorControls = editor.InspectorControls;
	var ColorPalette = editor.ColorPalette;
	var FontSizePicker = editor.FontSizePicker;
	var AlignmentToolbar = editor.AlignmentToolbar;
	var MediaUpload = editor.MediaUpload;

	var CButton = components.Button;
	var CColor = components.ColorPicker;
	var TextControl = components.TextControl;

	blocks.registerBlockType('noptin/email-optin', {
		title: i18n.__('Newsletter Optin', 'noptin'),
		icon: 'forms',
		category: 'layout',
		attributes: {
			title: {
				type: 'string',
				source: 'children',
				selector: 'h2',
				default: i18n.__('JOIN OUR NEWSLETTER', 'noptin'),
			},
			description: {
				type: 'string',
				source: 'children',
				default: i18n.__('Click the above title to edit it. You can also edit this section by clicking on it.', 'noptin'),
				selector: '.noptin_form_description',
			},
			button: {
				type: 'string',
				default: 'SUBSCRIBE',
			},
			bg_color: {
				type: 'string',
				default: '#fafafa',
			},
			title_color: {
				type: 'string',
				default: '#0085ba',
			},
			text_color: {
				type: 'string',
				default: '#32373c',
			},
			button_color: {
				type: 'string',
				default: 'rgb(255, 105, 0)',
			},
			button_text_color: {
				type: 'string',
				default: '#fafafa',
			},
		},
		edit: function (props) {
			var attributes = props.attributes;

			return [
				el(InspectorControls, { key: 'controls' },
					el(
						'h2', null,
						i18n.__('Button Text', 'noptin')
					),
					el(TextControl, {
						value: attributes.button,
						type: 'text',
						onChange: function (value) {
							props.setAttributes({ button: value });
						}
					}),
					el(
						'h2', null,
						i18n.__('Redirect Url', 'noptin')
					),
					el(
						'p', null,
						i18n.__('Optional. Where should we redirect users after they have successfully signed up?', 'noptin')
					),
					el(TextControl, {
						value: attributes.redirect,
						placeholder: 'http://example.com/download/gift.pdf',
						type: 'url',
						onChange: function (value) {
							props.setAttributes({ redirect: value });
						}
					}),
					el(
						'h2', null,
						i18n.__('Background Color', 'noptin')
					),
					el(
						ColorPalette, {
							onChange: function (value) {
								props.setAttributes({ bg_color: value });
							}
						}
					),

					el(
						'h2', null,
						i18n.__('Title Color', 'noptin')
					),
					el(
						ColorPalette, {
							onChange: function (value) {
								props.setAttributes({ title_color: value });
							}
						}
					),
					el(
						'h2', null,
						i18n.__('Text Color', 'noptin')
					),
					el(
						ColorPalette, {
							onChange: function (value) {
								props.setAttributes({ text_color: value });
							}
						}
					),

					el(
						'h2', null,
						i18n.__('Button Color', 'noptin')
					),
					el(
						ColorPalette, {
							onChange: function (value) {
								props.setAttributes({ button_color: value });
							}
						}
					),
					el(
						'h2', null,
						i18n.__('Button Text Color', 'noptin')
					),
					el(
						ColorPalette, {
							onChange: function (value) {
								props.setAttributes({ button_text_color: value });
							}
						}
					),
				),
				el('div', {
					className: props.className,
					style: {
						backgroundColor: attributes.bg_color,
						padding: '20px',
						color: attributes.text_color,

					}
				},
					el('form', {},
						el(RichText, {
							tagName: 'h2',
							inline: true,
							style: {
								color: attributes.title_color,
								textAlign: 'center',
							},
							placeholder: i18n.__('Write Form title…', 'noptin'),
							value: attributes.title,
							className: 'noptin_form_title',
							onChange: function (value) {
								props.setAttributes({ title: value });
							},
						}),
						el(RichText, {
							tagName: 'p',
							inline: true,
							style: {
								textAlign: 'center',
							},
							placeholder: i18n.__('Write Form Description', 'noptin'),
							value: attributes.description,
							className: 'noptin_form_description',
							onChange: function (value) {
								props.setAttributes({ description: value });
							},
						}),
						el('input', {
							type: 'email',
							className: 'noptin_form_input_email',
							placeholder: 'Email Address',
							required: true,
							style: {
								backgroundColor: 'rgba(255, 255, 255, 0.8)',
								width: '100%',
								display: 'block',
								textAlign: 'left',
								border: 'none',
								color: '#111'
							},
						}),
						el('input', {
							value: attributes.button,
							type: 'submit',
							style: {
								backgroundColor: attributes.button_color,
								padding: '5px',
								width: '100%',
								display: 'block',
								border: 'none',
								color: attributes.button_text_color,
								textAlign: 'center',
								fontWeight: '700',
								marginTop: '1em',
							},
							className: 'noptin_form_submit'
						}),
						el('div', {
							style: {
								border: '1px solid rgba(6, 147, 227, 0.8)',
								display: 'none',
								padding: '10px',
								marginTop: '10px',
							},
							className: 'noptin_feedback_success'
						}),
						el('div', {
							style: {
								border: '1px solid rgba(227, 6, 37, 0.8)',
								display: 'none',
								padding: '10px',
								marginTop: '10px',
							},
							className: 'noptin_feedback_error'
						}),
					),
				)
			]
		},
		save: function (props) {
			var attributes = props.attributes;

			return (
				el('div', {
					className: props.className,
					style: {
						backgroundColor: attributes.bg_color,
						padding: '20px',
						color: attributes.text_color,

					}
				},
					el('form', {},
						el(RichText.Content, {
							tagName: 'h2',
							inline: true,
							style: {
								color: attributes.title_color,
								textAlign: 'center',
							},
							value: attributes.title,
							className: 'noptin_form_title',
						}),
						el(RichText.Content, {
							tagName: 'p',
							inline: true,
							style: {
								textAlign: 'center',
							},
							value: attributes.description,
							className: 'noptin_form_description',
						}),
						el('input', {
							type: 'email',
							className: 'noptin_form_input_email',
							placeholder: 'Email Address',
							required: true,
							style: {
								backgroundColor: 'rgba(254, 254, 254, 0.5)',
								width: '100%',
								display: 'block',
								color: '#111',
								border: 'none',
								textAlign: 'left',
							},
						}),
						el('input', {
							value: attributes.button,
							type: 'submit',
							style: {
								backgroundColor: attributes.button_color,
								width: '100%',
								display: 'block',
								border: 'none',
								color: attributes.button_text_color,
								textAlign: 'center',
								fontWeight: '700',
								marginTop: '1em',
							},
							className: 'noptin_form_submit'
						}),
						el('input', {
							value: attributes.redirect,
							type: 'hidden',
							className: 'noptin_form_redirect'
						}),
						el('div', {
							style: {
								border: '1px solid rgba(6, 147, 227, 0.8)',
								display: 'none',
								padding: '10px',
								marginTop: '10px',
							},
							className: 'noptin_feedback_success'
						}),
						el('div', {
							style: {
								border: '1px solid rgba(227, 6, 37, 0.8)',
								display: 'none',
								padding: '10px',
								marginTop: '10px',
							},
							className: 'noptin_feedback_error'
						}),
					),
				)
			);
		},
	});

})(
	window.wp.blocks,
	window.wp.editor,
	window.wp.i18n,
	window.wp.element,
	window.wp.components,
	window._,
);
