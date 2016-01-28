/* 
Deprecated, doesn't work in FireFox, or in Flash 10

use Flash Exclusively instead

function is_clipboard_supported() {
	if (window.clipboardData && clipboardData.setData) {
		return true;
	}
	if (element.createTextRange) {
		return true;
	}
	if (d.createElement && d.getElementById) {
		return true;
	}
	return false;
}

function clipboard_copy(element) {
	var d = document;
	if (window.clipboardData && clipboardData.setData) {
		clipboardData.setData('Text', element.value);
		return true;
	}
	if (element.createTextRange) {
		var range = element.createTextRange();
		if (range && BodyLoaded == 1) {
			range.execCommand('Copy');
			return true;
		}
	} 
	if (navigator.userAgent.indexOf('FireFox')) {
		if (clipboard_copy_ff(element.value)) {
			return true;
		}
	}
	if (d.createElement) {
		var id = 'flashcopier';
		if (!d.getElementById(id)) {
			var div = d.createElement('div');
			div.id = id;
			d.body.appendChild(div);
		}
		var e = d.getElementById(id);
		e.innerHTML = '';
		var divinfo = 
			'<embed src="' 
			+ '/clipboard/_clipboard.swf" flashvars="clipboard=' 
			+ encodeURIComponent(element.value) + 
			'" width="0" height="0" type="application/x-shockwave-flash"></embed>';
		e.innerHTML = divinfo;
		return true;
	}
	return false;
}

function clipboard_copy_ff(text2copy)
{
	netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
	var Cc = Components.classes;
	var Ci = Components.interfaces;
	var Cstr = Cc["@mozilla.org/supports-string;1"];
	var CTra = Cc["@mozilla.org/widget/transferable;1"];
	var CClp = Cc["@mozilla.org/widget/clipboard;1"];
	var str = Cstr.createInstance(Ci.nsISupportsString);
	if (!str) return false; str.data = text2copy;
	var trans = CTra.createInstance(Ci.nsITransferable);
	if (!trans) return false;
	trans.addDataFlavor(”text/unicode”);
	trans.setTransferData(”text/unicode”, str, text2copy.length * 2);
	var clipboard = CClp.getService(Ci.nsIClipboard);
	if (!clipboard) return false;
	clipboard.setData(trans, null, Ci.nsIClipboard.kGlobalClipboard);
	return true;
}


*/
