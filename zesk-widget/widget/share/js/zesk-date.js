/* global is_date: false */
(function(exports) {
	"use strict";
	var __ = exports.__ || function (x) {
		return x.toString().right(":=", x);
	};
	$.extend(Date, {
		locale_months: function(lang) {
			var format = arguments[1] || "normal";
			switch (lang) {
				case "de":
				switch (format) {
					case "single":
						return ['J','F','M','A','M','J','J','A','S','O','N','D']; // NOT CONFIRMED: TODO
					case "min":
						return ['Ja','Fe','M&auml;','Ap','Ma','Ju','Jl','Au','Se','Ok','No','De']; // NOT CONFIRMED: TODO
					case "short":
						return ['Jan','Feb','M&auml;r','Apr','Mai','Jun','Jul','Aug','Sept','Okt','Nov','Dez'];
					default:
						return ['Januar','Februar','M&auml;rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
				}
				break;
				case "fr": /* Source: http://fr.wikipedia.org/wiki/Mois#Abr.C3.A9viations */
				switch (format) {
					case "single":
						return ['j','f','m','a','m','j','j','a','s','o','n','d'];
					case "min":
						return ['jr','fr','ms','al','ma','jn','jl','au','se','oc','no','de'];
					case "short":
						return ['jan','f&eacute;v','mar','avr','mai','jun','jul','ao&uuml;','sep','oct','nov','d&eacute;c'];
					default:
						return ['janvier','f&eacute;brier','mars','avril','mai','juin','juillet','ao&uuml;t','septembre','octobre','novembre','d&eacute;cembre'];
				}
				break;
				default:  //en
				switch (format) {
					case "single":
						return ["J","F","M","A","M","J","J","A","S","O","N","D"];
					case "min":
						return ["Ja","Fe","Ma","Ap","Ma","Jn","Jl","Au","Se","Oc","No","De"];
					case "short":
						return ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
					default:
						return ["January","February","March","April","May","June","July","August","September","October","November","December"];
				}
				break;
			}
		},
		locale_weekdays: function(lang, format) {
			format = format || "normal";
			switch (lang) {
				case "de":
				switch (format) {
					case "single":	return ["S", "M", "D", "M", "D", "F", "S"];
					case "short":	return ["Son", "Mon", "Die", "Mit", "Don", "Fre", "Sam"];
					case "min":		return ['So','Mo','Di','Mi','Do','Fr','Sa'];
					default:		return ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
				}
				break;
				case "fr":
				switch (format) {
					case "single":	return ['d','l','m','m','j','v','s'];
					case "short":	return ['dim','lun','mar','mer','jeu','ven','sam'];
					case "min":		return ['di','lu','ma','me','je','ve','sa'];
					default:		return ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
				}
				break;
				default:  // en
				switch (format) {
					case "single":	return ["S","M","T","W","T","F","S"];
					case "short":	return ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
					case "min":		return ["Su","Mo","Tu","We","Th","Fr","Sa"];
					default:		return ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
				}
				break;
			}
		},
		locale_weekdays_min: function (lang) {
			return Date.locale_weekdays(lang, "min");
		},
		locale_week_header: function (lang) {
			var wkheader = { en: 'Wk', fr: "Se", de: 'Wo' };
			return wkheader[lang] || wkheader.en;
		},
		/**
		* Formats are:
		*  d: A single day
		*  m: A single month
		*  m: Between months
		*  dd: Same month, same year, with two different dates
		*  mmdd: Custom day of month, dates across months, same year
		*  mdyy: Dates across years.
		*/
		format_range_strings: function() {
			return {
				de: {
					D: [ "{WWWW}, der {D}. {MMMM}, {YYYY}", "" ],
					M: [ "{MMMM} {YYYY}", "" ],
					MM: [ "vom {MMMM}", " bis zum {MMMM}, {YYYY}" ],
					MMDD: [ "vom {D}. {MMMM}", " bis zum {D}. {MMMM}, {YYYY}" ],
					DD: [ "vom {D}. {MMMM} bis zum", " {D}., {YYYY}"],
					MDYY: [ "vom {D}. {MMMM}, {YYYY}", " bis zum {D}. {MMMM}, {YYYY}" ]
				},
				en: {
					D: [ "{WWWW}, {MMMM} {DDD}, {YYYY}", "" ],
					M: [ "{MMMM} {YYYY}", "" ],
					MM: [ "{MMMM}", " - {MMMM}, {YYYY}" ],
					MMDD: [ "{MMMM} {DDD}", " - {MMMM} {DDD}, {YYYY}" ],
					DD: [ "{MMMM} {DDD}", " - {DDD}, {YYYY}"],
					MDYY: [ "{MMMM} {DDD}, {YYYY}", " - {MMMM} {DDD}, {YYYY}" ]
				},
				fr: {
					D: [ "le {WWWW} {DDD} {MMMM} {YYYY}", "" ],
					M: [ "{MMMM} {YYYY}", "" ],
					MM: [ "{MMMM}", " - {MMMM} {YYYY}" ],
					MMDD: [ "{DDD} {MMMM}", " - {DDD} {MMMM} {YYYY}" ],
					DD: [ "{DDD}", " - {DDD} {MMMM} {YYYY}"],
					MDYY: [ "{DDD} {MMMM} {YYYY}", " - {DDD} {MMMM} {YYYY}" ]
				}
			};
		}
	});
	$.extend(Date.prototype, {
		type: "date",
		clone: function () {
			var clone = new Date();
			clone.setTime(this.getTime());
			return clone;
		},
		add: function (Y,M,D,h,m,s) {
			if (s) {
				this.setTime(this.getTime() + (s * 1000));
			}
			if (m) {
				this.setTime(this.getTime() + (m * 60000));
			}
			if (h) {
				this.setTime(this.getTime() + (m * 360000));
			}
			if (D) {
				this.setTime(this.getTime() + (D * 86400000));
			}
			if (M) {
				this.setUTCMonth(this.getMonth(M)+(M%12));
				Y = Y + parseInt(M/12,10);
			}
			if (Y) {
				this.setUTCFullYear(this.getFullYear()+Y);
			}
		},
		add_unit: function (num, units) {
			var d = this;
			num = parseInt(num, 10);
			switch (units) {
			case "year":
				d.setFullYear(d.getFullYear() + num);
				return d;
			case "month":
				var m = d.getMonth();
				var day = d.getDate();
				d.setDate(1);
				m = m + num;
				if (m < 0) {
					m = 12 + m;
					d.setFullYear(d.getFullYear() - parseInt((-num + 11) / 12));
				} else if (m > 11) {
					d.setFullYear(d.getFullYear() + parseInt((num + 11) / 12));
				}
				d.setMonth((m % 12));
				d.setDate(Math.min(day, d.days_in_month(d)));
				return d;
			default:
			case "day":
				d.setTime(d.getTime() + num * 86400000);
				return d;
			}
		},
		add_zero: function(x) {
			return (x >= 10) ? x : "0" + x;
		},
		midnight: function() {
			this.setHours(0);
			this.setMinutes(0);
			this.setSeconds(0);
			return this;
		},
		midnight_UTC: function() {
			this.setUTCHours(0);
			this.setUTCMinutes(0);
			this.setUTCSeconds(0);
			return this;
		},
		locale_month: function(lang) {
			var format = arguments[1] || "normal";
			var mm = Date.locale_months(lang, format);
			return mm[this.getMonth()];
		},
		locale_weekday: function(lang) {
			var format = arguments[1] || "normal";
			var mm = Date.locale_weekdays(lang, format);
			return mm[this.getDay()];
		},
		is_last_day_of_month: function () {
			return this.days_in_month() === this.getDate();
		},
		after: function (date) {
			return this.getTime() > date.getTime();
		},
		before: function (date) {
			return this.getTime() < date.getTime();
		},
		days_in_month: function () {
			var m = this.getMonth();
			return [31,28 + ((this.getFullYear() % 4 === 0) ? 1 : 0),31,30,31,30,31,31,30,31,30,31][m];
		},
		format: function(format_string, lang) {
			var x = {};

			lang = lang || "en";

			x.Y = this.getFullYear().toString();
			x.M = (this.getMonth()+1).toString();
			x.D = this.getDate().toString();
			x.W = this.getDay().toString();

			x.h = this.getHours().toString();
			x.m = this.getMinutes().toString();
			x.s = this.getSeconds().toString();

			x.YY = this.add_zero(x.Y);
			x.MM = this.add_zero(x.M);
			x.DD = this.add_zero(x.D);
			x.WW = this.add_zero(x.W);
			x.hh = this.add_zero(x.h);
			x['12hh'] = this.add_zero(x.h % 12 === 0 ? 12 : x.h % 12);
			x.ampm = x.h < 12 ? __('Date:=am') : __('Date:=pm');
			x.AMPM = x.h < 12 ? __('Date:=AM') : __('Date:=PM');
			x.mm = this.add_zero(x.m);
			x.ss = this.add_zero(x.s);

			x.DDD = exports.Locale ? exports.Locale.ordinal(x.D) : x.D;
			// Special
			x.YYYY = x.Y;
			x.YY = x.YYYY.substring(2);

			x.MMMM = this.locale_month(lang);
			x.MMM = this.locale_month(lang,'short');

			x.WWWW = this.locale_weekday(lang);
			x.WWW = this.locale_weekday(lang, "short");

			return format_string.map(x, false);
		},
		date_string: function() {
			return this.getFullYear() + "-" + this.add_zero(this.getMonth()+1) + '-' + this.add_zero(this.getDate());
		},
		equal_dates: function(d) {
			if (is_date(d)) {
				return d.dateString() === this.dateString();
			}
			return false;
		},
		equal_months: function(d) {
			if (is_date(d)) {
				return d.getMonth() === this.getMonth();
			}
			return false;
		},
		equal_years: function(d) {
			if (is_date(d)) {
				return d.getFullYear() === this.getFullYear();
			}
			return false;
		},
		format_range: function (end_date, language) {
			var
			start_format = "",
			end_format = "",
			formats = Date.format_range_strings(),
			which;
			language = language || "en";
			if (this.equalDates(end_date)) {
				which = 'D';
			} else if (!this.equalYears(end_date)) {
				which = 'MDYY';
			} else if (!this.equalMonths(end_date)) {
				which = (this.getDate() === 1 && end_date.isLastDayOfMonth()) ? 'MM' : 'MMDD';
			} else if (this.getDate() === 1 && end_date.isLastDayOfMonth()) {
				which = "M";
			} else {
				which = "DD";
			}
			if (!formats[language]) {
				language = "en";
			}
			start_format = formats[language][which][0];
			end_format = formats[language][which][1];
			return this.format(start_format, language) + end_date.format(end_format, language);
		}
	});
}(window));
