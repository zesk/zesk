zesk.add_hook("document::ready", function() {
	var 
	$sources,
	format_currency = function (amount, precision) {
		var parsed_amount = parseFloat(amount);
		if (isNaN(parsed_amount)) {
			return "";
		}
		return parsed_amount.toFixed(precision);
	},
	format_currency_object = function (amount, data) {
		var
		format = data.format || '{symbol}{amount}',
		map = {
			amount: format_currency(amount, data.precision || 2),
			symbol: data.symbol
		};
		return format.map(map);
	};
	$sources = {};
	$('[data-currency]').each(function() {
		var $this = $(this), source = $this.data("source"), $source;
		if (source) {
			$source = $(source);
			if ($source) {
				$sources[source] = $source;
			}
		} else {
			var data = zesk.get_path('modules.world.currency', {
				symbol: '$?'
			});
			$this.html(format_currency_object($this.data("amount"), data));;
		}
	});
	$.each($sources, function(source) {
		var $source = this,
		update_currency = function(source, $source) {
			var 
			$option = $('option:selected', $source),
			format = $option.data("format") || "{symbol}{amount}";
			$('[data-currency][data-source=\'' + source + '\']').each(function() {
				var 
				$this = $(this);
				$this.html(format_currency_object($this.data('amount'), $option.data()));
			});
		};
		$source.off("change.world").on("change.world", function() {
			update_currency(source, $source);
		});
		update_currency(source, $source);
	});
});
