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

/***/ "./includes/assets/js/src/partials/frontend/display-popup.js":
/*!*******************************************************************!*\
  !*** ./includes/assets/js/src/partials/frontend/display-popup.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ display)\n/* harmony export */ });\n/* harmony import */ var _myquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./myquery */ \"./includes/assets/js/src/partials/frontend/myquery.js\");\n\nfunction display(popup, force) {\n  // Set variables.\n  var popup_type = popup.dataset.type;\n  if (!window.noptinPopups[popup_type]) {\n    window.noptinPopups[popup_type] = {\n      showing: false,\n      closed: false\n    };\n  }\n\n  // Abort if a popup is already showing.\n  if (!force && (window.noptinPopups[popup_type].showing || window.noptinSubscribed)) {\n    return;\n  }\n\n  // Do not display a popup that has been closed.\n  if (!force) {\n    if (!popup.dataset.key) {\n      return;\n    }\n    if (sessionStorage.getItem(\"noptinFormDisplayed\" + popup.dataset.key)) {\n      return;\n    }\n  }\n\n  // Log that we're already displayed the popup in this session.\n  sessionStorage.setItem(\"noptinFormDisplayed\" + popup.dataset.key, '1');\n\n  // Indicate that we're displaying a popup.\n  window.noptinPopups[popup_type].showing = true;\n\n  // Closes the popup.\n  var closePopup = function closePopup() {\n    window.noptinPopups[popup_type].showing = false;\n    (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup).removeClass('noptin-show');\n    (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])('body').removeClass('noptin-showing-' + popup_type);\n    if ('popup' == popup_type) {\n      (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])('body').removeClass('noptin-hide-overflow');\n    }\n  };\n\n  // Display the popup.\n  (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup).addClass('noptin-show');\n  (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])('body').addClass('noptin-showing-' + popup_type);\n  if ('popup' == popup_type) {\n    (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])('body').addClass('noptin-hide-overflow');\n  }\n\n  // Close the popup.\n  (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup).find('.noptin-close-popup').on('click', closePopup);\n  if ('popup' == popup_type) {\n    (0,_myquery__WEBPACK_IMPORTED_MODULE_0__[\"default\"])('.noptin-popup-backdrop').on('click', closePopup);\n  }\n}\n;\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/frontend/display-popup.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/frontend/myquery.js":
/*!*************************************************************!*\
  !*** ./includes/assets/js/src/partials/frontend/myquery.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _babel_runtime_helpers_construct__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/construct */ \"./node_modules/@babel/runtime/helpers/esm/construct.js\");\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ \"./node_modules/@babel/runtime/helpers/esm/classCallCheck.js\");\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ \"./node_modules/@babel/runtime/helpers/esm/createClass.js\");\n/* harmony import */ var _babel_runtime_helpers_toArray__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/toArray */ \"./node_modules/@babel/runtime/helpers/esm/toArray.js\");\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ \"./node_modules/@babel/runtime/helpers/esm/slicedToArray.js\");\n/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ \"./node_modules/@babel/runtime/helpers/esm/defineProperty.js\");\n/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ \"./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js\");\n\n\n\n\n\n\n\nfunction ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }\nfunction _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_5__[\"default\"])(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }\n// A clone of jquery.\nvar savedEvents = [];\n\n/**\r\n * Naively checks if a given event name is a native event.\r\n * @param {String} event Name of the event to test\r\n * @returns {Boolean}\r\n */\nvar isNativeEvent = function isNativeEvent(event) {\n  return typeof document[\"on\".concat(event)] !== \"undefined\";\n};\n\n/**\r\n * Checks if an event target is our intended target to call the handler for.\r\n * @param {HTMLElement} eventTarget Target passed from event.\r\n * @param {String} delegatedTarget Selector of a delegation target.\r\n * @param {HTMLElement} originalTarget \"Main\" (non delegated) target.\r\n * @returns {Boolean}\r\n */\nvar isTarget = function isTarget(eventTarget, delegatedTarget, originalTarget) {\n  /**\r\n   * If no delegate passed, then the event must have been called on\r\n   * on the original target or its descendents. No questions asked.\r\n   */\n  if (!delegatedTarget || typeof delegatedTarget !== \"string\") {\n    return true;\n  }\n\n  /**\r\n   * True if:\r\n   * 1. The event target matches the delegate target\r\n   * 2. The event target is a descendent of the delegate target.\r\n   */\n  return matches(eventTarget, delegatedTarget) || originalTarget.contains(eventTarget.closest(delegatedTarget));\n};\n\n/**\r\n * Checks that a given element complies with a supplied selector.\r\n * @param {HTMLElement} target Target element to test.\r\n * @param {String} selector Selector to test the element with.\r\n * @returns {Boolean}\r\n */\nvar matches = function matches(target, selector) {\n  if (!target) {\n    return false;\n  }\n  if (typeof target.matches === \"function\") {\n    return target.matches(selector);\n  } else if (typeof target.msMatchesSelector === \"function\") {\n    return target.msMatchesSelector(selector);\n  }\n  return false;\n};\n\n/**\r\n * Generates a list of nodes from a selector or an EventTarget.\r\n * @param {*} nodes\r\n * @returns {Array<EventTarget>}\r\n */\nvar parseNode = function parseNode(nodes) {\n  if (!nodes) {\n    return [];\n  }\n  if (typeof nodes === \"string\") {\n    return (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_6__[\"default\"])(document.querySelectorAll(nodes));\n  } else if (nodes instanceof NodeList) {\n    return (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_6__[\"default\"])(nodes);\n  } else if (typeof nodes.addEventListener === \"function\") {\n    return [nodes];\n  }\n  return [];\n};\n\n/**\r\n * Splits a string by ' ' and removes duplicates.\r\n * @param {String} events\r\n * @returns {Array<String>}\r\n */\nvar splitEvents = function splitEvents(events) {\n  if (typeof events !== \"string\") {\n    return [];\n  }\n  var uniqueEvents = events.split(\" \").reduce(function (_ref, current) {\n    var keys = _ref.keys,\n      existing = _ref.existing;\n    if (existing[current]) {\n      return {\n        keys: keys,\n        existing: existing\n      };\n    }\n    return {\n      keys: [].concat((0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_6__[\"default\"])(keys), [current]),\n      existing: _objectSpread(_objectSpread({}, existing), {}, (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_5__[\"default\"])({}, current, true))\n    };\n  }, {\n    keys: [],\n    existing: {}\n  });\n  return uniqueEvents.keys;\n};\n\n/**\r\n * Registers either a one time or a permanent listener on an EventTarget.\r\n * @param {EventTarget} target Target to add listener to.\r\n * @param {String} eventName Name of the event to listen to.\r\n * @param {Function} handler Handler callback function.\r\n * @param {Object} options.\r\n * @param {String} options.delegate Selector for delegation.\r\n * @param {Boolean} options.once Determines whether the handler should run once or more.\r\n */\nvar listen = function listen(target, eventName, handler, _ref2) {\n  var delegate = _ref2.delegate,\n    once = _ref2.once;\n  // Instead of using the user's own handler, we wrap it with our own.\n  // This is so we can implement deleg\n  var delegateHandler = function delegateHandler(e) {\n    if (isTarget(e.target, delegate, target)) {\n      var data = e && e.detail;\n      handler.call(delegate ? e.target : target, e, data);\n      if (once) {\n        target.removeEventListener(eventName, delegateHandler);\n      }\n    }\n  };\n\n  // We're keeping a reference to the original handler\n  // so the consumer can later on `off` that specific handler\n  delegateHandler.originalHandler = handler;\n  target.addEventListener(eventName, delegateHandler);\n  if (!once) {\n    setEvent(target, eventName, delegateHandler);\n  }\n};\n\n/**\r\n * Dispatches an event on a target, or calls its `on` function.\r\n * @param {EventTarget} target Event target to dispatch the event on.\r\n * @param {String} events space separated list of event names;\r\n * @param {Object} detail EventTarget Detail Object.\r\n * @param {Object} options\r\n */\nvar dispatch = function dispatch(target, events, detail, options) {\n  var hasDispatch = typeof target.dispatchEvent === \"function\";\n  splitEvents(events).forEach(function (eventName) {\n    var nativeEvent = isNativeEvent(eventName);\n    var event;\n    if (detail || !nativeEvent) {\n      event = new CustomEvent(eventName, Object.assign({\n        detail: detail,\n        bubbles: true\n      }, options));\n    } else {\n      event = new Event(eventName, Object.assign({\n        bubbles: true\n      }, options));\n    }\n    if (nativeEvent && typeof target[eventName] === \"function\") {\n      target[eventName]();\n    }\n    if (!hasDispatch) {\n      return;\n    }\n    target.dispatchEvent(event);\n  });\n};\n\n/**\r\n * Stores target and its events for later access.\r\n * @param {EventTarget} target An event target to store.\r\n * @param {String} event Event Name.\r\n * @param {Function} handler Event handler function.\r\n */\nvar setEvent = function setEvent(target, event, handler) {\n  if (!target || !event || !handler) {\n    return;\n  }\n  var targetIndex = savedEvents.findIndex(function (_ref3) {\n    var _ref4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(_ref3, 1),\n      current = _ref4[0];\n    return current === target;\n  });\n\n  // Get the existing target reference, or default to an empty object.\n  var _ref5 = savedEvents[targetIndex] || [target, {}],\n    _ref6 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(_ref5, 2),\n    _ = _ref6[0],\n    targetEvents = _ref6[1];\n  targetEvents[event] = targetEvents[event] || [];\n  targetEvents[event].push(handler);\n  if (targetIndex === -1) {\n    savedEvents.push([target, targetEvents]);\n  } else {\n    savedEvents[targetIndex] = [target, targetEvents];\n  }\n};\n\n/**\r\n * Removes Target events from storage\r\n * @param {EventTarget} target EventTarget to remove.\r\n * @param {String} [events] List of events to remove from storage.\r\n * @param {Function} [handler] Funtion to remove.\r\n */\nvar deleteEvents = function deleteEvents(target, events, handler) {\n  var targetIndex = savedEvents.findIndex(function (_ref7) {\n    var _ref8 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(_ref7, 1),\n      current = _ref8[0];\n    return current === target;\n  });\n  if (targetIndex === -1) {\n    return;\n  }\n  var _savedEvents$targetIn = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(savedEvents[targetIndex], 2),\n    targetEvents = _savedEvents$targetIn[1];\n  var eventsArray = splitEvents(events);\n\n  // Do this for each of the existing events for the current target.\n  var _loop = function _loop(event) {\n    if (\n    // * The consumer requested to remove the current event name\n    //    or if the user did not specify an event name\n    (eventsArray.indexOf(event) !== -1 || !events) &&\n    // * And the current target has this event registered\n    Object.prototype.hasOwnProperty.call(targetEvents, event) &&\n    // * And it is an array (safeguard)\n    Array.isArray(targetEvents[event])) {\n      // Filter out the events that the consumer wanted to remove\n      targetEvents[event] = targetEvents[event].filter(function (currentHandler) {\n        // If the consumer specified a specific handler to remove\n        if (handler) {\n          // and the handler doesn't match the current handler\n          if (currentHandler.originalHandler !== handler) {\n            // keep it in\n            return true;\n          } else {\n            // filter it out and remove its listener;\n            target.removeEventListener(event, currentHandler);\n            return false;\n          }\n        } else {\n          // Remove all handlers for current event name\n          target.removeEventListener(event, currentHandler);\n          return false;\n        }\n      });\n      if (!events) {\n        // Clear all the events\n        delete targetEvents[event];\n      }\n    }\n  };\n  for (var event in targetEvents) {\n    _loop(event);\n  }\n  if (!events) {\n    savedEvents.splice(targetIndex, 1);\n  }\n};\nvar bindEvents = function bindEvents(instance, options, _ref9) {\n  var _ref10 = (0,_babel_runtime_helpers_toArray__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(_ref9),\n    events = _ref10[0],\n    args = _ref10.slice(1);\n  if (!args.length) {\n    // no handler. bye.\n    return;\n  }\n\n  // One liner for:\n  // [handler] = args\n  // [delegate, handler] = args\n  var length = args.length,\n    handler = args[length - 1],\n    delegate = args[length - 2];\n  var eventsArray = splitEvents(events);\n  return instance[\"each\"](function (node) {\n    return eventsArray.forEach(function (event) {\n      return listen(node, event, handler, _objectSpread(_objectSpread({}, options), {}, {\n        delegate: delegate\n      }));\n    });\n  });\n};\n\n// The actual event manager.\nvar myQuery = /*#__PURE__*/function () {\n  function myQuery() {\n    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(this, myQuery);\n    this.length = 0;\n    this.add.apply(this, arguments);\n  }\n\n  // Mocks native splice\n  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(myQuery, [{\n    key: \"splice\",\n    value: function splice() {\n      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {\n        args[_key] = arguments[_key];\n      }\n      return Array.prototype.splice.apply(this, args);\n    }\n\n    // Mocks native forEach\n  }, {\n    key: \"each\",\n    value: function each() {\n      var _Array$prototype$forE;\n      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {\n        args[_key2] = arguments[_key2];\n      }\n      (_Array$prototype$forE = Array.prototype.forEach).call.apply(_Array$prototype$forE, [this].concat(args));\n      return this;\n    }\n\n    // Receives the event targets as an argument.\n    // Example, 'a:first-child'\n  }, {\n    key: \"add\",\n    value: function add() {\n      var _this = this;\n      for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {\n        args[_key3] = arguments[_key3];\n      }\n      args.forEach(function (selector) {\n        var nodeList = parseNode(selector);\n        nodeList.forEach(function (node) {\n          for (var i = 0; i < _this.length; i++) {\n            if (_this[i] === node) {\n              return;\n            }\n          }\n          _this[_this.length] = node;\n          _this.length++;\n        });\n      });\n      return this;\n    }\n\n    // Attaches actual event.\n  }, {\n    key: \"on\",\n    value: function on() {\n      for (var _len4 = arguments.length, args = new Array(_len4), _key4 = 0; _key4 < _len4; _key4++) {\n        args[_key4] = arguments[_key4];\n      }\n      return bindEvents(this, {}, args);\n    }\n\n    // Attaches an event once.\n  }, {\n    key: \"once\",\n    value: function once() {\n      for (var _len5 = arguments.length, args = new Array(_len5), _key5 = 0; _key5 < _len5; _key5++) {\n        args[_key5] = arguments[_key5];\n      }\n      return bindEvents(this, {\n        once: true\n      }, args);\n    }\n\n    //Removes an event.\n  }, {\n    key: \"off\",\n    value: function off(events, handler) {\n      return this[\"each\"](function (target) {\n        return deleteEvents(target, events, handler);\n      });\n    }\n\n    // Triggers specific events.\n  }, {\n    key: \"trigger\",\n    value: function trigger(events) {\n      var _ref11 = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},\n        data = _ref11.data,\n        options = _ref11.options;\n      return this[\"each\"](function (target) {\n        return dispatch(target, events, data, options);\n      });\n    }\n\n    // Checks if a given class name exists.\n  }, {\n    key: \"hasClass\",\n    value: function hasClass(className) {\n      return !!this[0] && this[0].classList.contains(className);\n    }\n\n    // Adds a class to the passed element.\n  }, {\n    key: \"addClass\",\n    value: function addClass(className) {\n      this.each(function (item) {\n        var classList = item.classList;\n        classList.add.apply(classList, className.split(/\\s/));\n      });\n      return this;\n    }\n\n    // Removes a class from the passed element.\n  }, {\n    key: \"removeClass\",\n    value: function removeClass(className) {\n      this.each(function (item) {\n        var classList = item.classList;\n        classList.remove.apply(classList, className.split(/\\s/));\n      });\n      return this;\n    }\n\n    // Toggles a given class name.\n  }, {\n    key: \"toggleClass\",\n    value: function toggleClass(className, b) {\n      this.each(function (item) {\n        var classList = item.classList;\n        if (typeof b !== 'boolean') {\n          b = !classList.contains(className);\n        }\n        classList[b ? 'add' : 'remove'].apply(classList, className.split(/\\s/));\n      });\n      return this;\n    }\n\n    // jQuery html method.\n  }, {\n    key: \"html\",\n    value: function html(_html) {\n      if (typeof _html === 'undefined') {\n        return this[0] ? this[0].innerHTML : '';\n      }\n      this.each(function (item) {\n        item.innerHTML = _html;\n      });\n      return this;\n    }\n\n    // jQuery text method.\n  }, {\n    key: \"text\",\n    value: function text(_text) {\n      if (typeof _text === 'undefined') {\n        return this[0] ? this[0].textContent : '';\n      }\n      this.each(function (item) {\n        item.textContent = _text;\n      });\n      return this;\n    }\n\n    // jQuery find method.\n  }, {\n    key: \"find\",\n    value: function find(el) {\n      var result = new myQuery();\n      if (this[0]) {\n        result.add(this[0].querySelectorAll(el));\n      }\n      return result;\n    }\n\n    /**\r\n     * jQuery append method.\r\n     *\r\n     * @param {String} element\r\n     * @return {myQuery}\r\n     */\n  }, {\n    key: \"append\",\n    value: function append(el) {\n      this.each(function (item) {\n        item.insertAdjacentHTML('beforeend', el);\n      });\n      return this;\n    }\n  }]);\n  return myQuery;\n}();\n;\n\n/**\r\n * Dom selector.\r\n *\r\n * @return {myQuery}\r\n */\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (function () {\n  for (var _len6 = arguments.length, args = new Array(_len6), _key6 = 0; _key6 < _len6; _key6++) {\n    args[_key6] = arguments[_key6];\n  }\n  return (0,_babel_runtime_helpers_construct__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(myQuery, args);\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/frontend/myquery.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/frontend/popup.js":
/*!***********************************************************!*\
  !*** ./includes/assets/js/src/partials/frontend/popup.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ init)\n/* harmony export */ });\n/* harmony import */ var _triggers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./triggers */ \"./includes/assets/js/src/partials/frontend/triggers.js\");\n\nfunction init(popup) {\n  try {\n    // Ensure we have a trigger.\n    if (popup.dataset.trigger && popup.dataset.type) {\n      var triggers = (0,_triggers__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup);\n      var trigger = popup.dataset.trigger;\n      if (trigger && triggers[trigger]) {\n        triggers[trigger]();\n      }\n    }\n  } catch (e) {\n    console.log(e);\n  }\n}\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/frontend/popup.js?");

/***/ }),

/***/ "./includes/assets/js/src/partials/frontend/triggers.js":
/*!**************************************************************!*\
  !*** ./includes/assets/js/src/partials/frontend/triggers.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ trigger)\n/* harmony export */ });\n/* harmony import */ var _display_popup__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./display-popup */ \"./includes/assets/js/src/partials/frontend/display-popup.js\");\n/* harmony import */ var _myquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./myquery */ \"./includes/assets/js/src/partials/frontend/myquery.js\");\n\n\n\n// Calculates the scroll percentage.\nvar getScrollPercent = function getScrollPercent() {\n  var doc = document.documentElement,\n    body = document.body;\n  return (doc.scrollTop || body.scrollTop) / ((doc.scrollHeight || body.scrollHeight) - doc.clientHeight) * 100;\n};\n\n// Throttle from lodash.\nvar throttle = __webpack_require__(/*! lodash.throttle */ \"./node_modules/lodash.throttle/index.js\");\nfunction trigger(popup) {\n  return {\n    // Displays a popup immeadiately.\n    immeadiate: function immeadiate() {\n      (0,_display_popup__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup);\n    },\n    // Exit intent.\n    before_leave: function before_leave() {\n      var _delayTimer = null,\n        sensitivity = 0,\n        //how many pixels from the top should we display the popup?\n        delay = 200; // wait 200ms before displaying popup\n\n      // Fired when the user scrolls out of view.\n      var watchLeave = function watchLeave(e) {\n        // Verify the sensitivity.\n        if (e.clientY > sensitivity) {\n          return;\n        }\n\n        // Wait for a while just in case the user changes their mind.\n        _delayTimer = setTimeout(function () {\n          // Display the popup.\n          (0,_display_popup__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup);\n\n          // Remove watchers.\n          (0,_myquery__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(document).off('mouseleave', watchLeave);\n          (0,_myquery__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(document).off('mouseenter', watchEnter);\n        }, delay);\n      };\n\n      // Fired when the user scrolls into view.\n      var watchEnter = function watchEnter() {\n        if (_delayTimer) {\n          clearTimeout(_delayTimer);\n          _delayTimer = null;\n        }\n      };\n\n      //Display popup when the user tries to leave...\n      (0,_myquery__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(document).on('mouseleave', watchLeave);\n\n      //...unless they decide to come back\n      (0,_myquery__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(document).on('mouseenter', watchEnter);\n    },\n    // After the user starts scrolling.\n    on_scroll: function on_scroll() {\n      // Maximum scroll percentage.\n      var percent = parseFloat(popup.dataset.value);\n      if (isNaN(percent)) {\n        return;\n      }\n\n      // Watch no more than once every 500ms\n      var watchScroll = throttle(function () {\n        if (getScrollPercent() > percent) {\n          (0,_display_popup__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup);\n          (0,_myquery__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(window).off('scroll', watchScroll);\n        }\n      }, 500);\n      (0,_myquery__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(window).on('scroll', watchScroll);\n    },\n    // after_delay.\n    after_delay: function after_delay() {\n      var delay = parseFloat(popup.dataset.value) * 1000;\n      if (isNaN(delay)) {\n        return;\n      }\n      setTimeout(function () {\n        (0,_display_popup__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup);\n      }, delay);\n    },\n    // after_click.\n    after_click: function after_click() {\n      // Abort if target not set.\n      if (!popup.dataset.value) {\n        return;\n      }\n      (0,_myquery__WEBPACK_IMPORTED_MODULE_1__[\"default\"])('body').on('click', popup.dataset.value, function (event) {\n        event.preventDefault();\n        (0,_display_popup__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(popup, true);\n      });\n    }\n  };\n}\n;\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/partials/frontend/triggers.js?");

/***/ }),

/***/ "./includes/assets/js/src/popups.js":
/*!******************************************!*\
  !*** ./includes/assets/js/src/popups.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";
eval("\n\n// Our own version of jQuery document ready.\nvar noptinReady = function noptinReady(cb) {\n  if (document.readyState === 'loading') {\n    document.addEventListener('DOMContentLoaded', cb);\n  } else {\n    cb();\n  }\n};\n\n// Init the plugin on dom ready.\nnoptinReady(function () {\n  window.noptinPopups = {};\n  var popup = (__webpack_require__(/*! ./partials/frontend/popup */ \"./includes/assets/js/src/partials/frontend/popup.js\")[\"default\"]);\n  var $ = (__webpack_require__(/*! ./partials/frontend/myquery */ \"./includes/assets/js/src/partials/frontend/myquery.js\")[\"default\"]);\n  $('.noptin-popup-wrapper').each(popup);\n});\n\n//# sourceURL=webpack://noptin/./includes/assets/js/src/popups.js?");

/***/ }),

/***/ "./node_modules/lodash.throttle/index.js":
/*!***********************************************!*\
  !*** ./node_modules/lodash.throttle/index.js ***!
  \***********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

eval("/**\n * lodash (Custom Build) <https://lodash.com/>\n * Build: `lodash modularize exports=\"npm\" -o ./`\n * Copyright jQuery Foundation and other contributors <https://jquery.org/>\n * Released under MIT license <https://lodash.com/license>\n * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>\n * Copyright Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors\n */\n\n/** Used as the `TypeError` message for \"Functions\" methods. */\nvar FUNC_ERROR_TEXT = 'Expected a function';\n\n/** Used as references for various `Number` constants. */\nvar NAN = 0 / 0;\n\n/** `Object#toString` result references. */\nvar symbolTag = '[object Symbol]';\n\n/** Used to match leading and trailing whitespace. */\nvar reTrim = /^\\s+|\\s+$/g;\n\n/** Used to detect bad signed hexadecimal string values. */\nvar reIsBadHex = /^[-+]0x[0-9a-f]+$/i;\n\n/** Used to detect binary string values. */\nvar reIsBinary = /^0b[01]+$/i;\n\n/** Used to detect octal string values. */\nvar reIsOctal = /^0o[0-7]+$/i;\n\n/** Built-in method references without a dependency on `root`. */\nvar freeParseInt = parseInt;\n\n/** Detect free variable `global` from Node.js. */\nvar freeGlobal = typeof __webpack_require__.g == 'object' && __webpack_require__.g && __webpack_require__.g.Object === Object && __webpack_require__.g;\n\n/** Detect free variable `self`. */\nvar freeSelf = typeof self == 'object' && self && self.Object === Object && self;\n\n/** Used as a reference to the global object. */\nvar root = freeGlobal || freeSelf || Function('return this')();\n\n/** Used for built-in method references. */\nvar objectProto = Object.prototype;\n\n/**\n * Used to resolve the\n * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)\n * of values.\n */\nvar objectToString = objectProto.toString;\n\n/* Built-in method references for those with the same name as other `lodash` methods. */\nvar nativeMax = Math.max,\n    nativeMin = Math.min;\n\n/**\n * Gets the timestamp of the number of milliseconds that have elapsed since\n * the Unix epoch (1 January 1970 00:00:00 UTC).\n *\n * @static\n * @memberOf _\n * @since 2.4.0\n * @category Date\n * @returns {number} Returns the timestamp.\n * @example\n *\n * _.defer(function(stamp) {\n *   console.log(_.now() - stamp);\n * }, _.now());\n * // => Logs the number of milliseconds it took for the deferred invocation.\n */\nvar now = function() {\n  return root.Date.now();\n};\n\n/**\n * Creates a debounced function that delays invoking `func` until after `wait`\n * milliseconds have elapsed since the last time the debounced function was\n * invoked. The debounced function comes with a `cancel` method to cancel\n * delayed `func` invocations and a `flush` method to immediately invoke them.\n * Provide `options` to indicate whether `func` should be invoked on the\n * leading and/or trailing edge of the `wait` timeout. The `func` is invoked\n * with the last arguments provided to the debounced function. Subsequent\n * calls to the debounced function return the result of the last `func`\n * invocation.\n *\n * **Note:** If `leading` and `trailing` options are `true`, `func` is\n * invoked on the trailing edge of the timeout only if the debounced function\n * is invoked more than once during the `wait` timeout.\n *\n * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred\n * until to the next tick, similar to `setTimeout` with a timeout of `0`.\n *\n * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)\n * for details over the differences between `_.debounce` and `_.throttle`.\n *\n * @static\n * @memberOf _\n * @since 0.1.0\n * @category Function\n * @param {Function} func The function to debounce.\n * @param {number} [wait=0] The number of milliseconds to delay.\n * @param {Object} [options={}] The options object.\n * @param {boolean} [options.leading=false]\n *  Specify invoking on the leading edge of the timeout.\n * @param {number} [options.maxWait]\n *  The maximum time `func` is allowed to be delayed before it's invoked.\n * @param {boolean} [options.trailing=true]\n *  Specify invoking on the trailing edge of the timeout.\n * @returns {Function} Returns the new debounced function.\n * @example\n *\n * // Avoid costly calculations while the window size is in flux.\n * jQuery(window).on('resize', _.debounce(calculateLayout, 150));\n *\n * // Invoke `sendMail` when clicked, debouncing subsequent calls.\n * jQuery(element).on('click', _.debounce(sendMail, 300, {\n *   'leading': true,\n *   'trailing': false\n * }));\n *\n * // Ensure `batchLog` is invoked once after 1 second of debounced calls.\n * var debounced = _.debounce(batchLog, 250, { 'maxWait': 1000 });\n * var source = new EventSource('/stream');\n * jQuery(source).on('message', debounced);\n *\n * // Cancel the trailing debounced invocation.\n * jQuery(window).on('popstate', debounced.cancel);\n */\nfunction debounce(func, wait, options) {\n  var lastArgs,\n      lastThis,\n      maxWait,\n      result,\n      timerId,\n      lastCallTime,\n      lastInvokeTime = 0,\n      leading = false,\n      maxing = false,\n      trailing = true;\n\n  if (typeof func != 'function') {\n    throw new TypeError(FUNC_ERROR_TEXT);\n  }\n  wait = toNumber(wait) || 0;\n  if (isObject(options)) {\n    leading = !!options.leading;\n    maxing = 'maxWait' in options;\n    maxWait = maxing ? nativeMax(toNumber(options.maxWait) || 0, wait) : maxWait;\n    trailing = 'trailing' in options ? !!options.trailing : trailing;\n  }\n\n  function invokeFunc(time) {\n    var args = lastArgs,\n        thisArg = lastThis;\n\n    lastArgs = lastThis = undefined;\n    lastInvokeTime = time;\n    result = func.apply(thisArg, args);\n    return result;\n  }\n\n  function leadingEdge(time) {\n    // Reset any `maxWait` timer.\n    lastInvokeTime = time;\n    // Start the timer for the trailing edge.\n    timerId = setTimeout(timerExpired, wait);\n    // Invoke the leading edge.\n    return leading ? invokeFunc(time) : result;\n  }\n\n  function remainingWait(time) {\n    var timeSinceLastCall = time - lastCallTime,\n        timeSinceLastInvoke = time - lastInvokeTime,\n        result = wait - timeSinceLastCall;\n\n    return maxing ? nativeMin(result, maxWait - timeSinceLastInvoke) : result;\n  }\n\n  function shouldInvoke(time) {\n    var timeSinceLastCall = time - lastCallTime,\n        timeSinceLastInvoke = time - lastInvokeTime;\n\n    // Either this is the first call, activity has stopped and we're at the\n    // trailing edge, the system time has gone backwards and we're treating\n    // it as the trailing edge, or we've hit the `maxWait` limit.\n    return (lastCallTime === undefined || (timeSinceLastCall >= wait) ||\n      (timeSinceLastCall < 0) || (maxing && timeSinceLastInvoke >= maxWait));\n  }\n\n  function timerExpired() {\n    var time = now();\n    if (shouldInvoke(time)) {\n      return trailingEdge(time);\n    }\n    // Restart the timer.\n    timerId = setTimeout(timerExpired, remainingWait(time));\n  }\n\n  function trailingEdge(time) {\n    timerId = undefined;\n\n    // Only invoke if we have `lastArgs` which means `func` has been\n    // debounced at least once.\n    if (trailing && lastArgs) {\n      return invokeFunc(time);\n    }\n    lastArgs = lastThis = undefined;\n    return result;\n  }\n\n  function cancel() {\n    if (timerId !== undefined) {\n      clearTimeout(timerId);\n    }\n    lastInvokeTime = 0;\n    lastArgs = lastCallTime = lastThis = timerId = undefined;\n  }\n\n  function flush() {\n    return timerId === undefined ? result : trailingEdge(now());\n  }\n\n  function debounced() {\n    var time = now(),\n        isInvoking = shouldInvoke(time);\n\n    lastArgs = arguments;\n    lastThis = this;\n    lastCallTime = time;\n\n    if (isInvoking) {\n      if (timerId === undefined) {\n        return leadingEdge(lastCallTime);\n      }\n      if (maxing) {\n        // Handle invocations in a tight loop.\n        timerId = setTimeout(timerExpired, wait);\n        return invokeFunc(lastCallTime);\n      }\n    }\n    if (timerId === undefined) {\n      timerId = setTimeout(timerExpired, wait);\n    }\n    return result;\n  }\n  debounced.cancel = cancel;\n  debounced.flush = flush;\n  return debounced;\n}\n\n/**\n * Creates a throttled function that only invokes `func` at most once per\n * every `wait` milliseconds. The throttled function comes with a `cancel`\n * method to cancel delayed `func` invocations and a `flush` method to\n * immediately invoke them. Provide `options` to indicate whether `func`\n * should be invoked on the leading and/or trailing edge of the `wait`\n * timeout. The `func` is invoked with the last arguments provided to the\n * throttled function. Subsequent calls to the throttled function return the\n * result of the last `func` invocation.\n *\n * **Note:** If `leading` and `trailing` options are `true`, `func` is\n * invoked on the trailing edge of the timeout only if the throttled function\n * is invoked more than once during the `wait` timeout.\n *\n * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred\n * until to the next tick, similar to `setTimeout` with a timeout of `0`.\n *\n * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)\n * for details over the differences between `_.throttle` and `_.debounce`.\n *\n * @static\n * @memberOf _\n * @since 0.1.0\n * @category Function\n * @param {Function} func The function to throttle.\n * @param {number} [wait=0] The number of milliseconds to throttle invocations to.\n * @param {Object} [options={}] The options object.\n * @param {boolean} [options.leading=true]\n *  Specify invoking on the leading edge of the timeout.\n * @param {boolean} [options.trailing=true]\n *  Specify invoking on the trailing edge of the timeout.\n * @returns {Function} Returns the new throttled function.\n * @example\n *\n * // Avoid excessively updating the position while scrolling.\n * jQuery(window).on('scroll', _.throttle(updatePosition, 100));\n *\n * // Invoke `renewToken` when the click event is fired, but not more than once every 5 minutes.\n * var throttled = _.throttle(renewToken, 300000, { 'trailing': false });\n * jQuery(element).on('click', throttled);\n *\n * // Cancel the trailing throttled invocation.\n * jQuery(window).on('popstate', throttled.cancel);\n */\nfunction throttle(func, wait, options) {\n  var leading = true,\n      trailing = true;\n\n  if (typeof func != 'function') {\n    throw new TypeError(FUNC_ERROR_TEXT);\n  }\n  if (isObject(options)) {\n    leading = 'leading' in options ? !!options.leading : leading;\n    trailing = 'trailing' in options ? !!options.trailing : trailing;\n  }\n  return debounce(func, wait, {\n    'leading': leading,\n    'maxWait': wait,\n    'trailing': trailing\n  });\n}\n\n/**\n * Checks if `value` is the\n * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)\n * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)\n *\n * @static\n * @memberOf _\n * @since 0.1.0\n * @category Lang\n * @param {*} value The value to check.\n * @returns {boolean} Returns `true` if `value` is an object, else `false`.\n * @example\n *\n * _.isObject({});\n * // => true\n *\n * _.isObject([1, 2, 3]);\n * // => true\n *\n * _.isObject(_.noop);\n * // => true\n *\n * _.isObject(null);\n * // => false\n */\nfunction isObject(value) {\n  var type = typeof value;\n  return !!value && (type == 'object' || type == 'function');\n}\n\n/**\n * Checks if `value` is object-like. A value is object-like if it's not `null`\n * and has a `typeof` result of \"object\".\n *\n * @static\n * @memberOf _\n * @since 4.0.0\n * @category Lang\n * @param {*} value The value to check.\n * @returns {boolean} Returns `true` if `value` is object-like, else `false`.\n * @example\n *\n * _.isObjectLike({});\n * // => true\n *\n * _.isObjectLike([1, 2, 3]);\n * // => true\n *\n * _.isObjectLike(_.noop);\n * // => false\n *\n * _.isObjectLike(null);\n * // => false\n */\nfunction isObjectLike(value) {\n  return !!value && typeof value == 'object';\n}\n\n/**\n * Checks if `value` is classified as a `Symbol` primitive or object.\n *\n * @static\n * @memberOf _\n * @since 4.0.0\n * @category Lang\n * @param {*} value The value to check.\n * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.\n * @example\n *\n * _.isSymbol(Symbol.iterator);\n * // => true\n *\n * _.isSymbol('abc');\n * // => false\n */\nfunction isSymbol(value) {\n  return typeof value == 'symbol' ||\n    (isObjectLike(value) && objectToString.call(value) == symbolTag);\n}\n\n/**\n * Converts `value` to a number.\n *\n * @static\n * @memberOf _\n * @since 4.0.0\n * @category Lang\n * @param {*} value The value to process.\n * @returns {number} Returns the number.\n * @example\n *\n * _.toNumber(3.2);\n * // => 3.2\n *\n * _.toNumber(Number.MIN_VALUE);\n * // => 5e-324\n *\n * _.toNumber(Infinity);\n * // => Infinity\n *\n * _.toNumber('3.2');\n * // => 3.2\n */\nfunction toNumber(value) {\n  if (typeof value == 'number') {\n    return value;\n  }\n  if (isSymbol(value)) {\n    return NAN;\n  }\n  if (isObject(value)) {\n    var other = typeof value.valueOf == 'function' ? value.valueOf() : value;\n    value = isObject(other) ? (other + '') : other;\n  }\n  if (typeof value != 'string') {\n    return value === 0 ? value : +value;\n  }\n  value = value.replace(reTrim, '');\n  var isBinary = reIsBinary.test(value);\n  return (isBinary || reIsOctal.test(value))\n    ? freeParseInt(value.slice(2), isBinary ? 2 : 8)\n    : (reIsBadHex.test(value) ? NAN : +value);\n}\n\nmodule.exports = throttle;\n\n\n//# sourceURL=webpack://noptin/./node_modules/lodash.throttle/index.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _arrayLikeToArray)\n/* harmony export */ });\nfunction _arrayLikeToArray(arr, len) {\n  if (len == null || len > arr.length) len = arr.length;\n  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];\n  return arr2;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _arrayWithHoles)\n/* harmony export */ });\nfunction _arrayWithHoles(arr) {\n  if (Array.isArray(arr)) return arr;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _arrayWithoutHoles)\n/* harmony export */ });\n/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayLikeToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js\");\n\nfunction _arrayWithoutHoles(arr) {\n  if (Array.isArray(arr)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _classCallCheck)\n/* harmony export */ });\nfunction _classCallCheck(instance, Constructor) {\n  if (!(instance instanceof Constructor)) {\n    throw new TypeError(\"Cannot call a class as a function\");\n  }\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/classCallCheck.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/construct.js":
/*!**************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/construct.js ***!
  \**************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _construct)\n/* harmony export */ });\n/* harmony import */ var _setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./setPrototypeOf.js */ \"./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js\");\n/* harmony import */ var _isNativeReflectConstruct_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isNativeReflectConstruct.js */ \"./node_modules/@babel/runtime/helpers/esm/isNativeReflectConstruct.js\");\n\n\nfunction _construct(Parent, args, Class) {\n  if ((0,_isNativeReflectConstruct_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])()) {\n    _construct = Reflect.construct.bind();\n  } else {\n    _construct = function _construct(Parent, args, Class) {\n      var a = [null];\n      a.push.apply(a, args);\n      var Constructor = Function.bind.apply(Parent, a);\n      var instance = new Constructor();\n      if (Class) (0,_setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(instance, Class.prototype);\n      return instance;\n    };\n  }\n  return _construct.apply(null, arguments);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/construct.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/createClass.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/createClass.js ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _createClass)\n/* harmony export */ });\n/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ \"./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js\");\n\nfunction _defineProperties(target, props) {\n  for (var i = 0; i < props.length; i++) {\n    var descriptor = props[i];\n    descriptor.enumerable = descriptor.enumerable || false;\n    descriptor.configurable = true;\n    if (\"value\" in descriptor) descriptor.writable = true;\n    Object.defineProperty(target, (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(descriptor.key), descriptor);\n  }\n}\nfunction _createClass(Constructor, protoProps, staticProps) {\n  if (protoProps) _defineProperties(Constructor.prototype, protoProps);\n  if (staticProps) _defineProperties(Constructor, staticProps);\n  Object.defineProperty(Constructor, \"prototype\", {\n    writable: false\n  });\n  return Constructor;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/createClass.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/defineProperty.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _defineProperty)\n/* harmony export */ });\n/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ \"./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js\");\n\nfunction _defineProperty(obj, key, value) {\n  key = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(key);\n  if (key in obj) {\n    Object.defineProperty(obj, key, {\n      value: value,\n      enumerable: true,\n      configurable: true,\n      writable: true\n    });\n  } else {\n    obj[key] = value;\n  }\n  return obj;\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/defineProperty.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/isNativeReflectConstruct.js":
/*!*****************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/isNativeReflectConstruct.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _isNativeReflectConstruct)\n/* harmony export */ });\nfunction _isNativeReflectConstruct() {\n  if (typeof Reflect === \"undefined\" || !Reflect.construct) return false;\n  if (Reflect.construct.sham) return false;\n  if (typeof Proxy === \"function\") return true;\n  try {\n    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));\n    return true;\n  } catch (e) {\n    return false;\n  }\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/isNativeReflectConstruct.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/iterableToArray.js":
/*!********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/iterableToArray.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _iterableToArray)\n/* harmony export */ });\nfunction _iterableToArray(iter) {\n  if (typeof Symbol !== \"undefined\" && iter[Symbol.iterator] != null || iter[\"@@iterator\"] != null) return Array.from(iter);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/iterableToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _iterableToArrayLimit)\n/* harmony export */ });\nfunction _iterableToArrayLimit(arr, i) {\n  var _i = null == arr ? null : \"undefined\" != typeof Symbol && arr[Symbol.iterator] || arr[\"@@iterator\"];\n  if (null != _i) {\n    var _s,\n      _e,\n      _x,\n      _r,\n      _arr = [],\n      _n = !0,\n      _d = !1;\n    try {\n      if (_x = (_i = _i.call(arr)).next, 0 === i) {\n        if (Object(_i) !== _i) return;\n        _n = !1;\n      } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0);\n    } catch (err) {\n      _d = !0, _e = err;\n    } finally {\n      try {\n        if (!_n && null != _i[\"return\"] && (_r = _i[\"return\"](), Object(_r) !== _r)) return;\n      } finally {\n        if (_d) throw _e;\n      }\n    }\n    return _arr;\n  }\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js":
/*!********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js ***!
  \********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _nonIterableRest)\n/* harmony export */ });\nfunction _nonIterableRest() {\n  throw new TypeError(\"Invalid attempt to destructure non-iterable instance.\\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.\");\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _nonIterableSpread)\n/* harmony export */ });\nfunction _nonIterableSpread() {\n  throw new TypeError(\"Invalid attempt to spread non-iterable instance.\\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.\");\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _setPrototypeOf)\n/* harmony export */ });\nfunction _setPrototypeOf(o, p) {\n  _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) {\n    o.__proto__ = p;\n    return o;\n  };\n  return _setPrototypeOf(o, p);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/slicedToArray.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _slicedToArray)\n/* harmony export */ });\n/* harmony import */ var _arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayWithHoles.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js\");\n/* harmony import */ var _iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./iterableToArrayLimit.js */ \"./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js\");\n/* harmony import */ var _unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./unsupportedIterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js\");\n/* harmony import */ var _nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./nonIterableRest.js */ \"./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js\");\n\n\n\n\nfunction _slicedToArray(arr, i) {\n  return (0,_arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr) || (0,_iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(arr, i) || (0,_unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(arr, i) || (0,_nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])();\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/slicedToArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toArray.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toArray.js ***!
  \************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _toArray)\n/* harmony export */ });\n/* harmony import */ var _arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayWithHoles.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js\");\n/* harmony import */ var _iterableToArray_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./iterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/iterableToArray.js\");\n/* harmony import */ var _unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./unsupportedIterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js\");\n/* harmony import */ var _nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./nonIterableRest.js */ \"./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js\");\n\n\n\n\nfunction _toArray(arr) {\n  return (0,_arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr) || (0,_iterableToArray_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(arr) || (0,_unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(arr) || (0,_nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])();\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/toArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _toConsumableArray)\n/* harmony export */ });\n/* harmony import */ var _arrayWithoutHoles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayWithoutHoles.js */ \"./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js\");\n/* harmony import */ var _iterableToArray_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./iterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/iterableToArray.js\");\n/* harmony import */ var _unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./unsupportedIterableToArray.js */ \"./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js\");\n/* harmony import */ var _nonIterableSpread_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./nonIterableSpread.js */ \"./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js\");\n\n\n\n\nfunction _toConsumableArray(arr) {\n  return (0,_arrayWithoutHoles_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(arr) || (0,_iterableToArray_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(arr) || (0,_unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(arr) || (0,_nonIterableSpread_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])();\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _toPrimitive)\n/* harmony export */ });\n/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ \"./node_modules/@babel/runtime/helpers/esm/typeof.js\");\n\nfunction _toPrimitive(input, hint) {\n  if ((0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(input) !== \"object\" || input === null) return input;\n  var prim = input[Symbol.toPrimitive];\n  if (prim !== undefined) {\n    var res = prim.call(input, hint || \"default\");\n    if ((0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(res) !== \"object\") return res;\n    throw new TypeError(\"@@toPrimitive must return a primitive value.\");\n  }\n  return (hint === \"string\" ? String : Number)(input);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/toPrimitive.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _toPropertyKey)\n/* harmony export */ });\n/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ \"./node_modules/@babel/runtime/helpers/esm/typeof.js\");\n/* harmony import */ var _toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPrimitive.js */ \"./node_modules/@babel/runtime/helpers/esm/toPrimitive.js\");\n\n\nfunction _toPropertyKey(arg) {\n  var key = (0,_toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"])(arg, \"string\");\n  return (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(key) === \"symbol\" ? key : String(key);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js":
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ _typeof)\n/* harmony export */ });\nfunction _typeof(obj) {\n  \"@babel/helpers - typeof\";\n\n  return _typeof = \"function\" == typeof Symbol && \"symbol\" == typeof Symbol.iterator ? function (obj) {\n    return typeof obj;\n  } : function (obj) {\n    return obj && \"function\" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? \"symbol\" : typeof obj;\n  }, _typeof(obj);\n}\n\n//# sourceURL=webpack://noptin/./node_modules/@babel/runtime/helpers/esm/typeof.js?");

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
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
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
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
/******/ 	var __webpack_exports__ = __webpack_require__("./includes/assets/js/src/popups.js");
/******/ 	
/******/ })()
;