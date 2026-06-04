// utils/cookie.js — Cookie helpers (Cookie Store API + document.cookie fallback)

const DAY_MS = 864e5;

/**
 * Decode cookie parts without failing on malformed third-party cookies.
 * @param {string} value
 * @returns {string}
 */
const safeDecode = (value) => {
	try {
		return decodeURIComponent(value);
	} catch {
		return value;
	}
};

/**
 * Normalize SameSite value.
 * @param {string} val
 * @returns {string} Capitalized: 'Lax' | 'Strict' | 'None'
 */
const normalizeSameSite = (val = 'Lax') => {
	switch (String(val).toLowerCase()) {
		case 'lax': return 'Lax';
		case 'strict': return 'Strict';
		case 'none': return 'None';
		default: return 'Lax';
	}
};

/**
 * Get a cookie value by name.
 * @param {string} name
 * @returns {Promise<string>}
 */
export async function getCookie(name) {
	if (window.cookieStore) {
		const entry = await window.cookieStore.get(name);
		return entry?.value ?? '';
	}

	// Fallback: parse document.cookie
	const cookies = document.cookie.split('; ');
	for (const c of cookies) {
		const eq = c.indexOf('=');
		if (eq === -1) continue;

		const key = safeDecode(c.slice(0, eq).trim());
		if (key === name) {
			return safeDecode(c.slice(eq + 1));
		}
	}

	return '';
}

/**
 * Set a cookie.
 * @param {string} name
 * @param {string} value
 * @param {Object} [options]
 * @param {number} [options.days=365]
 * @param {string} [options.path='/']
 * @param {boolean} [options.secure=true]
 * @param {string} [options.sameSite='Lax']
 * @returns {Promise<void>}
 */
export async function setCookie(name, value, { days = 365, path = '/', secure = true, sameSite = 'Lax' } = {}) {
	if (window.cookieStore) {
		const opts = {
			name,
			value,
			path,
			secure: !!secure,
			sameSite: normalizeSameSite(sameSite).toLowerCase(),
		};
		if (days) opts.expires = new Date(Date.now() + days * DAY_MS);
		return window.cookieStore.set(opts);
	}

	// Fallback
	let str = `${encodeURIComponent(name)}=${encodeURIComponent(value)};path=${path};SameSite=${normalizeSameSite(sameSite)}`;
	if (days) str += `;expires=${new Date(Date.now() + days * DAY_MS).toUTCString()}`;
	if (secure) str += ';Secure';
	document.cookie = str;
}

/**
 * Delete a cookie.
 * @param {string} name
 * @param {Object} [options]
 * @param {string} [options.path='/']
 * @returns {Promise<void>}
 */
export async function deleteCookie(name, { path = '/' } = {}) {
	if (window.cookieStore) {
		return window.cookieStore.delete({ name, path });
	}

	document.cookie = `${encodeURIComponent(name)}=;path=${path};expires=Thu, 01 Jan 1970 00:00:00 GMT`;
}
