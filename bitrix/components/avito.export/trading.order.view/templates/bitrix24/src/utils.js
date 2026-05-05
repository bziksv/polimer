export function htmlToElement(html, tag = 'div') {
	const renderer = document.createElement(tag);

	renderer.innerHTML = html;

	return renderer.firstElementChild;
}

export function pascalCase(str) {
	return str
		.split('_')
		.map((word) => word.substr(0, 1).toUpperCase() + word.substr(1).toLowerCase())
		.join('');
}

export function kebabCase(str) {
	return str
		.split('_')
		.map((word) =>  word.toLowerCase())
		.join('-');
}

export function replaceTemplateVariables(template, variables) {
	let result = template;

	if (variables == null) { return result; }

	for (const [key, value] of Object.entries(variables)) {
		result = result.replace('#' + key + '#', value);
	}

	return result;
}