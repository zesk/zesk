/*
	Add this to your page
	
		<div id="HoverTip" style="position:absolute;visibility:hidden;z-index:1000;">&nbsp;</div>
*/
/*
	Requires templates/control/object.js
 */


function HoverTip_ParseBoolean(value)
{
	value = '' + value.toLowerCase();
	switch (value.substring(0,1)) {
		case 'y': case '1': case 't':
			return true;
		case 'n': case '0': case 'f':
			return false;
		default:
			return _ud;
	}
}

/*
	ARG Tools
 */
function HoverTip_ParseArgs(t, args)
{
	if ((typeof t._args == typeof args) && t._args == args)
		return false;
	t._args = args;
	HoverTip_SetupArgFields(t);
	if (args == '')
		return true;
	var a = args.split(";");
	var  n = a.length;
	var tok, vType, name, value;
	if (n == 0)
		return true;
	for (var i = 0; i < n; i++) {
		tok = a[i].split("=",2);
		name = tok[0].toLowerCase();
		if (name.length == 0)
			continue;
		if (name.substring(0,1) == "_")
			continue;
		vType = eval("typeof t."+name);
		if (vType == _ud || (tok.length != 2))
			continue;
		value = tok[1];
		switch (vType) {
			case "string":
				value = '' + value;
				break;
			case "number":
				value = parseInt(value);
				if (isNaN(value)) {
					value = _ud;
				}
				break;
			case "boolean":
				if (typeof value == "numeric") {
					value = value ? true : false;
				} else {
					value = HoverTip_ParseBoolean(value);
				}
				break;
			default:
				value = _ud;
				break;
		}
		if (value != _ud) {
			eval("t." + name + "= value;");
		} else {
			alert("arg error " + name + "=" + tok[1]);
		}
	}
	// Handle dependencies here
	if (t.pinup) {
		t.follow = false;
	}
	return true;
}

function HoverTip_ObjectArg(t, obj)
{
	t._object = obj;
	if (t.rel) {
//		HoverTip_Debug(typeof t._object);
		if (typeof t._object == 'object') {
			t.x = Object_Left(t._object);
			t.y = Object_Top(t._object);
//			HoverTip_Debug(t.x + " " + t.y);
		} else {
			t.x = false;
			t.y = false;
		}
	}
}

function HoverTip_HTML(t) {
	var r = "";
	
	r += HTML_Tag("table", "class", t.tableclass, "width", t.width, "height", t.height, "border", 0, "cellspacing", 0, "cellpadding", 0, "bgcolor", t.bordercolor);
	r += "<tr><td>";
		r += HTML_Tag("table", "width", "100%", "border", 0, "cellspacing", t.borderwidth, "cellpadding", 0);
		if (t.title != '') {
			r += "<tr>";
			if (t.iconsrc != '') {
				r += "<td>";
				r += HTML_Tag("table", "width", "100%", "border", 0, "cellspacing", 0, "bgcolor", t.bodycolor);
				r += HTML_Tag("td", "width", "1%", "valign", "middle", "align", "center", "bgcolor", t.bodycolor, "style", "padding: " + t.padding + t.paddingunit);
				r += HTML_SingleTag("img", "src", t.iconsrc, "width", t.iconwidth, "height", t.iconheight, "border", 0);
				r += HTML_EndTag("td");
			}
			r += HTML_Tag("td", "valign", t.titlevalign, "align", t.titlealign, "bgcolor", t.bodycolor, "style", "padding: " + t.padding + t.paddingunit, "class", t.titleclass);
			r += t.title;
			r += HTML_EndTag("td");
			r += "</tr>";
			if (t.iconsrc != '') {
				r += HTML_EndTag("table");
				r += "</td>";
			}
		}
		r += "<tr>";
		r += HTML_Tag("td", "align", t.align, "valign", t.valign, "style", "padding: " + t.padding + t.paddingunit, "bgcolor", t.bodycolor);
		r += t._message;
		r += HTML_EndTag("td");
		r += "</tr>";
		r += HTML_EndTag("table");
	r += "</td></tr>";
	r += HTML_EndTag("table");
	Object_SetBackground(Object_GetStyle(t.objectid), "");
	return r;
}

function HoverTip_Status(t, text){
	/*
	if (t._enabled){
		if (t.follow) {
			HoverTip_MoveTo(t);
			t.follow = true;
		}
	}
	if (text !="") {
		self.status = text;
	}
	*/
}

/*
	a		action
	x		x position
	dx		x offset
	xw		x width
	x0		window x position
	x0w		window x width
	xsnap	snap to grid of x size
	
	This is called for both X alignment and Y alignment.
 */
function HoverTip_Align(a,x,dx,xw,x0,x0w,xsnap)
{

	var isL = false;
	var ox = x;
	var kwtest = '0:xw='+xw;

	switch (a) {
		case "left":
		case "top":
			x = x - dx - xw;
			if (x < x0)
				x = x0;
			isL = true;
			break;
		case "right":
		case "bottom":
			x = x + dx;
			if ((x + xw) > (x0 + x0w)){
				x = x0 + x0w - xw;
			}
			if (x < 0)
				x = 0;
			break;
		case "center":
			x = x + dx - (xw / 2);
			if (x < minx)
				x = minx;
			break;
		default:
			var v = ((x - x0) > (x0w / 2)) ? "left" : "right";
			kwtest += ', feedback';
			var res = HoverTip_Align(v,x,dx,xw,x0,x0w,xsnap);
			return res;
	}
	kwtest += ', 1:xw='+xw;
	if (xsnap > 1){
		var snap = x % xsnap;
		if (isL){
			x = x - xsnap - snap;
		} else{
			x = x + xsnap - snap;
		}
		if (x < x0)
			x = x0;
	}
	// xw is a problem // --- KW
	HoverTip_Debug(x+"="+a+",x="+ox+",dx="+dx+",xw="+xw+",x0="+x0+",x0w="+x0w+",xsnap="+xsnap+', KW:'+kwtest)
	return x + x0;
}

function HoverTip_MoveTo(t){
	var x = t.x;
	var y = t.y;
	var testing = ''; // --- KW
	if (t.xfix > -1){
		x	= t.xfix;
		testing += 'A,';
	} else{
		testing += 'B,';
		wx	= Frame_GetX(t._frame);
		wxw	= Frame_GetWidth(t._frame);
		x	= HoverTip_Align(t.xalign, t.x, t.xoff, t.width, wx, wxw, t.xsnap);
	}
	if (t.yfix > -1){
		testing += 'C,';
		y	= t.yfix;
	} else{
		testing += 'D,';
		wy	= Frame_GetY(t._frame);
		wyw	= Frame_GetHeight(t._frame);
		y	= HoverTip_Align(t.yalign, t.y, t.yoff, (t.height-10), wy, wyw, t.ysnap);
	}
//	HoverTip_Debug(":MoveTo: frame:"+wx+","+wy+"("+wxw+"x"+wyw+") pos=" + x + ','+ y + ', KW:' + testing);
	ObjectID_MoveTo(t.objectid, x, y);
}

function HoverTip_Debug(message)
{
	var i;
	var m = new Array();
	var x;
	
	for (i = 0; i < 5; i++) {
		x = Object_Get("HoverDebug" + i);
		if (x) {
			m.push(x);
		}
	}
	if (m.length == 0) {
		return;
	}
	i = m.length;
	while (i-- > 1) {
		m[i].value = m[i-1].value;
	}

	m[0].value = message;
}

function HoverTip_MouseMove(e)
{
	var t = gHoverTip;
	if (!t._enabled)
		return;
	t._mx = Event_GetX(e); // --- KW
	t._my = Event_GetY(e); // --- KW
	if (t.follow && t._isOn && !t._isPinned) {
//		if (t._mx != 0 && t._my != 0) {
			t.x = t._mx;
			t.y = t._my;
			HoverTip_MoveTo(t);
//		}
	}
}
function HoverTip_Close() 
{
	var t = gHoverTip;
	if (!gHoverTip)
		return;
	if (!t._isOn)
		return;
//	HoverTip_Debug("Close");
	ObjectID_VisibleHide(t.objectid);
	t._isOn = false;
	t._isDrawn = false;
	return false;
}

function HoverTip_Go() {
	var t		= gHoverTip;
	var html;
	
	if (!t._isOn) {
		return false;
	}
	html	= HoverTip_HTML(t);
	if (t.pinup){
		HoverTip_ClearTimeout();
		t._isPinned = true;
	}
	if (t.status == true){
		status_text = t._message;
	} else if (typeof t.status == "string") {
		status_text = t.status;
	} else {
		status_text = "";
	}
	if (!t._isDrawn) {
		t.x = t._mx;
		t.y = t._my;
		ObjectID_DisplayHide(t.objectid);
		Frame_WriteLayer(t._frame, t.objectid, html);
		HoverTip_Status(t,status_text);
	}
	if (t.follow) {
		t.x = t._mx;
		t.y = t._my;
	}
//	HoverTip_Debug("Go");
	HoverTip_MoveTo(t);
	if (!t._isDrawn) {
		ObjectID_DisplayShow(t.objectid);
		ObjectID_VisibleShow(t.objectid);
		t._isDrawn = true;
	}
//	t.follow	= true;
	if (t.timeout > 0) {
		HoverTip_SetTimeout("hide", "HoverTip_Hide()", t.timeout);
	}
	return (status_text != '');
}

function HoverTip_ClearTimeout()
{
	var t = gHoverTip;
	var a = arguments;
	if (!t._timer) {
		return;
	}
	if ((a.length > 0) && (t._timerMethod != a[0])) {
		return false;
	}
	clearTimeout(t._timer);
	t._timer = false;
	t._timerMethod = "";
	return true;
}

function HoverTip_SetTimeout(name, method, nSeconds)
{
	var t = gHoverTip;

	HoverTip_ClearTimeout();
	t._timer = setTimeout(method, nSeconds);
	t._timerMethod = name;
}

function HoverTip_Show(text)
{
	var t = gHoverTip;
	var a = arguments;
	var changed = false;
	
	a.dx = 100; 
	if (a.length > 1) {
		changed = HoverTip_ParseArgs(t, a[1]);
	} else {
		changed = HoverTip_ParseArgs(t, '');
	}
	if (a.length > 2) {
		HoverTip_ObjectArg(t, a[2]);
	}
	if (text == false) {
		text = ObjectID_Contents(t.objectid);
	}
	if (changed) {
		HoverTip_Debug("CHANGED");
		HoverTip_Close();
	} else if (t._isOn && t._isDrawn && t._message == text) {
		return;
	}
	t._message = text;
	HoverTip_Debug("Show " + changed + " " + t._isOn + " " + t._isDrawn + " " + (t._message == text));
	t._isOn		= true;
	t._isDrawn	= false;
	HoverTip_ClearTimeout('hide');
	if (t.showdelay == 0) {
		return HoverTip_Go();
	} else if (t._timer == false) {
		HoverTip_SetTimeout("show", "HoverTip_Go()", t.showdelay);
		return false;
	}
}

function HoverTip_Hide(){
	var t = gHoverTip;
//	HoverTip_Debug("Hide");
	if (!t._enabled)
		return true;
	if (t._isOn) {
		if (t.hidedelay == 0) {
			HoverTip_Close();
		} else {
			HoverTip_SetTimeout("hide", "HoverTip_Close()", t.hidedelay);
			return false;
		}
	}
	return true;
}

function HoverTip_SetupArgFields(t)
{
	t.rel			= false;		// Relative to object

	t.x				= 0;
	t.width			= 200;
	t.xoff			= 15;
	t.xalign		= "default";
	t.xsnap			= 0;
	t.xfix			= -1;
	
	t.y				= 0;
	t.height		= 0;
	t.yoff			= 15;
	t.yalign		= "default";
	t.ysnap			= 0;
	t.yfix			= -1;
	
	t.showdelay		= 0;
	t.hidedelay		= 3;
	t.status		= '';
	t.timeout		= 0;
	
	t.align			= "left";
	t.valign		= "middle";
	
	t.bodycolor		= "white";
	t.bordercolor	= "black";

	t.padding		= 3;
	t.paddingunit	= "px";
	t.borderwidth	= 1;
	
	t.iconsrc		= '';
	t.iconwidth		= _udi;
	t.iconheight	= _udi;
	
	t.tableclass	= 'hovertip';
	t.title			= '';
	t.titleclass	= 'hovertip-title';
	t.titlealign	= "left";
	t.titlevalign	= '';
	
	t.pinup			= false;
	t.follow		= true;
	
	t.objectid		= "HoverTip";
}

function HoverTip()
{	
	this._message		= '';
	this._args			= false;
	this._enabled		= false;
	
	this._frame			= self;
	this._isOn			= false;
	this._isDrawn		= false;
	this._timer			= false;
	this._timerMethod	= "";
	this._isPinned		= false;

	this._object		= false;
	
	this._mx			= 0;
	this._my			= 0;
	
	if (arguments.length > 0) {
		HoverTip_ParseArgs(this, arguments[0]);
	} else {
		HoverTip_ParseArgs(this, '');
	}
}

if (typeof gHoverArgs == _ud) {
	var gHoverArgs = "";
}
var gHoverTip = new HoverTip(gHoverArgs);

gHoverTip._enabled = Document_OnMouseMove(HoverTip_MouseMove);

