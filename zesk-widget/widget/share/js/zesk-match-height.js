(function(exports) {
	var
	$ = exports.jQuery,
	zesk = exports.zesk, go = function() {
		var $this = $(this),
		$target = $($this.attr('data-match-height'));
		$this.css("height", $target.height() + "px");
	};

	if (zesk) {
		zesk.addHook('window::load', function() {
			$('[data-match-height]').each(go);
		});
		$(window).off("resize.match-height").on('resize.match-height', function() {
			$('[data-match-height]').each(go);
		});
	}
}(window));
