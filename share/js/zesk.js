/*
 * $Id: zesk.js 3612 2016-02-02 23:38:16Z kent $
 *
 * Copyright (C) 2007 Market Acumen, Inc. All rights reserved
 */

/* Globals storage container */
(function(exports, $) {
	"use strict";
	var zesk = exports.zesk || {
		inited : false
	}, 
	hooks = {}, 
	html, 
	X = exports, 
	d = X.document, 
	L = X.location,
	_escape_map = {
		"&": "&amp;",
		"<": "&lt;",
		">": "&gt;",
		"\"": "&quot;",
		"`": "&#96;",
		"'": "&#x27"
	},
	_escape_map_flip;

	if (zesk.inited) {
		return;
	}

	function gettype(x) {
		if (x === null) {
			return 'null';
		}
		return Object.prototype.toString.call(x).split(' ')[1].split(']')[0].toLowerCase();
	}

	function avalue(obj, i, def) {
		if (def === undefined) {
			def = null;
		}
		if (typeof obj === "object") {
			if (typeof obj[i] !== "undefined") {
				return obj[i];
			}
			return def;
		}
		return def;
	}
	X.avalue = avalue;

	function is_bool(a) {
		return gettype(a) === 'boolean';
	}
	function is_numeric(a) {
		return gettype(a) === "number";
	}
	function is_string(a) {
		return gettype(a) === "string";
	}
	function is_array(a) {
		return gettype(a) === 'array';
	}
	function is_object(a) {
		return gettype(a) === 'object';
	}
	function is_integer(a) {
		return is_numeric(a) && parseInt(a, 10) === a;
	}
	function is_function(a) {
		return gettype(a) === "function";
	}
	function flip(object) {
		var i, result = {};
		for (i in object) {
			if (object.hasOwnProperty(i)) {
				result[String(object[i])] = i;
			}
		}
		return result;
	}

	X.is_array = is_array;
	X.is_object = is_object;
	X.is_array = is_array;
	X.is_number = is_numeric;
	X.is_numeric = is_numeric;
	X.is_bool = is_bool;
	X.is_string = is_string;
	X.is_integer = is_integer;
	X.is_function = is_function;

	X.html = html = {};
	_escape_map_flip = flip(_escape_map);
	$.extend(html, {
		specials: function (html) {
			return $("<textarea />").text(html).html();
		},
		escape: function (text) {
			return String(text).tr(_escape_map);
		},
		unescape: function (text) {
			return String(text).tr(_escape_map_flip);
		},
		encode : function(text) {
			var result = document.createElement('a').appendChild(document.createTextNode(text)).parentNode.innerHTML;
			return result.str_replace('"', '&quot;');
		},
		decode : function(html) {
			var a = document.createElement('a');
			a.innerHTML = html;
			return a.textContent;
		},
		to_attributes : function(mixed) {
			var obj = {};
			if (!is_string(mixed) || mixed.length === 0) {
				return arguments[1] || null;
			}
			$.each(mixed.split(" "), function() {
				var token = this.trim(), c = token.substr(0, 1);
				if (c === '#') {
					obj.id = token;
					return;
				}
				if (c === '.') {
					token = token.substr(1);
				}
				if (obj['class']) {
					obj['class'] += ' ' + token;
				} else {
					obj['class'] = token;
				}
			});
			return obj;
		},
		attributes : function(attributes) {
			var a, r = [];
			if (!attributes) {
				return "";
			}
			for (a in attributes) {
				if (attributes.hasOwnProperty(a)) {
					if (a.substr(0, 1) === '*') {
						r.push(a.substr(1) + "=\"" + attributes[a] + "\"");
					} else {
						r.push(a.toString() + "=\"" + html.encode(attributes[a]) + "\"");
					}
				}
			}
			if (r.length === 0) {
				return "";
			}
			return " " + r.join(" ");
		},
		tag : function(name, mixed) {
			var attributes, content, args = arguments;
			if (is_object(mixed)) {
				attributes = mixed;
				content = avalue(args, 2, null);
			} else if (args.length > 2) {
				attributes = html.to_attributes(mixed);
				content = args[2];
			} else {
				attributes = {};
				content = mixed;
			}
			name = name.toLowerCase();
			return "<" + name + " " + html.attributes(attributes) + (content === null ? " />" : ">" + content + "</" + name + ">");
		},
		tags : function(name, mixed) {
			var attributes, content, args = arguments, result = "";
			if (is_object(mixed)) {
				attributes = mixed;
				content = avalue(args, 2, null);
			} else if (args.length > 2) {
				attributes = html.to_attributes(mixed);
				content = args[2];
			} else {
				attributes = {};
				content = mixed;
			}
			$.each(content, function() {
				result += html.tag(name, attributes, this);
			});
			return result;
		}
	});

	function object_path(object, path, def) {
		var curr = object, k;
		path = to_list(path, [], ".");
		for (k = 0; k < path.length; k++) {
			if (k === path.length - 1) {
				return avalue(curr, path[k], def);
			}
			curr = avalue(curr, path[k]);
			if (curr === null) {
				return def;
			}
			if (!is_object(curr)) {
				return def;
			}
		}
		return curr;
	}

	function object_set_path(object, path, value) {
		var curr = object, k, seg;
		path = to_list(path, [], ".");
		for (k = 0; k < path.length; k++) {
			seg = path[k];
			if (typeof curr[seg] === "object") {
				curr = curr[seg];
			} else if (k === path.length - 1) {
				curr[seg] = value;
				break;
			} else {
				curr[seg] = {};
				curr = curr[seg];
			}
		}
		return object;
	}

	X.object_path = object_path;
	X.object_set_path = object_set_path;

	function hook_path(hook) {
		hook = to_list(hook, [], "::");
		if (hook.length === 1) {
			hook.push("*");
		}
		return hook;
	}

	X.zesk = zesk = {
		d : d,
		settings : {}, // Place module data here!
		hooks : {}, // Module hooks go here - use add_hook and hook to use
		w : exports,
		url_parts : {
			path : L.pathname,
			host : L.host,
			query : L.search,
			scheme : L.protocol,
			url : document.URL,
			uri : L.pathname + L.search
		},
		page_scripts : null,
		query_get : function(v, def) {
			def = def || null;
			var pair, i, u = d.URL.toString().right("?", null);
			if (!u) {
				return def;
			}
			u = u.split("&");
			for (i = 0; i < u.length; i++) {
				pair = u[i].split("=", 2);
				if (pair[0] === v) {
					return pair[1] || pair[0];
				}
			}
			return def;
		},
		css : function(p) {
			var css = d.createElement('link');
			css.rel = "stylesheet";
			css.href = p;
			css.media = arguments[1] || "all";
			d.getElementsByTagName('head')[0].appendChild(css);
		},
		log : function() {
			if (exports.console && exports.console.log) {
				exports.console.log(arguments);
			}
		},
		add_hook : function(hook, fun) {
			var path = hook_path(hook), curr = object_path(hooks, path);
			if (curr) {
				curr.push(fun);
			} else {
				curr = [ fun ];
				object_set_path(hooks, path, curr);
			}
		},
		has_hook : function(hook) {
			var funcs = object_path(hooks, hook_path(hook), null);
			return funcs ? true : false;
		},
		hook : function(hook) {
			var path = hook_path(hook), 
			args = X.clone(arguments), 
			funcs = object_path(hooks, path, null),
			results = [], 
			i;
			if (!funcs) {
				return results;
			}
			if (args.length > 1) {
				args.shift();
			} else {
				args = [];
			}

			for (i = 0; i < funcs.length; i++) {
				results.push(funcs[i].apply(null, args));
			}
			return results;
		},
		get_path : function(path, def) {
			return object_path(X.zesk.settings, path, def);
		},
		set_path : function(path, value) {
			return object_set_path(X.zesk.settings, path, value);
		},
		get : function(n) {
			var a = arguments;
			return avalue(X.zesk.settings, n, a.length > 1 ? a[1] : null);
		},
		getb : function(n) {
			var a = arguments, d = a.length > 1 ? a[1] : false;
			return to_bool(zesk.get(n, d));
		},
		set : function(n, v) {
			var a = arguments, overwrite = a.length > 2 ? to_bool(a[2]) : true;
			if (!overwrite && typeof X.zesk.settings[n] !== 'undefined') {
				return X.zesk.settings[n];
			}
			X.zesk.settings[n] = v;
			return v;
		},
		inherit : function(the_class, super_class, prototype) {
			// http://stackoverflow.com/questions/1114024/constructors-in-javascript-objects
			var method, Construct = function() {
			};
			super_class = super_class || Object;
			Construct.prototype = super_class.prototype;
			the_class.prototype = new Construct();
			the_class.prototype.constructor = the_class;
			the_class['super'] = super_class;
			if (prototype instanceof Object) {
				for (method in prototype) {
					if (prototype.hasOwnProperty(method)) {
						if (!the_class.prototype[method]) {
							the_class.prototype[method] = prototype[method];
						}
					}
				}
			}
			the_class.prototype.clone = function() {
				return X.clone(this);
			};
			return the_class;
		},
		each : function(x, fn, term_false) {
			var i, r;
			term_false = to_bool(term_false);
			if (is_array(x)) {
				for (i = 0; i < x.length; i++) {
					r = fn.call(x[i], i, x[i]);
					if (term_false) {
						if (!r) {
							return r;
						}
					} else if (r) {
						return r;
					}
				}
			} else if (is_object(x)) {
				for (i in x) {
					if (x.hasOwnProperty(i)) {
						r = fn.call(x[i], i, x[i]);
						if (term_false) {
							if (!r) {
								return r;
							}
						} else if (r) {
							return r;
						}
					}
				}
			} else {
				return fn.call(x, 0, x);
			}
		},
		tpl : function(mixed, map) {
			return $(mixed).html().map(map, false);
		},
		script_loaded : function(src) {
			if (zesk.page_scripts === null) {
				zesk.page_scripts = {};
				$('script[type="text/javascript"][src]').each(function() {
					zesk.page_scripts[$(this).attr('src')] = true;
				});
			}
			return zesk.page_scripts[src] || false;
		},
		stylesheet_loaded : function(href, media) {
			return $('link[rel="stylesheet"][href="' + href + '"][media="' + media + '"').length > 0;
		},
		message : function(message, options) {
			options = is_string(options) ? {
				level : options
			} : options;
			zesk.hook('message', message, options);
			zesk.log(message, options);
		},
		regexp_quote : function (str, delimiter) {
			return String(str).replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
		},
		handle_json : function(data) {
			var total = 0, success = function() {
				--total;
				if (total === 0) {
					if (data.ready) {
						$.each(data.ready, function() {
							/* zesk.log("evaluating " + this); */
							$.globalEval(this);
						});
					}
					if (data.location) {
						document.location = data.location;
					}
				}
			}, error = function(jqXHR) {
				exports.console.log("Request failed", jqXHR);
				success();
			};
			$.each({
				'head' : data.head_tags || [],
				'body' : data.body_tags || []
			}, function(append) {
				var tags = this;
				$.each(tags, function() {
					var tag = this, name = tag.name, attrs = tag.attributes || {}, content = tag.content || null;
					$(append).append(html.tag(name, attrs, content));
				});
			});
			if (data.stylesheets) {
				$.each(data.stylesheets, function() {
					var tag = this, name = tag.name, attrs = tag.attributes || {}, content = tag.content || null;
					if (name === "link") {
						if (attrs.href && zesk.stylesheet_loaded(attrs.href, attrs.media)) {
							return;
						}
						zesk.log("Loading stylesheet " + attrs.href + "(media=" + attrs.media + ")");
					}
					$('head').append(html.tag(name, attrs, content));
				});
			}
			if (data.scripts) {
				$.each(data.scripts, function() {
					if (!zesk.script_loaded(this)) {
						zesk.log("Loading " + this);
						total++;
						zesk.page_scripts[this] = this;
						$.ajax({
							url : this,
							dataType : 'script',
							success : success,
							error : error,
							async : false
						});
					}
				});
			}
			if (data.message) {
				if (is_array(data.message)) {
					$.each(data.message, function() {
						zesk.message(this, data.message_options || {});
					});
				} else {
					zesk.message(data.message);
				}
			}
			total++;
			success();
		}
	};

	X.clone = function(object) {
		var clone, prop, Constructor;
		if (object === null) {
			return object;
		}
		if (is_function(object)) {
			return object;
		}
		if (is_array(object) || X.gettype(object) === "arguments") {
			clone = [];
			for (var i = 0; i < object.length; i++) {
				clone.push(X.clone(object[i]));
			}
			return clone;
		}
		if (!is_object(object)) {
			return object;
		}
		Constructor = object.constructor;
		switch (Constructor) {
			case RegExp:
				clone = new Constructor(object.source, "g".substr(0, Number(object.global)) + "i".substr(0, Number(object.ignoreCase)) + "m".substr(0, Number(object.multiline)));
				break;
			case Date:
				clone = new Constructor(object.getTime());
				break;
			default:
				// Can not copy unknown objects
				return object;
		}
		for (prop in object) {
			if (object.hasOwnProperty(prop)) {
				clone[prop] = X.clone(object[prop]);
			}
		}
		return clone;
	};

	/* Kernel */
	X.is_float = function(a) {
		return typeof a === "number" && parseInt(a, 10) !== a;
	};

	X.is_date = function(a) {
		return Object.prototype.toString.call(a) === '[object Date]';
	};

	X.is_url = function(x) {
		return (/^http:\/\/.+|^https:\/\/.+|^mailto:.+@.+|^ftp:\/\/.+|^file:\/\/.+|^news:\/\/.+/).exec(x.toLowerCase().trim());
	};

	X.gettype = gettype;

	X.each = zesk.each;

	$.extend(Array.prototype, {
		contains : function(x) {
			for (var i = 0; i < this.length; i++) {
				if (this[i] === x) {
					return true;
				}
			}
			return false;
		},
		remove : function(x) {
			var temp = this.slice(0);
			temp.splice(x, 1);
			return temp;
		}
	});

	$.extend(String.prototype, {
		compare : function(a) {
			return (this < a) ? -1 : (this === a) ? 0 : 1;
		},
		left : function(delim, def) {
			var pos = this.indexOf(delim);
			return (pos < 0) ? avalue(arguments, 1, def || this) : this.substr(0, pos);
		},
		rleft : function(delim, def) {
			var pos = this.lastIndexOf(delim);
			return (pos < 0) ? avalue(arguments, 1, def || this) : this.substr(0, pos);
		},
		right : function(delim, def) {
			var pos = this.indexOf(delim);
			return (pos < 0) ? avalue(arguments, 1, def || this) : this.substr(pos + delim.length);
		},
		rright : function(delim, def) {
			var pos = this.lastIndexOf(delim);
			return (pos < 0) ? avalue(arguments, 1, def || this) : this.substr(pos + delim.length);
		},
		ltrim : function() {
			return this.replace(/^\s+/, '');
		},
		rtrim : function() {
			return this.replace(/\s+$/, '');
		},
		trim : function() {
			return this.replace(/^\s+/, '').replace(/\s+$/, '');
		},
		ends_with : function(x) {
			var xn = x.length;
			var n = this.length;
			if (xn > n) {
				return false;
			}
			if (this.substring(n - xn, n) === x) {
				return true;
			}
			return false;
		},

		begins : function(x) {
			var xn = x.length;
			var n = this.length;
			if (xn > n) {
				return false;
			}
			if (this.substring(0, xn) === x) {
				return true;
			}
			return false;
		},
		str_replace : function(s, r) {
			var str = this;
			var i;
			if (is_string(s)) {
				if (is_string(r)) {
					return this.split(s).join(r);
				}
				for (i = 0; i < r.length; i++) {
					str = str.str_replace(s, r[i]);
				}
				return str;
			}
			if (is_string(r)) {
				for (i = 0; i < s.length; i++) {
					str = str.str_replace(s[i], r);
				}
				return str;
			}
			var n = Math.min(s.length, r.length);
			for (i = 0; i < n; i++) {
				str = str.str_replace(s[i], r[i]);
			}
			return str;
		},
		tr : function (object) {
			var k, self = this;
			for (k in object) {
				self = self.str_replace(k, object[k]);
			}
			return self;
		},
		map : function(object, case_insensitive) {
			var k, suffix = "", self;
			case_insensitive = !!case_insensitive; // Convert to bool
			if (!is_object(object)) {
				return this;
			}
			self = this;
			if (case_insensitive) {
				object = zesk.change_key_case(object);
				suffix = "i";
			}
			for (k in object) {
				if (object.hasOwnProperty(k)) {
					var 
					value = object[k], 
					replace = value === null ? "" : object[k].toString(); 
					self = self.replace(new RegExp("\\{" + k + "\\}", "g" + suffix), replace);
				}
			}
			return self;
		},
		to_array : function() {
			var i, r = [];
			for (i = 0; i < this.length; i++) {
				r.push(this.charAt(i));
			}
			return r;
		},
		unquote : function() {
			var n = this.length;
			var q = arguments[0] || '""\'\'';
			var p = q.indexOf(this.substring(0, 1));
			if (p < 0) {
				return this;
			}
			if (this.substring(n - 1, n) === q.charAt(p + 1)) {
				return this.substring(1, n - 1);
			}
			return this;
		},
		toCamelCase : function() {
			var result = "";
			zesk.each(this.split("_"), function() {
				result += this.substr(0, 1).toUpperCase() + this.substr(1).toLowerCase();
			});
			return result;
		},
		fromCamelCase : function() {
			return this.replace(/[A-Z]/g, function(v) {
				return "_" + v.toLowerCase();
			});
		}
	});
	$.extend(String.prototype, {
		ends : String.prototype.ends_with
	});

	X.to_integer = function(x) {
		var d = arguments.length > 1 ? arguments[1] : null;
		x = parseInt(x, 10);
		if (typeof x === 'number') {
			return x;
		}
		return d;
	};

	function to_list(x, def, delim) {
		def = def || [];
		delim = delim || ".";
		if (is_array(x)) {
			return x;
		}
		if (x === null) {
			return def;
		}
		return x.toString().split(delim);
	}

	X.to_list = to_list;

	X.to_float = function(x) {
		var d = arguments.length > 1 ? arguments[1] : null;
		x = parseFloat(x);
		if (typeof x === 'number') {
			return x;
		}
		return d;
	};

	X.to_string = function(x) {
		return x.toString();
	};

	function to_bool(x) {
		var d = arguments.length > 1 ? arguments[1] : false;
		if (is_bool(x)) {
			return x;
		}
		if (is_numeric(x)) {
			return (x !== 0);
		}
		if (is_string(x)) {
			if ([ 't', 'true', '1', 'enabled', 'y', 'yes' ].contains(x)) {
				return true;
			}
			if ([ 'f', 'false', '0', 'disabled', 'n', 'no' ].contains(x)) {
				return false;
			}
		}
		return d;
	}
	X.to_bool = to_bool;

	X.empty = function(v) {
		return typeof v === "undefined" || v === null || v === "";
	};

	X.ZObject = function(options) {
		options = options || {};
		this.options = zesk.change_key_case($.extend({}, options));
		// this.constructor.super.call(this);
	};
	zesk.inherit(X.ZObject, null, {
		clone : function() {
			return X.clone(this);
		}
	});

	X.tag = function(name) {
		var a = arguments;
		if (a.length > 2) {
			return html.tag(name, a[1], a[2]);
		} else {
			return html.tag(name, a[1]);
		}
	};

	zesk.change_key_case = function(me) {
		var k, newo = {};
		for (k in me) {
			if (me.hasOwnProperty(k)) {
				newo[k.toLowerCase()] = me[k];
			}
		}
		return newo;
	};

	if (typeof Math.sign !== 'function') {
		Math.sign = function(x) {
			return x ? x < 0 ? -1 : 1 : 0;
		};
	}

	zesk.ajax_form = function() {
		var $form = $(this), target = $form.attr('target'), $target = $('#' + target);
		zesk.log($target.html());
	};

	/*
	 * Compatibility
	 */
	if (!exports.Object.keys) {
		exports.Object.keys = function(obj) {
			var keys = [], k;
			for (k in obj) {
				if (Object.prototype.hasOwnProperty.call(obj, k)) {
					keys.push(k);
				}
			}
			return keys;
		};
	}

	$.fn.equalheight = function(selector) {
		$(this).each(function() {
			var h = null;
			$(selector, $(this)).each(function() {
				h = Math.max($(this).height(), h);
			});
			$(selector, $(this)).each(function() {
				$(this).height(h + "px");
			});
		});
	};

	zesk.inited = true;

	$(document).ready(function() {
		zesk.hook("document::ready");
	});
	$(window).load(function() {
		zesk.hook("window::load");
	});
}(window, window.jQuery));
