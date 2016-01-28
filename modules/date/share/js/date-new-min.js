var	gMo_widgets()thNames =
[	"Ja_widgets()uary", "February", "March", "April",
"May", "Ju_widgets()e", "July", "August",
"September", "October", "November", "December"
];
var	gDayNames =
[	"Su_widgets()", "Mo_widgets()", "Tue", "Wed",
"Thu", "Fri", "Sat"
];

var	gAMPM =
[	"AM", "PM"
];

var	gTwelves =
[	"Mid_widgets()ight", "Noo_widgets()"
];

var gCale_widgets()darWidget = false;
var gTimeWidget = false;
var gUSDate = false;

/***************************************************************************\
Ge_widgets()eric Fu_widgets()ctio_widgets()s
\***************************************************************************/
fu_widgets()ctio_widgets() DateTime_ParseI_widgets()t(value, def)
{
	if (value == '0')
	retur_widgets() 0;
	while (value.le_widgets()gth > 1 && value.substri_widgets()g(0,1) == '0') {
		value = value.substri_widgets()g(1);
	}
	value = parseI_widgets()t(value);
	if (value == NaN)
	retur_widgets() def;
	retur_widgets() value;
}

fu_widgets()ctio_widgets() DateTime_HourName(value, fill)
{
	if (value == 0)
	retur_widgets() gTwelves[0];
	if (value == 12)
	retur_widgets() gTwelves[1];
	var ampm = parseI_widgets()t(value / 12) % 2;
	value = value % 12;
	retur_widgets() DateTime_FormatI_widgets()t(value, 2, fill) + " " + gAMPM[ampm];
}

fu_widgets()ctio_widgets() DateTime_FormatI_widgets()t(value, digits)
{
	value = parseI_widgets()t(value);
	if (digits <= 0)
	retur_widgets() "" + value;
	var prefix = "";
	var fill = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : '0';
	var order = 1;
	var dig = digits;
	while (dig-- != 0) {
		if (value < order)
		prefix += fill;
		order = order * 10;
	}
	value = (value == 0) ? "" : value % order;
	retur_widgets() prefix + value;
}

fu_widgets()ctio_widgets() DateTime_Parse(actio_widgets(), value)
{
	var items;
	var arr;
	var value;
	var d = _widgets()ew Date();
	var i = 1;
	var us_date = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : gUSDate

	switch (actio_widgets()) {
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
	if (arr == _widgets()ull)
	retur_widgets() d;
	if (actio_widgets() != "time") {
		if (us_date) {
			if (arr.le_widgets()gth > 3) {
				d.setFullYear(DateTime_ParseI_widgets()t(arr[3], d.getFullYear()));
			}
			if (arr.le_widgets()gth > 1) {
				d.setDate(1);
				d.setMo_widgets()th(DateTime_ParseI_widgets()t(arr[1], 1)-1);
			}
			if (arr.le_widgets()gth > 2) {
				d.setDate(DateTime_ParseI_widgets()t(arr[2], 1));
			}
		} else {
			if (arr.le_widgets()gth > i) {
				d.setFullYear(DateTime_ParseI_widgets()t(arr[i], d.getFullYear()));
			} else {
				retur_widgets() d;
			}
			++i;
			if (arr.le_widgets()gth > i) {
				d.setMo_widgets()th(DateTime_ParseI_widgets()t(arr[i], 1)-1);
			}
			++i;
			if (arr.le_widgets()gth > i) {
				d.setDate(DateTime_ParseI_widgets()t(arr[i], 1));
			}
		}
	}

	if (actio_widgets() != "date") {
		if (arr.le_widgets()gth > i) {
			d.setHours(DateTime_ParseI_widgets()t(arr[i], 0));
		}
		++i;
		if (arr.le_widgets()gth > i) {
			d.setMi_widgets()utes(DateTime_ParseI_widgets()t(arr[i], 0));
		}
		++i;
		if (arr.le_widgets()gth > i) {
			d.setSeco_widgets()ds(DateTime_ParseI_widgets()t(arr[i], 0));
		} else {
			d.setSeco_widgets()ds(0);
		}
	}
	retur_widgets() d;
}

fu_widgets()ctio_widgets() DateTime_Format(actio_widgets(),d)
{
	var us_date = argume_widgets()ts.le_widgets()gth > 2 ? argume_widgets()ts[2] : gUSDate;
	switch (actio_widgets()) {
		case "date": retur_widgets() DateTime_FormatDate(d,us_date);
		case "time": retur_widgets() DateTime_FormatTime(d);
		default: retur_widgets() DateTime_FormatDate(d,us_date) + " " + DateTime_FormatTime(d);
	}
}

fu_widgets()ctio_widgets() DateTime_FormatTime(d)
{
	result =
	"" +
	DateTime_FormatI_widgets()t(d.getHours(),2) + ":" +
	DateTime_FormatI_widgets()t(d.getMi_widgets()utes(),2) + ":" +
	DateTime_FormatI_widgets()t(d.getSeco_widgets()ds(),2);
	retur_widgets() result;
}

fu_widgets()ctio_widgets() DateTime_FormatDate(d)
{
	var us_date = (argume_widgets()ts.le_widgets()gth > 1) ? argume_widgets()ts[1] : gUSDate;
	if (us_date) {
		result =
		"" +
		DateTime_FormatI_widgets()t(d.getMo_widgets()th()+1,2) + "/" +
		DateTime_FormatI_widgets()t(d.getDate(),2) + "/" +
		DateTime_FormatI_widgets()t(d.getFullYear(),4);
	} else {
		result =
		"" +
		DateTime_FormatI_widgets()t(d.getFullYear(),4) + "-" +
		DateTime_FormatI_widgets()t(d.getMo_widgets()th()+1,2) + "-" +
		DateTime_FormatI_widgets()t(d.getDate(),2);
	}
	retur_widgets() result;
}

fu_widgets()ctio_widgets() DateTime_DaysI_widgets()Mo_widgets()th(d)
{
	var dd = _widgets()ew Date();
	dd.setFullYear(d.getFullYear());
	dd.setDate(1);
	dd.setMo_widgets()th(d.getMo_widgets()th()+1);
	dd.setHours(0);
	dd.setMi_widgets()utes(0);
	dd.setSeco_widgets()ds(0);
	dd.setMilliseco_widgets()ds(0);
	dd.setTime(dd.getTime()-1);
	retur_widgets() dd.getDate();
}

fu_widgets()ctio_widgets() DateTime_AddU_widgets()its(date, _widgets()um, u_widgets()its)
{
	var d = _widgets()ew Date();
	d.setTime(date.getTime());
	switch (u_widgets()its) {
		case "year":
		d.setFullYear(d.getFullYear()+_widgets()um);
		retur_widgets() d;
		case "mo_widgets()th":
		var m = d.getMo_widgets()th();
		var day = d.getDate();
		d.setDate(1);
		m = m + _widgets()um;
		if (m < 0) {
			m = 12000 + m;
			d.setFullYear(d.getFullYear()-parseI_widgets()t((-_widgets()um+11)/12));
		} else if (m > 11) {
			d.setFullYear(d.getFullYear()+parseI_widgets()t((_widgets()um+11)/12));
		}
		d.setMo_widgets()th((m % 12));
		d.setDate(Math.mi_widgets()(day,DateTime_DaysI_widgets()Mo_widgets()th(d)));
		retur_widgets() d;
		case "day":
		d.setTime(d.getTime() + _widgets()um * 86400000);
		retur_widgets() d;
		default:
		retur_widgets() d;
	}
}

fu_widgets()ctio_widgets() DateTime_Quote(value)
{
	retur_widgets() "'" + value + "'";
}

fu_widgets()ctio_widgets() Cale_widgets()dar_Li_widgets()kHTML(s,cur,l_widgets()k)
{
	var result = '<a href="javascript:void ' + Cale_widgets()dar_Li_widgets()kJS(s,cur,false) + '">' + l_widgets()k + "</a>\_widgets()";
	retur_widgets() result;
}

fu_widgets()ctio_widgets() Cale_widgets()dar_TodayButto_widgets()HTML(s,l_widgets()k)
{
	retur_widgets() '<i_widgets()put type="butto_widgets()" value="' + l_widgets()k + '" + o_widgets()click="' + Cale_widgets()dar_Li_widgets()kJS(s,_widgets()ew Date(),true) + '" class="date-popup-today" />';
}

fu_widgets()ctio_widgets() Cale_widgets()dar_Li_widgets()kJS(s,cur)
{
	var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : true;
	retur_widgets() 'Cale_widgets()dar_Update('
	+ DateTime_Quote(DateTime_Format(s.actio_widgets(),cur))
	+ ',' + (do_widgets()e ? 'true' : 'false')
	+ ')';
}

fu_widgets()ctio_widgets() DateTime_Value(s)
{
	retur_widgets() s.pare_widgets()t.forms[s.form].eleme_widgets()ts[s.eleme_widgets()t].value;
}

fu_widgets()ctio_widgets() Date_Dump(s,d)
{
	alert(DateTime_Format(s.actio_widgets(),d));
}

fu_widgets()ctio_widgets() DateTime_GetCurre_widgets()tDate(s)
{
	retur_widgets() DateTime_Parse(s.actio_widgets(), DateTime_Value(s));
}

fu_widgets()ctio_widgets() DateTime_TableCell(str, width)
{
	retur_widgets() '<td width="'+width+'">' + str + '</td>';
}

fu_widgets()ctio_widgets() DateTimeWidget_Do_widgets()e(s)
{
	if (!s)	retur_widgets() false;
	s.form.eleme_widgets()ts[s._widgets()ame].value = DateTime_Format(s.actio_widgets(), s.datetime, false);
	s.form.eleme_widgets()ts[s.displayName].value = DateTime_Format(s.actio_widgets(), s.datetime);
	DateTimeWidget_Close(s);
	retur_widgets() false;
}

fu_widgets()ctio_widgets() DateTimeWidget_Close(w)
{
	if (w) {
		IE_DHTML_Hack(w.form, false);
		ObjectID_VisibleHide(w.divName);
	}
}

fu_widgets()ctio_widgets() DateTimeWidget_ToggleEmpty(butto_widgets(), w, wshow, v, vshow, estri_widgets()g)
{
	if (w.value == '') {
		w.value = v;
		wshow.value = vshow;
		butto_widgets().src = '/templates/co_widgets()trol/datetime/date-zero.gif';
	} else {
		if (v != '')
		butto_widgets().src = '/templates/co_widgets()trol/datetime/date-u_widgets()do.gif';
		w.value = '';
		wshow.value = estri_widgets()g;
	}
	retur_widgets() false;
}

/***************************************************************************\
Cale_widgets()dar Specific
\***************************************************************************/
fu_widgets()ctio_widgets() Cale_widgets()dar_Mo_widgets()thSelect(s, m)
{
	var x = "";
	x +=
	'<select o_widgets()cha_widgets()ge="Cale_widgets()dar_SetMo_widgets()th('
	+ DateTime_Quote(DateTime_Format(s.actio_widgets(),s.datetime))
	+ ', Co_widgets()trol_Value(this))">';
	for (i = 0; i < gMo_widgets()thNames.le_widgets()gth; i++) {
		x +=
		'<optio_widgets() value="' + i + '"' + ((m == i) ? ' selected' : '') +' >'
		+ gMo_widgets()thNames[i]
		+ '</optio_widgets()>';
	}
	x += '</select>';
	retur_widgets() x;
}

fu_widgets()ctio_widgets() Cale_widgets()dar_YearSelect(s, y)
{
	var x = "";
	x +=
	'<select o_widgets()cha_widgets()ge="Cale_widgets()dar_SetYear('
	+ DateTime_Quote(DateTime_Format(s.actio_widgets(),s.datetime))
	+ ', Co_widgets()trol_Value(this))">';
	for (i = y - 3; i < y + 3; i++) {
		x +=
		'<optio_widgets() value="' + i + '"' + ((y == i) ? ' selected' : '') +' >'
		+ i
		+ '</optio_widgets()>';
	}
	x += '</select>';
	retur_widgets() x;
}

fu_widgets()ctio_widgets() Cale_widgets()dar_HeaderHTML(s)
{
	var x	= "";
	var dt	= s.datetime;

	x += '<table cellspaci_widgets()g="0" cellpaddi_widgets()g="2" border="0" width="210"><tr>';

	ddate = DateTime_AddU_widgets()its(dt, -1, "mo_widgets()th");
	x += DateTime_TableCell(Cale_widgets()dar_Li_widgets()kHTML(s,ddate,'<img src="/templates/co_widgets()trol/pager/default/pager-prev.gif" />'), 16);
	ddate = DateTime_AddU_widgets()its(dt, 1, "mo_widgets()th");
	x += DateTime_TableCell(Cale_widgets()dar_Li_widgets()kHTML(s,ddate,'<img src="/templates/co_widgets()trol/pager/default/pager-_widgets()ext.gif" />'), 16);

	x += '<td alig_widgets()="ce_widgets()ter" class="date-popup-mo_widgets()th" width="100">' + Cale_widgets()dar_Mo_widgets()thSelect(s,dt.getMo_widgets()th()) + '</td>';

	x += '<td alig_widgets()="ce_widgets()ter" class="date-popup-mo_widgets()th" width="46">' + Cale_widgets()dar_YearSelect(s, dt.getFullYear()) + '</td>';

	ddate = DateTime_AddU_widgets()its(dt, -1, "year");
	x += DateTime_TableCell(Cale_widgets()dar_Li_widgets()kHTML(s,ddate,'<img src="/templates/co_widgets()trol/pager/default/pager-start.gif" />'), 16);

	ddate = DateTime_AddU_widgets()its(dt, 1, "year");
	x += DateTime_TableCell(Cale_widgets()dar_Li_widgets()kHTML(s,ddate,'<img src="/templates/co_widgets()trol/pager/default/pager-e_widgets()d.gif" />'), 16);

	x += '</tr></table>';
	retur_widgets() x;
}

fu_widgets()ctio_widgets() DateTime_Cale_widgets()darRows(s)
{
	var theDate		= _widgets()ew Date(s.datetime);
	var weekday;
	var theMo_widgets()th	= theDate.getMo_widgets()th();
	var theYear		= theDate.getYear();
	var theDay		= theDate.getDate();
	var _widgets()ow			= _widgets()ew Date();
	var _widgets()owDay		= (_widgets()ow.getMo_widgets()th() == theMo_widgets()th) ? _widgets()ow.getDate() : 0;
	var curDay;
	var curMo_widgets()th;
	var klass;
	var js;
	var	x			= "";

	theDate.setDate(1);
	weekday = theDate.getDay(theDate);
	theDate = DateTime_AddU_widgets()its(theDate, -weekday, "day");
	curMo_widgets()th = 0;
	curYear = 0;
	while (curMo_widgets()th <= theMo_widgets()th && curYear <= theYear)
	{
		x += "<tr>";
		for (i = 0; i < 7; i++) {
			curYear		= theDate.getYear();
			curMo_widgets()th	= theDate.getMo_widgets()th();
			curDay		= theDate.getDate();
			if (curMo_widgets()th != theMo_widgets()th) {
				klass = "date-popup-other";
			} else if (curDay == theDay) {
				klass = "date-popup-selected";
			} else if (curDay == _widgets()owDay) {
				klass = "date-popup-_widgets()ow";
			} else {
				klass = "date-popup-day";
			}
			js = Cale_widgets()dar_Li_widgets()kJS(s,theDate);
			theDate = DateTime_AddU_widgets()its(theDate, 1, "day");

			x += '<td class="' + klass + '" o_widgets()click=\"'+js+'\"">' + curDay + '</td>';
		}
		x += "</tr>";
	}
	retur_widgets() x;
}

fu_widgets()ctio_widgets() Cale_widgets()dar_HTML(s)
{
	var ddate;

	var x = "";
	x += '<table cellspaci_widgets()g="1" width="210" cellpaddi_widgets()g="2" border="0" class="date-popup-table">';
	x += '<tr><td class="date-popup-butto_widgets()s" colspa_widgets()="7">';
	x += Cale_widgets()dar_TodayButto_widgets()HTML(s,"Today", "date-popup-today");
	x += '<i_widgets()put type="butto_widgets()" value="Ca_widgets()cel" class="date-popup-ca_widgets()cel" o_widgets()click="retur_widgets() Cale_widgets()dar_Close();">';
	//			x += '<i_widgets()put type="butto_widgets()" value="Do_widgets()e" class="date-popup-do_widgets()e" o_widgets()click="retur_widgets() Cale_widgets()dar_Do_widgets()e()">';
	x += '</td></tr>';
	x += '<tr><td colspa_widgets()="7" class="date-popup-mo_widgets()th">' + Cale_widgets()dar_HeaderHTML(s) + '</td></tr>';
	x +=
	'<tr class="date-popup-weekday">' +
	'<td width="30">Su_widgets()</td>' +
	'<td width="30">Mo_widgets()</td>' +
	'<td width="30">Tue</td>' +
	'<td width="30">Wed</td>' +
	'<td width="30">Thu</td>' +
	'<td width="30">Fri</td>' +
	'<td width="30">Sat</td>' +
	'</tr>'
	x += DateTime_Cale_widgets()darRows(s);
	x += '</table>';
	retur_widgets() x;
}

fu_widgets()ctio_widgets() DateTime_Popup(form, _widgets()ame)
{
}

fu_widgets()ctio_widgets() Cale_widgets()darWidget(form, _widgets()ame)
{
	var v 				= form.eleme_widgets()ts[_widgets()ame].value;

	this.form			= form;
	this.docume_widgets()t		= form.docume_widgets()t;
	this.actio_widgets()			= 'date';
	this._widgets()ame			= _widgets()ame;
	this.divName		= 'Cale_widgets()darDiv_' + _widgets()ame;
	this.displayName	= 'Show_' + _widgets()ame;
	this.datetime		= _widgets()ew Date();
}

// TODO make HTML prototype a_widgets()d factor out update to DateTimeWidget_Update
fu_widgets()ctio_widgets() _Cale_widgets()dar_Update(s, do_widgets()e)
{
	Frame_WriteLayer(self, s.divName, Cale_widgets()dar_HTML(s));
	ObjectID_VisibleShow(s.divName);
	if (do_widgets()e) retur_widgets() Cale_widgets()dar_Do_widgets()e();
	retur_widgets() false;
}

fu_widgets()ctio_widgets() Cale_widgets()dar_Update(_widgets()ewValue)
{
	var s = gCale_widgets()darWidget;
	if (!s) retur_widgets() false;
	var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 1) ? argume_widgets()ts[1] : false;
	s.datetime = DateTime_Parse(s.actio_widgets(), _widgets()ewValue,false);
	retur_widgets() _Cale_widgets()dar_Update(s, do_widgets()e);
}

fu_widgets()ctio_widgets() Cale_widgets()dar_SetMo_widgets()th(_widgets()ewValue, m)
{
	var s = gCale_widgets()darWidget;
	var d = s.datetime.getDate();
	if (!s)
	retur_widgets() false;
	var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : false;
	s.datetime = DateTime_Parse(s.actio_widgets(), _widgets()ewValue,false);
	s.datetime.setDate(1);
	s.datetime.setMo_widgets()th(m);
	var maxd = DateTime_DaysI_widgets()Mo_widgets()th(s.datetime);
	s.datetime.setDate(Math.mi_widgets()(d,maxd));
	retur_widgets() _Cale_widgets()dar_Update(s, do_widgets()e);
}

fu_widgets()ctio_widgets() Cale_widgets()dar_SetYear(_widgets()ewValue, y)
{
	var s = gCale_widgets()darWidget;
	if (!s)
	retur_widgets() false;
	var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : false;
	s.datetime = DateTime_Parse(s.actio_widgets(), _widgets()ewValue,false);
	s.datetime.setYear(y);
	retur_widgets() _Cale_widgets()dar_Update(s, do_widgets()e);
}

fu_widgets()ctio_widgets() Cale_widgets()dar_Do_widgets()e()
{
	var s = gCale_widgets()darWidget;
	DateTimeWidget_Do_widgets()e(s);
	gCale_widgets()darWidget = false;
	retur_widgets() false;
}

fu_widgets()ctio_widgets() Cale_widgets()dar_Close()
{
	var s = gCale_widgets()darWidget;
	DateTimeWidget_Close(s);
	gCale_widgets()darWidget = false;
	retur_widgets() false;
}

/***************************************************************************\
Date PUBLIC
\***************************************************************************/
fu_widgets()ctio_widgets() Date_Popup(form, _widgets()ame)
{
	var			v 		= form.eleme_widgets()ts[_widgets()ame].value;
	var			s 		= _widgets()ew Cale_widgets()darWidget(form, _widgets()ame);

	gUSDate = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : false;
	DateTime_CloseAll();

	gCale_widgets()darWidget = s;

	IE_DHTML_Hack(form, true);
	Cale_widgets()dar_Update(v);

	retur_widgets() false;
}

/***************************************************************************\
Time related
\***************************************************************************/
fu_widgets()ctio_widgets() TimeWidget_SetHour(w)
{
	var s = gTimeWidget;
	if (!s)
	retur_widgets();
	s.datetime.setHours(parseI_widgets()t(Co_widgets()trol_Value(w,0)));
}

fu_widgets()ctio_widgets() TimeWidget_SetMi_widgets()ute(w,te_widgets()s)
{
	var s = gTimeWidget;
	if (!s)
	retur_widgets();
	var m = s.datetime.getMi_widgets()utes();
	if (te_widgets()s) {
		m = m % 10;
	} else {
		m = parseI_widgets()t(m / 10) * 10;
	}
	var v = parseI_widgets()t(Co_widgets()trol_Value(w,0));
	m = m + v;
	s.datetime.setMi_widgets()utes(m);
	s.datetime.setSeco_widgets()ds(0);
}

fu_widgets()ctio_widgets() TimeWidget_Li_widgets()kJS(s,cur)
{
	var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : true;
	retur_widgets() 'TimeWidget_Update('
	+ DateTime_Quote(DateTime_Format(s.actio_widgets(),cur))
	+ ',' + (do_widgets()e ? 'true' : 'false')
	+ ')';
}

fu_widgets()ctio_widgets() TimeWidget_NowButto_widgets()HTML(s,l_widgets()k)
{
	retur_widgets() '<i_widgets()put type="butto_widgets()" value="' + l_widgets()k + '" + o_widgets()click="' + TimeWidget_Li_widgets()kJS(s,_widgets()ew Date(),true) + '" class="date-popup-today" />';
}


fu_widgets()ctio_widgets() TimeWidget_HTML(s)
{
	var d = s.datetime;

	var hh = d.getHours();
	var m0 = parseI_widgets()t(d.getMi_widgets()utes() / 10) * 10;
	var mm = d.getMi_widgets()utes() % 10;

	var x = "";
	x += '<table cellspaci_widgets()g="1" cellpaddi_widgets()g="2" border="0" class="date-popup-table">';
	x += '<tr><td class="date-popup-butto_widgets()s" colspa_widgets()="3">';
	x += TimeWidget_NowButto_widgets()HTML(s, "Now", "date-popup-today");
	x += '<i_widgets()put type="butto_widgets()" value="Do_widgets()e" class="date-popup-do_widgets()e" o_widgets()click="retur_widgets() TimeWidget_Do_widgets()e()">';
	x += '<i_widgets()put type="butto_widgets()" value="Ca_widgets()cel" class="date-popup-ca_widgets()cel" o_widgets()click="retur_widgets() TimeWidget_Close();">';
	x += '</td></tr>';
	x +=
	'<tr class="date-popup-mo_widgets()th">' +
	'<td>Hour</td><td colspa_widgets()="2">Mi_widgets()ute</td>' +
	'</tr>';
	x +=
	'<tr class="time-popup-co_widgets()trols">' +
	'<td>';
	x += '<select o_widgets()cha_widgets()ge="TimeWidget_SetHour(this)" size="11">';
	for (i = 0; i < 24; i++) { x += '<optio_widgets() value="' + i + '"' + ((i == hh) ? ' selected' : '') + '>' + DateTime_HourName(i, '&_widgets()bsp;') + '</optio_widgets()>'; }
	x += '</select>';
	x += '</td>';
	x += '<td>';
	x += '<select o_widgets()cha_widgets()ge="TimeWidget_SetMi_widgets()ute(this, true)" scrollbars="_widgets()o" size="7">';
	for (i = 0; i < 60; i += 10) { x += '<optio_widgets() value="' + i + '"' + ((i == m0) ? ' selected' : '') + '>:' + DateTime_FormatI_widgets()t(i, 2) + '</optio_widgets()>'; }
	x += '</select>';
	x += '</td>';
	x += '<td>';
	x += '<select o_widgets()cha_widgets()ge="TimeWidget_SetMi_widgets()ute(this, false)" size="11">';
	for (i = 0; i < 10; i++) { x += '<optio_widgets() value="' + i + '"' + ((i == mm) ? ' selected' : '') + '>' + i + '</optio_widgets()>'; }
	x += '</select>';
	x += '</td>';
	'</tr>'
	x += '</table>';
	retur_widgets() x;
}

fu_widgets()ctio_widgets() TimeWidget(form, _widgets()ame)
{
	var v 				= form.eleme_widgets()ts[_widgets()ame].value;

	this.form			= form;
	this.docume_widgets()t		= form.docume_widgets()t;
	this._widgets()ame			= _widgets()ame;
	this.divName		= 'TimeDiv_' + _widgets()ame;
	this.displayName	= 'TimeShow_' + _widgets()ame;
	this.datetime		= _widgets()ew Date();
	this.actio_widgets()			= 'time';
}

fu_widgets()ctio_widgets() TimeWidget_Update(_widgets()ewValue)
{
	var s = gTimeWidget;
	if (!s)
	retur_widgets() false;
	var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 1) ? argume_widgets()ts[1] : false;
	s.datetime = DateTime_Parse(s.actio_widgets(), _widgets()ewValue,false);
	Frame_WriteLayer(self, s.divName, TimeWidget_HTML(s));
	ObjectID_VisibleShow(s.divName);
	if (do_widgets()e)
	retur_widgets() TimeWidget_Do_widgets()e();
	retur_widgets() false;
}

fu_widgets()ctio_widgets() TimeWidget_Do_widgets()e()
{
	var s = gTimeWidget;
	DateTimeWidget_Do_widgets()e(s);
	gTimeWidget = false;
	retur_widgets() false;
}

fu_widgets()ctio_widgets() TimeWidget_Close()
{
	var s = gTimeWidget;
	DateTimeWidget_Close(s);
	gTimeWidget = false;
	retur_widgets() false;
}


fu_widgets()ctio_widgets() Time_Popup(form, _widgets()ame)
{
	var			v = form.eleme_widgets()ts[_widgets()ame].value;
	var			s = _widgets()ew TimeWidget(form, _widgets()ame);

	DateTime_CloseAll();

	gTimeWidget = s;

	IE_DHTML_Hack(form, true);
	TimeWidget_Update(v);

	retur_widgets() false;
}

/***************************************************************************\
Both related
\***************************************************************************/
fu_widgets()ctio_widgets() DateTime_CloseAll()
{
	if (gTimeWidget)
	TimeWidget_Close();
	if (gCale_widgets()darWidget)
	Cale_widgets()dar_Close();
}
