/*
 * $Id: jquery.timer.js 4217 2016-11-27 03:15:03Z kent $
 *
 * @copyright Copyright (C) 2022 Market Acumen, Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*global window:false */
(function(exports, $) {
	"use strict";
	var __ = window.__ || function(m) {
		m = m.split(":=");
		return m[1] || m[0];
	};
	var old_timer = $.fn.timer || null;
	var timer_data = "timer_object";
	var interval = null;
	var timer = '[data-timer]';
	var tick = function() {
		var i_am_ticked = false;
		$(timer).each(function() {
			var $this = $(this), data = $this.data(timer_data);
			if (!data) {
				$this.data(timer_data, data = new Timer(this));
			}
			data.tick();
			i_am_ticked = true;
		});
		if (!i_am_ticked && interval) {
			clearInterval(interval);
		}
	}, Timer = function(el, options) {
		var self = this, default_opts = {
		    'timer': null,
		    'format': null,
		    'formatPast': '{duration} ago',
		    'formatPastZero': 'just a moment ago',
		    'formatNow': 'now',
		    'formatFuture': 'in {duration}',
		    'formatFutureZero': 'any moment now',
		    'nowPast': 5,
		    'nowFuture': 0,
		    'done': null,
		    'trigger': null,
		    'triggerTarget': null,
		    'unitMinimum': 'second'
		}, $el, first, opts = $.extend({}, default_opts, options || {});

		this.$source = $el = $(el);
		$.each(default_opts, function(key) {
			self[key] = $el.data(key) || opts[key];
		});
		if (!this.timer) {
			this.timer = this.$source.attr('data-timer').toString();
		} else {
			$el.attr('data-timer', 1);
		}
		this.date = new Date();
		if (this.timer < 0 || (typeof this.timer === "string" && (first = this.timer.substring(0, 1)) === "+" || first === '-')) {
			this.date.setTime(this.date.getTime() + parseInt(this.timer,10) * 1000);
		} else {
			this.date.setTime(this.timer * 1000);
		}
		this.tick();
		if (!interval) {
			interval = setInterval(tick, 501);
		}
	}, add0 = function(x) {
		return (x < 10) ? "0" + x : x;
	}, plural = function(s, n) {
		n = parseInt(n, 10);
		if (n === 1) {
			return s;
		}
		return s + "s";
	}, units = {
	    week: 604800,
	    day: 86400,
	    hour: 3600,
	    minute: 60,
	    second: 1
	},
	/**
	 * Output a duration of time as a string
	 *
	 * @param integer
	 *            delta Number of seconds to output
	 * @param string
	 *            min_unit Minimum unit to output, in English: "second",
	 *            "minute", "hour", "day", "week"
	 * @return string
	 */
	duration_string = function(delta, min_unit) {
		var number, result = [], prefix = "", remain;
		if (delta < 0) {
			delta = -delta;
		}
		$.each(units, function(unit) {
			if (delta <= 0) {
				return;
			}
			if (unit === min_unit || delta >= this) {
				number = parseInt(delta / this, 10);
				delta -= number * this;
				prefix = "~";
				remain = delta / this;
				if (remain > 0.5) {
					number += 1;
				} else if (remain < 0.1) {
					prefix = "";
				}
				result.push(prefix + number + " " + plural(unit, number));
				if (unit === min_unit) {
					delta = 0;
				}
				return false;
			}
		});
		return result.join(", ");
	};

	Timer.prototype.tick = function() {
		var now = (new Date()).getTime(), format, delta = parseInt(Math.abs(now - this.date.getTime()) / 1000, 10), unit_sec = units[this.unitMinimum] || 1, isZero = parseInt(delta / unit_sec, 10) === 0, isPast = now > this.date
		        .getTime();

		if (this.format) {
			format = this.format;
		} else if (isPast) {
			// Timer is in the past (e.g. 20 seconds ago)
			format = (delta > this.nowPast) ? (isZero ? this.formatPastZero : this.formatPast) : this.formatNow;
		} else {
			// Timer is in the future (e.g. in 20 seconds)
			format = (delta > this.nowFuture) ? (isZero ? this.formatFutureZero : this.formatFuture) : this.formatNow;
		}
		this.$source.html(this.render(delta, format));
		if (isZero && this.done) {
			this.done.call(this);
			this.done = null;
		}
		if (isPast && this.trigger) {
			$(this.triggerTarget || this.$source).trigger(this.trigger);
			this.destroy();
		}
	};

	Timer.prototype.render = function(delta, format) {
		var h = parseInt(delta / 3600, 10), h12 = h % 12 || 12, m = parseInt((delta % 3600) / 60, 10), s = delta % 60, map = {
		    'h': h,
		    '12h': h12,
		    '12hh': add0(h12),
		    'ampm': h < 12 ? __('Time:=am') : __('Time:=pm'),
		    'AMPM': h < 12 ? __('Time:=AM') : __('Time:=PM'),
		    'hh': add0(h),
		    'hour': plural("hour", h),
		    'm': m,
		    'mm': add0(m),
		    'minute': plural("minute", m),
		    's': s,
		    'ss': add0(s),
		    'second': plural('second', s),
		    'seconds': delta,
		    'duration': duration_string(delta, this.unitMinimum),
		};
		$.each(map, function(val) {
			format = format.split('{' + val + '}').join(this);
		});
		return format;
	};

	Timer.prototype.destroy = function() {
		this.$source.attr('data-timer', null);
		this.$source.data(timer_data, null);
	};

	$.fn.timer = function(options) {
		return $(this).each(function() {
			var $this = $(this), data = $this.data(timer_data);
			if (!data) {
				$this.data(timer_data, (data = new Timer(this, options)));
			}
			if (typeof options === 'string') {
				data[options].call($this);
			}
		});
	};

	$.fn.timer.Constructor = timer;

	$.fn.timer.noConflict = function() {
		$.fn.timer = old_timer;
		return this;
	};

	$.extend($, {
		timer: {
			instrument: function() {
				$(timer).timer();
			}
		}
	});
	// This is a one-shot deal. It will pick up additional timers as it matches
	// the [data-timer] attribute to do so
	$(document).ready(function() {
		$.timer.instrument();
	});
}(window, window.jQuery));
