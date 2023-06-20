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

/***/ "./includes/assets/js/src/newsletter-editor.js":
/*!*****************************************************!*\
  !*** ./includes/assets/js/src/newsletter-editor.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

eval("(function ($) {\n  //The newsletter editor\n  window.noptinNewsletterEditor = (__webpack_require__(/*! ./partials/newsletter-editor.js */ \"./includes/assets/js/src/partials/newsletter-editor.js\")[\"default\"]);\n\n  //Init the newsletter editor\n  $(document).ready(function () {\n    // Init the newsletter editor.\n    noptinNewsletterEditor.init();\n\n    // Hide/Show the schedule editor.\n    $('.noptin-newsletter-schedule-control .edit-schedule').on('click', function (e) {\n      e.preventDefault();\n      var parent = $(this).closest('.noptin-newsletter-schedule-control');\n      parent.find('.noptin-schedule').slideDown();\n      parent.find('.edit-schedule').fadeOut();\n      parent.find('.scheduled').show();\n      parent.find('.not-scheduled').hide();\n      parent.find('.scheduled-date').hide();\n    });\n\n    // Hide/Show sending options.\n    $('.noptin-email_sender').on('change', function (e) {\n      var val = $(this).val();\n      $('.noptin-sender-options').hide();\n      $(\".noptin-sender-options.sender-\".concat(val)).show();\n    });\n\n    // Hide/Show select 2 options.\n    $('.noptin-newsletter-select_2').select2();\n\n    // Change email type.\n    $('#noptin-email-type').on('change', function () {\n      $(this).closest('form').attr('data-type', $(this).val());\n    });\n\n    // Change timing.\n    $('#noptin-automated-email-when-to-run').on('change', function () {\n      $('.noptin-automation-delay-wrapper').toggle($(this).val() == 'delayed');\n    });\n\n    // Post digest timing.\n    $('#noptin-post-digest-frequency').on('change', function () {\n      $('.noptin-post-digest-day').toggle($(this).val() == 'weekly');\n      $('.noptin-post-digest-date').toggle($(this).val() == 'monthly');\n      $('.noptin-post-digest-year-day').toggle($(this).val() == 'yearly');\n      $('.noptin-post-digest-x-days').toggle($(this).val() == 'x_days');\n    });\n\n    // Reverts form to original after a data has been saved.\n    var hideScheduleEditor = function hideScheduleEditor(el) {\n      el.find('.noptin-schedule').slideUp();\n      el.find('.edit-schedule').fadeIn();\n      if ('scheduled' == el.data('status')) {\n        el.find('.scheduled-date').show();\n        el.find('.scheduled').show();\n        el.find('.not-scheduled').hide();\n        if (el.data('schedules')) {\n          var button_id = el.data('schedules');\n          $(\"#\".concat(button_id)).val($(\"#\".concat(button_id)).data('scheduled'));\n        }\n      } else {\n        el.find('.scheduled-date').hide();\n        el.find('.scheduled').hide();\n        el.find('.not-scheduled').show();\n        if (el.data('schedules')) {\n          var _button_id = el.data('schedules');\n          $(\"#\".concat(_button_id)).val($(\"#\".concat(_button_id)).data('not-scheduled'));\n        }\n      }\n    };\n    hideScheduleEditor($('.noptin-newsletter-schedule-control'));\n\n    // Save date changes.\n    $('.noptin-newsletter-schedule-control .save-timestamp').on('click', function (e) {\n      e.preventDefault();\n      var parent = $(this).closest('.noptin-newsletter-schedule-control');\n      var selected_date = parent.find('.noptin-schedule-input-date').val();\n      var selected_time = parent.find('.noptin-schedule-input-time').val();\n      var date_time = \"\".concat(selected_date, \" \").concat(selected_time);\n      parent.find('.scheduled-date').text(date_time);\n      parent.find('.noptin-schedule-selected-date').val(date_time);\n      parent.data('status', 'scheduled');\n      hideScheduleEditor(parent);\n    });\n\n    // Hide the schedule editor.\n    $('.noptin-newsletter-schedule-control .cancel-timestamp').on('click', function (e) {\n      e.preventDefault();\n      hideScheduleEditor($(this).closest('.noptin-newsletter-schedule-control'));\n    });\n\n    // Attach the date pickers\n    $('.noptin-schedule-input-date').flatpickr({\n      dateFormat: \"Y-m-d\",\n      minDate: \"today\",\n      altInput: true,\n      altFormat: 'F j, Y'\n    });\n\n    // Attach the time pickers\n    $('.noptin-schedule-input-time').flatpickr({\n      enableTime: true,\n      noCalendar: true,\n      dateFormat: \"H:i\",\n      time_24hr: true\n    });\n\n    // Remove newsletter recipient.\n    $('.noptin-manual-email-recipients').on('click', '.noptin-manual-email-recipient-remove', function (e) {\n      e.preventDefault();\n      $(this).closest('.noptin-manual-email-recipient').remove();\n    });\n  });\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/newsletter-editor.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/newsletter-editor.js":
/*!**************************************************************!*\
  !*** ./includes/assets/js/src/partials/newsletter-editor.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  initial_form: null,\n  init: function init() {\n    var $ = jQuery;\n\n    // Are we sending a test email?\n    $('.noptin-send-test-email').on('click', this.send_test_email);\n\n    //Upsells\n    $('.noptin-filter-recipients').on('click', this.filter_recipients);\n    $('.noptin-filter-post-notifications-post-types').on('click', this.new_post_notifications_filter_post_types);\n    $('.noptin-filter-post-notifications-taxonomies').on('click', this.new_post_notifications_filter_taxonomies);\n\n    // Stop sending a campaign.\n    $('.noptin-stop-campaign').on('click', this.stop_campaign);\n  },\n  // Stops a sending campaign.\n  stop_campaign: function stop_campaign(e) {\n    e.preventDefault();\n    var data = {\n      id: jQuery(this).data('id'),\n      _wpnonce: noptin_params.nonce,\n      action: 'noptin_stop_campaign'\n    };\n\n    // Init sweetalert.\n    Swal.fire({\n      titleText: \"Are you sure?\",\n      text: \"This campaign will stop sending and be reverted to draft status.\",\n      type: 'warning',\n      showCancelButton: true,\n      confirmButtonColor: '#d33',\n      cancelButtonColor: '#9e9e9e',\n      confirmButtonText: 'Yes, stop it!',\n      showLoaderOnConfirm: true,\n      showCloseButton: true,\n      focusConfirm: false,\n      allowOutsideClick: function allowOutsideClick() {\n        return !Swal.isLoading();\n      },\n      //Fired when the user clicks on the confirm button\n      preConfirm: function preConfirm() {\n        jQuery.get(noptin_params.ajaxurl, data).done(function () {\n          window.location = window.location;\n          Swal.fire('Success', 'Your campaign was reverted to draft', 'success');\n        }).fail(function () {\n          Swal.fire('Error', 'Unable to stop your campaign. Try again.', 'error');\n        });\n\n        //Return a promise that never resolves\n        return jQuery.Deferred();\n      }\n    });\n  },\n  //Sends an ajax request to the server requesting it to send a test email\n  send_test_email: function send_test_email(e) {\n    e.preventDefault();\n\n    //Save tinymce\n    tinyMCE.triggerSave();\n\n    //Form data\n    var data = jQuery(this).closest('form').serialize();\n\n    //Init sweetalert\n    Swal.fire({\n      titleText: \"Send a test email to:\",\n      showCancelButton: true,\n      confirmButtonColor: '#3085d6',\n      cancelButtonColor: '#d33',\n      confirmButtonText: 'Send',\n      showLoaderOnConfirm: true,\n      showCloseButton: true,\n      input: 'email',\n      inputValue: noptin_params.admin_email,\n      inputPlaceholder: noptin_params.admin_email,\n      allowOutsideClick: function allowOutsideClick() {\n        return !Swal.isLoading();\n      },\n      //Fired when the user clicks on the confirm button\n      preConfirm: function preConfirm(email) {\n        //Add the test email\n        data += \"&email=\" + email;\n\n        //Add action\n        data += \"&action=noptin_send_test_email\";\n        jQuery.post(noptin_params.ajaxurl, data).done(function (data) {\n          if (data.success) {\n            Swal.fire('Success', data.data, 'success');\n          } else {\n            Swal.fire({\n              type: 'error',\n              title: 'Error!',\n              text: data.data,\n              showCloseButton: true,\n              confirmButtonText: 'Close',\n              confirmButtonColor: '#9e9e9e',\n              footer: \"<a href=\\\"https://noptin.com/guide/sending-emails/troubleshooting/?utm_medium=plugin-dashboard&utm_campaign=email-campaigns&utm_source=troubleshooting\\\">How to troubleshoot this error.</a>\"\n            });\n          }\n        }).fail(function (jqXHR) {\n          Swal.fire({\n            type: 'error',\n            title: 'Unable to connect',\n            text: 'This might be a problem with your server or your internet connection',\n            showCloseButton: true,\n            confirmButtonText: 'Close',\n            confirmButtonColor: '#9e9e9e',\n            footer: \"<code>Status: \".concat(jqXHR.status, \" &nbsp; Status text: \").concat(jqXHR.statusText, \"</code>\")\n          });\n        });\n\n        //Return a promise that never resolves\n        return jQuery.Deferred();\n      }\n    });\n  }\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/newsletter-editor.js?");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/newsletter-editor.js");
/******/ 	
/******/ })()
;