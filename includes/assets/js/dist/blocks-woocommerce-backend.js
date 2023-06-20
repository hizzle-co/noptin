/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./includes/assets/js/src/wc/edit.js":
/*!*******************************************!*\
  !*** ./includes/assets/js/src/wc/edit.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   Edit: () => (/* binding */ Edit),\n/* harmony export */   Save: () => (/* binding */ Save)\n/* harmony export */ });\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ \"@wordpress/block-editor\");\n/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/settings */ \"@woocommerce/settings\");\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__);\n\n/**\r\n * External dependencies\r\n */\n\n\n\n\n\n// Prepare env.\nvar _getSetting = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__.getSetting)('noptin_data'),\n  adminUrl = _getSetting.adminUrl;\nvar Edit = function Edit() {\n  var blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.useBlockProps)();\n  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(\"div\", blockProps, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Placeholder, {\n    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Noptin Newsletter', 'newsletter-optin-box'),\n    className: \"wp-block-noptin-newsletter-block-placeholder\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(\"span\", {\n    className: \"wp-block-noptin-newsletter-block-placeholder__description\",\n    style: {\n      display: 'block',\n      margin: '0 0 1em'\n    }\n  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('If the Noptin newsletter subscription checkbox is enabled, it will appear here.', 'newsletter-optin-box')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {\n    isPrimary: true,\n    href: \"\".concat(adminUrl, \"admin.php?page=noptin-settings&tab=integrations&section=woocommerce#noptin-settings-section-settings_section_woocommerce\"),\n    target: \"_blank\",\n    rel: \"noopener noreferrer\",\n    className: \"wp-block-mailpoet-newsletter-block-placeholder__button\"\n  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enable/Disable', 'newsletter-optin-box'))));\n};\nvar Save = function Save() {\n  return null;\n};\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/wc/edit.js?");

/***/ }),

/***/ "./includes/assets/js/src/wc/index.js":
/*!********************************************!*\
  !*** ./includes/assets/js/src/wc/index.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @woocommerce/settings */ \"@woocommerce/settings\");\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/blocks */ \"@wordpress/blocks\");\n/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./edit */ \"./includes/assets/js/src/wc/edit.js\");\n/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./block.json */ \"./includes/assets/js/src/wc/block.json\");\n\n/**\r\n * External dependencies\r\n */\n\n\n\n\n/**\r\n * Internal dependencies\r\n */\n\n\n\n// Prepare env.\nvar _getSetting = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__.getSetting)('noptin_data'),\n  position = _getSetting.position;\n_block_json__WEBPACK_IMPORTED_MODULE_5__.parent = [position];\n\n// Register the block.\n(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_5__, {\n  icon: {\n    src: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SVG, {\n      xmlns: \"http://www.w3.org/2000/svg\",\n      viewBox: \"0 0 20 16\"\n    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(\"g\", {\n      fill: \"none\",\n      fillRule: \"evenodd\"\n    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(\"path\", {\n      stroke: \"currentColor\",\n      strokeWidth: \"1.5\",\n      d: \"M2 .75h16c.69 0 1.25.56 1.25 1.25v12c0 .69-.56 1.25-1.25 1.25H2c-.69 0-1.25-.56-1.25-1.25V2C.75 1.31 1.31.75 2 .75z\"\n    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(\"path\", {\n      fill: \"currentColor\",\n      d: \"M7.667 7.667A2.34 2.34 0 0010 5.333 2.34 2.34 0 007.667 3a2.34 2.34 0 00-2.334 2.333 2.34 2.34 0 002.334 2.334zM11.556 3H17v3.889h-5.444V3zm2.722 2.916l1.944-1.36v-.779L14.278 5.14l-1.945-1.362v.778l1.945 1.361zm-5.834-.583a.78.78 0 00-.777-.777.78.78 0 00-.778.777c0 .428.35.778.778.778a.78.78 0 00.777-.778zm3.89 5.904c0-1.945-3.088-2.785-4.667-2.785-1.58 0-4.667.84-4.667 2.785v1.097h9.333v-1.097zM7.666 10c-1.012 0-2.163.389-2.738.778h5.475C9.821 10.38 8.678 10 7.667 10z\"\n    }))),\n    foreground: '#874FB9'\n  },\n  edit: _edit__WEBPACK_IMPORTED_MODULE_4__.Edit,\n  save: _edit__WEBPACK_IMPORTED_MODULE_4__.Save\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/wc/index.js?");

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./includes/assets/js/src/wc/block.json":
/*!**********************************************!*\
  !*** ./includes/assets/js/src/wc/block.json ***!
  \**********************************************/
/***/ ((module) => {

eval("module.exports = JSON.parse('{\"apiVersion\":2,\"name\":\"noptin/checkout-newsletter-subscription\",\"version\":\"1.0.0\",\"title\":\"Noptin Newsletter Subscription\",\"category\":\"woocommerce\",\"description\":\"Adds a newsletter subscription checkbox to the checkout.\",\"icon\":\"forms\",\"textdomain\":\"newsletter-optin-box\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false,\"inserter\":false},\"attributes\":{\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":true}},\"text\":{\"type\":\"string\",\"source\":\"html\",\"selector\":\".wp-block-woocommerce-checkout-noptin-newsletter-subscription\",\"default\":\"\"}},\"parent\":[\"woocommerce/checkout-contact-information-block\"]}');\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/wc/block.json?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/wc/index.js");
/******/ 	
/******/ })()
;