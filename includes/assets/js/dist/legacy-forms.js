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

/***/ "./includes/assets/js/src/legacy-forms.js":
/*!************************************************!*\
  !*** ./includes/assets/js/src/legacy-forms.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _partials_dom_ready__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./partials/dom-ready */ \"./includes/assets/js/src/partials/dom-ready.js\");\n/* harmony import */ var _partials_frontend_subscribe__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./partials/frontend/subscribe */ \"./includes/assets/js/src/partials/frontend/subscribe.js\");\n\n\n\n// Init when the DOM is ready.\n(0,_partials_dom_ready__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(function () {\n  // Add the subscribe handler to all forms.\n  document.querySelectorAll('.noptin-optin-form-wrapper form, .wp-block-noptin-email-optin form, .noptin-email-optin-widget form, .noptin-optin-form').forEach(function (form) {\n    (0,_partials_frontend_subscribe__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(form);\n  });\n\n  // Add name attributes to all email fields.\n  document.querySelectorAll('.wp-block-noptin-email-optin form input[type=email], .noptin-email-optin-widget form input[type=email]').forEach(function (input) {\n    // Add name attribute.\n    input.setAttribute('name', 'email');\n  });\n\n  // Check if jQuery is available.\n  if (typeof jQuery !== 'undefined') {\n    // Hide slide in forms.\n    jQuery('.noptin-popup-close').on('click', function (e) {\n      e.preventDefault();\n      jQuery(this).closest('.noptin-showing').removeClass('noptin-showing');\n    });\n  }\n  document.addEventListener('click', function (e) {\n    // Check if there is an element with a .noptin-showing class.\n    var showing = document.querySelector('.noptin-showing');\n\n    // Check if the user clicked on a mark as existing subscriber button.\n    if (e.target.matches('.noptin-mark-as-existing-subscriber')) {\n      e.preventDefault();\n      var setCookie = function setCookie(cname) {\n        var d = new Date();\n        d.setTime(d.getTime() + 30 * 24 * 60 * 60 * 1000); // 30 days from now in milliseconds\n        var expires = \"expires=\" + d.toUTCString();\n        document.cookie = \"\".concat(cname, \"=1;\").concat(expires, \";path=\").concat(noptin.cookie_path);\n      };\n      if (noptin.cookie) {\n        setCookie(noptin.cookie);\n      }\n      setCookie('noptin_email_subscribed');\n      if (showing && jQuery) {\n        jQuery(this).closest('.noptin-showing').removeClass('noptin-showing');\n      }\n\n      // popups.close()\n      if (window.noptin_popups) {\n        window.noptin_popups.subscribed = true;\n      }\n    }\n  });\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/legacy-forms.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/dom-ready.js":
/*!******************************************************!*\
  !*** ./includes/assets/js/src/partials/dom-ready.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ domReady)\n/* harmony export */ });\n/**\r\n * Specify a function to execute when the DOM is fully loaded.\r\n *\r\n * @param {Callback} callback A function to execute after the DOM is ready.\r\n *\r\n * @example\r\n * ```js\r\n * import domReady from '@wordpress/dom-ready';\r\n *\r\n * domReady( function() {\r\n * \t//do something after DOM loads.\r\n * } );\r\n * ```\r\n *\r\n * @return {void}\r\n */\nfunction domReady(callback) {\n  if (typeof document === 'undefined') {\n    return;\n  }\n  if (document.readyState === 'complete' ||\n  // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.\n  document.readyState === 'interactive' // DOMContentLoaded fires at this point, so we call directly.\n  ) {\n    return void callback();\n  }\n\n  // DOMContentLoaded has not fired yet, delay callback until then.\n  document.addEventListener('DOMContentLoaded', callback);\n}\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/dom-ready.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/frontend/subscribe.js":
/*!***************************************************************!*\
  !*** ./includes/assets/js/src/partials/frontend/subscribe.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ subscribe)\n/* harmony export */ });\n/**\r\n * Adds a honey pot field to the form then watches for submissions.\r\n *\r\n * Only handles legacy forms.\r\n *\r\n * @param {Element} form The form element.\r\n *\r\n * @return {void}\r\n */\nfunction subscribe(form) {\n  if (!form) {\n    return;\n  }\n\n  // Displays an error message.\n  function showError(message) {\n    form.querySelector('.noptin_feedback_error').innerHTML = message;\n    form.querySelector('.noptin_feedback_error').style.display = 'block';\n    form.querySelector('.noptin_feedback_success').style.display = 'none';\n  }\n\n  // Displays a success message.\n  function showSuccess(message) {\n    form.querySelector('.noptin_feedback_success').innerHTML = message;\n    form.querySelector('.noptin_feedback_success').style.display = 'block';\n    form.querySelector('.noptin_feedback_error').style.display = 'none';\n  }\n\n  // Without using jQuery\n  // Prepend <label style=\"display: none;\"><input type=\"checkbox\" name=\"noptin_confirm_submit\"/>Are you sure?</label>\n  var honey_pot = document.createElement('label');\n  honey_pot.style.display = 'none';\n  honey_pot.innerHTML = '<input type=\"checkbox\" name=\"noptin_confirm_submit\"/>Are you sure?';\n  form.prepend(honey_pot);\n\n  // Watch for form submissions\n  form.addEventListener('submit', function (e) {\n    // Prevent the form from submitting\n    e.preventDefault();\n\n    // Fade the form to 0.5 opacity\n    form.style.opacity = 0.5;\n\n    // Remove any previous feedback and hide it.\n    form.querySelector('.noptin_feedback_success').innerHTML = '';\n    form.querySelector('.noptin_feedback_error').innerHTML = '';\n    form.querySelector('.noptin_feedback_success').style.display = 'none';\n    form.querySelector('.noptin_feedback_error').style.display = 'none';\n\n    // Prep all form data\n    var data = new URLSearchParams(\"action=noptin_new_subscriber&nonce=\".concat(noptin.nonce, \"&conversion_page=\").concat(window.location.href));\n    var fields = new FormData(form);\n    fields.forEach(function (value, key) {\n      data.append(key, value);\n    });\n\n    // Send the data to the server\n    window\n\n    // Post the form.\n    .fetch(noptin.ajaxurl, {\n      method: 'POST',\n      body: data,\n      credentials: 'same-origin',\n      headers: {\n        'Accept': 'application/json',\n        'Content-Type': 'application/x-www-form-urlencoded'\n      }\n    })\n\n    // Check status.\n    .then(function (response) {\n      if (response.status >= 200 && response.status < 300) {\n        return response;\n      }\n      throw response.text();\n    })\n\n    // Parse JSON.\n    .then(function (response) {\n      return response.json();\n    })\n\n    // Handle the response.\n    .then(function (response) {\n      // Was the ajax invalid?\n      if (!response) {\n        throw noptin.connect_err;\n      }\n\n      // An error occured.\n      if (response.success === false) {\n        throw response.data;\n      }\n      if (response.success === true) {\n        // Maybe redirect to success page.\n        if (response.data.action === 'redirect') {\n          window.location.href = response.data.redirect;\n        }\n\n        // Display success message.\n        if (response.data.msg) {\n          form.innerHTML = '<div class=\"noptin-big noptin-padded\">' + response.data.msg + '</div>';\n          form.style.opacity = 1;\n          form.style.display = 'flex';\n          form.style.justifyContent = 'center';\n          setTimeout(function () {\n            document.querySelector('.noptin-showing') && document.querySelector('.noptin-showing').classList.remove('noptin-showing');\n          }, 2000);\n        }\n      } else {\n        throw 'Invalid response';\n      }\n    })\n\n    // Google Analytics.\n    .then(function () {\n      try {\n        // Track the event.\n        if (typeof window.gtag === 'function') {\n          window.gtag('event', 'subscribe', {\n            'method': 'Noptin Form'\n          });\n        } else if (typeof window.ga === 'function') {\n          window.ga('send', 'event', 'Noptin Form', 'Subscribe', 'Noptin');\n        }\n      } catch (err) {\n        console.error(err.message);\n      }\n    })\n\n    // Display error.\n    [\"catch\"](function (e) {\n      console.log(e);\n      if (typeof e === 'string') {\n        showError(e);\n      } else {\n        showError(noptin.connect_err);\n      }\n      form.style.opacity = 1;\n    });\n  });\n}\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/frontend/subscribe.js?");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/legacy-forms.js");
/******/ 	
/******/ })()
;