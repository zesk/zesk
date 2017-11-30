/*global html: false */
(function (exports, $) {
	"use strict";
	var
	__ = exports.__ || function (phrase, map) {
		phrase = phrase.toString().right(":=");
		return map instanceof Object ? phrase.map(map) : phrase;
	},
	data_api = "[data-widget=\"control-date\"]",
	Control_Date = function (element, options) {
		this.init('Control_Date', element, options);
	};

	$.extend(Control_Date.prototype, {
		init: function (type, element, options) {
			var e, is_hover, div_name;
			
			this.type = type;
			this.$div = $(element);
			this.$display = e = $("input[type=text]", element);
			this.$element = $("input[type=hidden]", element);
			
			div_name = 'Control_Date-calendar-' + e.attr('id');
			
			e.after('<div id="' + div_name + '"></div>');
			
			this.options = this.get_options(options || {});
			this.enabled = true;
			
			is_hover = this.options.trigger === 'hover';
			e.on(is_hover ? 'mouseenter' : 'focus', this.options.selector, $.proxy(this.enter, this));
			e.on(is_hover ? 'mouseleave' : 'blur', this.options.selector, $.proxy(this.leave, this));

			this.action = 'date';
			this.name = this.$element.attr('name');
			this.$calendar = $('#' + div_name);
			this.$calendar.addClass('Control_Date-calendar');
			this.load_value();
			this.update_display();
		},
		get_options: function (options) {
			return $.extend({}, $.fn.control_date.defaults, this.$div.data(), options);
		},
		close_all: function() {
			//$('.Control_Date_Calendar_Popup').hide();
		},
		enter: function() {
			this.load_value();
			this.update();
		},
		update_display: function () {
			this.$display.val(this.val !== "" ? this.datetime.format(this.options.displayFormat) : this.options.emptyString);
		},
		done: function () {
			var v = this.val = this.datetime.format(this.options.format);
			this.$element.val(v);
			this.update_display();
			this.close();
		},
		close: function () {
			this.$calendar.hide();
		},
		cancel: function () {
			this.load_value();
			this.update_display();
			this.close();
		},
		html_today: function () {
			return '<input type="button" value="' + this.options.textToday + '" class="today" />';
		},
		html_table_cell: function (content, klass) {
			return '<td class="' + klass + '">' + content + '</td>';
		},
		html_link: function (attrs, content) {
			return html.tag('a', attrs, content);
		},
		icon: function (klass) {
			return '<i class="' + klass + '" />';
		},
		html_header: function () {
			var x = "", dt = this.datetime, now = new Date(), css_class, testdate;
			
			now.setDate(1);
			now.midnight();
			x += '<table class="date-popup-header"><tr>';
			if (this.options.futureOnly && dt.clone().add_unit(-1, "month").before(now)) {
				x += this.html_table_cell(this.icon('last-month disabled'), "arrow");
			} else {
				x += this.html_table_cell(this.html_link({'data-unit': 'month', 'data-number': '-1', 'class': 'nav'}, this.icon('last-month')), "arrow");
			}
			x += this.html_table_cell(this.html_link({'data-unit': 'month', 'data-number': '1', 'class': 'nav'}, this.icon('next-month')), "arrow");
			x += '<td class="date-popup-month">' + this.control_month(dt.getMonth(),dt.getFullYear()) + '</td>';
			x += '<td class="date-popup-year">' + this.control_year(dt.getFullYear()) + '</td>';
			if (this.options.futureOnly && dt.clone().add_unit(-1, "month").before(now)) {
				x += this.html_table_cell(this.icon('last-year disabled'), "arrow");
			} else {
				x += this.html_table_cell(this.html_link({'data-unit': 'year', 'data-number': '-1', 'class': 'nav'}, this.icon('last-year')), "arrow");
			}
			x += this.html_table_cell(this.html_link({'data-unit': 'year', 'data-number': '1', 'class': 'nav'}, this.icon('next-year')), "arrow");
			x += '</tr></table>';
			
			return x;
		},
		html: function () {
			var
			x = "",
			dow = exports.Date ? exports.Date.locale_weekdays(this.locale, "short") : 'Sun,Mon,Tue,Wed,Thu,Fri,Sat'.split(",");
			x += '<table class="date-popup-table">';
			x += '<tr class="buttons"><td colspan="7">';
			x += this.html_today();
			x += '<input type="button" value="Cancel" class="cancel">';
			x += '</td></tr>';
			x += '<tr class="controls"><td colspan="7">' + this.html_header() + '</td></tr>';
			x += '<tr class="weekday">';
			$.each(dow, function() {
				x += "<td>" + this + "</td>";
			});
			x += this.html_rows();
			x += '</table>';
			return x;
		},
		html_rows: function () {
			var
			theDate = (this.datetime ? this.datetime : new Date()).clone(),
			weekday,
			theMonth = theDate.getMonth(),
			theYear = theDate.getFullYear(),
			theDay = theDate.getDate(),
			now = (new Date()).midnight(),
			nowDay = (now.getMonth() === theMonth) ? now.getDate() : 0,
			curYear,
			curMonth,
			curDay,
			i,
			klass,
			x = "";

			now.midnight();
			theDate.setDate(1);
			weekday = theDate.getDay(theDate);
			theDate = theDate.add_unit(-weekday);
			curMonth = 0;
			curYear = 0;
			while (curMonth <= theMonth && curYear <= theYear) {
				x += "<tr>";
				for (i = 0; i < 7; i++) {
					curYear = theDate.getFullYear();
					curMonth = theDate.getMonth();
					curDay = theDate.getDate();
					if (curMonth !== theMonth) {
						klass = " other";
					} else if (curDay === theDay) {
						klass = " selected";
					} else if (curDay === nowDay) {
						klass = " now";
					} else {
						klass = "";
					}
					if (this.options.futureOnly && theDate.before(now)) {
						klass += " disabled";
					}
					x += '<td class="day' + klass + '" data-year="' + curYear + '" data-month="' + curMonth + '" data-day="' + curDay + '">' + curDay + '</td>';
					theDate = theDate.add_unit(1, "day");
				}
				x += "</tr>";
			}
			return x;
		},
		control_month: function (month, year) {
			var x = "", i,
			months = exports.Date.locale_months(this.options.locale),
			now = (new Date()).midnight(),
			start_month = (now.getFullYear() >= year) ? now.getMonth() : 0;

			x += '<select class="month">';
			for (i = start_month; i < months.length; i++) {
				x += '<option value="' + i + '"' + ((month === i) ? ' selected' : '') + ' >' + months[i] + '</option>';
			}
			x += '</select>';
			return x;
		},
		control_year: function (year) {
			var x = "", i, start_year, now = new Date();
			start_year = this.options.futureOnly ? Math.min(now.getFullYear(), year) : year - 3;
			x += '<select class="year">';
			for (i = start_year; i < year + 3; i++) {
				x += '<option value="' + i + '"' + ((year === i) ? ' selected' : '') + ' >' + i + '</option>';
			}
			x += '</select>';
			return x;
		},
		post_render: function () {
			var self = this;
			$('.month', this.$calendar).on('change', function() { self.set_month($(this).val()); });
			$('.today', this.$calendar).on('click', $.proxy(this.today, this));
			$('.year', this.$calendar).on('change', function() { self.set_year($(this).val()); });
			$('.cancel', this.$calendar).on('click', $.proxy(this.cancel, this));
			$('.nav', this.$calendar).on('click', function() { self.nav($(this)); });
			$('.day:not(.disabled)', this.$calendar).on('click', function() { self.click($(this)); });
		},
		click: function (element) {
			var
			$element = $(element),
			month = $element.data('month'),
			year = $element.data('year'),
			day = $element.data('day'),
			dt = this.datetime;
			dt.midnight();
			dt.setDate(1);
			dt.setMonth(month);
			dt.setYear(year);
			dt.setDate(day);
			this.update();
			this.done();
		},
		nav: function (element) {
			var $element = $(element), number = $element.attr('data-number'), unit = $element.attr('data-unit');
			this.datetime.add_unit(number, unit);
			this.update();
		},
		today: function () {
			this.datetime = new Date();
			this.update();
		},
		set_month: function (month) {
			var days_in_month, old_day = this.datetime.getDate();
			this.datetime.setDate(1);
			this.datetime.setMonth(month);
			days_in_month = this.datetime.days_in_month();
			this.datetime.setDate(Math.min(old_day, days_in_month));
			this.update();
		},
		set_year: function (year) {
			this.datetime.setYear(year);
			return this.update();
		},
		load_value: function (value) {
			var
			value = this.val = this.$element.val(),
			items,
			arr,
			d = new Date(),
			action = this.action,
			i = 1;

			this.datetime = new Date();
			if (value === null || value === "") {
				this.val = ""; 
				return false;
			}
			switch (this.action) {
				case "date":
					if (this.options.us_date) {
						items = /([0-9]{2})\/([0-9]{2})\/([0-9]{4})/;
					} else {
						items = /([0-9]{4})-([0-9]{2})-([0-9]{2})/;
					}
					break;
				case "time":
					items = /([0-9]{2}):([0-9]{2}):([0-9]{2})/;
					break;
				default:
					items = /([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/;
					break;
			}

			arr = items.exec(value);
			if (arr === null) {
				this.val = "";
				return false;
			}
			if (action !== "time") {
				if (this.options.us_date) {
					if (arr.length > 3) {
						d.setFullYear(this.parse_int(arr[3], d.getFullYear()));
					}
					if (arr.length > 1) {
						d.setDate(1);
						d.setMonth(this.parse_int(arr[1], 1) - 1);
					}
					if (arr.length > 2) {
						d.setDate(this.parse_int(arr[2], 1));
					}
				} else {
					if (arr.length > i) {
						d.setFullYear(this.parse_int(arr[i], d.getFullYear()));
					} else {
						this.datetime = d;
						return true;
					}
					++i;
					if (arr.length > i) {
						d.setMonth(this.parse_int(arr[i], 1) - 1);
					}
					++i;
					if (arr.length > i) {
						d.setDate(this.parse_int(arr[i], 1));
					}
				}
			}

			if (action !== "date") {
				if (arr.length > i) {
					d.setHours(this.parse_int(arr[i], 0));
				}++i;
				if (arr.length > i) {
					d.setMinutes(this.parse_int(arr[i], 0));
				}++i;
				if (arr.length > i) {
					d.setSeconds(this.parse_int(arr[i], 0));
				} else {
					d.setSeconds(0);
				}
			}
			this.datetime = d;
			return true;
			
		},
		parse_int: function (value, def) {
			if (value === '0') {
				return 0;
			}
			while (value.length > 1 && value.substring(0, 1) === '0') {
				value = value.substring(1);
			}
			value = parseInt(value, 10);
			if (isNaN(value)) {
				return def;
			}
			return value;
		},
		update: function () {
			var now = (new Date()).midnight();
			if (this.options.futureOnly && this.datetime.before(now)) {
				this.datetime = now;
			}
			this.$calendar.html(this.html()).show();
			this.post_render();
		},
		leave: function() {
			//this.close();
		},
		destroy: function () {
			
		}
	});

	$.fn.control_date = function (option) {
		return this.each(function () {
			var $this = $(this), data = $this.data('Control_Date');
			if (!data) {
				$this.data('Control_Date', (data = new Control_Date(this, option)));
			}
			if (typeof option === 'string') {
				data[option].call($this);
			}
		});
	};
  
	$.fn.control_date.Constructor = Control_Date;

	$.fn.control_date.defaults = {
		selector: false,
		us_date: false,
		trigger: 'focus',
		format: '{YYYY}-{MM}-{DD}',
		displayFormat: '{MMMM} {DDD}, {YYYY}',
		emptyString: null,
		futureOnly: false,
		pastOnly: false,
		title: '',
		textToday: 'Today',
	};

	$(document).ready(function () {
		$(data_api).control_date();
	});
}(window, window.jQuery));