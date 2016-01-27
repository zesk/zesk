// $Id: jquery.csspngfix.js 11 2011-06-24 22:50:37Z kent $
jQuery.fn.cssPNGFix = function(sizingMethod) {
	var sizingMethod = (sizingMethod) || 'scale';
	if ($.browser.msie && parseInt($.browser.version) < 7) {
		$(this).each(function() {
			var css_bg_img = $(this).css('background-image');
			var bg_img = css_bg_img.substring(5, css_bg_img.length - 2);
			$(this).css({
				'background':'none',
				'filter':'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="' + bg_img + '", sizingMethod="' + sizingMethod + '")'
				});
		});
	};
	return $(this);
};

jQuery.fn.imgPNGFix = function(sizingMethod) {
	var sizingMethod = (sizingMethod) || 'scale';
	if ($.browser.msie && parseInt($.browser.version) < 7) {
		$(this).each(function() {
			var src = $(this).attr('src');
			if (src.substring(src.length-4).toLowerCase() == '.png') {
				var spacer_src = 'http://static.kent.glucose'+ '/share/images/spacer.gif';
				var the_img = $(this);
				$(the_img).attr('src', spacer_src);
				$(the_img).css({
					'background':'none',
					'filter':'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="' + src + '", sizingMethod="' + sizingMethod + '")'
				});
			}
		});
	};
	return $(this);
};

$(function() {
	if($.browser.msie && parseInt($.browser.version) < 7) {
		$('.png_bg').each(function() {
			var css_bg_img = $(this).css('background-image');
			var bg_img = css_bg_img.substring(5, css_bg_img.length - 2);
			$(this).css({
				'background':'none',
				'filter':'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="' + bg_img + '", sizingMethod="image")'
				});
		});
		$('img').imgPNGFix('image');
	};
});
