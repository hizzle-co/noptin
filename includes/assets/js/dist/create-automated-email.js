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

/***/ "./includes/assets/js/src/components/email-campaigns/create-automated-email.js":
/*!*************************************************************************************!*\
  !*** ./includes/assets/js/src/components/email-campaigns/create-automated-email.js ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ CreateAutomatedEmail)\n/* harmony export */ });\n/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ \"./node_modules/@babel/runtime/helpers/esm/extends.js\");\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ \"./node_modules/@babel/runtime/helpers/esm/slicedToArray.js\");\n/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/typeof */ \"./node_modules/@babel/runtime/helpers/esm/typeof.js\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/api-fetch */ \"@wordpress/api-fetch\");\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../section */ \"./includes/assets/js/src/components/section.js\");\n\n\n\n\n/**\r\n * WordPress dependencies\r\n */\n\n\n\n\n\n/**\r\n * Local dependancies.\r\n */\n\n\n/**\r\n * Displays a campaign type.\r\n *\r\n * @param {Object} props\r\n * @param {String} props.name The campaign type.\r\n * @param {String} props.title The campaign type title.\r\n * @param {String} props.description The campaign type description.\r\n * @param {String|Object} props.image The campaign type image.\r\n * @param {Boolean} props.is_available Whether the campaign type is available.\r\n * @param {String} props.create_url The campaign type creation URL.\r\n * @param {String} props.upgrade_url The campaign type upgrade URL.\r\n * @return {JSX.Element}\r\n */\nfunction EmailType(_ref) {\n  var name = _ref.name,\n    title = _ref.title,\n    description = _ref.description,\n    image = _ref.image,\n    is_available = _ref.is_available,\n    create_url = _ref.create_url,\n    upgrade_url = _ref.upgrade_url;\n  var Image = function Image() {\n    // URLs.\n    if (typeof image === 'string' && image.startsWith('http')) {\n      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"img\", {\n        src: image,\n        alt: title\n      });\n    }\n\n    // Dashicons.\n    if (typeof image === 'string') {\n      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Icon, {\n        size: 64,\n        icon: image,\n        style: {\n          color: '#424242'\n        }\n      });\n    }\n\n    // SVG or Dashicons with fill color.\n    if ((0,_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(image) === 'object') {\n      var fill = image.fill || '#008000';\n      var path = image.path || '';\n      var viewBox = image.viewBox || '0 0 64 64';\n      if (image.path) {\n        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.SVG, {\n          viewBox: viewBox,\n          xmlns: \"http://www.w3.org/2000/svg\"\n        }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Path, {\n          fill: fill,\n          d: path\n        }));\n      }\n      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Icon, {\n        size: 64,\n        style: {\n          color: fill\n        },\n        icon: image.icon\n      });\n    }\n    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Icon, {\n      size: 64,\n      icon: \"email\",\n      style: {\n        color: '#424242'\n      }\n    });\n    ;\n  };\n  var buttonVariant = is_available ? 'primary' : 'secondary';\n  var buttonIcon = is_available ? 'arrow-right-alt' : 'lock';\n  var buttonLabel = is_available ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Set-up', 'newsletter-optin-box') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Upgrade', 'newsletter-optin-box');\n  var buttonUrl = is_available ? create_url : upgrade_url;\n  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.FlexItem, {\n    as: _wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Card,\n    className: \"noptin-component-card noptin-automated-email-type noptin-automated-email-type__\".concat(name),\n    variant: \"secondary\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Flex, {\n    direction: \"column\",\n    justify: \"space-between\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.FlexBlock, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CardBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Flex, {\n    wrap: true\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.FlexItem, {\n    className: \"noptin-component-card-image\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(Image, null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.FlexBlock, {\n    className: \"noptin-component-card-content\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"h3\", null, title), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"p\", null, description), !is_available ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"p\", {\n    style: {\n      color: '#a00'\n    }\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"em\", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Not available in your plan', 'newsletter-optin-box'))) : null)))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.FlexItem, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CardFooter, {\n    className: \"noptin-email-type-action\",\n    justify: \"flex-end\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {\n    variant: buttonVariant,\n    href: buttonUrl\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"span\", {\n    className: \"noptin-email-type-action__label\"\n  }, buttonLabel), \"\\xA0\", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Icon, {\n    size: 16,\n    icon: buttonIcon\n  }))))));\n}\n\n/**\r\n * Displays several campaign types.\r\n *\r\n * @param {Object} props\r\n * @param {Array} props.types The campaign types.\r\n * @param {String} props.title The campaign types title.\r\n * @return {JSX.Element}\r\n */\nfunction EmailTypes(_ref2) {\n  var types = _ref2.types,\n    title = _ref2.title;\n  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false),\n    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(_useState, 2),\n    showingAll = _useState2[0],\n    setShowingAll = _useState2[1];\n  var shouldLimit = types.length > 3;\n  if (types.length === 0) {\n    return null;\n  }\n  var visibleTypes = showingAll ? types : types.slice(0, 3);\n  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_section__WEBPACK_IMPORTED_MODULE_7__[\"default\"], {\n    className: \"noptin-automated-email-types\",\n    title: title\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CardBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Flex, {\n    className: \"noptin-component-card-list\",\n    justify: \"left\",\n    align: \"stretch\",\n    wrap: true\n  }, visibleTypes.map(function (type, index) {\n    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(EmailType, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__[\"default\"])({\n      key: index\n    }, type));\n  }))), shouldLimit ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CardFooter, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"div\", {\n    className: \"noptin-automated-email-types__show-all\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {\n    isLink: true,\n    onClick: function onClick() {\n      return setShowingAll(!showingAll);\n    }\n  }, showingAll ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Show less', 'newsletter-optin-box') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Show all', 'newsletter-optin-box')))) : null);\n}\n\n/**\r\n * Groups the campaign types by category.\r\n *\r\n * @param {Array} types The campaign types.\r\n * @returns {Object}\r\n */\nfunction groupTypesByCategory(types) {\n  var categories = {};\n  types.forEach(function (type) {\n    if (!categories[type.category]) {\n      categories[type.category] = [];\n    }\n    categories[type.category].push(type);\n  });\n  return categories;\n}\n\n/**\r\n * Displays the app.\r\n *\r\n * @param {Object} props\r\n * @returns {JSX.Element}\r\n */\nfunction CreateAutomatedEmail() {\n  // Prepare the app.\n  var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(true),\n    _useState4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(_useState3, 2),\n    loading = _useState4[0],\n    setLoading = _useState4[1];\n  var _useState5 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)([]),\n    _useState6 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(_useState5, 2),\n    types = _useState6[0],\n    setTypes = _useState6[1];\n  var _useState7 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null),\n    _useState8 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(_useState7, 2),\n    error = _useState8[0],\n    setError = _useState8[1];\n\n  // Fetch the campaign types.\n  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {\n    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({\n      path: '/noptin/v1/automated-email-campaign-types'\n    }).then(function (types) {\n      setTypes(groupTypesByCategory(types));\n    })[\"catch\"](function (error) {\n      setError(error);\n    })[\"finally\"](function () {\n      setLoading(false);\n    });\n  }, []);\n\n  // Loading indicator.\n  if (loading) {\n    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Spinner, null);\n  }\n\n  // Spinner.\n  if (error) {\n    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Notice, {\n      status: \"error\",\n      isDismissible: false\n    }, error.message);\n  }\n  var categories = Object.keys(types);\n\n  // Display the app.\n  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(\"div\", {\n    className: \"noptin-es6-app\"\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.SlotFillProvider, null, categories.map(function (category, index) {\n    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(EmailTypes, {\n      key: index,\n      title: category,\n      types: types[category]\n    });\n  })));\n}\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/components/email-campaigns/create-automated-email.js?");

/***/ }),

/***/ "./includes/assets/js/src/components/section.js":
/*!******************************************************!*\
  !*** ./includes/assets/js/src/components/section.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ Section)\n/* harmony export */ });\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ \"./node_modules/@babel/runtime/helpers/esm/slicedToArray.js\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);\n\n\n\n\n\n/**\r\n * Displays a section.\r\n *\r\n * @param {Object} props\r\n * @param {String} props.title\r\n * @param {JSX.Element} props.children\r\n * @param {String} props.className\r\n * @param {Boolean} props.isSecodary\r\n * @return {JSX.Element}\r\n */\nfunction Section(_ref) {\n  var title = _ref.title,\n    isSecodary = _ref.isSecodary,\n    className = _ref.className,\n    children = _ref.children;\n  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true),\n    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(_useState, 2),\n    isOpen = _useState2[0],\n    setIsOpen = _useState2[1];\n  className = className || '';\n  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Card, {\n    variant: isSecodary ? 'secondary' : 'primary',\n    className: \"noptin-component__section \".concat(className)\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.CardHeader, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Flex, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FlexBlock, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(\"h3\", null, title)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FlexItem, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {\n    isTertiary: true,\n    onClick: function onClick() {\n      return setIsOpen(!isOpen);\n    }\n  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Icon, {\n    icon: isOpen ? 'arrow-up-alt2' : 'arrow-down-alt2'\n  }))))), isOpen && children);\n}\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/components/section.js?");

/***/ }),

/***/ "./includes/assets/js/src/create-automated-email.js":
/*!**********************************************************!*\
  !*** ./includes/assets/js/src/create-automated-email.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/dom-ready */ \"@wordpress/dom-ready\");\n/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _components_email_campaigns_create_automated_email__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/email-campaigns/create-automated-email */ \"./includes/assets/js/src/components/email-campaigns/create-automated-email.js\");\n\n\n\n\n_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1___default()(function () {\n  // Fetch rule ID and action and trigger editor div.\n  var container = document.getElementById('noptin-create-automated-email__app');\n  if (container) {\n    var App = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.StrictMode, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_email_campaigns_create_automated_email__WEBPACK_IMPORTED_MODULE_2__[\"default\"], null));\n\n    // React 18.\n    if (_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot) {\n      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot)(container).render(App);\n    } else {\n      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)(App, container);\n    }\n  }\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/create-automated-email.js?");

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/dom-ready":
/*!**********************************!*\
  !*** external ["wp","domReady"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["domReady"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _arrayLikeToArray)\n/* harmony export */ });\nfunction _arrayLikeToArray(arr, len) {\n  if (len == null || len > arr.length) len = arr.length;\n  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];\n  return arr2;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _arrayWithHoles)\n/* harmony export */ });\nfunction _arrayWithHoles(arr) {\n  if (Array.isArray(arr)) return arr;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/extends.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/extends.js ***!
  \************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _extends)\n/* harmony export */ });\nfunction _extends() {\n  _extends = Object.assign ? Object.assign.bind() : function (target) {\n    for (var i = 1; i < arguments.length; i++) {\n      var source = arguments[i];\n      for (var key in source) {\n        if (Object.prototype.hasOwnProperty.call(source, key)) {\n          target[key] = source[key];\n        }\n      }\n    }\n    return target;\n  };\n  return _extends.apply(this, arguments);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/extends.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _iterableToArrayLimit)\n/* harmony export */ });\nfunction _iterableToArrayLimit(arr, i) {\n  var _i = null == arr ? null : \"undefined\" != typeof Symbol && arr[Symbol.iterator] || arr[\"@@iterator\"];\n  if (null != _i) {\n    var _s,\n      _e,\n      _x,\n      _r,\n      _arr = [],\n      _n = !0,\n      _d = !1;\n    try {\n      if (_x = (_i = _i.call(arr)).next, 0 === i) {\n        if (Object(_i) !== _i) return;\n        _n = !1;\n      } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0);\n    } catch (err) {\n      _d = !0, _e = err;\n    } finally {\n      try {\n        if (!_n && null != _i[\"return\"] && (_r = _i[\"return\"](), Object(_r) !== _r)) return;\n      } finally {\n        if (_d) throw _e;\n      }\n    }\n    return _arr;\n  }\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js":
/*!********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _nonIterableRest)\n/* harmony export */ });\nfunction _nonIterableRest() {\n  throw new TypeError(\"Invalid attempt to destructure non-iterable instance.\\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.\");\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/slicedToArray.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _slicedToArray)\n/* harmony export */ });\n/* harmony import */ var _arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayWithHoles.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js\");\n/* harmony import */ var _iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./iterableToArrayLimit.js */ \"./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js\");\n/* harmony import */ var _unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./unsupportedIterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js\");\n/* harmony import */ var _nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./nonIterableRest.js */ \"./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js\");\n\n\n\n\nfunction _slicedToArray(arr, i) {\n  return (0,_arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr) || (0,_iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(arr, i) || (0,_unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(arr, i) || (0,_nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])();\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/slicedToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js":
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _typeof)\n/* harmony export */ });\nfunction _typeof(obj) {\n  \"@babel/helpers - typeof\";\n\n  return _typeof = \"function\" == typeof Symbol && \"symbol\" == typeof Symbol.iterator ? function (obj) {\n    return typeof obj;\n  } : function (obj) {\n    return obj && \"function\" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? \"symbol\" : typeof obj;\n  }, _typeof(obj);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/typeof.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _unsupportedIterableToArray)\n/* harmony export */ });\n/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayLikeToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js\");\n\nfunction _unsupportedIterableToArray(o, minLen) {\n  if (!o) return;\n  if (typeof o === \"string\") return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(o, minLen);\n  var n = Object.prototype.toString.call(o).slice(8, -1);\n  if (n === \"Object\" && o.constructor) n = o.constructor.name;\n  if (n === \"Map\" || n === \"Set\") return Array.from(o);\n  if (n === \"Arguments\" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(o, minLen);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js?");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/create-automated-email.js");
/******/ 	
/******/ })()
;