// @flow

export function compileTemplate(template: string, replaces: Object) {
	let result = template;

	for (const key in replaces) {
		if (!replaces.hasOwnProperty(key)) { continue; }

		const replaceKey = '#' + key + '#';
		const replaceValue = replaces[key];

		do {
			result = result.replace(replaceKey, replaceValue);
		} while (result.indexOf(replaceKey) !== -1);
	}

	return result;
}

export function htmlToElement(html, tag = 'div') {
	const renderer = document.createElement(tag);

	renderer.innerHTML = html;

	return renderer.firstElementChild;
}