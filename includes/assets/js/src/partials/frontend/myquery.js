// A clone of jquery.
const savedEvents = [];

/**
 * Naively checks if a given event name is a native event.
 * @param {String} event Name of the event to test
 * @returns {Boolean}
 */
const isNativeEvent = event => typeof document[`on${event}`] !== "undefined";

/**
 * Checks if an event target is our intended target to call the handler for.
 * @param {HTMLElement} eventTarget Target passed from event.
 * @param {String} delegatedTarget Selector of a delegation target.
 * @param {HTMLElement} originalTarget "Main" (non delegated) target.
 * @returns {Boolean}
 */
const isTarget = (eventTarget, delegatedTarget, originalTarget) => {
	/**
	 * If no delegate passed, then the event must have been called on
	 * on the original target or its descendents. No questions asked.
	 */
	if (!delegatedTarget || typeof delegatedTarget !== "string") {
		return true;
	}

	/**
	 * True if:
	 * 1. The event target matches the delegate target
	 * 2. The event target is a descendent of the delegate target.
	 */
	return (
		matches(eventTarget, delegatedTarget) ||
		originalTarget.contains(eventTarget.closest(delegatedTarget))
	);
};

/**
 * Checks that a given element complies with a supplied selector.
 * @param {HTMLElement} target Target element to test.
 * @param {String} selector Selector to test the element with.
 * @returns {Boolean}
 */
const matches = (target, selector) => {
	if (!target) {
		return false;
	}

	if (typeof target.matches === "function") {
		return target.matches(selector);
	} else if (typeof target.msMatchesSelector === "function") {
		return target.msMatchesSelector(selector);
	}

	return false;
};

/**
 * Generates a list of nodes from a selector or an EventTarget.
 * @param {*} nodes
 * @returns {Array<EventTarget>}
 */
const parseNode = nodes => {
	if (!nodes) {
		return [];
	}

	if (typeof nodes === "string") {
		return [...document.querySelectorAll(nodes)];
	} else if (nodes instanceof NodeList) {
		return [...nodes];
	} else if (typeof nodes.addEventListener === "function") {
		return [nodes];
	}

	return [];
};

/**
 * Splits a string by ' ' and removes duplicates.
 * @param {String} events
 * @returns {Array<String>}
 */
const splitEvents = events => {
	if (typeof events !== "string") {
		return [];
	}

	const uniqueEvents = events.split(" ").reduce(
		({ keys, existing }, current) => {
			if (existing[current]) {
				return { keys, existing };
			}

			return {
				keys: [...keys, current],
				existing: { ...existing, [current]: true }
			};
		},
		{ keys: [], existing: {} }
	);

	return uniqueEvents.keys;
};

/**
 * Registers either a one time or a permanent listener on an EventTarget.
 * @param {EventTarget} target Target to add listener to.
 * @param {String} eventName Name of the event to listen to.
 * @param {Function} handler Handler callback function.
 * @param {Object} options.
 * @param {String} options.delegate Selector for delegation.
 * @param {Boolean} options.once Determines whether the handler should run once or more.
 */
const listen = (target, eventName, handler, { delegate, once }) => {
	// Instead of using the user's own handler, we wrap it with our own.
	// This is so we can implement deleg
	const delegateHandler = e => {
		if (isTarget(e.target, delegate, target)) {
			const data = e && e.detail;
			handler.call((delegate ? e.target : target), e, data);

			if (once) {
				target.removeEventListener(eventName, delegateHandler);
			}
		}
	};

	// We're keeping a reference to the original handler
	// so the consumer can later on `off` that specific handler
	delegateHandler.originalHandler = handler;

	target.addEventListener(eventName, delegateHandler);

	if (!once) {
		setEvent(target, eventName, delegateHandler);
	}
};

/**
 * Dispatches an event on a target, or calls its `on` function.
 * @param {EventTarget} target Event target to dispatch the event on.
 * @param {String} events space separated list of event names;
 * @param {Object} detail EventTarget Detail Object.
 * @param {Object} options
 */
const dispatch = (target, events, detail, options) => {
	const hasDispatch = typeof target.dispatchEvent === "function";

	splitEvents(events).forEach(eventName => {
		const nativeEvent = isNativeEvent(eventName);
		let event;

		if (detail || !nativeEvent) {
			event = new CustomEvent(
				eventName,
				Object.assign({ detail, bubbles: true }, options)
			);
		} else {
			event = new Event(eventName, Object.assign({ bubbles: true }, options));
		}

		if (nativeEvent && typeof target[eventName] === "function") {
			target[eventName]();
		}

		if (!hasDispatch) {
			return;
		}
		target.dispatchEvent(event);
	});
};

/**
 * Stores target and its events for later access.
 * @param {EventTarget} target An event target to store.
 * @param {String} event Event Name.
 * @param {Function} handler Event handler function.
 */
const setEvent = (target, event, handler) => {
	if (!target || !event || !handler) {
		return;
	}
	const targetIndex = savedEvents.findIndex(
		([current]) => current === target
	);

	// Get the existing target reference, or default to an empty object.
	const [_, targetEvents] = savedEvents[targetIndex] || [target, {}];

	targetEvents[event] = targetEvents[event] || [];
	targetEvents[event].push(handler);

	if (targetIndex === -1) {
		savedEvents.push([target, targetEvents]);
	} else {
		savedEvents[targetIndex] = [target, targetEvents];
	}
};

/**
 * Removes Target events from storage
 * @param {EventTarget} target EventTarget to remove.
 * @param {String} [events] List of events to remove from storage.
 * @param {Function} [handler] Funtion to remove.
 */
const deleteEvents = (target, events, handler) => {
	const targetIndex = savedEvents.findIndex(
		([current]) => current === target
	);
	if (targetIndex === -1) {
		return;
	}

	const [, targetEvents] = savedEvents[targetIndex];

	const eventsArray = splitEvents(events);

	// Do this for each of the existing events for the current target.
	for (const event in targetEvents) {
		if (
			// * The consumer requested to remove the current event name
			//    or if the user did not specify an event name
			(eventsArray.indexOf(event) !== -1 || !events) &&
			// * And the current target has this event registered
			Object.prototype.hasOwnProperty.call(targetEvents, event) &&
			// * And it is an array (safeguard)
			Array.isArray(targetEvents[event])
		) {
			// Filter out the events that the consumer wanted to remove
			targetEvents[event] = targetEvents[event].filter(currentHandler => {
				// If the consumer specified a specific handler to remove
				if (handler) {
					// and the handler doesn't match the current handler
					if (currentHandler.originalHandler !== handler) {
						// keep it in
						return true;
					} else {
						// filter it out and remove its listener;
						target.removeEventListener(event, currentHandler);
						return false;
					}
				} else {
					// Remove all handlers for current event name
					target.removeEventListener(event, currentHandler);
					return false;
				}
			});

			if (!events) {
				// Clear all the events
				delete targetEvents[event];
			}
		}
	}

	if (!events) {
		savedEvents.splice(targetIndex, 1);
	}
};

const bindEvents = (instance, options, [events, ...args]) => {
	if (!args.length) {
		// no handler. bye.
		return;
	}

	// One liner for:
	// [handler] = args
	// [delegate, handler] = args
	const { length, [length - 1]: handler, [length - 2]: delegate } = args;

	const eventsArray = splitEvents(events);
	return instance["each"](node =>
		eventsArray.forEach(event =>
			listen(node, event, handler, {
				...options,
				delegate
			})
		)
	);
};

// The actual event manager.
class myQuery {
	constructor(...args) {
		this.length = 0;
		this.add(...args);
	}

	// Mocks native splice
	splice (...args) {
		return Array.prototype.splice.apply(this, args);
	}

	// Mocks native forEach
	each (...args) {
		Array.prototype.forEach.call(this, ...args);
		return this;
	}

	// Receives the event targets as an argument.
	// Example, 'a:first-child'
	add (...args) {
		args.forEach(selector => {
			const nodeList = parseNode(selector);

			nodeList.forEach(node => {
				for (let i = 0; i < this.length; i++) {
					if (this[i] === node) {
						return;
					}
				}
				this[this.length] = node;
				this.length++;
			});
		});

		return this;
	}

	// Attaches actual event.
	on (...args) {
		return bindEvents(this, {}, args);
	}

	// Attaches an event once.
	once (...args) {
		return bindEvents(this, { once: true }, args);
	}

	//Removes an event.
	off (events, handler) {
		return this["each"](target => deleteEvents(target, events, handler));
	}

	// Triggers specific events.
	trigger ( events, { data, options } = {} ) {
		return this["each"](target => dispatch(target, events, data, options));
	}

	// Checks if a given class name exists.
	hasClass ( className ) {
		return !!this[ 0 ] && this[ 0 ].classList.contains( className );
	}

	// Adds a class to the passed element.
	addClass ( className ) {
		this.each( function( item ) {
			const classList = item.classList;
			classList.add.apply( classList, className.split( /\s/ ) );
		});
		return this;
	}

	// Removes a class from the passed element.
	removeClass ( className ) {
		this.each( function( item ) {
			var classList = item.classList;
			classList.remove.apply( classList, className.split( /\s/ ) );
		});
		return this;
	}

	// Toggles a given class name.
	toggleClass ( className, b ) {
		this.each( function( item ) {
			var classList = item.classList;
			if( typeof b !== 'boolean' ) {
				b = !classList.contains( className );
			}
			classList[ b ? 'add' : 'remove' ].apply( classList, className.split( /\s/ ) );
		});
		return this;
	}

	// jQuery html method.
	html ( html ) {
		if( typeof html === 'undefined' ) {
			return this[ 0 ] ? this[ 0 ].innerHTML : '';
		}

		this.each( function( item ) {
			item.innerHTML = html;
		});
		return this;
	}

	// jQuery text method.
	text ( text ) {
		if( typeof text === 'undefined' ) {
			return this[ 0 ] ? this[ 0 ].textContent : '';
		}

		this.each( function( item ) {
			item.textContent = text;
		});
		return this;
	}

	// jQuery find method.
	find ( el ) {

		let result = new myQuery();

		if ( this[ 0 ] ) {
			result.add( this[ 0 ].querySelectorAll( el ) );
		}

		return result;
	}

};

export default (...args) => new myQuery(...args);
