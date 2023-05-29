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

/***/ "./includes/assets/js/src/view-automation-rules.js":
/*!*********************************************************!*\
  !*** ./includes/assets/js/src/view-automation-rules.js ***!
  \*********************************************************/
/***/ (() => {

eval("(function ($) {\n  // Wait for the dom to load...\n  $(document).ready(function () {\n    // When .noptin-toggle-automation-rule checkbox changes, save via ajax.\n    $('.noptin-toggle-automation-rule').on('change', function () {\n      var isChecked = $(this).is(':checked');\n      var ruleID = $(this).closest('tr').data('id');\n      $.post(noptinViewRules.ajaxurl, {\n        action: 'noptin_toggle_automation_rule',\n        rule_id: ruleID,\n        _ajax_nonce: noptinViewRules.nonce,\n        enabled: isChecked ? 1 : 0\n      })[\"catch\"](function (error) {\n        console.log(error);\n      });\n    });\n\n    // When .noptin-automation-rule-action__delete is clicked, delete via ajax.\n    $('.noptin-automation-rule-action__delete').on('click', function (e) {\n      e.preventDefault();\n\n      // Confirm the user wants to delete the rule.\n      if (!confirm(noptinViewRules.confirmDelete)) {\n        return;\n      }\n      var row = $(this).closest('tr');\n      var ruleID = row.data('id');\n      $.post(noptinViewRules.ajaxurl, {\n        action: 'noptin_delete_automation_rule',\n        rule_id: ruleID,\n        _ajax_nonce: noptinViewRules.nonce\n      });\n\n      // Fade out the row.\n      row.fadeOut(1000, function () {\n        row.remove();\n      });\n    });\n  });\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/view-automation-rules.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./includes/assets/js/src/view-automation-rules.js"]();
/******/ 	
/******/ })()
;