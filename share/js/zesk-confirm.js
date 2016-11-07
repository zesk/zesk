/*
 * $Id: zesk-confirm.js 4076 2016-10-18 04:16:28Z kent $
 *
 * Copyright (C) 2007 Market Acumen, Inc. All rights reserved
 */

/* Confirm dialog box on certain actions */

;(function(exports, $) {
	"use strict";
	$('.confirm[data-confirm]').on('click', function (e) {
		var $this = $(this);
		if (exports.confirm($this.data('confirm'))) {
			return true;
		}
		e.stopPropagation();
		e.preventDefault();
		return false;
	});
}(window, window.jQuery));
