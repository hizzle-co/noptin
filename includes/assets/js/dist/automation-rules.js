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

/***/ "./includes/assets/js/src/automation-rules.js":
/*!****************************************************!*\
  !*** ./includes/assets/js/src/automation-rules.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

eval("(function ($) {\n  // Enables/Disables the submit button depending on whether or not\n  // both a trigger and an action have been set.\n  var maybeEnableSubmit = function maybeEnableSubmit() {\n    // Set up the current trigger and action.\n    var trigger = $('.noptin-automation-rule-trigger-hidden').val();\n    var action = $('.noptin-automation-rule-action-hidden').val(); // Are both of them set-up?\n\n    if (trigger && action) {\n      $('.noptin-automation-rule-create').prop(\"disabled\", false).removeClass('button-secondary').addClass('button-primary');\n    } else {\n      $('.noptin-automation-rule-create').prop(\"disabled\", true).removeClass('button-primary').addClass('button-secondary');\n    }\n  };\n\n  $('#noptin-automation-rule-editor .noptin-automation-rule-trigger').ddslick({\n    width: 400,\n    onSelected: function onSelected(data) {\n      var selected = data.selectedData.value;\n      $('.noptin-automation-rule-trigger-hidden').val(selected);\n      maybeEnableSubmit();\n    }\n  });\n  $('#noptin-automation-rule-editor .noptin-automation-rule-action').ddslick({\n    width: 400,\n    onSelected: function onSelected(data) {\n      var selected = data.selectedData.value;\n      $('.noptin-automation-rule-action-hidden').val(selected);\n      maybeEnableSubmit();\n    }\n  });\n\n  if ($('#noptin-automation-rule-editor.edit-automation-rule').length) {\n    __webpack_require__(/*! ./partials/automation-rules-editor.js */ \"./includes/assets/js/src/partials/automation-rules-editor.js\").default;\n  }\n})(jQuery);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/automation-rules.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/automation-rules-editor.js":
/*!********************************************************************!*\
  !*** ./includes/assets/js/src/partials/automation-rules-editor.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _noptin_select_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./noptin-select.vue */ \"./includes/assets/js/src/partials/noptin-select.vue\");\n\nvar rulesApp = new Vue({\n  components: {\n    // Select2.\n    'noptin-select': _noptin_select_vue__WEBPACK_IMPORTED_MODULE_0__.default\n  },\n  el: '#noptin-automation-rule-editor',\n  data: jQuery.extend(true, {}, noptinRules),\n  methods: {\n    saveRule: function saveRule() {\n      var $ = jQuery; // Provide visual feedback by fading the form.\n\n      $(this.$el).fadeTo('fast', 0.33);\n      $(this.$el).find('.save-automation-rule').css({\n        'visibility': 'visible'\n      }); // Prepare rule data.\n\n      var data = {\n        'id': this.rule_id,\n        'action_settings': this.action_settings,\n        'trigger_settings': this.trigger_settings,\n        'action': 'noptin_save_automation_rule',\n        '_ajax_nonce': noptinRules.nonce\n      };\n\n      if (jQuery('#wp-noptinemailbody-wrap').length) {\n        if (tinyMCE.get('noptinemailbody')) {\n          this.action_settings.email_content = tinyMCE.get('noptinemailbody').getContent();\n        } else {\n          this.action_settings.email_content = $('#noptinemailbody').val();\n        }\n      }\n\n      var error = this.error;\n      var saved = this.saved;\n      var el = this.$el; // Hide form notices.\n\n      $(this.$el).find('.noptin-save-saved').hide();\n      $(this.$el).find('.noptin-save-error').hide(); // Post the state data to the server.\n\n      jQuery.post(noptinRules.ajaxurl, data) // Show a success msg after we are done.\n      .done(function () {\n        $(el).find('.noptin-save-saved').show().html(\"<p>\".concat(saved, \"</p>\"));\n      }) // Else alert the user about the error.\n      .fail(function () {\n        $(el).find('.noptin-save-error').show().html(\"<p>\".concat(error, \"</p>\"));\n      }).always(function () {\n        $(el).fadeTo('fast', 1).find('.save-automation-rule').css({\n          'visibility': 'hidden'\n        });\n      });\n    }\n  },\n  mounted: function mounted() {}\n});\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (rulesApp);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/automation-rules-editor.js?");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-3[0].rules[0].use!./node_modules/vue-loader/lib/index.js??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-3[0].rules[0].use!./node_modules/vue-loader/lib/index.js??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

eval("//\n//\n//\n//\n//\n//\nmodule.exports = {\n  props: ['value', 'tags'],\n  mounted: function mounted() {\n    var _this = this;\n\n    var tags = this.tags == 'yes';\n    jQuery(this.$el) // init select2\n    .select2({\n      width: 'resolve',\n      tags: tags\n    }) //Sync the current value\n    .val(this.value) //Then trigger a change event\n    .trigger('change.select2') // emit input event on change.\n    .on('change', function (e) {\n      _this.$emit('input', jQuery(e.currentTarget).val());\n    });\n  },\n  watch: {\n    value: function value(_value) {\n      // update value\n      jQuery(this.$el).val(_value).trigger('change.select2');\n    }\n  },\n  destroyed: function destroyed() {\n    jQuery(this.$el).off().select2('destroy');\n  }\n};\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/noptin-select.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-3%5B0%5D.rules%5B0%5D.use!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_3_0_rules_0_use_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-3[0].rules[0].use!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./noptin-select.vue?vue&type=script&lang=js& */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-3[0].rules[0].use!./node_modules/vue-loader/lib/index.js??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_3_0_rules_0_use_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_babel_loader_lib_index_js_clonedRuleSet_3_0_rules_0_use_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__);\n/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};\n/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _node_modules_babel_loader_lib_index_js_clonedRuleSet_3_0_rules_0_use_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== \"default\") __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _node_modules_babel_loader_lib_index_js_clonedRuleSet_3_0_rules_0_use_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[__WEBPACK_IMPORT_KEY__]\n/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((_node_modules_babel_loader_lib_index_js_clonedRuleSet_3_0_rules_0_use_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default())); \n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/noptin-select.vue?");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&":
/*!******************************************************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6& ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./noptin-select.vue?vue&type=template&id=9c8262a6& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&\");\n\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/noptin-select.vue?");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&":
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6& ***!
  \*********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function() {\n  var _vm = this\n  var _h = _vm.$createElement\n  var _c = _vm._self._c || _h\n  return _c(\n    \"select\",\n    { staticStyle: { width: \"100%\" } },\n    [_vm._t(\"default\")],\n    2\n  )\n}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/noptin-select.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ normalizeComponent)\n/* harmony export */ });\n/* globals __VUE_SSR_CONTEXT__ */\n\n// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).\n// This module is a runtime utility for cleaner component module output and will\n// be included in the final webpack user bundle.\n\nfunction normalizeComponent (\n  scriptExports,\n  render,\n  staticRenderFns,\n  functionalTemplate,\n  injectStyles,\n  scopeId,\n  moduleIdentifier, /* server only */\n  shadowMode /* vue-cli only */\n) {\n  // Vue.extend constructor export interop\n  var options = typeof scriptExports === 'function'\n    ? scriptExports.options\n    : scriptExports\n\n  // render functions\n  if (render) {\n    options.render = render\n    options.staticRenderFns = staticRenderFns\n    options._compiled = true\n  }\n\n  // functional template\n  if (functionalTemplate) {\n    options.functional = true\n  }\n\n  // scopedId\n  if (scopeId) {\n    options._scopeId = 'data-v-' + scopeId\n  }\n\n  var hook\n  if (moduleIdentifier) { // server build\n    hook = function (context) {\n      // 2.3 injection\n      context =\n        context || // cached call\n        (this.$vnode && this.$vnode.ssrContext) || // stateful\n        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional\n      // 2.2 with runInNewContext: true\n      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {\n        context = __VUE_SSR_CONTEXT__\n      }\n      // inject component styles\n      if (injectStyles) {\n        injectStyles.call(this, context)\n      }\n      // register component module identifier for async chunk inferrence\n      if (context && context._registeredComponents) {\n        context._registeredComponents.add(moduleIdentifier)\n      }\n    }\n    // used by ssr in case component is cached and beforeCreate\n    // never gets called\n    options._ssrRegister = hook\n  } else if (injectStyles) {\n    hook = shadowMode\n      ? function () {\n        injectStyles.call(\n          this,\n          (options.functional ? this.parent : this).$root.$options.shadowRoot\n        )\n      }\n      : injectStyles\n  }\n\n  if (hook) {\n    if (options.functional) {\n      // for template-only hot-reload because in that case the render fn doesn't\n      // go through the normalizer\n      options._injectStyles = hook\n      // register for functional component in vue file\n      var originalRender = options.render\n      options.render = function renderWithStyleInjection (h, context) {\n        hook.call(context)\n        return originalRender(h, context)\n      }\n    } else {\n      // inject component registration as beforeCreate hook\n      var existing = options.beforeCreate\n      options.beforeCreate = existing\n        ? [].concat(existing, hook)\n        : [hook]\n    }\n  }\n\n  return {\n    exports: scriptExports,\n    options: options\n  }\n}\n\n\n//# sourceURL=webpack://noptin/./node_modules/vue-loader/lib/runtime/componentNormalizer.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin-select.vue":
/*!***********************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin-select.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./noptin-select.vue?vue&type=template&id=9c8262a6& */ \"./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&\");\n/* harmony import */ var _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./noptin-select.vue?vue&type=script&lang=js& */ \"./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&\");\n/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};\n/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__) if(__WEBPACK_IMPORT_KEY__ !== \"default\") __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[__WEBPACK_IMPORT_KEY__]\n/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n\n\n\n\n\n/* normalize component */\n;\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__.default)(\n  _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,\n  _noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__.render,\n  _noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"includes/assets/js/src/partials/noptin-select.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/noptin-select.vue?");

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
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
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
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/automation-rules.js");
/******/ 	
/******/ })()
;