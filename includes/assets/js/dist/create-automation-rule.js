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

/***/ "./includes/assets/js/src/create-automation-rule.js":
/*!**********************************************************!*\
  !*** ./includes/assets/js/src/create-automation-rule.js ***!
  \**********************************************************/
/***/ (() => {

eval("jQuery(function ($) {\n  // Wait for the dom to load...\n  $(document).ready(function () {\n    // Prevent clicks on .noptin-automation-rule-create if the link is disabled.\n    $('.noptin-automation-rule-create').on('click', function (e) {\n      if ($(this).prop('disabled')) {\n        e.preventDefault();\n      }\n    });\n\n    // Listens to select changes and updates the description.\n    var handleSelectChange = function handleSelectChange(e) {\n      // Set up the current trigger and action.\n      var trigger = $('.noptin-automation-rules-dropdown-trigger').val();\n      var action = $('.noptin-automation-rules-dropdown-action').val();\n      var button = $('.noptin-automation-rule-create');\n\n      // Are both of them set-up?\n      if (trigger && action) {\n        button.removeClass('button-secondary disabled').addClass('button-primary');\n\n        // Update the button href.\n        var urlTemplate = button.data(\"\".concat(action, \"-url\"));\n        var url;\n        if (urlTemplate) {\n          url = urlTemplate.replace('NOPTIN_TRIGGER_ID', trigger);\n        } else {\n          var theURL = new URL(button.data('default-url'));\n\n          // Add the trigger.\n          theURL.searchParams.set('noptin-trigger', trigger);\n\n          // Add the action.\n          theURL.searchParams.set('noptin-action', action);\n          url = theURL.toString();\n        }\n\n        // Update the button href.\n        button.attr('href', url);\n      } else {\n        $('.noptin-automation-rule-create').removeClass('button-primary').addClass('button-secondary disabled');\n      }\n    };\n\n    // Attach select2 to the select fields.\n    $('.noptin-automation-rules-dropdown').select2({\n      templateResult: function templateResult(option) {\n        var description = $(option.element).data('description');\n        return $(\"<div><strong>\".concat(option.text, \"</strong><p class=\\\"description\\\">\").concat(description, \"</p></div>\"));\n      },\n      templateSelection: function templateSelection(option) {\n        $(option.element).closest('.noptin-automation-rule-editor-section').find('.noptin-automation-rule-editor-section-description').text($(option.element).data('description'));\n        return option.text;\n      }\n    }).on('change', handleSelectChange).trigger('change');\n  });\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/create-automation-rule.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./includes/assets/js/src/create-automation-rule.js"]();
/******/ 	
/******/ })()
;