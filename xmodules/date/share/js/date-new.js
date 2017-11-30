var	gMonthNames =
[	"January", "February", "March", "April",
"May", "June", "July", "August",
"September", "October", "November", "December"
];
var	gDayNames =
[	"Sun", "Mon", "Tue", "Wed",
"Thu", "Fri", "Sat"
];

var	gAMPM =
[	"AM", "PM"
];

var	gTwelves =
[	"Midnight", "Noon"
];

var gCalendarWidget = false;
var gTimeWidget = false;
var gUSDate = false;

/***************************************************************************\
Generic Functions
\***************************************************************************/
function DateTime_ParseInt(value, def)
{
	if (value == '0')
	return 0;
	while (value.length > 1 && value.substring(0,1) == '0') {
		value = value.substring(1);
	}
	value = parseInt(value);
	if (value == NaN)
	return def;
	return value;
}

function DateTime_HourName(value, fill)
{
	if (value == 0)
	return gTwelves[0];
	if (value == 12)
	return gTwelves[1];
	var ampm = parseInt(value / 12) % 2;
	value = value % 12;
	return DateTime_FormatInt(value, 2, fill) + " " + gAMPM[ampm];
}

function DateTime_FormatInt(value, digits)
{
	value = parseInt(value);
	if (digits <= 0)
	return "" + value;
	var prefix = "";
	var fill = (arguments.length > 2) ? arguments[2] : '0';
	var order = 1;
	var dig = digits;
	while (dig-- != 0) {
		if (value < order)
		prefix += fill;
		order = order * 10;
	}
	value = (value == 0) ? "" : value % order;
	return prefix + value;
}

function DateTime_Parse(action, value)
{
	var items;
	var arr;
	var value;
	var d = new Date();
	var i = 1;
	var us_date = (arguments.length > 2) ? arguments[2] : gUSDate

	switch (action) {
		case "date":
		{
			if (us_date) {
				items = /([0-9]{2})\/([0-9]{2})\/([0-9]{4})/;
			} else {
				items = /([0-9]{4})-([0-9]{2})-([0-9]{2})/;
			}
			break;
		}
		case "time":
		{
			items = /([0-9]{2}):([0-9]{2}):([0-9]{2})/;
			break;
		}
		default:
		{
			items = /([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/;
			break;
		}
	}

	arr = items.exec(value);
	if (arr == null)
	return d;
	if (action != "time") {
		if (us_date) {
			if (arr.length > 3) {
				d.setFullYear(DateTime_ParseInt(arr[3], d.getFullYear()));
			}
			if (arr.length > 1) {
				d.setDate(1);
				d.setMonth(DateTime_ParseInt(arr[1], 1)-1);
			}
			if (arr.length > 2) {
				d.setDate(DateTime_ParseInt(arr[2], 1));
			}
		} else {
			if (arr.length > i) {
				d.setFullYear(DateTime_ParseInt(arr[i], d.getFullYear()));
			} else {
				return d;
			}
			++i;
			if (arr.length > i) {
				d.setMonth(DateTime_ParseInt(arr[i], 1)-1);
			}
			++i;
			if (arr.length > i) {
				d.setDate(DateTime_ParseInt(arr[i], 1));
			}
		}
	}

	if (action != "date") {
		if (arr.length > i) {
			d.setHours(DateTime_ParseInt(arr[i], 0));
		}
		++i;
		if (arr.length > i) {
			d.setMinutes(DateTime_ParseInt(arr[i], 0));
		}
		++i;
		if (arr.length > i) {
			d.setSeconds(DateTime_ParseInt(arr[i], 0));
		} else {
			d.setSeconds(0);
		}
	}
	return d;
}

function DateTime_Format(action,d)
{
	var us_date = arguments.length > 2 ? arguments[2] : gUSDate;
	switch (action) {
		case "date": return DateTime_FormatDate(d,us_date);
		case "time": return DateTime_FormatTime(d);
		default: return DateTime_FormatDate(d,us_date) + " " + DateTime_FormatTime(d);
	}
}

function DateTime_FormatTime(d)
{
	result =
	"" +
	DateTime_FormatInt(d.getHours(),2) + ":" +
	DateTime_FormatInt(d.getMinutes(),2) + ":" +
	DateTime_FormatInt(d.getSeconds(),2);
	return result;
}

function DateTime_FormatDate(d)
{
	var us_date = (arguments.length > 1) ? arguments[1] : gUSDate;
	if (us_date) {
		result =
		"" +
		DateTime_FormatInt(d.getMonth()+1,2) + "/" +
		DateTime_FormatInt(d.getDate(),2) + "/" +
		DateTime_FormatInt(d.getFullYear(),4);
	} else {
		result =
		"" +
		DateTime_FormatInt(d.getFullYear(),4) + "-" +
		DateTime_FormatInt(d.getMonth()+1,2) + "-" +
		DateTime_FormatInt(d.getDate(),2);
	}
	return result;
}

function DateTime_DaysInMonth(d)
{
	var dd = new Date();
	dd.setFullYear(d.getFullYear());
	dd.setDate(1);
	dd.setMonth(d.getMonth()+1);
	dd.setHours(0);
	dd.setMinutes(0);
	dd.setSeconds(0);
	dd.setMilliseconds(0);
	dd.setTime(dd.getTime()-1);
	return dd.getDate();
}

function DateTime_AddUnits(date, num, units)
{
	var d = new Date();
	d.setTime(date.getTime());
	switch (units) {
		case "year":
		d.setFullYear(d.getFullYear()+num);
		return d;
		case "month":
		var m = d.getMonth();
		var day = d.getDate();
		d.setDate(1);
		m = m + num;
		if (m < 0) {
			m = 12000 + m;
			d.setFullYear(d.getFullYear()-parseInt((-num+11)/12));
		} else if (m > 11) {
			d.setFullYear(d.getFullYear()+parseInt((num+11)/12));
		}
		d.setMonth((m % 12));
		d.setDate(Math.min(day,DateTime_DaysInMonth(d)));
		return d;
		case "day":
		d.setTime(d.getTime() + num * 86400000);
		return d;
		default:
		return d;
	}
}

function DateTime_Quote(value)
{
	return "'" + value + "'";
}

function Calendar_LinkHTML(s,cur,lnk)
{
	var result = '<a href="javascript:void ' + Calendar_LinkJS(s,cur,false) + '">' + lnk + "</a>\n";
	return result;
}

function Calendar_TodayButtonHTML(s,lnk)
{
	return '<input type="button" value="' + lnk + '" + onclick="' + Calendar_LinkJS(s,new Date(),true) + '" class="date-popup-today" />';
}

function Calendar_LinkJS(s,cur)
{
	var done = (arguments.length > 2) ? arguments[2] : true;
	return 'Calendar_Update('
	+ DateTime_Quote(DateTime_Format(s.action,cur))
	+ ',' + (done ? 'true' : 'false')
	+ ')';
}

function DateTime_Value(s)
{
	return s.parent.forms[s.form].elements[s.element].value;
}

function Date_Dump(s,d)
{
	alert(DateTime_Format(s.action,d));
}

function DateTime_GetCurrentDate(s)
{
	return DateTime_Parse(s.action, DateTime_Value(s));
}

function DateTime_TableCell(str, width)
{
	return '<td width="'+width+'">' + str + '</td>';
}

function DateTimeWidget_Done(s)
{
	if (!s)	return false;
	s.form.elements[s.name].value = DateTime_Format(s.action, s.datetime, false);
	s.form.elements[s.displayName].value = DateTime_Format(s.action, s.datetime);
	DateTimeWidget_Close(s);
	return false;
}

function DateTimeWidget_Close(w)
{
	if (w) {
		IE_DHTML_Hack(w.form, false);
		ObjectID_VisibleHide(w.divName);
	}
}

function DateTimeWidget_ToggleEmpty(button, w, wshow, v, vshow, estring)
{
	if (w.value == '') {
		w.value = v;
		wshow.value = vshow;
		button.src = '/templates/control/datetime/date-zero.gif';
	} else {
		if (v != '')
		button.src = '/templates/control/datetime/date-undo.gif';
		w.value = '';
		wshow.value = estring;
	}
	return false;
}

/***************************************************************************\
Calendar Specific
\***************************************************************************/
function Calendar_MonthSelect(s, m)
{
	var x = "";
	x +=
	'<select onchange="Calendar_SetMonth('
	+ DateTime_Quote(DateTime_Format(s.action,s.datetime))
	+ ', Control_Value(this))">';
	for (i = 0; i < gMonthNames.length; i++) {
		x +=
		'<option value="' + i + '"' + ((m == i) ? ' selected' : '') +' >'
		+ gMonthNames[i]
		+ '</option>';
	}
	x += '</select>';
	return x;
}

function Calendar_YearSelect(s, y)
{
	var x = "";
	x +=
	'<select onchange="Calendar_SetYear('
	+ DateTime_Quote(DateTime_Format(s.action,s.datetime))
	+ ', Control_Value(this))">';
	for (i = y - 3; i < y + 3; i++) {
		x +=
		'<option value="' + i + '"' + ((y == i) ? ' selected' : '') +' >'
		+ i
		+ '</option>';
	}
	x += '</select>';
	return x;
}

function Calendar_HeaderHTML(s)
{
	var x	= "";
	var dt	= s.datetime;

	x += '<table cellspacing="0" cellpadding="2" border="0" width="210"><tr>';

	ddate = DateTime_AddUnits(dt, -1, "month");
	x += DateTime_TableCell(Calendar_LinkHTML(s,ddate,'<img src="/templates/control/pager/default/pager-prev.gif" />'), 16);
	ddate = DateTime_AddUnits(dt, 1, "month");
	x += DateTime_TableCell(Calendar_LinkHTML(s,ddate,'<img src="/templates/control/pager/default/pager-next.gif" />'), 16);

	x += '<td align="center" class="date-popup-month" width="100">' + Calendar_MonthSelect(s,dt.getMonth()) + '</td>';

	x += '<td align="center" class="date-popup-month" width="46">' + Calendar_YearSelect(s, dt.getFullYear()) + '</td>';

	ddate = DateTime_AddUnits(dt, -1, "year");
	x += DateTime_TableCell(Calendar_LinkHTML(s,ddate,'<img src="/templates/control/pager/default/pager-start.gif" />'), 16);

	ddate = DateTime_AddUnits(dt, 1, "year");
	x += DateTime_TableCell(Calendar_LinkHTML(s,ddate,'<img src="/templates/control/pager/default/pager-end.gif" />'), 16);

	x += '</tr></table>';
	return x;
}

function DateTime_CalendarRows(s)
{
	var theDate		= new Date(s.datetime);
	var weekday;
	var theMonth	= theDate.getMonth();
	var theYear		= theDate.getYear();
	var theDay		= theDate.getDate();
	var now			= new Date();
	var nowDay		= (now.getMonth() == theMonth) ? now.getDate() : 0;
	var curDay;
	var curMonth;
	var klass;
	var js;
	var	x			= "";

	theDate.setDate(1);
	weekday = theDate.getDay(theDate);
	theDate = DateTime_AddUnits(theDate, -weekday, "day");
	curMonth = 0;
	curYear = 0;
	while (curMonth <= theMonth && curYear <= theYear)
	{
		x += "<tr>";
		for (i = 0; i < 7; i++) {
			curYear		= theDate.getYear();
			curMonth	= theDate.getMonth();
			curDay		= theDate.getDate();
			if (curMonth != theMonth) {
				klass = "date-popup-other";
			} else if (curDay == theDay) {
				klass = "date-popup-selected";
			} else if (curDay == nowDay) {
				klass = "date-popup-now";
			} else {
				klass = "date-popup-day";
			}
			js = Calendar_LinkJS(s,theDate);
			theDate = DateTime_AddUnits(theDate, 1, "day");

			x += '<td class="' + klass + '" onclick=\"'+js+'\"">' + curDay + '</td>';
		}
		x += "</tr>";
	}
	return x;
}

function Calendar_HTML(s)
{
	var ddate;

	var x = "";
	x += '<table cellspacing="1" width="210" cellpadding="2" border="0" class="date-popup-table">';
	x += '<tr><td class="date-popup-buttons" colspan="7">';
	x += Calendar_TodayButtonHTML(s,"Today", "date-popup-today");
	x += '<input type="button" value="Cancel" class="date-popup-cancel" onclick="return Calendar_Close();">';
	//			x += '<input type="button" value="Done" class="date-popup-done" onclick="return Calendar_Done()">';
	x += '</td></tr>';
	x += '<tr><td colspan="7" class="date-popup-month">' + Calendar_HeaderHTML(s) + '</td></tr>';
	x +=
	'<tr class="date-popup-weekday">' +
	'<td width="30">Sun</td>' +
	'<td width="30">Mon</td>' +
	'<td width="30">Tue</td>' +
	'<td width="30">Wed</td>' +
	'<td width="30">Thu</td>' +
	'<td width="30">Fri</td>' +
	'<td width="30">Sat</td>' +
	'</tr>'
	x += DateTime_CalendarRows(s);
	x += '</table>';
	return x;
}

function DateTime_Popup(form, name)
{
}

function CalendarWidget(form, name)
{
	var v 				= form.elements[name].value;

	this.form			= form;
	this.document		= form.document;
	this.action			= 'date';
	this.name			= name;
	this.divName		= 'CalendarDiv_' + name;
	this.displayName	= 'Show_' + name;
	this.datetime		= new Date();
}

// TODO make HTML prototype and factor out update to DateTimeWidget_Update
function _Calendar_Update(s, done)
{
	Frame_WriteLayer(self, s.divName, Calendar_HTML(s));
	ObjectID_VisibleShow(s.divName);
	if (done) return Calendar_Done();
	return false;
}

function Calendar_Update(newValue)
{
	var s = gCalendarWidget;
	if (!s) return false;
	var done = (arguments.length > 1) ? arguments[1] : false;
	s.datetime = DateTime_Parse(s.action, newValue,false);
	return _Calendar_Update(s, done);
}

function Calendar_SetMonth(newValue, m)
{
	var s = gCalendarWidget;
	var d = s.datetime.getDate();
	if (!s)
	return false;
	var done = (arguments.length > 2) ? arguments[2] : false;
	s.datetime = DateTime_Parse(s.action, newValue,false);
	s.datetime.setDate(1);
	s.datetime.setMonth(m);
	var maxd = DateTime_DaysInMonth(s.datetime);
	s.datetime.setDate(Math.min(d,maxd));
	return _Calendar_Update(s, done);
}

function Calendar_SetYear(newValue, y)
{
	var s = gCalendarWidget;
	if (!s)
	return false;
	var done = (arguments.length > 2) ? arguments[2] : false;
	s.datetime = DateTime_Parse(s.action, newValue,false);
	s.datetime.setYear(y);
	return _Calendar_Update(s, done);
}

function Calendar_Done()
{
	var s = gCalendarWidget;
	DateTimeWidget_Done(s);
	gCalendarWidget = false;
	return false;
}

function Calendar_Close()
{
	var s = gCalendarWidget;
	DateTimeWidget_Close(s);
	gCalendarWidget = false;
	return false;
}

/***************************************************************************\
Date PUBLIC
\***************************************************************************/
function Date_Popup(form, name)
{
	var			v 		= form.elements[name].value;
	var			s 		= new CalendarWidget(form, name);

	gUSDate = (arguments.length > 2) ? arguments[2] : false;
	DateTime_CloseAll();

	gCalendarWidget = s;

	IE_DHTML_Hack(form, true);
	Calendar_Update(v);

	return false;
}

/***************************************************************************\
Time related
\***************************************************************************/
function TimeWidget_SetHour(w)
{
	var s = gTimeWidget;
	if (!s)
	return;
	s.datetime.setHours(parseInt(Control_Value(w,0)));
}

function TimeWidget_SetMinute(w,tens)
{
	var s = gTimeWidget;
	if (!s)
	return;
	var m = s.datetime.getMinutes();
	if (tens) {
		m = m % 10;
	} else {
		m = parseInt(m / 10) * 10;
	}
	var v = parseInt(Control_Value(w,0));
	m = m + v;
	s.datetime.setMinutes(m);
	s.datetime.setSeconds(0);
}

function TimeWidget_LinkJS(s,cur)
{
	var done = (arguments.length > 2) ? arguments[2] : true;
	return 'TimeWidget_Update('
	+ DateTime_Quote(DateTime_Format(s.action,cur))
	+ ',' + (done ? 'true' : 'false')
	+ ')';
}

function TimeWidget_NowButtonHTML(s,lnk)
{
	return '<input type="button" value="' + lnk + '" + onclick="' + TimeWidget_LinkJS(s,new Date(),true) + '" class="date-popup-today" />';
}


function TimeWidget_HTML(s)
{
	var d = s.datetime;

	var hh = d.getHours();
	var m0 = parseInt(d.getMinutes() / 10) * 10;
	var mm = d.getMinutes() % 10;

	var x = "";
	x += '<table cellspacing="1" cellpadding="2" border="0" class="date-popup-table">';
	x += '<tr><td class="date-popup-buttons" colspan="3">';
	x += TimeWidget_NowButtonHTML(s, "Now", "date-popup-today");
	x += '<input type="button" value="Done" class="date-popup-done" onclick="return TimeWidget_Done()">';
	x += '<input type="button" value="Cancel" class="date-popup-cancel" onclick="return TimeWidget_Close();">';
	x += '</td></tr>';
	x +=
	'<tr class="date-popup-month">' +
	'<td>Hour</td><td colspan="2">Minute</td>' +
	'</tr>';
	x +=
	'<tr class="time-popup-controls">' +
	'<td>';
	x += '<select onchange="TimeWidget_SetHour(this)" size="11">';
	for (i = 0; i < 24; i++) { x += '<option value="' + i + '"' + ((i == hh) ? ' selected' : '') + '>' + DateTime_HourName(i, '&nbsp;') + '</option>'; }
	x += '</select>';
	x += '</td>';
	x += '<td>';
	x += '<select onchange="TimeWidget_SetMinute(this, true)" scrollbars="no" size="7">';
	for (i = 0; i < 60; i += 10) { x += '<option value="' + i + '"' + ((i == m0) ? ' selected' : '') + '>:' + DateTime_FormatInt(i, 2) + '</option>'; }
	x += '</select>';
	x += '</td>';
	x += '<td>';
	x += '<select onchange="TimeWidget_SetMinute(this, false)" size="11">';
	for (i = 0; i < 10; i++) { x += '<option value="' + i + '"' + ((i == mm) ? ' selected' : '') + '>' + i + '</option>'; }
	x += '</select>';
	x += '</td>';
	'</tr>'
	x += '</table>';
	return x;
}

function TimeWidget(form, name)
{
	var v 				= form.elements[name].value;

	this.form			= form;
	this.document		= form.document;
	this.name			= name;
	this.divName		= 'TimeDiv_' + name;
	this.displayName	= 'TimeShow_' + name;
	this.datetime		= new Date();
	this.action			= 'time';
}

function TimeWidget_Update(newValue)
{
	var s = gTimeWidget;
	if (!s)
	return false;
	var done = (arguments.length > 1) ? arguments[1] : false;
	s.datetime = DateTime_Parse(s.action, newValue,false);
	Frame_WriteLayer(self, s.divName, TimeWidget_HTML(s));
	ObjectID_VisibleShow(s.divName);
	if (done)
	return TimeWidget_Done();
	return false;
}

function TimeWidget_Done()
{
	var s = gTimeWidget;
	DateTimeWidget_Done(s);
	gTimeWidget = false;
	return false;
}

function TimeWidget_Close()
{
	var s = gTimeWidget;
	DateTimeWidget_Close(s);
	gTimeWidget = false;
	return false;
}


function Time_Popup(form, name)
{
	var			v = form.elements[name].value;
	var			s = new TimeWidget(form, name);

	DateTime_CloseAll();

	gTimeWidget = s;

	IE_DHTML_Hack(form, true);
	TimeWidget_Update(v);

	return false;
}

/***************************************************************************\
Both related
\***************************************************************************/
function DateTime_CloseAll()
{
	if (gTimeWidget)
	TimeWidget_Close();
	if (gCalendarWidget)
	Calendar_Close();
}
