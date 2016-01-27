/**
* hoverBubble is Brian Cherne's hoverIntent, and David Turnbull's corners extension
*
* $Id: jquery.hoverbubble.js 2115 2014-02-09 06:14:39Z kent $
*
* // advanced usage receives configuration object only
* $("ul li").hoverBubble(content, {
*	sensitivity: 7, // number = sensitivity threshold (must be 1 or higher)
*	interval: 100,   // number = milliseconds of polling interval
*	timeout: 0,   // number = milliseconds delay before onMouseOut function call
* });
*
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Kent Davidson kent -at- marketruler.com
*/
(function($) {
	$.fn.hoverBubble = function(content, user_opts) {
		// default configuration options
		var opts = {
			sensitivity: 7,
			interval: 100,
			timeout: 0
		};

		// override configuration options with user supplied object
		opts = $.extend(default_opts, user_opts);

		var hoverBubble_over = function () {
			var data = content;
			if (content.substr(0,1) === '#') {
				data = $(content).html();
			}
	        $(this).append($('<div class="hover-bubble rounded"><img class="hover-bubble-nib-TL" src="/share/zesk/widgets/hoverbubble/hoverbubble-nib-TL.png" width="20" height="20" />'+data+'</div>'));
	        $('.rounded').corners(opts.corners || "15px");
		};

		opts.over = hoverBubble_over;
		opts.out = function () {};

		$(this).hoverIntent(opts);
	};
})(jQuery);