/**
 * LayoutBerg Debug Helper
 *
 * Enable debug mode to see parsing details in the console
 * Usage: In browser console, run: layoutbergEnableDebug()
 */

window.layoutbergEnableDebug = function () {
	window.layoutbergDebug = true;
};

window.layoutbergDisableDebug = function () {
	window.layoutbergDebug = false;
};
