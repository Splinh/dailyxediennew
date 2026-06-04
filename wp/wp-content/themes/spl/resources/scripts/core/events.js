// core/events.js - Global EventBus (pub/sub pattern)

/**
 * Simple pub/sub event bus
 */
class EventBus {
	/** @type {Map<string, Set<Function>>} */
	#listeners = new Map();

	/**
	 * Subscribe to an event
	 * @param {string} event - Event name
	 * @param {Function} cb - Callback function
	 * @returns {Function} - The callback (for unsubscribing)
	 */
	on(event, cb) {
		if (!this.#listeners.has(event)) {
			this.#listeners.set(event, new Set());
		}
		this.#listeners.get(event).add(cb);
		return cb;
	}

	/**
	 * Subscribe to an event (fires once then unsubscribes)
	 * @param {string} event - Event name
	 * @param {Function} cb - Callback function
	 * @returns {void}
	 */
	once(event, cb) {
		const wrapper = (payload) => {
			cb(payload);
			this.off(event, wrapper);
		};
		this.on(event, wrapper);
	}

	/**
	 * Unsubscribe from an event
	 * @param {string} event - Event name
	 * @param {Function} [cb] - Callback to remove (omit to remove all)
	 * @returns {void}
	 */
	off(event, cb) {
		if (!this.#listeners.has(event)) return;
		if (!cb) {
			this.#listeners.delete(event);
			return;
		}

		const set = this.#listeners.get(event);
		set.delete(cb);
		if (set.size === 0) {
			this.#listeners.delete(event);
		}
	}

	/**
	 * Emit an event to all subscribers
	 * @param {string} event - Event name
	 * @param {*} [payload={}] - Event payload
	 * @returns {void}
	 */
	emit(event, payload = {}) {
		if (!this.#listeners.has(event)) return;
		for (const cb of [...this.#listeners.get(event)]) {
			try {
				cb(payload);
			} catch (e) {
				console.error(`[EventBus:${event}]`, e);
			}
		}
	}

	/**
	 * Check if event has subscribers
	 * @param {string} event - Event name
	 * @returns {boolean}
	 */
	has(event) {
		return this.#listeners.has(event) && this.#listeners.get(event).size > 0;
	}

	/**
	 * Get subscriber count for an event
	 * @param {string} event - Event name
	 * @returns {number}
	 */
	count(event) {
		return this.#listeners.get(event)?.size || 0;
	}

	/**
	 * Clear all event subscriptions
	 * @returns {void}
	 */
	clear() {
		this.#listeners.clear();
	}

	/**
	 * Get all registered event names
	 * @returns {string[]}
	 */
	get events() {
		return [...this.#listeners.keys()];
	}
}

export default new EventBus();
