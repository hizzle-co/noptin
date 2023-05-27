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

/***/ "./includes/assets/js/src/form-editor.js":
/*!***********************************************!*\
  !*** ./includes/assets/js/src/form-editor.js ***!
  \***********************************************/
/***/ (() => {

eval("(function ($) {\n  // Switch tabs.\n  $('#noptin-form-editor-new .noptin-tab-button').on('click', function (e) {\n    e.preventDefault();\n    var id = $(this).data('id');\n    var list = $(this).closest('.noptin-tab-list');\n    var tab = $(this).parent();\n\n    // Abort if the tab is active.\n    if (tab.hasClass('active')) {\n      return;\n    }\n\n    // Change active/inactive tab classes.\n    list.find(\".active\").removeClass('active');\n    tab.addClass('active');\n\n    // Hide/show tab content.\n    $(\".noptin-form-tab-content-active\").removeClass('noptin-form-tab-content-active');\n    $(\".noptin-form-tab-content-\".concat(id)).addClass('noptin-form-tab-content-active');\n\n    // Update document title.\n    var tab_title = $('.noptin-form-tab-content-active h2:first-of-type').text();\n    if (tab_title) {\n      var title = document.title.split('-');\n      document.title = document.title.replace(title[0], tab_title + ' ');\n    }\n\n    // Update address bar.\n    if (window.history.replaceState) {\n      window.history.replaceState(id, tab_title, $(this).attr('href'));\n    }\n    $(this).closest('form').attr('action', $(this).attr('href'));\n  });\n\n  // Toggle accordions.\n  $('#noptin-form-editor-app').on('click', '#noptin-form-editor-container .noptin-accordion-trigger', function (e) {\n    e.preventDefault();\n    var panel = $(this).closest('.noptin-settings-panel'),\n      button = panel.find('.noptin-accordion-trigger'),\n      isExpanded = 'true' === button.attr('aria-expanded');\n    if (isExpanded) {\n      button.attr('aria-expanded', 'false');\n      panel.addClass('noptin-settings-panel__hidden', true);\n    } else {\n      button.attr('aria-expanded', 'true');\n      panel.removeClass('noptin-settings-panel__hidden', false);\n    }\n  });\n\n  // Warn if a user is leaving the page without saving changes.\n  var isSaving = false;\n  var initialState = $('.post-type-noptin-form #post').serialize();\n  jQuery(window).on('beforeunload', function (e) {\n    var currentState = $('.post-type-noptin-form #post').serialize();\n    if (!isSaving && initialState != currentState) {\n      var confirmationMessage = 'Do you wish to save your changes first? Your changes will be discarded if you choose leave without saving them.';\n      (e || window.event).returnValue = confirmationMessage; // Gecko + IE.\n      return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.\n    }\n  });\n\n  // Save tinymce when submitting the form.\n  $('.post-type-noptin-form #post').on('submit', function () {\n    isSaving = true;\n\n    // Save editor content.\n    if (window.tinyMCE) {\n      window.tinyMCE.triggerSave();\n    }\n  });\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/form-editor.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./includes/assets/js/src/form-editor.js"]();
/******/ 	
/******/ })()
;