/*
 * $Id: zesk-confirm.js 2404 2014-04-03 04:37:49Z kent $
 *
 * Copyright (C) 2007 Market Acumen, Inc. All rights reserved
 */

/* Confirm dialog box on certain actions */

;(function(exports, $) {
	"use strict";
	$('.confirm[data-confirm]').on('click', function (e) {
		var $this = $(this);
		if (confirm($this.data('confirm'))) {
			return true;
		}
		return false;
	});
}(window, window.jQuery));
