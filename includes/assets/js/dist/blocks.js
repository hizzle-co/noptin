/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./includes/assets/js/src/blocks.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./includes/assets/js/src/blocks.js":
/*!******************************************!*\
  !*** ./includes/assets/js/src/blocks.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function (blocks, editor, i18n, element, components, _) {\n  var el = element.createElement;\n  var RichText = editor.RichText;\n  var InspectorControls = editor.InspectorControls;\n  var ColorPalette = editor.ColorPalette;\n  var TextControl = components.TextControl;\n  blocks.registerBlockType('noptin/email-optin', {\n    title: i18n.__('Newsletter Optin', 'noptin'),\n    icon: 'forms',\n    category: 'layout',\n    attributes: {\n      title: {\n        type: 'string',\n        source: 'children',\n        selector: 'h2',\n        \"default\": i18n.__('JOIN OUR NEWSLETTER', 'noptin')\n      },\n      description: {\n        type: 'string',\n        source: 'children',\n        \"default\": i18n.__('Click the above title to edit it. You can also edit this section by clicking on it.', 'noptin'),\n        selector: '.noptin_form_description'\n      },\n      button: {\n        type: 'string',\n        \"default\": 'SUBSCRIBE'\n      },\n      bg_color: {\n        type: 'string',\n        \"default\": '#eeeeee'\n      },\n      title_color: {\n        type: 'string',\n        \"default\": '#313131'\n      },\n      text_color: {\n        type: 'string',\n        \"default\": '#32373c'\n      },\n      button_color: {\n        type: 'string',\n        \"default\": '#313131'\n      },\n      button_text_color: {\n        type: 'string',\n        \"default\": '#fafafa'\n      }\n    },\n    edit: function edit(props) {\n      var attributes = props.attributes;\n      return [el(InspectorControls, {\n        key: 'controls'\n      }, el(components.PanelBody, {\n        'title': i18n.__('Button Text', 'noptin')\n      }, el(TextControl, {\n        value: attributes.button,\n        type: 'text',\n        onChange: function onChange(value) {\n          props.setAttributes({\n            button: value\n          });\n        }\n      })), //Redirect url\n      el(components.PanelBody, {\n        'title': i18n.__('Redirect Url', 'noptin'),\n        initialOpen: false\n      }, el('h2', null, i18n.__('Redirect Url', 'noptin')), el('p', null, i18n.__('Optional. Where should we redirect users after they have successfully signed up?', 'noptin')), el(TextControl, {\n        value: attributes.redirect,\n        placeholder: 'http://example.com/download/gift.pdf',\n        type: 'url',\n        onChange: function onChange(value) {\n          props.setAttributes({\n            redirect: value\n          });\n        }\n      })), //Background color\n      el(components.PanelBody, {\n        'title': i18n.__('Background Color', 'noptin'),\n        initialOpen: false\n      }, el(components.PanelRow, null, el(ColorPalette, {\n        onChange: function onChange(value) {\n          props.setAttributes({\n            bg_color: value\n          });\n        }\n      }))), //Title color\n      el(components.PanelBody, {\n        'title': i18n.__('Title Color', 'noptin'),\n        initialOpen: false\n      }, el(components.PanelRow, null, el(ColorPalette, {\n        onChange: function onChange(value) {\n          props.setAttributes({\n            title_color: value\n          });\n        }\n      }))), //Text color\n      el(components.PanelBody, {\n        'title': i18n.__('Description Color', 'noptin'),\n        initialOpen: false\n      }, el(components.PanelRow, null, el(ColorPalette, {\n        onChange: function onChange(value) {\n          props.setAttributes({\n            text_color: value\n          });\n        }\n      }))), //Button\n      el(components.PanelBody, {\n        'title': i18n.__('Button Color', 'noptin'),\n        initialOpen: false\n      }, //Color\n      el('p', null, i18n.__('Text Color', 'noptin')), el(ColorPalette, {\n        onChange: function onChange(value) {\n          props.setAttributes({\n            button_text_color: value\n          });\n        }\n      }), //Background color\n      el('p', null, i18n.__('Background Color', 'noptin')), el(ColorPalette, {\n        onChange: function onChange(value) {\n          props.setAttributes({\n            button_color: value\n          });\n        }\n      }))), el('div', {\n        className: props.className,\n        style: {\n          backgroundColor: attributes.bg_color,\n          padding: '20px',\n          color: attributes.text_color\n        }\n      }, el('form', {}, el(RichText, {\n        tagName: 'h2',\n        inline: true,\n        style: {\n          color: attributes.title_color,\n          textAlign: 'center'\n        },\n        placeholder: i18n.__('Write Form title…', 'noptin'),\n        value: attributes.title,\n        className: 'noptin_form_title',\n        onChange: function onChange(value) {\n          props.setAttributes({\n            title: value\n          });\n        }\n      }), el(RichText, {\n        tagName: 'p',\n        inline: true,\n        style: {\n          textAlign: 'center'\n        },\n        placeholder: i18n.__('Write Form Description', 'noptin'),\n        value: attributes.description,\n        className: 'noptin_form_description',\n        onChange: function onChange(value) {\n          props.setAttributes({\n            description: value\n          });\n        }\n      }), el('input', {\n        type: 'email',\n        className: 'noptin_form_input_email',\n        placeholder: 'Email Address',\n        required: true\n      }), el('input', {\n        value: attributes.button,\n        type: 'submit',\n        style: {\n          backgroundColor: attributes.button_color,\n          color: attributes.button_text_color\n        },\n        className: 'noptin_form_submit'\n      }), el('div', {\n        style: {\n          border: '1px solid rgba(6, 147, 227, 0.8)',\n          display: 'none',\n          padding: '10px',\n          marginTop: '10px'\n        },\n        className: 'noptin_feedback_success'\n      }), el('div', {\n        style: {\n          border: '1px solid rgba(227, 6, 37, 0.8)',\n          display: 'none',\n          padding: '10px',\n          marginTop: '10px'\n        },\n        className: 'noptin_feedback_error'\n      })))];\n    },\n    save: function save(props) {\n      var attributes = props.attributes;\n      return el('div', {\n        className: props.className,\n        style: {\n          backgroundColor: attributes.bg_color,\n          padding: '20px',\n          color: attributes.text_color\n        }\n      }, el('form', {}, el(RichText.Content, {\n        tagName: 'h2',\n        inline: true,\n        style: {\n          color: attributes.title_color,\n          textAlign: 'center'\n        },\n        value: attributes.title,\n        className: 'noptin_form_title'\n      }), el(RichText.Content, {\n        tagName: 'p',\n        inline: true,\n        style: {\n          textAlign: 'center'\n        },\n        value: attributes.description,\n        className: 'noptin_form_description'\n      }), el('input', {\n        type: 'email',\n        className: 'noptin_form_input_email',\n        placeholder: 'Email Address',\n        required: true\n      }), el('input', {\n        value: attributes.button,\n        type: 'submit',\n        style: {\n          backgroundColor: attributes.button_color,\n          color: attributes.button_text_color\n        },\n        className: 'noptin_form_submit'\n      }), el('input', {\n        value: attributes.redirect,\n        type: 'hidden',\n        className: 'noptin_form_redirect'\n      }), el('div', {\n        style: {\n          border: '1px solid rgba(6, 147, 227, 0.8)',\n          display: 'none',\n          padding: '10px',\n          marginTop: '10px'\n        },\n        className: 'noptin_feedback_success'\n      }), el('div', {\n        style: {\n          border: '1px solid rgba(227, 6, 37, 0.8)',\n          display: 'none',\n          padding: '10px',\n          marginTop: '10px'\n        },\n        className: 'noptin_feedback_error'\n      })));\n    }\n  });\n})(window.wp.blocks, window.wp.editor, window.wp.i18n, window.wp.element, window.wp.components, window._);\n\n//# sourceURL=webpack:///./includes/assets/js/src/blocks.js?");

/***/ })

/******/ });