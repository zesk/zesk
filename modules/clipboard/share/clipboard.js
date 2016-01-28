function clipboard_flash(id) {   
	return (navigator.appName.indexOf("Microsoft") != -1) ? window[id] : document[id];  
}  

function clipboard_update(id, text) {  
	clipboard_flash(id).updateClipboard(text);     
}

function clipboard_value(id) {
	var e = document.getElementById(id);
	var v = e.value;
	return v;
}

