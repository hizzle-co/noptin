/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./includes/assets/js/src/newsletter-editor.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./includes/assets/js/src/newsletter-editor.js":
/*!*****************************************************!*\
  !*** ./includes/assets/js/src/newsletter-editor.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("(function ($) {\n  //The newsletter editor\n  window.noptinNewsletterEditor = __webpack_require__(/*! ./partials/newsletter-editor.js */ \"./includes/assets/js/src/partials/newsletter-editor.js\")[\"default\"]; //Init the newsletter editor\n\n  $(document).ready(function () {\n    //Init the newsletter editor\n    noptinNewsletterEditor.init();\n  });\n})(jQuery);\n\n//# sourceURL=webpack:///./includes/assets/js/src/newsletter-editor.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/newsletter-editor.js":
/*!**************************************************************!*\
  !*** ./includes/assets/js/src/partials/newsletter-editor.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _noptin_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./noptin.js */ \"./includes/assets/js/src/partials/noptin.js\");\nfunction _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  initial_form: null,\n  init: function init() {\n    var $ = jQuery; //Create a new automation\n\n    $('.noptin-create-new-automation-campaign').on('click', this.create_automation);\n    $(document).on('click', '.noptin-automation-type-select.enabled', this.select_automation); //Send test email\n\n    $('#wp-noptinemailbody-media-buttons').append('&nbsp;<a class=\"button noptin-send-test-email\"><span class=\"wp-menu-image dashicons-before dashicons-email-alt\"></span>Send a test email</a>'); //Are we sending a test email?\n\n    $('.noptin-send-test-email').on('click', this.send_test_email); //Upsells\n\n    $('.noptin-filter-recipients').on('click', this.filter_recipients);\n    $('.noptin-filter-post-notifications-post-types').on('click', this.new_post_notifications_filter_post_types);\n    $('.noptin-filter-post-notifications-taxonomies').on('click', this.new_post_notifications_filter_taxonomies); //Delete campaign\n\n    $('.noptin-delete-campaign').on('click', this.delete_campaign);\n  },\n  //Creates a new automation\n  create_automation: function create_automation(e) {\n    e.preventDefault(); //Init sweetalert\n\n    Swal.fire({\n      html: jQuery('#noptin-create-automation').html(),\n      showConfirmButton: false,\n      showCloseButton: true,\n      width: 600\n    });\n  },\n  //Select an automation\n  select_automation: function select_automation(e) {\n    var _Swal$fire;\n\n    e.preventDefault();\n    var parent = jQuery(this).find('.noptin-automation-type-setup-form').clone().find('form').attr('id', 'noptinCurrentForm').parent();\n    var form = parent.html();\n    parent.remove(); //Init sweetalert\n\n    Swal.fire((_Swal$fire = {\n      html: form,\n      showCloseButton: true,\n      width: 800,\n      showCancelButton: true,\n      confirmButtonText: 'Continue',\n      showLoaderOnConfirm: true\n    }, _defineProperty(_Swal$fire, \"showCloseButton\", true), _defineProperty(_Swal$fire, \"focusConfirm\", false), _defineProperty(_Swal$fire, \"allowOutsideClick\", function allowOutsideClick() {\n      return !Swal.isLoading();\n    }), _defineProperty(_Swal$fire, \"preConfirm\", function preConfirm() {\n      var data = _noptin_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"].getFormData(jQuery('#noptinCurrentForm'));\n      data.action = \"noptin_setup_automation\";\n      jQuery.post(noptin_params.ajaxurl, data).done(function (url) {\n        window.location = url;\n      }).fail(function (jqXHR) {\n        Swal.fire({\n          type: 'error',\n          title: 'Error',\n          text: 'There was an error creating your automation',\n          showCloseButton: true,\n          confirmButtonText: 'Close',\n          confirmButtonColor: '#9e9e9e',\n          footer: \"<code>Status: \".concat(jqXHR.status, \" &nbsp; Status text: \").concat(jqXHR.statusText, \"</code>\")\n        });\n      }); //Return a promise that never resolves\n\n      return jQuery.Deferred();\n    }), _Swal$fire));\n  },\n  //Deletes a campagin\n  delete_campaign: function delete_campaign(e) {\n    e.preventDefault();\n    var row = jQuery(this).closest('tr');\n    var data = {\n      id: jQuery(this).data('id'),\n      _wpnonce: noptin_params.nonce,\n      action: 'noptin_delete_campaign'\n    }; //Init sweetalert\n\n    Swal.fire({\n      titleText: \"Are you sure?\",\n      text: \"You are about to permanently delete this campaign.\",\n      type: 'warning',\n      showCancelButton: true,\n      confirmButtonColor: '#d33',\n      cancelButtonColor: '#9e9e9e',\n      confirmButtonText: 'Yes, delete it!',\n      showLoaderOnConfirm: true,\n      showCloseButton: true,\n      focusConfirm: false,\n      allowOutsideClick: function allowOutsideClick() {\n        return !Swal.isLoading();\n      },\n      //Fired when the user clicks on the confirm button\n      preConfirm: function preConfirm() {\n        jQuery.get(noptin_params.ajaxurl, data).done(function () {\n          jQuery(row).remove();\n          Swal.fire('Success', 'Your campaign was deleted', 'success');\n        }).fail(function () {\n          Swal.fire('Error', 'Unable to delete your campaign. Try again.', 'error');\n        }); //Return a promise that never resolves\n\n        return jQuery.Deferred();\n      }\n    });\n  },\n  //Sends an ajax request to the server requesting it to send a test email\n  send_test_email: function send_test_email(e) {\n    e.preventDefault(); //Save tinymce\n\n    tinyMCE.triggerSave(); //Form data\n\n    var data = _noptin_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"].getFormData(jQuery(this).closest('form')); //Init sweetalert\n\n    Swal.fire({\n      titleText: \"Send a test email to:\",\n      showCancelButton: true,\n      confirmButtonColor: '#3085d6',\n      cancelButtonColor: '#d33',\n      confirmButtonText: 'Send',\n      showLoaderOnConfirm: true,\n      showCloseButton: true,\n      input: 'email',\n      inputValue: noptin_params.admin_email,\n      inputPlaceholder: noptin_params.admin_email,\n      allowOutsideClick: function allowOutsideClick() {\n        return !Swal.isLoading();\n      },\n      //Fired when the user clicks on the confirm button\n      preConfirm: function preConfirm(email) {\n        //Add the test email\n        data.email = email; //Add action\n\n        data.action = \"noptin_send_test_email\";\n        jQuery.post(noptin_params.ajaxurl, data).done(function (data) {\n          if (data.success) {\n            Swal.fire('Success', data.data, 'success');\n          } else {\n            Swal.fire({\n              type: 'error',\n              title: 'Error!',\n              text: data.data,\n              showCloseButton: true,\n              confirmButtonText: 'Close',\n              confirmButtonColor: '#9e9e9e',\n              footer: \"<a href=\\\"https://noptin.com/guide/sending-emails/troubleshooting/\\\">How to troubleshoot this error.</a>\"\n            });\n          }\n        }).fail(function (jqXHR) {\n          Swal.fire({\n            type: 'error',\n            title: 'Unable to connect',\n            text: 'This might be a problem with your server or your internet connection',\n            showCloseButton: true,\n            confirmButtonText: 'Close',\n            confirmButtonColor: '#9e9e9e',\n            footer: \"<code>Status: \".concat(jqXHR.status, \" &nbsp; Status text: \").concat(jqXHR.statusText, \"</code>\")\n          });\n        }); //Return a promise that never resolves\n\n        return jQuery.Deferred();\n      }\n    });\n  },\n  //Filters email recipients\n  filter_recipients: function filter_recipients(e) {\n    e.preventDefault();\n\n    if (!jQuery('#noptin_recipients_filter_div').length) {\n      Swal.fire({\n        titleText: \"Addon Needed!\",\n        html: \"Install the <strong>Ultimate Addons Pack</strong> to filter recipients by their sign up method/form, tags or the time in which they signed up.\",\n        showCancelButton: true,\n        confirmButtonColor: '#3085d6',\n        cancelButtonColor: '#d33',\n        confirmButtonText: 'Install Addon',\n        showCloseButton: true\n      }).then(function (result) {\n        if (result.value) {\n          window.location.href = 'https://noptin.com/product/ultimate-addons-pack';\n        }\n      });\n    }\n  },\n  new_post_notifications_filter_post_types: function new_post_notifications_filter_post_types(e) {\n    e.preventDefault();\n    Swal.fire({\n      titleText: \"Addon Needed!\",\n      html: \"Install the <strong>Ultimate Addons Pack</strong> to send new post notifications to other post types.\",\n      showCancelButton: true,\n      confirmButtonColor: '#3085d6',\n      cancelButtonColor: '#d33',\n      confirmButtonText: 'Install Addon',\n      showCloseButton: true\n    }).then(function (result) {\n      if (result.value) {\n        window.location.href = 'https://noptin.com/product/ultimate-addons-pack';\n      }\n    });\n  },\n  new_post_notifications_filter_taxonomies: function new_post_notifications_filter_taxonomies(e) {\n    e.preventDefault();\n    Swal.fire({\n      titleText: \"Addon Needed!\",\n      html: \"Install the <strong>Ultimate Addons Pack</strong> to limit new post notifications to specific categories, tags or other taxonomies.\",\n      showCancelButton: true,\n      confirmButtonColor: '#3085d6',\n      cancelButtonColor: '#d33',\n      confirmButtonText: 'Install Addon',\n      showCloseButton: true\n    }).then(function (result) {\n      if (result.value) {\n        window.location.href = 'https://noptin.com/product/ultimate-addons-pack';\n      }\n    });\n  }\n});\n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/newsletter-editor.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin.js":
/*!***************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  templateData: function templateData(key) {\n    var data = {};\n\n    if (noptinEditor && noptinEditor.templates[key]) {\n      var template = noptinEditor.templates[key]['data'];\n      Object.keys(template).forEach(function (key) {\n        data[key] = template[key];\n      });\n    }\n\n    return data;\n  },\n  applyTemplate: function applyTemplate(template, instance) {\n    Object.keys(template).forEach(function (key) {\n      instance[key] = template[key];\n    });\n    this.updateFormSizes(instance);\n  },\n  updateFormSizes: function updateFormSizes(instance) {\n    if (instance.optinType == 'sidebar') {\n      instance.formHeight = '400px';\n      instance.formWidth = '300px';\n      instance.singleLine = false;\n      return;\n    }\n\n    if (instance.optinType == 'popup') {\n      instance.formWidth = '620px';\n      instance.formHeight = '280px';\n      return;\n    }\n\n    instance.formHeight = '280px';\n    instance.formWidth = '620px';\n  },\n  updateCustomCss: function updateCustomCss(css) {\n    jQuery('#formCustomCSS').text(css);\n  },\n  getColorThemeOptions: function getColorThemeOptions() {\n    var themes = [];\n    Object.keys(noptinEditor.color_themes).forEach(function (key) {\n      var theme = {\n        text: key,\n        value: noptinEditor.color_themes[key],\n        imageSrc: noptin_params.icon //description: \"Description with Facebook\",\n\n      };\n      themes.push(theme);\n    });\n    return themes;\n  },\n  getColorTheme: function getColorTheme(instance) {\n    return instance.colorTheme.split(\" \");\n  },\n  changeColorTheme: function changeColorTheme(instance) {\n    var colors = this.getColorTheme(instance);\n\n    if (colors.length) {\n      instance.noptinFormBg = colors[0];\n      instance.noptinFormBorderColor = colors[2];\n      instance.noptinButtonColor = colors[0];\n      instance.noptinButtonBg = colors[1];\n      instance.titleColor = colors[1];\n      instance.descriptionColor = colors[1];\n      instance.noteColor = colors[1];\n    }\n  },\n  getFormData: function getFormData(form) {\n    var data = {},\n        fields = jQuery(form).serializeArray();\n    jQuery.each(fields, function (i, field) {\n      data[field.name] = field.value;\n    });\n    return data;\n  }\n});\n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/noptin.js?");

/***/ })

/******/ });