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

/***/ "./includes/assets/js/src/helper.js":
/*!******************************************!*\
  !*** ./includes/assets/js/src/helper.js ***!
  \******************************************/
/***/ (() => {

eval("(function ($) {\n  \"use strict\"; // Attach the tooltips\n\n  $(document).ready(function () {\n    // Activate license.\n    $('.noptin-helper-activate-license-modal').on('click', function (e) {\n      e.preventDefault();\n      var data = {\n        'product_id': $(this).data('id'),\n        '_wpnonce': noptin_helper.rest_nonce\n      }; // Init sweetalert.\n\n      var activating = $(this).data('activating');\n      Swal.fire({\n        titleText: noptin_helper.activate_license,\n        showCancelButton: true,\n        confirmButtonColor: '#3085d6',\n        cancelButtonColor: '#d33',\n        confirmButtonText: noptin_helper.activate,\n        cancelButtonText: noptin_helper.cancel,\n        showLoaderOnConfirm: true,\n        showCloseButton: true,\n        input: 'text',\n        inputPlaceholder: noptin_helper.license_key,\n        footer: activating,\n        allowOutsideClick: function allowOutsideClick() {\n          return !Swal.isLoading();\n        },\n        inputValidator: function inputValidator(value) {\n          if (!value) {\n            return noptin_helper.license_key;\n          }\n        },\n        //Fired when the user clicks on the confirm button.\n        preConfirm: function preConfirm(license_key) {\n          data.license_key = license_key;\n          jQuery.post(noptin_helper.license_activate_url, data).done(function () {\n            Swal.fire({\n              position: 'top-end',\n              icon: 'success',\n              title: noptin_helper.license_activated,\n              showConfirmButton: false,\n              timer: 1500\n            });\n            window.location = window.location;\n          }).fail(function (jqXHR) {\n            var footer = jqXHR.statusText;\n\n            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {\n              footer = jqXHR.responseJSON.message;\n            }\n\n            Swal.fire({\n              icon: 'error',\n              title: footer,\n              footer: '<code>' + noptin_helper.license_activation_error + '</code>',\n              showCloseButton: true,\n              confirmButtonText: noptin_helper.close,\n              confirmButtonColor: '#9e9e9e',\n              showConfirmButton: false\n            });\n          }); //Return a promise that never resolves\n\n          return jQuery.Deferred();\n        }\n      });\n    }); // Deactivate license.\n\n    $('.noptin-helper-deactivate-license-modal').on('click', function (e) {\n      e.preventDefault();\n      var data = {\n        'license_key': $(this).data('license_key'),\n        '_wpnonce': noptin_helper.rest_nonce\n      }; //Init sweetalert\n\n      Swal.fire({\n        icon: 'warning',\n        titleText: noptin_helper.deactivate_license,\n        showCancelButton: true,\n        confirmButtonColor: '#3085d6',\n        cancelButtonColor: '#d33',\n        confirmButtonText: noptin_helper.deactivate,\n        cancelButtonText: noptin_helper.cancel,\n        showLoaderOnConfirm: true,\n        showCloseButton: true,\n        footer: noptin_helper.deactivate_warning,\n        allowOutsideClick: function allowOutsideClick() {\n          return !Swal.isLoading();\n        },\n        //Fired when the user clicks on the confirm button.\n        preConfirm: function preConfirm() {\n          jQuery.post(noptin_helper.license_deactivate_url, data).done(function () {\n            Swal.fire({\n              position: 'top-end',\n              icon: 'success',\n              title: noptin_helper.license_deactivated,\n              showConfirmButton: false,\n              timer: 1500\n            });\n            window.location = window.location;\n          }).fail(function (jqXHR) {\n            var footer = jqXHR.statusText;\n\n            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {\n              footer = jqXHR.responseJSON.message;\n            }\n\n            Swal.fire({\n              icon: 'error',\n              title: noptin_helper.license_deactivation_error,\n              footer: footer,\n              showCloseButton: true,\n              confirmButtonText: noptin_helper.close,\n              confirmButtonColor: '#9e9e9e',\n              showConfirmButton: false\n            });\n          }); //Return a promise that never resolves\n\n          return jQuery.Deferred();\n        }\n      });\n    });\n  });\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/helper.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./includes/assets/js/src/helper.js"]();
/******/ 	
/******/ })()
;