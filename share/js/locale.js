/* global zesk: false, to_string: false, avalue: false */
;(function (exports) {
	"use strict";
	var 
	plural_en = function (s, count) {
		count = parseInt(count, 10);
		if (count === 1) {
			return s;
		}
		var ess = Locale.translate('plural:=' + s.toLowerCase(), 'en', null);
		if (ess) {
			return ess;
		}
		var s1 = s.substring(s.length - 1);
		switch (s1) {
			case 'x':
				return s + "es";
			case 'y':
				return s.substring(0, s.length - 1) + "ies";
		}
		return s + 's';
	},
	Locale = exports.Locale = {
		plural: function plural(s, n, locale) {
			n = n || 2;
			if (Locale.language(locale) === "en") {
				return plural_en(s, n);
			} else {
				return plural_en(s, n);
			}
		},
		plural_n: function (s, n, locale) {
			return n + " " + Locale.plural(s, n, locale);
		},
		locale: function (set) {
			if (set) {
				return zesk.set('locale', set);
			}
			return zesk.get('locale', 'en_US');
		},
		language: function () {
			var x = to_string(arguments[0] || Locale.locale());
			return x.left('_', 'en').toLowerCase();
		},
		ordinal: function (n) {
			return n + Locale.ordinal_suffix(n);
		},
		ordinal_suffix: function (n) {
			var
			m10 = n % 10,
			m100 = n % 100;
			if (m100 > 10 && m100 < 20) {
				return "th";
			}
			return avalue({ 1: "st", 2: "nd", 3: "rd" }, m10, "th");
		},
		translation: function (locale, map) {
			var tt = zesk.get('translation-table', {});
			locale = locale.toLowerCase();
			if (!tt[locale]) {
				tt[locale] = {};
			}
			for ( var k in map) {
				if (map.hasOwnProperty(k)) {
					tt[locale][k] = map[k].toString();
				}
			}
			zesk.set('translation-table', tt);
		},
		translate: function(string, locale) {
			var
			text = string.toString(),
			phrase = string.right(':=', string),
			tt = zesk.get('translation-table'),
			r,
			_default = arguments.length > 2 ? arguments[2] : phrase;

			locale = locale || Locale.locale();
			tt = [ avalue(tt, locale, {}), avalue(tt, Locale.language(locale), {}) ];
			r = zesk.each(tt, function(i, t) {
			//	console.log('tried ', text, i, t, t[text]);
				return t[text] || null;
			});
			if (r) {
				return r;
			}
			r = zesk.each(tt, function(i, t) {
			//	console.log('tried ', phrase, i, t, t[phrase]);
				return t[phrase] || null;
			});
			if (r) {
				return r;
			}
			r = zesk.each(tt, function(i, t) {
			//	console.log('tried ', phrase.toLowerCase(), i, t, t[phrase.toLowerCase()]);
				return t[phrase.toLowerCase()] || null;
			});
			if (r) {
				return Locale.case_match_simple(r, phrase);
			}
			return _default;
		},
		case_match_simple: function (string, pattern) {
			var char1 = pattern.substr(0, 1);
			var char2 = pattern.substr(1, 1);
			if (char1 === char1.toLowerCase(char1)) {
				return string.toLowerCase();
			} else if (char2 === char2.toLowerCase()) {
				return string.substring(0, 1).toUpperCase() + string.substring(1).toLowerCase();
			} else {
				return string.toUpperCase();
			}
		},
		load: function (locale, tt) {
			var tables = zesk.get('translation-table', {});
			tables[locale] = tt;
		}
	};
	Locale.translation('en', {
		'plural:=day' : 'days',
		'plural:=staff' : 'staff',
		'plural:=sheep' : 'sheep',
		'plural:=octopus' : 'octopi',
		'plural:=news' : 'news'
	});
	exports.__ = Locale.__ = function (phrase, map) {
		if (phrase instanceof Object) {
			zesk.each(phrase, function (k) {
				phrase[k] = Locale.__(phrase[k], map);
			});
			return phrase;
		}
		phrase = Locale.translate(phrase);
		if (!map instanceof Object) {
			return phrase;
		}
		return phrase.map(map, true);
	};
}(window));