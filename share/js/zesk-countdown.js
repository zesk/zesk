(function(exports, $) {
	var 
	zesk = exports.zesk, 
	Locale = exports.Locale, 
	match = "[data-countdown-seconds]:visible", 
	now = function() {
		return (new Date()).getTime();
	}, 
	defaults = {
	    format : "",
	    seconds : "",
	    action : "click"
	}, 
	Countdown = function(element, options) {
		var self = this, $this = $(element);

		this.$element = $this;
		$.each(defaults, function(key) {
			var name = 'countdown' + key.toCamelCase(), value = $this.data(name);
			self[key] = value ? value : this;
		});
		$.extend(this, options);
		this.interval = setInterval(function() {
			self.tick();
		}, 1000);
		this.original = $this.text();
		if (!this.format) {
			this.format = $this.text() + " {countdown}";
		}
		this.end_time = now() + this.seconds * 1000;
		$this.on(this.action, function () {
			self.done();
		});
		self.tick();
	}, 
	document_ready = function(context) {
		context = context || $('body');
		$(match, context).countdown();
	};
	$.extend(Countdown.prototype, {
		done : function () {
			clearInterval(this.interval);
			this.interval = null;
			this.$element.text(this.original);
		},
		trigger : function () {
			this.$element[this.action]();
		},
		tick : function() {
			var 
			current_time = now(),
			delta = parseInt((this.end_time - current_time) / 1000, 10);
			if (delta <= 0) {
				this.trigger();
				this.done();
			} else {
				var map = {
					countdown : Locale.plural_n(Locale.translate('second'), delta)
				};
				this.$element.text(this.format.map(map));
			}
		}
	});
	$.fn.countdown = function(options) {
		var element = function() {
			var $this = $(this);
			$this.data('zesk-countdown', new Countdown($this, options));
		};
		$(this).each(function(index, item) {
			element.call(item);
		});
	};
	zesk.add_hook('document::ready', document_ready);
}(window, window.jQuery));
