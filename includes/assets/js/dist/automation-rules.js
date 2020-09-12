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
/******/ 	return __webpack_require__(__webpack_require__.s = "./includes/assets/js/src/automation-rules.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./includes/assets/js/src/automation-rules.js":
/*!****************************************************!*\
  !*** ./includes/assets/js/src/automation-rules.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("(function ($) {\n  // Enables/Disables the submit button depending on whether or not\n  // both a trigger and an action have been set.\n  var maybeEnableSubmit = function maybeEnableSubmit() {\n    // Set up the current trigger and action.\n    var trigger = $('.noptin-automation-rule-trigger-hidden').val();\n    var action = $('.noptin-automation-rule-action-hidden').val(); // Are both of them set-up?\n\n    if (trigger && action) {\n      $('.noptin-automation-rule-create').prop(\"disabled\", false).removeClass('button-secondary').addClass('button-primary');\n    } else {\n      $('.noptin-automation-rule-create').prop(\"disabled\", true).removeClass('button-primary').addClass('button-secondary');\n    }\n  };\n\n  $('#noptin-automation-rule-editor .noptin-automation-rule-trigger').ddslick({\n    width: 400,\n    onSelected: function onSelected(data) {\n      var selected = data.selectedData.value;\n      $('.noptin-automation-rule-trigger-hidden').val(selected);\n      maybeEnableSubmit();\n    }\n  });\n  $('#noptin-automation-rule-editor .noptin-automation-rule-action').ddslick({\n    width: 400,\n    onSelected: function onSelected(data) {\n      var selected = data.selectedData.value;\n      $('.noptin-automation-rule-action-hidden').val(selected);\n      maybeEnableSubmit();\n    }\n  });\n\n  if ($('#noptin-automation-rule-editor.edit-automation-rule').length) {\n    __webpack_require__(/*! ./partials/automation-rules-editor.js */ \"./includes/assets/js/src/partials/automation-rules-editor.js\").default;\n  }\n})(jQuery);\n\n//# sourceURL=webpack:///./includes/assets/js/src/automation-rules.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/automation-rules-editor.js":
/*!********************************************************************!*\
  !*** ./includes/assets/js/src/partials/automation-rules-editor.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _noptin_select_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./noptin-select.vue */ \"./includes/assets/js/src/partials/noptin-select.vue\");\n\nvar rulesApp = new Vue({\n  components: {\n    // Select2.\n    'noptin-select': _noptin_select_vue__WEBPACK_IMPORTED_MODULE_0__[\"default\"]\n  },\n  el: '#noptin-automation-rule-editor',\n  data: jQuery.extend(true, {}, noptinRules),\n  methods: {\n    saveRule: function saveRule() {\n      var $ = jQuery; // Provide visual feedback by fading the form.\n\n      $(this.$el).fadeTo('fast', 0.33);\n      $(this.$el).find('.save-automation-rule').css({\n        'visibility': 'visible'\n      }); // Prepare rule data.\n\n      var data = {\n        'id': this.rule_id,\n        'action_settings': this.action_settings,\n        'trigger_settings': this.trigger_settings,\n        'action': 'noptin_save_automation_rule',\n        '_ajax_nonce': noptinRules.nonce\n      };\n\n      if (jQuery('#wp-noptinemailbody-wrap').length) {\n        this.action_settings.email_content = tinyMCE.get('noptinemailbody').getContent();\n      }\n\n      var error = this.error;\n      var saved = this.saved;\n      var el = this.$el; // Hide form notices.\n\n      $(this.$el).find('.noptin-save-saved').hide();\n      $(this.$el).find('.noptin-save-error').hide(); // Post the state data to the server.\n\n      jQuery.post(noptinRules.ajaxurl, data) // Show a success msg after we are done.\n      .done(function () {\n        $(el).find('.noptin-save-saved').show().html(\"<p>\".concat(saved, \"</p>\"));\n      }) // Else alert the user about the error.\n      .fail(function () {\n        $(el).find('.noptin-save-error').show().html(\"<p>\".concat(error, \"</p>\"));\n      }).always(function () {\n        $(el).fadeTo('fast', 1).find('.save-automation-rule').css({\n          'visibility': 'hidden'\n        });\n      });\n    }\n  },\n  mounted: function mounted() {}\n});\n/* harmony default export */ __webpack_exports__[\"default\"] = (rulesApp);\n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/automation-rules-editor.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin-select.vue":
/*!***********************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin-select.vue ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./noptin-select.vue?vue&type=template&id=9c8262a6& */ \"./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&\");\n/* harmony import */ var _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./noptin-select.vue?vue&type=script&lang=js& */ \"./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&\");\n/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__) if([\"default\"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[key]; }) }(__WEBPACK_IMPORT_KEY__));\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n\n\n\n\n\n/* normalize component */\n\nvar component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(\n  _noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__[\"render\"],\n  _noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__[\"staticRenderFns\"],\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"includes/assets/js/src/partials/noptin-select.vue\"\n/* harmony default export */ __webpack_exports__[\"default\"] = (component.exports);\n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/noptin-select.vue?");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib??ref--3!../../../../../node_modules/vue-loader/lib??vue-loader-options!./noptin-select.vue?vue&type=script&lang=js& */ \"./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__);\n/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if([\"default\"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));\n /* harmony default export */ __webpack_exports__[\"default\"] = (_node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default.a); \n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/noptin-select.vue?");

/***/ }),

/***/ "./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&":
/*!******************************************************************************************!*\
  !*** ./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6& ***!
  \******************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib??vue-loader-options!./noptin-select.vue?vue&type=template&id=9c8262a6& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&\");\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"render\", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__[\"render\"]; });\n\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"staticRenderFns\", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_noptin_select_vue_vue_type_template_id_9c8262a6___WEBPACK_IMPORTED_MODULE_0__[\"staticRenderFns\"]; });\n\n\n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/noptin-select.vue?");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--3!./node_modules/vue-loader/lib??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("//\n//\n//\n//\n//\n//\nmodule.exports = {\n  props: ['value', 'tags'],\n  mounted: function mounted() {\n    var _this = this;\n\n    var tags = this.tags == 'yes';\n    jQuery(this.$el) // init select2\n    .select2({\n      width: 'resolve',\n      tags: tags\n    }) //Sync the current value\n    .val(this.value) //Then trigger a change event\n    .trigger('change.select2') // emit input event on change.\n    .on('change', function (e) {\n      _this.$emit('input', jQuery(e.currentTarget).val());\n    });\n  },\n  watch: {\n    value: function value(_value) {\n      // update value\n      jQuery(this.$el).val(_value).trigger('change.select2');\n    }\n  },\n  destroyed: function destroyed() {\n    jQuery(this.$el).off().select2('destroy');\n  }\n};\n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/noptin-select.vue?./node_modules/babel-loader/lib??ref--3!./node_modules/vue-loader/lib??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6&":
/*!************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./includes/assets/js/src/partials/noptin-select.vue?vue&type=template&id=9c8262a6& ***!
  \************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"render\", function() { return render; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"staticRenderFns\", function() { return staticRenderFns; });\nvar render = function() {\n  var _vm = this\n  var _h = _vm.$createElement\n  var _c = _vm._self._c || _h\n  return _c(\n    \"select\",\n    { staticStyle: { width: \"100%\" } },\n    [_vm._t(\"default\")],\n    2\n  )\n}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n\n//# sourceURL=webpack:///./includes/assets/js/src/partials/noptin-select.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"default\", function() { return normalizeComponent; });\n/* globals __VUE_SSR_CONTEXT__ */\n\n// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).\n// This module is a runtime utility for cleaner component module output and will\n// be included in the final webpack user bundle.\n\nfunction normalizeComponent (\n  scriptExports,\n  render,\n  staticRenderFns,\n  functionalTemplate,\n  injectStyles,\n  scopeId,\n  moduleIdentifier, /* server only */\n  shadowMode /* vue-cli only */\n) {\n  // Vue.extend constructor export interop\n  var options = typeof scriptExports === 'function'\n    ? scriptExports.options\n    : scriptExports\n\n  // render functions\n  if (render) {\n    options.render = render\n    options.staticRenderFns = staticRenderFns\n    options._compiled = true\n  }\n\n  // functional template\n  if (functionalTemplate) {\n    options.functional = true\n  }\n\n  // scopedId\n  if (scopeId) {\n    options._scopeId = 'data-v-' + scopeId\n  }\n\n  var hook\n  if (moduleIdentifier) { // server build\n    hook = function (context) {\n      // 2.3 injection\n      context =\n        context || // cached call\n        (this.$vnode && this.$vnode.ssrContext) || // stateful\n        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional\n      // 2.2 with runInNewContext: true\n      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {\n        context = __VUE_SSR_CONTEXT__\n      }\n      // inject component styles\n      if (injectStyles) {\n        injectStyles.call(this, context)\n      }\n      // register component module identifier for async chunk inferrence\n      if (context && context._registeredComponents) {\n        context._registeredComponents.add(moduleIdentifier)\n      }\n    }\n    // used by ssr in case component is cached and beforeCreate\n    // never gets called\n    options._ssrRegister = hook\n  } else if (injectStyles) {\n    hook = shadowMode\n      ? function () {\n        injectStyles.call(\n          this,\n          (options.functional ? this.parent : this).$root.$options.shadowRoot\n        )\n      }\n      : injectStyles\n  }\n\n  if (hook) {\n    if (options.functional) {\n      // for template-only hot-reload because in that case the render fn doesn't\n      // go through the normalizer\n      options._injectStyles = hook\n      // register for functional component in vue file\n      var originalRender = options.render\n      options.render = function renderWithStyleInjection (h, context) {\n        hook.call(context)\n        return originalRender(h, context)\n      }\n    } else {\n      // inject component registration as beforeCreate hook\n      var existing = options.beforeCreate\n      options.beforeCreate = existing\n        ? [].concat(existing, hook)\n        : [hook]\n    }\n  }\n\n  return {\n    exports: scriptExports,\n    options: options\n  }\n}\n\n\n//# sourceURL=webpack:///./node_modules/vue-loader/lib/runtime/componentNormalizer.js?");

/***/ })

/******/ });