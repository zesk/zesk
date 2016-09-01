(function(exports, $) {
	"use strict";
	var zesk = exports.zesk;
	var attribute_decrypted = 'href';
	var attribute_prefix = 'data-' + attribute_decrypted + '-';
	var attribute_encrypted = attribute_prefix + 'encrypted';
	var attribute_seed = attribute_prefix + 'seed';
	var decrypt_emails = function(context) {
		$('a[' + attribute_encrypted + ']', context).each(function() {
			var $this = $(this);
			var encrypted = $this.attr(attribute_encrypted);
			var offset = $this.attr(attribute_seed);
			var i, decrypted = "";

			encrypted = unescape(encrypted);

			for (i = 0; i < encrypted.length; i++) {
				decrypted += String.fromCharCode(((encrypted.charCodeAt(i) - offset) % 240) + 32);
			}
			$this.attr(attribute_encrypted, null);
			$this.attr(attribute_seed, null);
			$this.attr(attribute_decrypted, decrypted);
		});
	};

	if (zesk) {
		zesk.add_hook("document::ready", decrypt_emails);
	}
	$(document).ready(function() {
		decrypt_emails($('html'));
	});

}(window, window.jQuery));
