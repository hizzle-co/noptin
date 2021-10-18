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

eval("window.noptinResizePreview = function () {\n  var preview = jQuery('#noptin-form-preview');\n  preview.height(Math.ceil(preview.contents().find('body').height()) + 50);\n};\n\n(function ($) {\n  // Switch tabs.\n  $('#noptin-form-editor-nav-tab-wrapper .nav-tab').on('click', function (e) {\n    e.preventDefault();\n    var id = $(this).data('id'); // Change active/inactive tab classes.\n\n    $(\"#noptin-form-editor-nav-tab-wrapper .nav-tab-active:not(.noptin-form-tab-\".concat(id, \")\")).removeClass('nav-tab-active');\n    $(this).addClass('nav-tab-active').blur(); // Hide/show tab content.\n\n    $(\".noptin-form-tab-content-active:not(.noptin-form-tab-content-\".concat(id, \")\")).removeClass('noptin-form-tab-content-active');\n    $(\".noptin-form-tab-content-\".concat(id)).addClass('noptin-form-tab-content-active'); // Update document title.\n\n    var tab_title = $('.noptin-form-tab-content-active h2:first-of-type').text();\n\n    if (tab_title) {\n      var title = document.title.split('-');\n      document.title = document.title.replace(title[0], tab_title + ' ');\n    }\n  }); // Toggle accordions.\n\n  $('#noptin-form-editor-app').on('click', '#noptin-form-editor-container .noptin-accordion-trigger', function (e) {\n    e.preventDefault();\n    var panel = $(this).closest('.noptin-settings-panel'),\n        button = panel.find('.noptin-accordion-trigger'),\n        isExpanded = 'true' === button.attr('aria-expanded');\n\n    if (isExpanded) {\n      button.attr('aria-expanded', 'false');\n      panel.addClass('noptin-settings-panel__hidden', true);\n    } else {\n      button.attr('aria-expanded', 'true');\n      panel.removeClass('noptin-settings-panel__hidden', false);\n    }\n  }); // Add new field.\n\n  $('#noptin-form-fields-panel-fields .noptin-button-add-field').on('click', function (e) {\n    e.preventDefault();\n    $('#noptin-form-fields-panel-fields .form-fields-inner').append($('#noptin-form-fields-panel-new-field-template').html());\n  }); // Change field type.\n\n  $('#noptin-form-editor-app').on('change', '.noptin-form-settings-field-type', function (e) {\n    e.preventDefault(); // Get field type.\n\n    var val = $(this).val(),\n        panel_content = $(this).closest('.noptin-settings-panel__content'); // Update settings template and ids.\n\n    panel_content.html($(\"#noptin-form-fields-panel-\".concat(val, \"-template\")).html()).attr('id', \"noptin-form-fields-panel-fields-\".concat(val, \"-content\")).closest('.noptin-settings-panel').attr('id', \"noptin-form-fields-panel-fields-\".concat(val)).find(\".noptin-accordion-trigger\").first().attr('aria-controls', \"noptin-form-fields-panel-fields-\".concat(val, \"-content\"));\n    panel_content.find('.noptin-form-field-label').trigger('input');\n  }); // Update field labels.\n\n  $('#noptin-form-fields-panel-fields').on('input', '.noptin-form-field-label', function () {\n    $(this).closest('.noptin-settings-panel').find('.noptin-accordion-trigger .title').first().text($(this).val());\n  }); // Delete fields.\n\n  $('#noptin-form-fields-panel-fields').on('click', '.noptin-field-editor-delete', function (e) {\n    var _this = this;\n\n    e.preventDefault();\n    $(this).closest('.noptin-settings-panel').fadeOut(400, function () {\n      $(_this)[\"delete\"]();\n    });\n  }); // Resize iframe.\n\n  $('#noptin-form-preview').on('load', noptinResizePreview);\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/form-editor.js?");

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