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

/***/ "./includes/assets/js/src/subscribers.js":
/*!***********************************************!*\
  !*** ./includes/assets/js/src/subscribers.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/dom-ready */ \"@wordpress/dom-ready\");\n/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ \"@wordpress/api-fetch\");\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _utils_fade_out__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./utils/fade-out */ \"./includes/assets/js/src/utils/fade-out.js\");\n\n\n\n\n\n// Init the subscribers page.\n_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0___default()(function () {\n  // Check when .noptin-toggle-subscription-status checkbox changes, save via ajax.\n  document.querySelectorAll('.noptin-toggle-subscription-status').forEach(function (checkbox) {\n    checkbox.addEventListener('change', function () {\n      var isChecked = checkbox.checked;\n      var row = checkbox.closest('tr');\n      var subscriberID = row.dataset.id;\n      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({\n        path: '/noptin/v1/subscribers/' + subscriberID,\n        method: 'POST',\n        data: {\n          status: isChecked ? 'subscribed' : 'unsubscribed'\n        }\n      })[\"catch\"](function (err) {\n        console.log(err);\n      });\n\n      // Get td.column-status and update the inner HTML\n      var statusColumn = row.querySelector('.column-status');\n      if (!statusColumn) {\n        return;\n      }\n      if (isChecked) {\n        statusColumn.innerHTML = '<span class=\"noptin-badge success\">' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Subscribed', 'newsletter-optin-box') + '</span>';\n      } else {\n        statusColumn.innerHTML = '<span class=\"noptin-badge notification\">' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Unsubscribed', 'newsletter-optin-box') + '</span>';\n      }\n    });\n  });\n\n  // Delete subscriber when .noptin-record-action__delete is clicked.\n  document.querySelectorAll('.noptin-record-action__delete').forEach(function (button) {\n    button.addEventListener('click', function (e) {\n      e.preventDefault();\n\n      // Confirm the user wants to delete the subscriber.\n      if (!confirm((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Are you sure you want to delete this subscriber?', 'newsletter-optin-box'))) {\n        return;\n      }\n      var row = button.closest('tr');\n      var subscriberID = row.dataset.id;\n      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({\n        path: '/noptin/v1/subscribers/' + subscriberID,\n        method: 'DELETE'\n      })[\"catch\"](function (err) {\n        console.log(err);\n      });\n      (0,_utils_fade_out__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(row);\n    });\n  });\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/subscribers.js?");

/***/ }),

/***/ "./includes/assets/js/src/utils/fade-out.js":
/*!**************************************************!*\
  !*** ./includes/assets/js/src/utils/fade-out.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ fadeOut)\n/* harmony export */ });\n/**\n * Fade out an element\n *\n * @param {HTMLElement} element\n */\nfunction fadeOut(element) {\n  var opacity = 1;\n  var timer = setInterval(function () {\n    if (opacity <= 0.1) {\n      clearInterval(timer);\n      element.style.display = 'none';\n    }\n    element.style.opacity = opacity;\n    opacity -= opacity * 0.1;\n  }, 10);\n}\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/utils/fade-out.js?");

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/dom-ready":
/*!**********************************!*\
  !*** external ["wp","domReady"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["domReady"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

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
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/subscribers.js");
/******/ 	
/******/ })()
;