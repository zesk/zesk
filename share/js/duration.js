(function (exports, $) {
	exports.duration_update = function (id, datetime) {
		var 
		$input = $(id),
		format = $input.data('format') || '{12hh}:{mm} {ampm}',
		end = new Date();
		$('option', $input).each(function () {
			var
			$this = $(this),
			tag = 'control_duration',
			text = $this.data(tag), 
			delta = 0;
			if (!text) {
				$this.data(tag, text = $this.text());
			}
			delta += parseInt($this.attr('value'), 10);
			delta *= 60;
			delta *= 1000;
			end.setTime(datetime.getTime() + delta);
			$this.text(text + ' (ends at ' + end.format(format) + ')');
		});
	};
}(window, window.jQuery));