
/*
 *
 * Manipulation 
 *
 */

function unquote(x)
{
	var
	n = x.length,
	q = arguments[1] || '""\'\'',
	p = q.indexOf(x.substring(0,1));
	if (p < 0) {
		return x;
	}
	if (x.substring(n-1, n) == q.charAt(p+1)) {
		return x.substring(1,n-1);
	}
	return x;
}

function str_replace(search, replace, subject) {
	return subject.split(search).join(replace);
}

/**
 * Enter description here...
 * 
 * @param unknown_type
 *            mixed
 * @return array
 */
function html_attributes_parse(mixed) {
	var x = {};
	if (is_object(mixed)) {
		return mixed;
	}
	if (!is_string(mixed)) {
		return x;
	}
	var pattern = / *([A-Za-z][-_:A-Za-z0-9]*)=("[^"]*"|'[^']*'|[^"'\s]+)/; // '
	mixed = " " + mixed;
	var result;
	while (result = pattern.exec(mixed)) {
		x[result[1]] = unhtmlspecialchars(result[2]).unquote();
		mixed = mixed.substring(result.index + result[0].length);
	}
	return x;
}

function html_attributes(attrs) {
	attrs = html_attributes_parse(attrs);
	var result = "";
	for ( var k in attrs) {
		result = result + " " + k.toLowerCase() + "=\""
				+ htmlspecialchars(attrs[k]) + "\"";
	}
	return result;
}

function add_zero(x) {
	if (x < 10)
		return "0" + x;
	return x;
}

function htmlentities() {
	return {
		'&' : '&amp;',
		'<' : '&lt;',
		'>' : '&gt;',
		'"' : '&quot;'
	};
}

function clean_newlines(x) {
	x = "" + x;
	return str_replace("\n", "\r\n", str_replace("\r", "", x));
}
function nl2br(x) {
	x = "" + x;
	return x.split("\r\n").join("<br />");
}

function br2nl(x) {
	x = "" + x;
	x = x.split("<br />").join("\r\n");
	x = x.split("<br>").join("\r\n");
	return x;
}

function htmlspecialchars(x) {
	var e = htmlentities()
	x = "" + x;
	for ( var k in e) {
		if (e.hasOwnProperty(k)) {
			x = str_replace(k, e[k], x);
		}
	}
	return x;
}

function unhtmlspecialchars(x) {
	var e = htmlentities()
	x = "" + x;
	for ( var k in e) {
		if (e.hasOwnProperty(k)) {
			x = str_replace(e[k], k, x);
		}
	}
	return x;
}

function hex_decode(x) {
	var h = "0123456789ABCDEF";
	var r = [];
	x = x.toUpperCase();
	for ( var i = 0; i < x.length; i = i + 2) {
		r[r.length] = (h.indexOf(x[i]) << 4) | h.indexOf(x[i + 1]);
	}
	return r;
}

function hex_encode(x) {
	var h = "0123456789ABCDEF";
	var r = "";
	var c;
	for ( var i = 0; i < x.length; i++) {
		c = x[i];
		r = r + h[(c >> 4) & 0x0F] + h[c & 0x0F]
	}
	return r;
}

function count_occurences(haystack, needle) {
	var n = haystack.split(needle).length;
	return n - 1;
}

/*******************************************************************************
 * ******************************************************************************\
 * 
 * Locale functionalilty
 *  \
 ******************************************************************************/

function locale() {
	var a = arguments;
	if (a.length > 0) {
		return _S('locale', a[0]);
	}
	return zesk.get('locale', 'en_US');
}
function language() {
	var x = to_string(arguments[0] || locale());
	return x.left('_', 'en').toLowerCase();
}
function region() {
	var x = to_string(arguments[0] || locale());
	return x.right('_', 'US').toUpperCase();
}

function translation(locale, map) {
	var tt = zesk.get('translation-table', {});
	locale = locale.toLowerCase();
	if (avalue(tt, locale) === null) {
		tt[locale] = {};
	}
	for ( var k in map) {
		if (map.hasOwnProperty(k)) {
			tt[locale][k] = map[k].toString();
		}
	}
	zesk.set('translation-table', tt);
}

String.prototype._T = function(/* locale=null, default=null */) {
	var a = arguments, loc = a[0] || locale(), text = this.toString(), phrase = this
			.right('::', this), def = a.length > 1 ? a[1] : phrase;
	var tt = zesk.get('translation-table');
	var tt = [ avalue(tt, loc, {}), avalue(tt, language(loc), {}) ], matches;
	var r = each(tt, function(i, t) {
		return t[text] || null;
	});
	if (r) {
		return r;
	}
	r = each(tt, function(i, t) {
		return t[phrase] || null;
	});
	if (r) {
		return r;
	}
	r = each(tt, function(i, t) {
		return t[phrase.toLowerCase()] || null;
	});
	if (r) {
		return case_match_simple(r, phrase);
	}
	return def;
}

String.prototype._M = function() {
	var a = arguments, s = this.toString(), i;
	for (i = 0; i < a.length; i++) {
		s = s.str_replace('{' + i + '}', a[i].toString());
	}
	return s;
}

function case_match_simple(string, pattern) {
	var char1 = pattern.substr(0, 1);
	var char2 = pattern.substr(1, 1);
	if (char1 === char1.toLowerCase(char1)) {
		return string.toLowerCase();
	} else if (char2 === char2.toLowerCase()) {
		return string.substring(0, 1).toUpperCase()
				+ string.substring(1).toLowerCase();
	} else {
		return string.toUpperCase();
	}
}

translation('en', {
	'plural:day' : 'days',
	'plural:staff' : 'staff',
	'plural:sheep' : 'sheep',
	'plural:octopus' : 'octopi',
	'plural:news' : 'news'
});

function plural_en(s/* , n=2 */) {
	var n = to_integer(arguments[1] || 2, 2);
	if (n === 1) {
		return s;
	}
	var ess = ('plural:' + s.toLowerCase())._T('en', null);
	if (ess) {
		return ess;
	}
	var s2 = s.substring(s.length - 2);
	var s1 = s.substring(s.length - 1);
	switch (s1) {
		case 'x':
			return s + "es";
		case 'y':
			return s.substring(0, s.length - 1) + "ies";
	}
	return s + 's';
}

function plural(s/* , n=2, locale=null */) {
	var n = to_integer(arguments[1], 2);
	var locale = arguments[2];
	switch (language(locale)) {
		default:
		case "en":
			return plural_en(s, n, locale)
	}
}

function plural_n(noun/* , n=2, locale=null */) {
	var n = to_integer(arguments[1], 2);
	var loc = arguments[2] || locale();
	var lang = language(loc);
	return 'plural_n::{0} {1}'._T()._M(n, plural(noun, n));
}

function these(noun/* , n=2, locale=null */) {
	var n = to_integer(arguments[1], 2);
	var locale = arguments[2];
	if (n === 1) {
		return "these::this {0}"._T()._M(noun);
	}
	return 'these::these {0}'._T()._M(plural_n(noun, n));
}

function ordinal(n, lang/* , gender="m" */) {
	lang = lang || "en";
	var gender = arguments[2] || 'm';
	switch (lang) {
		case "de":
			var articles = {
				m : 'der',
				f : 'die',
				n : 'das'
			};
			var article = articles[gender] || articles['m'];
			return article + " " + n + ".";
		case "fr":
			if (n === 1) {
				return n + (gender === 'm' ? 'er' : '&egrave;re');
			}
			return n + 'e';
		default: // "en"
			if ((n + 90) % 100 < 10) {
				return n + "th";
			}
			switch (n % 10) {
				case 1:
					return n + "st";
				case 2:
					return n + "nd";
				case 3:
					return n + "rd";
				default:
					return n + "th";
			}
	}
	return n;
}

/**
 * Dates in french do not use the ordinal except for the 1st Other languages
 * vary. English always uses the 1st.
 */
function date_ordinal(n, lang) {
	switch (lang) {
		case "de":
			return "der $n.";
		case "fr":
			if (n === 1) {
				return ordinal(n, lang);
			}
			return n;
		default: // en
			return ordinal(n, lang);
	}
}

/*******************************************************************************
 * ******************************************************************************\
 * 
 * Email functionality
 *  \
 ******************************************************************************/
function Email_Clean(e) {
	var r = /[^\@A-Z0-9a-z_\.\-\']+/g; // '
	e = e.replace(r, "");
	return e;
}

function Email_Valid(e) {
	e = Email_Clean(e);
	var r = /^[^@]+@[a-z0-9][a-z0-9\.\-]+\.[a-z]{2,4}$/i;
	if (e.match(r)) {
		return true;
	}
	return false;
}

function round(n, digits) {
	var mult = Math.pow(10, digits);
	return Math.round(n * mult) / mult;
}

function format_bytes(n) {
	if (n > 1073741824) {
		return round((n / 1073741824), 1) + " GB";
	} else if (n > 1048576) {
		return round((n / 1048576), 1) + " MB";
	} else {
		return parseInt(n / 1024) + " KB";
	}
}

function Cookie_Find(name, def) {
	var c = document.cookie;
	var s = c.indexOf(name + '=');
	if (s < 0) {
		return def;
	}
	s += name.length + 1;
	var e = c.indexOf(';', s);
	if (e < 0) {
		e = c.length;
	}
	return unescape(c.substring(s, e));
}

function Cookie_Set(name, value) {
	var d = new Date(2020, 1, 1, 0, 0, 0);
	document.cookie = name + "=" + escape(value) + '; path=/; expires='
			+ d.toGMTString();
}

/* Deprecated */

var isByID = (document.getElementById) ? true : false;
var isAll = (document.all) ? true : false;

var isIE4 = (isAll && !isByID) ? true : false;
var isIE5 = (isAll && isByID) ? true : false;
var isFF = (navigator.userAgent.indexOf('FireFox') > 0) ? true : false;
var isIE = (isIE4) ? true : false;

var isIECSS1 = (isAll && document.compatMode && document.compatMode === 'CSS1Compat') ? true
		: false;

var isNS4 = (document.layers) ? true : false;
var isNS6 = (!isIE && !isAll && isByID) ? true : false;
var isNS = (isNS4 || isNS6) ? true : false;
if (isNS4) {
	var nsWinX = window.innerWidth;
	var nsWinY = window.innerHeight;
}

var is4 = (isIE4 || isNS4) ? true : false;

var isOpera = (navigator.userAgent.indexOf('Opera') > 0) ? true : false;

/*
 * Browser-specific
 */
function Frame_GetX(f) {
	if (!f) {
		return 0;
	}
	if (isIECSS1) {
		return f.document.documentElement.scrollLeft;
	} else if (isAll) {
		return f.document.body.scrollLeft;
	}
	if (typeof f.pageXOffset === "undefined") {
		return f.pageXOffset;
	}
	return 0;
}

function Frame_GetY(f) {
	if (!f || f === "undefined") {
		return 0;
	}
	if (isIECSS1) {
		return f.document.documentElement.scrollTop;
	} else if (isAll) {
		return f.document.body.scrollTop;
	}
	if (typeof f.pageYOffset === "undefined") {
		return f.pageYOffset;
	}
	return 0;
}

function Frame_GetWidth(f) {
	if (!f || f === "undefined") {
		return 0;
	}
	if (isIECSS1) {
		return f.clientWidth;
	} else if (isAll) {
		return f.document.body.clientWidth;
	} else if (isNS4 || isByID) {
		return f.clientWidth;
	}
	return false;
}

function Window_Width() {
	if (isIE) {
		return document.documentElement.clientWidth;
	}
	return window.innerWidth;
}

function Document_Body(d) {
	var tags = d.getElementsByTagName('body');
	if (tags.length < 1) {
		return false;
	}
	return tags[0];
}

function Window_Height() {
	if (isIE) {
		return document.documentElement.clientHeight;
	}
	return window.innerHeight;
}

function Frame_GetHeight(f) {
	if (!f || f === "undefined") {
		return false;
	}
	if (isIECSS1) {
		return f.clientWidth;
	} else if (isAll) {
		return f.document.body.clientHeight;
	} else if (isNS4 || isByID) {
		return f.clientHeight;
	}
	return false;
}

function FrameObject_Get(f, id) {
	var x = null;
	if (isNS4) {
		x = f.document.filters.document.layers[id];
		if (x && x != "undefined") {
			return x;
		}
	} else if (isIE4) {
		x = f.document.all[id];
	} else if (isByID) {
		x = f.document.getElementById(id);
	}
	if (x && x != "undefined") {
		return x;
	}
	return null;
}

function FrameObject_GetStyle(f, id) {
	var x = null;
	if (isNS4) {
		x = f.document.filters.document.layers[id];
		if (x && x != "undefined") {
			return x;
		}
	} else if (isIE4) {
		x = f.document.all[id];
	} else if (isByID) {
		x = f.document.getElementById(id);
	}
	if (x && x != "undefined") {
		return x.style;
	}
	return null;
}

/*
 * f Frame layer layerID text layer text
 */
function Frame_WriteLayer(f, id, text) {
	var x = FrameObject_Get(f, id);
	if (!x) {
		return false;
	}
	text += "\n";
	if (isNS4) {
		x = x.document;
		if (typeof x === "undefined") {
			alert('x.document is ' + "undefined");
			return false;
		}
		x.write(text);
		x.close();
	} else if (isAll) {
		x.innerHTML = text;
	} else if (isByID) {
		var r = f.document.createRange();
		r.setStartBefore(x);
		frag = r.createContextualFragment(text);
		while (x.hasChildNodes()) {
			x.removeChild(x.lastChild);
		}
		x.appendChild(frag);
	}
	return true;
}

function Object_GetStyle(id) {
	var x = Object_Get(id);
	return x ? x.style : null;
}

function Object_Get(id) {
	return document.getElementById(id);
}

function Object_GetHeight(x) {
	return x.clientHeight;
}

function Object_GetWidth(x) {
	if (typeof x != "object") {
		return false;
	}
	if (isIECSS1) {
		return x.offsetWidth;
	} else {
		return x.clientWidth;
	}
}

function ObjectID_GetWidth(id) {
	var x = Object_Get(id);
	return Object_GetWidth(x);
}

function ObjectID_GetHeight(id) {
	var x = Object_Get(id);
	return Object_GetHeight(x);
}

function ObjectIDs_Width(x) {
	var n = 0;
	for ( var i = 0; i < x.length; i++) {
		n = n + ObjectID_GetWidth(x[i]);
	}
	return n;
}

function ObjectIDs_Height(x) {
	var nn, n = 0;
	for ( var i = 0; i < x.length; i++) {
		nn = ObjectID_GetHeight(x[i]);
		n = n + nn;
	}
	return n;
}

function Object_SetContents(obj, y) {
	obj.innerHTML = y;
	return true;
}

function ObjectID_SetContents(id, y) {
	var obj = Object_Get(id);
	if (!obj) {
		return false;
	}
	return Object_SetContents(obj, y);
}

function ObjectID_Prepend(id, y) {
	var obj = Object_Get(id);
	if (!obj) {
		return false;
	}
	obj.outerHTML = y + obj.outerHTML;
}

function Object_Contents(obj) {
	return obj.innerHTML;
}

function ObjectID_Contents(id) {
	var obj = Object_Get(id);
	if (obj) {
		return obj.innerHTML;
	}
	return '';
}

function Object_Exists(id) {
	return Object_Get(id) ? true : false;
}

function NS_OnResize() {
	if (nsWinX != window.innerWidth || nsWinY != window.innerHeight) {
		location.reload();
	}
}

function Document_OnMouseMove(func) {
	if (typeof func === "undefined") {
		return false;
	}
	if (isNS4) {
		var nsWinX = window.innerWidth;
		var nsWinY = window.innerHeight;
		window.onresize = NS_OnResize;
	}
	if ((isNS4) || (isAll) || (isByID)) {
		document.onmousemove = func;
		if (isNS4) {
			document.captureEvents(Event.MOUSEMOVE);
		}
		return true;
	}
	return false;
}

function ObjectID_DisplayHide(id) {
	var x = Object_GetStyle(id);
	if (x) {
		var save_display = 'none';
		if (typeof x._save_display != "undefined") {
			save_display = x._save_display;
		}
		x.display = save_display;
	}
}

function ObjectID_DisplayShow(id) {
	var x = Object_GetStyle(id);
	if (x) {
		x._save_display = x.display;
		x.display = 'block';
	}
}

function ObjectID_DisplayShowOne(id, hideids) {
	hideids = hideids.split(";");
	for ( var i = 0; i < hideids.length; i++) {
		if (hideids[i].length > 0) {
			ObjectID_DisplayHide(hideids[i]);
		}
	}
	ObjectID_DisplayShow(id);
}

function ObjectID_Display(id, value) {
	if (value) {
		ObjectID_DisplayShow(id);
	} else {
		ObjectID_DisplayHide(id);
	}
}

function Object_IsDisplay(x) {
	if (!x) {
		alert("Object_IsDisplay(!x)");
		return false;
	}
	return (x.display === 'none' || x.display === '') ? false : true;
}

function ObjectID_IsDisplay(id) {
	return Object_IsDisplay(Object_GetStyle(id));
}

function ObjectID_DisplayToggle(id) {
	if (ObjectID_IsDisplay(id)) {
		ObjectID_DisplayHide(id);
		return false;
	} else {
		ObjectID_DisplayShow(id);
		return true;
	}
}

function ObjectID_ToggleLink(a, id0, id1, showText, hideText) {
	var result = ObjectID_DisplayToggle(id0);
	ObjectID_DisplayToggle(id1);
	a.innerHTML = result ? showText : hideText;
}

function Object_Top(obj) {
	if (!obj || obj === "undefined") {
		return false;
	}
	var n = 0;
	while (obj) {
		n += obj.offsetTop;
		obj = obj.offsetParent;
	}
	return n;
}

function Object_VisibleShow(x) {
	if (!x) {
		alert("Object_VisibleShow: no x");
		return;
	}
	if (isNS4) {
		x.visibility = "show";
	} else if (isAll || isByID) {
		x.visibility = "visible";
	}
}

function Object_VisibleHide(x) {
	if (!x) {
		return;
	}
	if (isNS4) {
		x.visibility = "hide";
	} else if (isAll || isByID) {
		x.visibility = "hidden";
	}
}

function Object_IsVisible(x) {
	if (!x) {
		alert("Object_IsVisible: no x");
		return false;
	}
	if (isNS4) {
		return (x.visibility === "hide") ? false : true;
	} else if (isIE4 || isNS6) {
		return (x.visibility === "hidden") ? false : true;
	}
	return true;
}

function Object_VisibleToggle(x) {
	if (!x) {
		return;
	}
	if (Object_IsVisible(x)) {
		Object_VisibleHide(x);
	} else {
		Object_VisibleShow(x);
	}
}

function Object_SetBackground(x) {
	if (!x) {
		if (isNS4) {
			x.background.src = null;
		} else if (isIE4 || isNS6) {
			x.backgroundImage = "none";
		}
	} else {
		if (isNS4) {
			x.background.src = x;
		} else if (isIE4 || isNS6) {
			x.backgroundImage = "url(" + x + ")";
		}
	}
}

function ObjectID_VisibleToggle(id) {
	return Object_VisibleToggle(Object_GetStyle(id));
}

function ObjectID_VisibleHide(id) {
	return Object_VisibleHide(Object_GetStyle(id));
}

function ObjectID_VisibleShow(id) {
	return Object_VisibleShow(Object_GetStyle(id));
}

function ObjectID_IsVisible(id) {
	return Object_IsVisible(Object_GetStyle(id));
}

function Object_MoveTo(obj, x, y) {
	if (!obj || obj === "undefined") {
		return;
	}
	var sfx = 'px';
	if (is4) {
		if (!isAll) {
			sfx = '';
		}
	} else if (!isByID) {
		return;
	}
	obj.left = x + sfx;
	obj.top = y + sfx;
}

function ObjectID_MoveTo(id, x, y) {
	return Object_MoveTo(Object_GetStyle(id), x, y);
}

function Object_Left(obj) {
	if (!obj || obj === "undefined") {
		return false;
	}
	var n = 0;
	while (obj) {
		n += obj.offsetLeft;
		obj = obj.offsetParent;
	}
	return n;
}

/*
 * HTML Tools
 */
function _HTML_Tag(name, args, single) {
	var a = args;
	var n, v;
	var r = [];
	r[r.length] = name;
	for ( var i = 1; i < a.length; i += 2) {
		n = a[i];
		v = a[i + 1];
		if (v !== 0
				&& (v === '' || v === "undefined" || typeof v === "undefined")) {
			continue;
		}
		r[r.length] = n + '="' + v + '"';
	}
	return '<' + r.join(" ") + (single ? '/' : '') + '>';
}

function HTML_SingleTag(name) {
	return _HTML_Tag(name, arguments, true);
}

function HTML_Tag(name) {
	return _HTML_Tag(name, arguments, false);
}

function HTML_EndTag(name) {
	return '</' + name + '>';
}

function IE_DHTML_Hack(form, hide) {
	if (!isIE || isOpera) {
		return;
	}
	var i;
	var verb = (hide) ? "hidden" : "visible";
	for (i = 0; i < form.elements.length; i++) {
		if (form.elements[i].type.indexOf('select') === 0
				&& form.elements[i].name !== "") {
			form.elements[i].style.visibility = verb;
		}
	}
}

function Document_Scan(n, expr) {
	var c, v, x, offset, result = [];
	for ( var i = 0; i < n.childNodes.length; i++) {
		c = n.childNodes[i];
		if (c.nodeType === 3) {
			v = c.nodeValue;
		} else if (c.nodeType === 1 && c.nodeName.toLowerCase() === "input") {
			v = c.value;
		} else {
			v = null;
		}
		if (v !== null) {
			offset = v.search(expr);
			if (offset >= 0) {
				x = {};
				x.offset = offset;
				x.match = v.match(expr);
				x.node = c;
				result.push(x);
			}
		}
		result = result.concat(Document_Scan(c, expr));
	}
	return result;
}

function RandomString() {
	return (Math.random() + '').substring(2, 10);
}

function noop() {
}

var gStyleSheetTitle = Cookie_Find('style', 'normal');

function Server_Message(uri) {
	var i = new Image();
	var r = RandomString();
	var p = uri.lastIndexOf('?');
	if (p < 0) {
		uri += '?';
	} else {
		uri += '&';
	}
	i.src = uri + "r=" + r;
}

function Style_SetActive(title/* , saveIt */) {
	var i, a, main;
	var saveIt = (arguments.length > 1) ? arguments[1] : true;
	for (i = 0; (a = document.getElementsByTagName("link")[i]); i++) {
		if (a.getAttribute("rel").indexOf("style") != -1
				&& a.getAttribute("title")) {
			a.disabled = true;
			if (a.getAttribute("title") === title) {
				a.disabled = false;
			}
		}
	}
	if (saveIt) {
		gStyleSheetTitle = title;
		setCookie('style', title);
	}
}

function Style_Refresh() {
	var src;
	var i = document.images.style_change;
	if (i) {
		src = 'style_' + gStyleSheetTitle + '.gif';
		i.src = '/images/' + src;
	}
	Style_SetActive(gStyleSheetTitle, true);
}

function Style_Toggle() {
	var a = arguments;
	var i;
	var c;

	if (a.length === 0) {
		a[0] = 'normal';
		a[1] = 'big';
	}
	c = 0;
	for (i = 0; i < a.length; i++) {
		if (a[i] === gStyleSheetTitle) {
			c = (i + 1) % a.length;
			break;
		}
	}
	gStyleSheetTitle = a[c];
	Style_Refresh();
}

function ObjectID_DisplayToggleState(f, id, fname) {
	f[fname].value = ObjectID_DisplayToggle(id) ? "block" : "none";
	return false;
}

function AppendSection(to, contents) {
	ObjectID_SetContents(to, ObjectID_Contents(to) + contents);
	return true;
}

function String_Catenate(delimiter /* , string1, string2 */) {
	var i;
	var s;

	s = "";
	for (i = 1; i < arguments.length; i++) {
		if (arguments[i] !== "") {
			if (s !== "") {
				s += delimiter;
			}
			s += arguments[i];
		}
	}
	return s;
}

function URL_Valid(u, schemes) {
	u = u.trim();
	if (u === "") {
		return true;
	}
	var s = schemes.toLowerCase().split(";");
	var n = s.length;
	var x = u.indexOf('://');
	if (x < 0) {
		alert('Please specify a URL containing "://", such as http://cr.to.');
		return false;
	}
	var scheme = u.substr(0, x).toLowerCase();
	for ( var i = 0; i < n; i++) {
		if (s[i] === scheme) {
			return true;
		}
	}
	alert('Please specify a URL that begins with ' + s.join(' or '));
	return false;
}

function Window_NormalizeNameFromURI(uri) {
	var e = /[^A-Z0-9_]/gi;
	var r = uri.replace(e, "_");
	if (r === "") {
		r = 'default';
	}
	return r;
}

function Window_FormName(w) {
	if ((typeof w.form.name != "undefined") && w.form.name !== "") {
		return w.form.name;
	}
	return w.form.id;
}

function Window_Open(theLink, width, height) {
	var wparam = "resizable=yes,toolbar=no,menubar=no,scrollbars=yes,width="
			+ width + ",height=" + height;
	var name = Window_NormalizeNameFromURI(theLink);
	var newwin = window.open(theLink, name, wparam);
	if (newwin) {
		newwin.focus();
	}
	return newwin;
}

var gFormsPending = 0;
var gConfirmTime = new Date();
var gFormState = {};
var gLastAnswer = false;

function Control_Value(control) {
	var def = arguments[1] || "";
	var value;
	if (!control) {
		return def;
	}
	if (control.type.substr(0, 6) === "select") {
		return control.options[control.selectedIndex].value;
	} else if (control.type === "checkbox") {
		return control.checked;
	} else {
		value = control.value;
	}
	if ((value === "") || (value === null)) {
		return def;
	}
	return value;
}

function Control_HasValue(control) {
	var v = Control_Value(control, false);
	if ((v === "") || (v === null)) {
		return false;
	}
	return true;
}

function Control_SelectText(control, def) {
	if (!control) {
		return def;
	}
	if (control.type.substr(0, 6) === "select") {
		return control.options[control.selectedIndex].text;
	}
	return def;
}

function Control_SetValue(control, value) {
	if (!control) {
		alert("Control_SetValue(!control," + value + ")");
		return false;
	}
	if (control.type === "select") {
		var i;
		for (i = 0; i < control.options.length; i++) {
			if (control.options[i].value === value) {
				control.selectedIndex = i;
				return true;
			}
		}
		alert("Control_SetValue(" + form + "," + name + "," + value
				+ "): unable to find select value");
		return false;
	} else if (control.type === "checkbox") {
		control.checked = (value) ? true : false;
		return true;
	} else {
		if (control.value != value) {
			control.value = value;
		}
		return true;
	}
}

/*
 * This doesn't work, unless we alert between SetValue and submit
 */
function Control_SetValuePost(control, value) {
	Control_SetValue(control, value);
	control.form.submit();
	return true;
}

function Control_Checked(control) {
	if (control.type === "checkbox") {
		return control.checked;
	}
	return false;
}

function Form_SetChecked(form, name, value) {
	var control;
	control = form.elements[name];
	if (!control) {
		return;
	}
	if (control.type === 'checkbox') {
		form.elements[name].checked = value;
	}
}

function Form_GetControl(form, name /* , func */) {
	var control;
	var func = "unspecified";
	if (arguments.length > 2) {
		func = arguments[2];
	}
	control = form.elements[name];
	if (!control) {
		// alert(func + "(" + form + ","+ name +"," + value + "): undefined");
		return false;
	}
	return control;
}
function Form_SetBackground(form, name, color) {
	var control = Form_GetControl(form, name, "Form_SetEnabled");
	if (control) {
		control.style.backgroundColor = color;
	}
}

function Form_SetEnabled(form, name, value) {
	var control = Form_GetControl(form, name, "Form_SetEnabled");
	if (control) {
		control.disabled = (value) ? false : true;
	}
}

function Form_GetEnabled(form, name) {
	var control = Form_GetControl(form, name, "Form_SetEnabled");
	if (control) {
		return !control.disabled;
	}
	return false;
}

function Form_Value(form, name, def) {
	var control;
	control = form.elements[name];
	if (!control || typeof control === "undefined") {
		return def;
	}
	return Control_Value(control, def);
}

function Form_SetValue(form, name, value) {
	var control = form.elements[name];
	if (!control) {
		return false;
	}
	if (!Control_SetValue(control, value)) {
		return false;
	}
	return true;
}

function Form_SetEnabledLike(form, mixed, enabled) {
	var e = form.elements;
	var i;
	if (typeof mixed === "object") {
		for (i = 0; i < mixed.length; i++) {
			Form_SetEnabledLike(form, mixed[i], enabled);
		}
	} else {
		for (i = 0; i < e.length; i++) {
			if (e[i].name.indexOf(mixed) >= 0) {
				e[i].disabled = enabled ? false : true;
			}
		}
	}
}

function Control_ClearFocus(w, value) {
	if (w.value === value) {
		w.value = '';
	}
}

function Control_ResetFocus(w, value) {
	if (w.value === '') {
		w.value = value;
	}
}

function clink(w) {
	if (gFormsPending === 0) {
		return true;
	}
	var fname = false;
	if (typeof w === 'object') {
		fname = w.name;
	}
	var n, message = '';
	gFormsPending = 0;

	for (n in gFormState) {
		if (gFormState.hasOwnProperty(n)) {
			if (gFormState[n].changed) {
				if (n != fname) {
					message += gFormState[n].message + "\n";
				}
				gFormsPending++;
			}
		}
	}
	if (message !== "") {
		var newTime = new Date();
		var delta = newTime.getTime() - gConfirmTime.getTime();
		// alert(newTime.getTime() + " - " + gConfirmTime.getTime() + " = " +
		// delta);
		if (delta < 2000) {
			return gLastAnswer;
		}
		gConfirmTime = new Date();
		gLastAnswer = confirm(message);
		return gLastAnswer;
	}
	return true;
}

function clinkhref(x) {
	if (gFormsPending === 0) {
		return true;
	}
	if (clink(null)) {
		document.location = x;
		return true;
	}
	return false;
}

function Form_Register(form, message) {
	gFormState[form.name] = {};
	gFormState[form.name].message = message;
	gFormState[form.name].changed = 0;
}

function Form_Cancel(form) {
	form.reset();
	gFormState[form.name].changed = 0;
}

function Form_CancelAll() {
	for ( var i in gFormState) {
		if (gFormState.hasOwnProperty(i)) {
			Form_Cancel(document.forms[i]);
		}
	}
}

function ichanged(w) {
	if (typeof gFormState[w.form.name] != "undefined") {
		gFormState[w.form.name].changed = 1;
		++gFormsPending;
	}
}

var gCallback = {};

function callback(o, func) {
	o.to = setTimeout(func + "(" + o.n + ")", o.nMS);
}

function textarea_resizer(fname, ename, xpad, ypad) {
	var e = document.forms[fname].elements[ename];

	this.xpad = xpad;
	this.ypad = ypad;

	this.ww = Window_Width();
	this.wh = Window_Height();

	this.e = e;
	this.dx = -Object_GetWidth(e);
	this.dy = -Object_GetHeight(e);
	this.n = gCallback.length;
	this.nMS = 10;

	e.cols += 10;
	e.rows += 10;

	callback(this, "ta_test");

	gCallback[this.n] = this;
}

function ta_test(n) {
	if (typeof Window_Height === "undefined") {
		callback(this, "ta_test");
	} else {
		var o = gCallback[n];
		var e = o.e;

		o.dx = (o.dx + Object_GetWidth(e)) / 10.0;
		o.dy = (o.dy + Object_GetHeight(e)) / 10.0;

		gCallback[n] = o;

		callback(this, "ta_resize");
	}
}

function ta_resize(n) {
	var o = gCallback[n];
	var e = o.e;

	if (typeof e.cols === "undefined") {
		o.nMS = 1000;
		callback(this, "ta_resize");
		return;
	}

	var ww = Window_Width();
	var wh = Window_Height();

	e.cols = Math.max(parseInt((ww - o.xpad) / o.dx, 10), 10);
	e.rows = Math.max(parseInt((wh - o.ypad) / o.dy, 10), 10);

	o.nMS = 10;
	callback(this, "ta_resize");
}

/** ********* Lists ****************** */

function ListControl_CountChecked(form, prefix, value) {
	var i = form.elements.length;
	var n = 0;
	var name = prefix + "[]";
	var nlen = name.length;
	if (Form_Value(form, prefix + "CheckAll", false)) {
		return -1;
	}
	while (i-- !== 0) {
		if ((form.elements[i].type === "checkbox")
				&& (form.elements[i].name.substring(0, nlen) === name)
				&& (form.elements[i].checked === value)) {
			++n;
		}
	}
	return n;
}

function ListControl_SetChecked(form, prefix, value) {
	var i = form.elements.length;
	var nlen = prefix.length;
	while (i-- !== 0) {
		if (form.elements[i].name.substring(0, nlen) === prefix) {
			form.elements[i].checked = value;
		}
	}
}

function ListControl_CheckPage(control, prefix) {
	var form = control.form;
	var value = Control_Value(control, false);

	ListControl_SetChecked(form, prefix + "[]", value);
	Form_SetChecked(form, prefix + "CheckAll", false);
}

function ListControl_CheckItem(control, prefix) {
	var form = control.form;
	var value = Control_Value(control, false);
	if (value === false) {
		Form_SetChecked(form, prefix + "CheckAll", false);
	} else if (ListControl_CountChecked(form, prefix, false) === 0) {
		Form_SetChecked(form, prefix + "CheckAll", true);
	}
}

function ListControl_CheckAll(control, prefix) {
	var form = control.form;
	var value = Control_Value(control, false);

	if (value || (ListControl_CountChecked(form, prefix, false) === 0)) {
		ListControl_SetChecked(form, prefix + "[]", value);
	}
}

function ListControl_Action(control, name, promptPrefix, promptSuffix,
		confirmPrefix, confirmSuffix) {
	var form = control.form;

	var id = Control_Value(control, false);
	if (id === false) {
		return false;
	}
	if (id === "new") {
		var promptString = String_Catenate(" ", promptPrefix, name,
				promptSuffix);
		nn = window.prompt(promptString);
		if (nn === null || nn === "null" || nn === "") {
			return false;
		} else {
			control.selectedIndex = 0;
			Form_SetValue(form, control.name + "New", nn);
			form.submit();
			return true;
		}
	} else {
		var message = String_Catenate(" ", confirmPrefix, name, confirmSuffix);
		if (confirm(message)) {
			form.submit();
			return true;
		}
	}
	return false;
}

function ListControl_ConfirmAction(button, prefix, action, object_name) {
	var form = button.form;
	var m;
	var n;
	object_name = ((object_name || 'item') + "").trim();
	object_name = object_name || "item";
	var n = ListControl_CountChecked(form, prefix, true);
	var verb = ("ListAction::" + action)._T();
	if (n === 0) {
		m = 'ListAction::You need to select at least one {0} to {1}.'._T();
		m = m._T()._M(object_name, verb);
		message(m);
		return false;
	}
	m = 'Are you sure you want to delete {0}?'._T()._M(these(object_name, n));
	if (confirm(m)) {
		form.submit();
		return true;
	} else {
		return false;
	}
}

/*******************************************************************************
 * ****************************************************************************************\
 * 
 * Query String manipulation
 *  \
 ******************************************************************************/

function QS_parse() {
	var qs = arguments[0] ? arguments[0] : document.search;
	var pos;
	if ((pos = qs.indexOf('?')) >= 0) {
		qs = qs.substring(pos + 1);
	}
	var q = qs.split('&');
	var r = {};
	var p;
	for ( var i = 0; i < q.length; i++) {
		p = q[i].split('=', 2);
		r[p[0]] = (p.length === 2) ? p[1] : true;
	}
	return r;
}

function QS_format(url, q_add/* , q_remove=false */) {
	var k;
	var qs = QS_parse(url);
	if (typeof q_add === "string") {
		q_add = QS_parse(q_add);
	}
	var q_remove = arguments[2] || "";
	if (typeof q_remove === "string") {
		q_remove = q_remove.split(";");
	}
	if (typeof q_remove === "array") {
		q_remove = q_remove.join(";");
	}
	q_remove = ";" + q_remove + ';';
	var sep = "?";
	if ((k = url.indexOf('?')) >= 0) {
		url = url.substring(0, k);
	}
	for (k in qs) {
		if (qs.hasOwnProperty(k)) {
			if (q_add[k]) {
				continue;
			}
			url = url + sep + k + "=" + escape(qs[k]);
			sep = "&";
		}
	}
	for (k in q_add) {
		if (q_add.hasOwnProperty(k)) {
			if (q_remove.indexOf(';' + k + ';') >= 0) {
				continue;
			}
			url = url + sep + k + "=" + escape(q_add[k]);
			sep = "&";
		}
	}
	return url;
}

function QS_get(n) {
	var d = arguments.length > 1 ? arguments[1] : null;
	var q = location.search;
	if (q.substring(0, 1) === "?") {
		q = q.substring(1);
	}
	q = q.split("&");
	var p;
	n = n.toLowerCase();
	for ( var i = 0; i < q.length; i++) {
		p = q[i].split("=", 2);
		if (p.length === 2 && p[0].toLowerCase() === n) {
			return unescape(p[1]);
		}
	}
	return d;
}

/*******************************************************************************
 * ****************************************************************************************\
 * 
 * prototype.js related stuff here
 *  \
 ******************************************************************************/
var ajax_finish = {};

function toggle(id) {
	var e = new Effect[Element.visible(id) ? 'BlindUp' : 'BlindDown'](id, {
		duration : 0.25
	});
}

function ajax_edit(id, uri) {
	if (Element.empty(id)) {
		var x = new Ajax.Updater(id, uri, {
			method : 'get',
			onFailure : function() {
				Element.classNames(id).add('error');
			},
			onComplete : function() {
				var e = new Effect.BlindDown(id, {
					duration : 0.25
				});
			}
		});
	} else {
		toggle(id);
	}
	if (arguments.length >= 3) {
		if (typeof arguments[2] === "function") {
			ajax_finish[id] = arguments[2];
		}
	}
}

function ajax_form_cancel(id) {
	var delay = arguments.length > 1 ? arguments[1] : 0;
	var e = new Effect.Fade(id, {
		duration : 1,
		delay : delay,
		afterFinish : function() {
			$(id).innerHTML = "";
		}
	});
	ajax_finish[id] = false;
}

function ajax_set_content(id, content) {
	id = $(id);
	id.innerHTML = content;
}

function ajax_form_finish(id, message) {
	if (message.length > 0) {
		ajax_set_content(id, '<div class="message">' + message + '</div>');
	}
	var finish_function = ajax_finish[id];
	ajax_finish[id] = null;
	ajax_form_cancel(id, 3);
	if (typeof finish_function === "function") {
		finish_function();
	}
}

function ajax_form_handle_result(id) {
	var content = $(id).innerHTML;
	if (content.indexOf("*success*") > 0) {
		ajax_form_finish(id, '');
	}
}

function form_disable(form_id) {
	var form;
	if (typeof form_id === "object") {
		form = form_id;
	} else {
		form = document.forms[form_id];
	}
	for (i = 0; i < form.elements.length; i++) {
		form.elements[i].disabled = true;
	}
}

function ajax_form_serialize(form) {
	var elements = Form.getElements($(form));
	var queryComponents = [];
	for ( var i = 0; i < elements.length; i++) {
		if (elements[i].type === 'button') {
			continue;
		}
		var queryComponent = Form.Element.serialize(elements[i]);
		if (queryComponent) {
			queryComponents.push(queryComponent);
		}
	}
	return queryComponents.join('&');
}

function ajax_form_submit(widget, id, uri/* , finish_fuction */) {
	var qs = ajax_form_serialize(widget.form) + "&OK=1&embed=1&ajax_id=" + id;
	form_disable(widget.form);
	if (typeof arguments[3] === "function") {
		ajax_finish[id] = arguments[3];
	}
	var x = new Ajax.Updater(id, uri, {
		method : 'post',
		postBody : qs,
		onFailure : function() {
			alert("A problem occurred");
		},
		onComplete : function() {
			ajax_form_handle_result(id);
		}
	});
}

function ajax_form_continue(form, id) {
	var form_name = form;
	form = $(form);
	var uri = form.action;
	if (form_name === "ajax_main_form") {
		form.action = form.action
				+ ((form.action.indexOf("?") < 0) ? "?" : "&") + "_continue=1";
		form.submit();
		return true;
	}
	var qs = ajax_form_serialize(form) + "&embed=2&_continue=1&ajax_id=" + id;
	form_disable(form);
	var x = new Ajax.Updater(id, uri, {
		method : 'post',
		postBody : qs,
		onFailure : function() {
			alert("A problem occurred");
		},
		onComplete : function() {
		}
	});
}

function toggle_edit(id) {
	// new Effect[Element.visible(id + "_edit") ? "SlideUp" : "SlideDown"](id +
	// "_edit",{duration:0.5});
	$('#' + id + '_edit').slideToggle();
}

function slide_toggle(id) {
	// new Effect[Element.visible(id) ? "SlideUp" :
	// "SlideDown"](id,{duration:0.5});
	$('#' + id).slideToggle();
}

function html_control_append(id, content) {
	var new_div = document.createElement('div');
	new_div.innerHTML = content;
	$(id).parentNode.appendChild(new_div);
}

function image_share_change(id, new_src) {
	var src = $(id).attr('src');
	var pos = src.indexOf('/share/');
	src = src.substr(0, pos) + new_src;
	$(id).attr('src', src);
}

function arrow_down_right_all(v) {
	if (v) {
		$('.toggle-arrow-content').show();
		$('div.toggle-arrow a.toggle-arrow img').each(function(k, v) {
			image_share_change(v, '/share/zesk/images/toggle/small-down.gif');
		});
	} else {
		$('.toggle-arrow-content').hide();
		$('div.toggle-arrow a.toggle-arrow img').each(function(k, v) {
			image_share_change(v, '/share/zesk/images/toggle/small-right.gif');
		});
	}
}

function list_dhtml_extract(w, id) {
	var f = w.form;
	var find = '[' + id + ']';
	var qs = '';
	for ( var i = 0; i < f.elements.length; i++) {
		var e = f.elements[i];
		if (e.name.substring(e.name.length - find.length) === find) {
			qs += (qs ? '&' : '')
					+ e.name.substring(0, e.name.length - find.length) + '='
					+ escape(Control_Value(e));
		}
	}
	return qs;
}

function list_dhtml_save(w, id) {
	w.value = "Saving...";
	w.disabled = true;
	var x = new Ajax.Updater('list-message-' + id, 'edit.php?ID=' + id + '&'
			+ list_dhtml_extract(w, id) + '&ajax_list=1', {
		onComplete : function() {
			w.disabled = false;
			w.value = "Save";
			var e = new Effect.Fade('list-message-' + id, {
				delay : 2,
				duration : 1
			});
		}
	});
}

function list_dhtml_delete(w, id) {
	if (!confirm('Are you sure you want to delete?')) {
		return false;
	}
	w.value = "Deleting ...";
	w.disabled = true;
	var x = new Ajax.Updater('list-message-' + id, 'edit.php?ID=' + id + '&'
			+ list_dhtml_extract(w, id) + '&action=delete&ajax_list=1', {
		onComplete : function() {
			w.disabled = true;
			w.value = "Deleted!";
			var e = new Effect.Fade('list-row-' + id, {
				delay : 0.2,
				duration : 1
			});
		}
	});
}

function QueryParameter(name/* , default */) {
	var s = document.location.search;
	var def = (arguments.length > 1) ? arguments[1] : false;
	if (s.substr(0, 1) === '?') {
		s = s.substr(1);
	}
	var x = s.split('&'), i, nv;
	for (i = 0; i < x.length; i++) {
		nv = x[i].split('=');
		if (nv[0].toLowerCase() === name) {
			if (nv.length === 1) {
				return true;
			}
			return unescape(nv[1]);
		}
	}
	return def;
}

function hide_id(id) {
	Element.hide(id);
}

function closebox_fade(area, indelay, induration) {
	return new Effect.SlideUp(area, {
		delay : indelay,
		duration : induration
	});
}

/*******************************************************************************
 * ****************************************************************************************\
 * 
 * jquery.js related stuff here
 *  \
 ******************************************************************************/
function message(m) {
	$('#message-container').hide();
	$('#message-container .message').html(m);
	$('#message-container').fadeIn();
	setTimeout('$(\'#message-container\').fadeOut();', 5000);
	$('#message-container').click(function() {
		$(this).fadeOut('fast');
	});
}

function pager_limit_change(ajax_id) {
	$.get('?' + $(":input", this.form).serialize(), function(data) {
		$('#' + ajax_id).html(data);
	});
}

function arrow_down_right_jquery(id) {
	var state_url = arguments[1] || false;
	var is_vis = $('#' + id).is(":visible");
	$('#' + id).slideToggle(
			500,
			function() {
				image_share_change('#' + id + "_img",
						"/share/zesk/images/toggle/small-"
								+ (is_vis ? "right" : "down") + ".gif");
			});
	if (state_url) {
		state_url = state_url.replace(/\{value\}/, is_vis ? 'false' : 'true');
		$.get(state_url);
	}
	if (!state_url) {
		if (!is_vis) {
			document.location = "#@" + id;
		} else {
			document.location = "#";
		}
	}
}

function ellipsis_toggle(n) {
	$('#ellipsis-' + n).toggle();
	$('#ellipsis-' + n + '-all').toggle();
}

function ajax_jquery_form_cancel(id, func) {
	$('#' + id).slideUp(500, function() {
		if (func) {
			func();
		}
	});
}

function ajax_jquery_form_submit(id, func) {
	// var data = $('#'+id+' form').serialize();
	var uri = $('#' + id + ' form').attr('action');
	var data = {};
	// var debug = "";
	$('#' + id + ' form :input:not(:button)').each(function() {
		data[$(this).attr('name')] = $(this).val();
		// debug += $(this).attr('name') + "=" + $(this).val() + "\n";
	});
	// alert(debug);
	$.post(uri, data, func || null);
}

if (!window.console) {
	var console = {};
	console.log = function(x) {
	};
}

function cdn_prefix() {
	if (arguments[0]) {
		window._cdn_prefix = arguments[0];
	}
	return window._cdn_prefix ? window._cdn_prefix : "";
}

function arrow_down_right(id) {
	var state_url = arguments[1] || false;
	if (typeof Prototype === "undefined") {
		arrow_down_right_jquery(id, state_url);
	} else {
		var is_vis = Element.visible(id);
		if (false) {
			if (is_vis) {
				$(id).hide();
			} else {
				$(id).show();
			}
			image_share_change(id + "_img",
					(is_vis) ? "/share/zesk/images/toggle/small-right.gif"
							: "/share/zesk/images/toggle/small-down.gif");
		} else {
			var args = arguments[1] || {
				duration : 0.25
			};
			if (is_vis) {
				args.afterFinish = function() {
					image_share_change(id + "_img",
							"/share/zesk/images/toggle/small-right.gif");
				};
			} else {
				args.afterFinish = function() {
					image_share_change(id + "_img",
							"/share/zesk/images/toggle/small-down.gif");
				};
			}
			var e = new Effect[is_vis ? 'BlindUp' : 'BlindDown'](id, args);
		}
		state_url = state_url.replace(/\{value\}/, is_vis ? 'false' : 'true');
		var x = new Ajax.Updater(state_url);
	}

}

function arrow_down_right_load(iid, url) {
	var id = iid;
	if ($('#' + id).html() === "") {
		$.get(url, function(html) {
			$('#' + id).html(html);
			arrow_down_right(id);
		});
	} else {
		arrow_down_right(id);
	}
}
function Control_Select_ORM_Dynamic_KeyDown(e) {
	if (e.keyCode === 13) {
		alert($(e).serialize());
	}
}

function Control_Select_ORM_Dynamic_Update(name, url) {
	var id = name;
	var form = $('#' + id + '_widget :input');
	form = form[0].form;
	var data = $(":input", form).serialize();
	var p = '#' + id + '_widget';
	p = p + ' ';
	var query = $(p + '.csod-input input').val();
	if (query === "") {
		return;
	}
	url += (url.indexOf('?') < 0 ? '?' : '&') + data;
	$(p + '.csod-wait').show();
	$.getJSON(url, function(data) {
		$(p + '.csod-wait').hide();
		if (typeof data === "object") {
			var options = "";
			for (var k in data) {
				if (data.hasOwnProperty(k)) {
					options = options + '<option value=\"' + k + '\">' + data[k] + '</option>';
				}
			}
			$(p + '.csod-input').hide();
			$(p + '.csod-search').hide();
			$(p + '.csod-select').html(
					'<select name=\"' + id + '\">' + options + '</select>');
			$(p + '.csod-message').hide('slow');
			$(p + '.csod-select').show();
		} else {
			if ("" + data === "0") {
				$(p + '.csod-message-no span.query').html(query);
				$(p + '.csod-message-no').show('slow');
				$(p + '.csod-message').hide('slow');
			} else {
				$(p + '.csod-message span.count').html(data);
				$(p + '.csod-message').show('slow');
				$(p + '.csod-message-no').hide('slow');
			}
		}
	});
}

function Control_Select_ORM_Dynamic_Reset(id) {
	var p = '#' + id + '_widget ';
	$(p + '.csod-input input').val('');
	$(p + '.csod-select').html(
			'<input name=\"' + id + '\" value="" type="hidden" />');
	$(p + '.csod-none').hide();
	$(p + '.csod-some').show();
	$(p + '.csod-input input').blur();
}

function Control_Arrow_onload() {
	var frag = document.URL.split('#');
	if (frag[1] && frag[1].substr(0, 1) === '@') {
		$('#' + frag[1].right('@')).show();
	}
}

function Control_Arrow_toggle(id) {
	var state_url = arguments[1] || false;
	var img = $('#' + id + '_img');
	var item = $('#' + id);
	var is_vis = item.is(":visible");
	item.slideToggle(500, function() {
		img.attr('src', img.attr('data-src-' + (is_vis ? 'closed' : 'open')));
	});
	if (state_url) {
		state_url = state_url.replace(/\{value\}/, is_vis ? 'false' : 'true');
		$.get(state_url);
	}
	if (!state_url) {
		if (!is_vis) {
			document.location = "#@" + id;
		} else {
			document.location = "#";
		}
	}
}

