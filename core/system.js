'use strict';

class HfCore {
	/**
	 * Request an Endpunkt
	 * @param method
	 * @param parameters
	 * @returns {Promise<Response>}
	 */
	static request(method = '', parameters = {}) {
		return fetch('/wp-json/' + method, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify(parameters),
		}).then(response => response.text());
	}
}

// Debug
function l(log) {
	console.log(log);
}