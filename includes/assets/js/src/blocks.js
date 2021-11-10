(function (blocks, editor, i18n, element, components, _) {
	var el = element.createElement;
	var RichText = editor.RichText;
	var InspectorControls = editor.InspectorControls;
	var ColorPalette = editor.ColorPalette;
	var TextControl = components.TextControl;

	blocks.registerBlockType('noptin/email-optin', {
		title: i18n.__('Newsletter Optin', 'newsletter-optin-box'),
		icon: 'forms',
		category: 'layout',
		attributes: {
			title: {
				type: 'string',
				source: 'children',
				selector: 'h2',
				default: i18n.__('JOIN OUR NEWSLETTER', 'newsletter-optin-box'),
			},
			description: {
				type: 'string',
				source: 'children',
				default: i18n.__('Click the above title to edit it. You can also edit this section by clicking on it.', 'newsletter-optin-box'),
				selector: '.noptin_form_description',
			},
			button: {
				type: 'string',
				default: 'SUBSCRIBE',
			},
			bg_color: {
				type: 'string',
				default: '#eeeeee',
			},
			title_color: {
				type: 'string',
				default: '#313131',
			},
			text_color: {
				type: 'string',
				default: '#32373c',
			},
			button_color: {
				type: 'string',
				default: '#313131',
			},
			button_text_color: {
				type: 'string',
				default: '#fafafa',
			},
		},
		edit (props) {
			var attributes = props.attributes;

			return [
				el(InspectorControls, { key: 'controls' },

				el(
					components.PanelBody, { 'title': i18n.__('Button Text', 'newsletter-optin-box') },
					el(TextControl, {
						value: attributes.button,
						type: 'text',
						onChange (value) {
							props.setAttributes({ button: value });
						}
					}),
				),

					//Redirect url
					el(
						components.PanelBody, { 'title': i18n.__('Redirect Url', 'newsletter-optin-box'), initialOpen: false },

						el(
							'h2', null,
							i18n.__('Redirect Url', 'newsletter-optin-box')
						),

						el(
							'p', null,
							i18n.__('Optional. Where should we redirect users after they have successfully signed up?', 'newsletter-optin-box')
						),

						el(TextControl, {
							value: attributes.redirect,
							placeholder: 'http://example.com/download/gift.pdf',
							type: 'url',
							onChange (value) {
								props.setAttributes({ redirect: value });
							}
						}),

					),


					//Background color
					el(
						components.PanelBody, { 'title': i18n.__('Background Color', 'newsletter-optin-box'), initialOpen: false },
						el(
							components.PanelRow, null,
							el(
								ColorPalette, {
									onChange (value) {
										props.setAttributes({ bg_color: value });
									}
								}
							)
						)
					),

					//Title color
					el(
						components.PanelBody, { 'title': i18n.__('Title Color', 'newsletter-optin-box'), initialOpen: false },
						el(
							components.PanelRow, null,
							el(
								ColorPalette, {
									onChange (value) {
										props.setAttributes({ title_color: value });
									}
								}
							)
						)
					),

					//Text color
					el(
						components.PanelBody, { 'title': i18n.__('Description Color', 'newsletter-optin-box'), initialOpen: false },
						el(
							components.PanelRow, null,
							el(
								ColorPalette, {
									onChange (value) {
										props.setAttributes({ text_color: value });
									}
								}
							)
						)
					),

					//Button
					el(
						components.PanelBody, { 'title': i18n.__('Button Color', 'nnewsletter-optin-boxoptin'), initialOpen: false },

						//Color
						el(
							'p', null,
							i18n.__('Text Color', 'newsletter-optin-box')
						),

						el(
							ColorPalette, {
								onChange (value) {
									props.setAttributes({ button_text_color: value });
								}
							}
						),

						//Background color
						el(
							'p', null,
							i18n.__('Background Color', 'newsletter-optin-box')
						),
						el(
							ColorPalette, {
								onChange (value) {
									props.setAttributes({ button_color: value });
								}
							}
						)
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
							placeholder: i18n.__('Write Form title…', 'newsletter-optin-box'),
							value: attributes.title,
							className: 'noptin_form_title',
							onChange (value) {
								props.setAttributes({ title: value });
							},
						}),
						el(RichText, {
							tagName: 'p',
							inline: true,
							style: {
								textAlign: 'center',
							},
							placeholder: i18n.__('Write Form Description', 'newsletter-optin-box'),
							value: attributes.description,
							className: 'noptin_form_description',
							onChange (value) {
								props.setAttributes({ description: value });
							},
						}),
						el('input', {
							type: 'email',
							className: 'noptin_form_input_email',
							placeholder: 'Email Address',
							required: true,
						}),
						el('input', {
							value: attributes.button,
							type: 'submit',
							style: {
								backgroundColor: attributes.button_color,
								color: attributes.button_text_color,
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
		save (props) {
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
						}),
						el('input', {
							value: attributes.button,
							type: 'submit',
							style: {
								backgroundColor: attributes.button_color,
								color: attributes.button_text_color,
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
