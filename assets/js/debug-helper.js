/**
 * LayoutBerg Debug Helper
 * 
 * Enable debug mode to see parsing details in the console
 * Usage: In browser console, run: layoutbergEnableDebug()
 */

window.layoutbergEnableDebug = function() {
    window.layoutbergDebug = true;
    console.log('LayoutBerg debug mode enabled. You will see parsing details in the console.');
    console.log('To disable, run: layoutbergDisableDebug()');
};

window.layoutbergDisableDebug = function() {
    window.layoutbergDebug = false;
    console.log('LayoutBerg debug mode disabled.');
};

// Add instructions to console
console.log('%cLayoutBerg Debug Helper Available', 'color: #6366f1; font-weight: bold;');
console.log('Run layoutbergEnableDebug() to see block parsing details');