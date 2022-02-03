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

eval("(function ($) {\n  //The newsletter editor\n  window.noptinNewsletterEditor = (__webpack_require__(/*! ./partials/newsletter-editor.js */ \"./includes/assets/js/src/partials/newsletter-editor.js\")[\"default\"]); //Init the newsletter editor\n\n  $(document).ready(function () {\n    // Init the newsletter editor.\n    noptinNewsletterEditor.init(); // Hide/Show the schedule editor.\n\n    $('.noptin-newsletter-schedule-control .edit-schedule').on('click', function (e) {\n      e.preventDefault();\n      var parent = $(this).closest('.noptin-newsletter-schedule-control');\n      parent.find('.noptin-schedule').slideDown();\n      parent.find('.edit-schedule').fadeOut();\n      parent.find('.scheduled').show();\n      parent.find('.not-scheduled').hide();\n      parent.find('.scheduled-date').hide();\n    }); // Hide/Show sending options.\n\n    $('.noptin-email_sender').on('change', function (e) {\n      var val = $(this).val();\n      $('.noptin-sender-options').hide();\n      $(\".noptin-sender-options.sender-\".concat(val)).show();\n    }); // Hide/Show select 2 options.\n\n    $('.noptin-newsletter-select_2').select2(); // Change email type.\n\n    $('#noptin-automated-email-type').on('change', function () {\n      $('.noptin-automated-email').attr('data-type', $(this).val());\n      $('#noptin_automation_advanced').toggle($(this).val() == 'normal');\n    }); // Change timing.\n\n    $('#noptin-automated-email-when-to-run').on('change', function () {\n      $('.noptin-automation-delay-wrapper').toggle($(this).val() == 'delayed');\n    }); // Post digest timing.\n\n    $('#noptin-post-digest-frequency').on('change', function () {\n      $('.noptin-post-digest-day').toggle($(this).val() == 'weekly');\n      $('.noptin-post-digest-date').toggle($(this).val() == 'monthly');\n    }); // Reverts form to original after a data has been saved.\n\n    var hideScheduleEditor = function hideScheduleEditor(el) {\n      el.find('.noptin-schedule').slideUp();\n      el.find('.edit-schedule').fadeIn();\n\n      if ('scheduled' == el.data('status')) {\n        el.find('.scheduled-date').show();\n        el.find('.scheduled').show();\n        el.find('.not-scheduled').hide();\n\n        if (el.data('schedules')) {\n          var button_id = el.data('schedules');\n          $(\"#\".concat(button_id)).val($(\"#\".concat(button_id)).data('scheduled'));\n        }\n      } else {\n        el.find('.scheduled-date').hide();\n        el.find('.scheduled').hide();\n        el.find('.not-scheduled').show();\n\n        if (el.data('schedules')) {\n          var _button_id = el.data('schedules');\n\n          $(\"#\".concat(_button_id)).val($(\"#\".concat(_button_id)).data('not-scheduled'));\n        }\n      }\n    };\n\n    hideScheduleEditor($('.noptin-newsletter-schedule-control')); // Save date changes.\n\n    $('.noptin-newsletter-schedule-control .save-timestamp').on('click', function (e) {\n      e.preventDefault();\n      var parent = $(this).closest('.noptin-newsletter-schedule-control');\n      var selected_date = parent.find('.noptin-schedule-input-date').val();\n      var selected_time = parent.find('.noptin-schedule-input-time').val();\n      var date_time = \"\".concat(selected_date, \" \").concat(selected_time);\n      parent.find('.scheduled-date').text(date_time);\n      parent.find('.noptin-schedule-selected-date').val(date_time);\n      parent.data('status', 'scheduled');\n      hideScheduleEditor(parent);\n    }); // Hide the schedule editor.\n\n    $('.noptin-newsletter-schedule-control .cancel-timestamp').on('click', function (e) {\n      e.preventDefault();\n      hideScheduleEditor($(this).closest('.noptin-newsletter-schedule-control'));\n    }); // Attach the date pickers\n\n    $('.noptin-schedule-input-date').flatpickr({\n      dateFormat: \"Y-m-d\",\n      minDate: \"today\",\n      altInput: true,\n      altFormat: 'F j, Y'\n    }); // Attach the time pickers\n\n    $('.noptin-schedule-input-time').flatpickr({\n      enableTime: true,\n      noCalendar: true,\n      dateFormat: \"H:i\",\n      time_24hr: true\n    });\n  });\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/newsletter-editor.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/newsletter-editor.js":
/*!**************************************************************!*\
  !*** ./includes/assets/js/src/partials/newsletter-editor.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _noptin_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./noptin.js */ \"./includes/assets/js/src/partials/noptin.js\");\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  initial_form: null,\n  init: function init() {\n    var $ = jQuery; //Send test email\n\n    $('#wp-noptinemailbody-media-buttons, #wp-noptin-automation-email-content-media-buttons').append('&nbsp;<a class=\"button noptin-send-test-email\"><span class=\"wp-menu-image dashicons-before dashicons-email-alt\"></span>Send a test email</a>'); // Are we sending a test email?\n\n    $('.noptin-send-test-email').on('click', this.send_test_email); //Upsells\n\n    $('.noptin-filter-recipients').on('click', this.filter_recipients);\n    $('.noptin-filter-post-notifications-post-types').on('click', this.new_post_notifications_filter_post_types);\n    $('.noptin-filter-post-notifications-taxonomies').on('click', this.new_post_notifications_filter_taxonomies); //Delete campaign\n\n    $('.noptin-delete-campaign').on('click', this.delete_campaign); // Stop sending a campaign.\n\n    $('.noptin-stop-campaign').on('click', this.stop_campaign);\n  },\n  // Stops a sending campaign.\n  stop_campaign: function stop_campaign(e) {\n    e.preventDefault();\n    var data = {\n      id: jQuery(this).data('id'),\n      _wpnonce: noptin_params.nonce,\n      action: 'noptin_stop_campaign'\n    }; // Init sweetalert.\n\n    Swal.fire({\n      titleText: \"Are you sure?\",\n      text: \"This campaign will stop sending and be reverted to draft status.\",\n      type: 'warning',\n      showCancelButton: true,\n      confirmButtonColor: '#d33',\n      cancelButtonColor: '#9e9e9e',\n      confirmButtonText: 'Yes, stop it!',\n      showLoaderOnConfirm: true,\n      showCloseButton: true,\n      focusConfirm: false,\n      allowOutsideClick: function allowOutsideClick() {\n        return !Swal.isLoading();\n      },\n      //Fired when the user clicks on the confirm button\n      preConfirm: function preConfirm() {\n        jQuery.get(noptin_params.ajaxurl, data).done(function () {\n          window.location = window.location;\n          Swal.fire('Success', 'Your campaign was reverted to draft', 'success');\n        }).fail(function () {\n          Swal.fire('Error', 'Unable to stop your campaign. Try again.', 'error');\n        }); //Return a promise that never resolves\n\n        return jQuery.Deferred();\n      }\n    });\n  },\n  //Deletes a campagin\n  delete_campaign: function delete_campaign(e) {\n    e.preventDefault();\n    var row = jQuery(this).closest('tr');\n    var data = {\n      id: jQuery(this).data('id'),\n      _wpnonce: noptin_params.nonce,\n      action: 'noptin_delete_campaign'\n    }; //Init sweetalert\n\n    Swal.fire({\n      titleText: \"Are you sure?\",\n      text: \"You are about to permanently delete this campaign.\",\n      type: 'warning',\n      showCancelButton: true,\n      confirmButtonColor: '#d33',\n      cancelButtonColor: '#9e9e9e',\n      confirmButtonText: 'Yes, delete it!',\n      showLoaderOnConfirm: true,\n      showCloseButton: true,\n      focusConfirm: false,\n      allowOutsideClick: function allowOutsideClick() {\n        return !Swal.isLoading();\n      },\n      //Fired when the user clicks on the confirm button\n      preConfirm: function preConfirm() {\n        jQuery.get(noptin_params.ajaxurl, data).done(function () {\n          jQuery(row).remove();\n          Swal.fire('Success', 'Your campaign was deleted', 'success');\n        }).fail(function () {\n          Swal.fire('Error', 'Unable to delete your campaign. Try again.', 'error');\n        }); //Return a promise that never resolves\n\n        return jQuery.Deferred();\n      }\n    });\n  },\n  //Sends an ajax request to the server requesting it to send a test email\n  send_test_email: function send_test_email(e) {\n    e.preventDefault(); //Save tinymce\n\n    tinyMCE.triggerSave(); //Form data\n\n    var data = _noptin_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"].getFormData(jQuery(this).closest('form')); //Init sweetalert\n\n    Swal.fire({\n      titleText: \"Send a test email to:\",\n      showCancelButton: true,\n      confirmButtonColor: '#3085d6',\n      cancelButtonColor: '#d33',\n      confirmButtonText: 'Send',\n      showLoaderOnConfirm: true,\n      showCloseButton: true,\n      input: 'email',\n      inputValue: noptin_params.admin_email,\n      inputPlaceholder: noptin_params.admin_email,\n      allowOutsideClick: function allowOutsideClick() {\n        return !Swal.isLoading();\n      },\n      //Fired when the user clicks on the confirm button\n      preConfirm: function preConfirm(email) {\n        //Add the test email\n        data.email = email; //Add action\n\n        data.action = \"noptin_send_test_email\";\n        jQuery.post(noptin_params.ajaxurl, data).done(function (data) {\n          if (data.success) {\n            Swal.fire('Success', data.data, 'success');\n          } else {\n            Swal.fire({\n              type: 'error',\n              title: 'Error!',\n              text: data.data,\n              showCloseButton: true,\n              confirmButtonText: 'Close',\n              confirmButtonColor: '#9e9e9e',\n              footer: \"<a href=\\\"https://noptin.com/guide/sending-emails/troubleshooting/\\\">How to troubleshoot this error.</a>\"\n            });\n          }\n        }).fail(function (jqXHR) {\n          Swal.fire({\n            type: 'error',\n            title: 'Unable to connect',\n            text: 'This might be a problem with your server or your internet connection',\n            showCloseButton: true,\n            confirmButtonText: 'Close',\n            confirmButtonColor: '#9e9e9e',\n            footer: \"<code>Status: \".concat(jqXHR.status, \" &nbsp; Status text: \").concat(jqXHR.statusText, \"</code>\")\n          });\n        }); //Return a promise that never resolves\n\n        return jQuery.Deferred();\n      }\n    });\n  },\n  //Filters email recipients\n  filter_recipients: function filter_recipients(e) {\n    e.preventDefault();\n\n    if (!jQuery('#noptin_recipients_filter_div').length) {\n      Swal.fire({\n        titleText: \"Addon Needed!\",\n        html: \"Install the <strong>Ultimate Addons Pack</strong> to filter recipients by their sign up method/form, tags or the time in which they signed up.\",\n        showCancelButton: true,\n        confirmButtonColor: '#3085d6',\n        cancelButtonColor: '#d33',\n        confirmButtonText: 'Install Addon',\n        showCloseButton: true\n      }).then(function (result) {\n        if (result.value) {\n          window.location.href = 'https://noptin.com/product/ultimate-addons-pack';\n        }\n      });\n    }\n  },\n  new_post_notifications_filter_post_types: function new_post_notifications_filter_post_types(e) {\n    e.preventDefault();\n    Swal.fire({\n      titleText: \"Addon Needed!\",\n      html: \"Install the <strong>Ultimate Addons Pack</strong> to send new post notifications to other post types.\",\n      showCancelButton: true,\n      confirmButtonColor: '#3085d6',\n      cancelButtonColor: '#d33',\n      confirmButtonText: 'Install Addon',\n      showCloseButton: true\n    }).then(function (result) {\n      if (result.value) {\n        window.location.href = 'https://noptin.com/product/ultimate-addons-pack';\n      }\n    });\n  },\n  new_post_notifications_filter_taxonomies: function new_post_notifications_filter_taxonomies(e) {\n    e.preventDefault();\n    Swal.fire({\n      titleText: \"Addon Needed!\",\n      html: \"Install the <strong>Ultimate Addons Pack</strong> to limit new post notifications to specific categories, tags or other taxonomies.\",\n      showCancelButton: true,\n      confirmButtonColor: '#3085d6',\n      cancelButtonColor: '#d33',\n      confirmButtonText: 'Install Addon',\n      showCloseButton: true\n    }).then(function (result) {\n      if (result.value) {\n        window.location.href = 'https://noptin.com/product/ultimate-addons-pack';\n      }\n    });\n  }\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/newsletter-editor.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin.js":
/*!***************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  templateData: function templateData(key) {\n    var data = {};\n\n    if (noptinEditor && noptinEditor.templates[key]) {\n      var template = noptinEditor.templates[key]['data'];\n      Object.keys(template).forEach(function (key) {\n        data[key] = template[key];\n      });\n    }\n\n    return data;\n  },\n  applyTemplate: function applyTemplate(template, instance) {\n    Object.keys(template).forEach(function (key) {\n      instance[key] = template[key];\n    });\n    instance.hideFields = false;\n    instance.gdprCheckbox = false;\n    this.updateFormSizes(instance);\n  },\n  updateFormSizes: function updateFormSizes(instance) {\n    if (instance.optinType == 'sidebar') {\n      instance.formHeight = '400px';\n      instance.formWidth = '300px';\n      instance.singleLine = false;\n      return;\n    }\n\n    if (instance.optinType == 'popup') {\n      instance.formWidth = '620px';\n      instance.formHeight = '280px';\n      return;\n    }\n\n    if (instance.optinType == 'slide_in') {\n      instance.formWidth = '400px';\n      instance.formHeight = '280px';\n      return;\n    }\n\n    instance.formHeight = '280px';\n    instance.formWidth = '620px';\n  },\n  updateCustomCss: function updateCustomCss(css) {\n    jQuery('#formCustomCSS').text(css);\n  },\n  getColorThemeOptions: function getColorThemeOptions() {\n    var themes = [];\n    Object.keys(noptinEditor.color_themes).forEach(function (key) {\n      var theme = {\n        text: key,\n        value: noptinEditor.color_themes[key],\n        imageSrc: noptin_params.icon //description: \"Description with Facebook\",\n\n      };\n      themes.push(theme);\n    });\n    return themes;\n  },\n  getColorTheme: function getColorTheme(instance) {\n    return instance.colorTheme.split(\" \");\n  },\n  changeColorTheme: function changeColorTheme(instance) {\n    var colors = this.getColorTheme(instance);\n\n    if (colors.length) {\n      instance.noptinFormBg = colors[0];\n      instance.formBorder.border_color = colors[2];\n      instance.noptinButtonColor = colors[0];\n      instance.noptinButtonBg = colors[1];\n      instance.titleColor = colors[1];\n      instance.descriptionColor = colors[1];\n      instance.noteColor = colors[1];\n    }\n  },\n  getFormData: function getFormData(form) {\n    var data = {},\n        fields = jQuery(form).serializeArray();\n    jQuery.each(fields, function (i, field) {\n      data[field.name] = field.value;\n    });\n    return data;\n  }\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/noptin.js?");

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