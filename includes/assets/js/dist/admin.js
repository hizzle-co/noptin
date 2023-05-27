/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./includes/assets/js/src/admin.js":
/*!*****************************************!*\
  !*** ./includes/assets/js/src/admin.js ***!
  \*****************************************/
/***/ (() => {

eval("(function ($) {\n  // Settings app.\n  if ('undefined' == typeof noptinSettings) {\n    window.noptinSettings = {};\n  }\n\n  // Global noptin object.\n  window.noptin = window.noptin || {};\n\n  // Wait for the dom to load...\n  $(document).ready(function () {\n    // ... then init tooltips...\n    if ($.fn.tooltipster) {\n      $('.noptin-tip').tooltipster();\n    }\n\n    // ... and select 2.\n    if ($.fn.select2) {\n      $('.noptin-select2').each(function () {\n        var options = {\n          dropdownParent: $('#noptin-wrapper'),\n          width: 'resolve',\n          createTag: function createTag(params) {\n            var term = $.trim(params.term);\n            if (term === '') {\n              return null;\n            }\n            return {\n              id: term,\n              text: term,\n              newTag: true // add additional parameters\n            };\n          }\n        };\n\n        var messages = $(this).data('messages');\n        if (messages) {\n          options.language = {};\n          Object.keys(messages).forEach(function (key) {\n            options.language[key] = function () {\n              return messages[key];\n            };\n          });\n        }\n        $(this).select2(options);\n      });\n    }\n  });\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/admin.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./includes/assets/js/src/admin.js"]();
/******/ 	
/******/ })()
;