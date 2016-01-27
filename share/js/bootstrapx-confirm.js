/**
 * Copyright (C) 2013 Market Acumen, Inc.
 */
!function($) {
	"use strict";
	$.fn.confirmx = function(options) {
		var
		modal_opts = {
			keyboard: true,
			show: true
		},
		defaults = {
			id: 'confirmx-modal',
			templateId: 'confirmx-modal',
			okLabel: "Yes",
			cancelLabel: "No",
			message: "",
			timer: 0,
			success: function () {
			},
			failure: function () {
			}
		},
		opts = $.extend({}, defaults, options),
		content = $(this).html(), 
		
		$modal = $('#'+opts.id);
		if ($modal.length === 0) {
			$('body').append('<div id="'+opts.id+'"></div>');
			$modal = $('#'+opts.id);
		}
		$modal.html(_.template(content, opts, {'variable':'data'}));
		$(".ok", $modal).on('click', function () {
			if (typeof opts.success === 'function') {
				opts.success();
			}
			$modal.modal('hide');
		});
		$(".cancel", $modal).on('click', function () {
			if (typeof opts.failure === 'function') {
				opts.failure();
			}
			$modal.modal('hide');
		});
		$modal.modal();
		return $modal;
	}
}(window.jQuery);
