(function (exports, $) {
	"use strict";
	var ua = exports.navigator.userAgent.toString().toLowerCase();
	if ($.browser) {
		return;
	}
	$.browser = {
		msie: ua.indexOf('msie ') >= 0
	};
}(window, window.jQuery));