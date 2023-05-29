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

/***/ "./includes/assets/js/src/wc/block.js":
/*!********************************************!*\
  !*** ./includes/assets/js/src/wc/block.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ \"./node_modules/@babel/runtime/helpers/esm/slicedToArray.js\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ \"@woocommerce/blocks-checkout\");\n/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/settings */ \"@woocommerce/settings\");\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__);\n\n\n/**\r\n * External dependencies\r\n */\n\n\n\nvar _getSetting = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__.getSetting)('noptin_data', ''),\n  optinEnabled = _getSetting.optinEnabled,\n  defaultText = _getSetting.defaultText,\n  defaultStatus = _getSetting.defaultStatus;\nvar Block = function Block(_ref) {\n  var checkoutExtensionData = _ref.checkoutExtensionData;\n  if (!optinEnabled) {\n    return null;\n  }\n  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(defaultStatus),\n    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(_useState, 2),\n    checked = _useState2[0],\n    setChecked = _useState2[1];\n  var _ref2 = checkoutExtensionData || {},\n    setExtensionData = _ref2.setExtensionData;\n  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {\n    setExtensionData('noptin', 'optin', checked);\n  }, [checked, setExtensionData]);\n  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_2__.CheckboxControl, {\n    className: \"wc-block-components-noptin-newsletter-subscription\",\n    checked: checked,\n    onChange: setChecked\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.RawHTML, null, defaultText));\n};\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Block);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/wc/block.js?");

/***/ }),

/***/ "./includes/assets/js/src/wc/frontend.js":
/*!***********************************************!*\
  !*** ./includes/assets/js/src/wc/frontend.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ \"@woocommerce/blocks-checkout\");\n/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @woocommerce/settings */ \"@woocommerce/settings\");\n/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _block__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block */ \"./includes/assets/js/src/wc/block.js\");\n/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ \"./includes/assets/js/src/wc/block.json\");\n/**\r\n * External dependencies\r\n */\n\n\n\n/**\r\n * Internal dependencies\r\n */\n\n\n\n// Prepare env.\nvar _getSetting = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__.getSetting)('noptin_data'),\n  position = _getSetting.position;\n_block_json__WEBPACK_IMPORTED_MODULE_3__.parent = [position];\n(0,_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__.registerCheckoutBlock)({\n  metadata: _block_json__WEBPACK_IMPORTED_MODULE_3__,\n  component: _block__WEBPACK_IMPORTED_MODULE_2__[\"default\"]\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/wc/frontend.js?");

/***/ }),

/***/ "@woocommerce/blocks-checkout":
/*!****************************************!*\
  !*** external ["wc","blocksCheckout"] ***!
  \****************************************/
/***/ ((module) => {

module.exports = window["wc"]["blocksCheckout"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _arrayLikeToArray)\n/* harmony export */ });\nfunction _arrayLikeToArray(arr, len) {\n  if (len == null || len > arr.length) len = arr.length;\n  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];\n  return arr2;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _arrayWithHoles)\n/* harmony export */ });\nfunction _arrayWithHoles(arr) {\n  if (Array.isArray(arr)) return arr;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _iterableToArrayLimit)\n/* harmony export */ });\nfunction _iterableToArrayLimit(arr, i) {\n  var _i = null == arr ? null : \"undefined\" != typeof Symbol && arr[Symbol.iterator] || arr[\"@@iterator\"];\n  if (null != _i) {\n    var _s,\n      _e,\n      _x,\n      _r,\n      _arr = [],\n      _n = !0,\n      _d = !1;\n    try {\n      if (_x = (_i = _i.call(arr)).next, 0 === i) {\n        if (Object(_i) !== _i) return;\n        _n = !1;\n      } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0);\n    } catch (err) {\n      _d = !0, _e = err;\n    } finally {\n      try {\n        if (!_n && null != _i[\"return\"] && (_r = _i[\"return\"](), Object(_r) !== _r)) return;\n      } finally {\n        if (_d) throw _e;\n      }\n    }\n    return _arr;\n  }\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js":
/*!********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _nonIterableRest)\n/* harmony export */ });\nfunction _nonIterableRest() {\n  throw new TypeError(\"Invalid attempt to destructure non-iterable instance.\\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.\");\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/slicedToArray.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _slicedToArray)\n/* harmony export */ });\n/* harmony import */ var _arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayWithHoles.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js\");\n/* harmony import */ var _iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./iterableToArrayLimit.js */ \"./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js\");\n/* harmony import */ var _unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./unsupportedIterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js\");\n/* harmony import */ var _nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./nonIterableRest.js */ \"./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js\");\n\n\n\n\nfunction _slicedToArray(arr, i) {\n  return (0,_arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr) || (0,_iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(arr, i) || (0,_unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(arr, i) || (0,_nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])();\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/slicedToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _unsupportedIterableToArray)\n/* harmony export */ });\n/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayLikeToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js\");\n\nfunction _unsupportedIterableToArray(o, minLen) {\n  if (!o) return;\n  if (typeof o === \"string\") return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(o, minLen);\n  var n = Object.prototype.toString.call(o).slice(8, -1);\n  if (n === \"Object\" && o.constructor) n = o.constructor.name;\n  if (n === \"Map\" || n === \"Set\") return Array.from(o);\n  if (n === \"Arguments\" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(o, minLen);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js?");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/wc/frontend.js");
/******/ 	
/******/ })()
;