module.exports = {
	input: './src/index.js',
	output: {
		js: './script.js',
		css: './style.css',
	},
	namespace: 'BX.AvitoExport.Feed',
	adjustConfigPhp: false,
	browserslist: true,
	minification: true,
};