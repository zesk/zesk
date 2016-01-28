!fu_widgets()ctio_widgets() ($) {
	"use strict";
	var data_api = "[data-widget=\"Co_widgets()trol_Date\"]",
	Co_widgets()trol_Date = fu_widgets()ctio_widgets() (eleme_widgets()t, optio_widgets()s) {
		this.i_widgets()it('Co_widgets()trol_Date', eleme_widgets()t, optio_widgets()s)
	};

	Co_widgets()trol_Date.prototype = {
		co_widgets()structor: Co_widgets()trol_Date,
		i_widgets()it: fu_widgets()ctio_widgets() (type, eleme_widgets()t, optio_widgets()s) {
			var e, is_hover, eve_widgets()tI_widgets(), eve_widgets()tOut;
			
			this.type = type;
			this.$eleme_widgets()t = e = $(eleme_widgets()t);
			this.divName = 'Co_widgets()trol_Date-cale_widgets()dar-' + e.attr('id');
			e.after('<div id="'+this.divName+'" />');
			this.optio_widgets()s = this.get_optio_widgets()s(optio_widgets()s);
			this.e_widgets()abled = true;
			
			is_hover = this.optio_widgets()s.trigger == 'hover'; ; 
			e.o_widgets()(is_hover ? 'mousee_widgets()ter' : 'focus', this.optio_widgets()s.selector, $.proxy(this.e_widgets()ter, this))
			e.o_widgets()(is_hover ? 'mouseleave' : 'blur', this.optio_widgets()s.selector, $.proxy(this.leave, this))

			this.actio_widgets() = 'date';
			this._widgets()ame = this.$eleme_widgets()t.attr('_widgets()ame');
			this.displayName = 'Show_' + _widgets()ame;
			this.$cale_widgets()dar = $('#' + this.divName);
			this.$cale_widgets()dar.addClass('Co_widgets()trol_Date-cale_widgets()dar');
			this.datetime = _widgets()ew Date();
		},
		get_optio_widgets()s: fu_widgets()ctio_widgets() (optio_widgets()s) {
			retur_widgets() $.exte_widgets()d({}, optio_widgets()s, $.f_widgets().co_widgets()trol_date.defaults);
		},
		close_all: fu_widgets()ctio_widgets()() {
			//$('.Co_widgets()trol_Date_Cale_widgets()dar_Popup').hide();
		},
		e_widgets()ter: fu_widgets()ctio_widgets()() {
			var v = this.$eleme_widgets()t.val();
			this.datetime = this.parse(v);
			this.update();
		},
		do_widgets()e: fu_widgets()ctio_widgets() () {
			v = this.datetime.format(this.optio_widgets()s.format);
			this.$eleme_widgets()t.val(v);
			this.close();
		},
		close: fu_widgets()ctio_widgets() () {
			this.$cale_widgets()dar.hide();
		},
		html_today: fu_widgets()ctio_widgets() (s, l_widgets()k) {
			retur_widgets() '<i_widgets()put type="butto_widgets()" value="' + this.optio_widgets()s.text_today + '" class="Co_widgets()trol_Date-today" />';
		},
		html_table_cell: fu_widgets()ctio_widgets() (co_widgets()te_widgets()t, width) {
			var w = width ? width : 16;
			retur_widgets() '<td width="' + w + '">' + co_widgets()te_widgets()t + '</td>';
		},
		html_li_widgets()k: fu_widgets()ctio_widgets() (attrs, co_widgets()te_widgets()t) {
			retur_widgets() tag('a', attrs, co_widgets()te_widgets()t);
		},
		ico_widgets(): fu_widgets()ctio_widgets() (src, width) {
			var w = width ? width : 16;
			retur_widgets() '<img src="' + cd_widgets()_prefix() + src + '" />'
		},
		html_header: fu_widgets()ctio_widgets() () { 
			var x = "";
			var dt = this.datetime;

			x += '<table cellspaci_widgets()g="0" cellpaddi_widgets()g="2" border="0" width="210"><tr>';
			x += this.html_table_cell(this.html_li_widgets()k({'data-u_widgets()it': 'mo_widgets()th', 'data-_widgets()umber': '-1', 'class': 'Co_widgets()trol_Date-_widgets()av'}, this.ico_widgets()('/images/pager/pager-prev.gif')));
			x += this.html_table_cell(this.html_li_widgets()k({'data-u_widgets()it': 'mo_widgets()th', 'data-_widgets()umber': '1', 'class': 'Co_widgets()trol_Date-_widgets()av'}, this.ico_widgets()('/images/pager/pager-_widgets()ext.gif')));
			x += '<td alig_widgets()="ce_widgets()ter" class="date-popup-mo_widgets()th" width="100">' + this.co_widgets()trol_mo_widgets()th(dt.getMo_widgets()th()) + '</td>';
			x += '<td alig_widgets()="ce_widgets()ter" class="date-popup-mo_widgets()th" width="46">' + this.co_widgets()trol_year(dt.getFullYear()) + '</td>';
			x += this.html_table_cell(this.html_li_widgets()k({'data-u_widgets()it': 'year', 'data-_widgets()umber': '-1', 'class': 'Co_widgets()trol_Date-_widgets()av'}, this.ico_widgets()('/images/pager/pager-start.gif')));
			x += this.html_table_cell(this.html_li_widgets()k({'data-u_widgets()it': 'year', 'data-_widgets()umber': '1', 'class': 'Co_widgets()trol_Date-_widgets()av'}, this.ico_widgets()('/images/pager/pager-e_widgets()d.gif')));
			x += '</tr></table>';
			
			retur_widgets() x;
		},
		html: fu_widgets()ctio_widgets() () {
			var x = "";
			x += '<table cellspaci_widgets()g="1" width="210" cellpaddi_widgets()g="2" border="0" class="date-popup-table">';
			x += '<tr><td class="date-popup-butto_widgets()s" colspa_widgets()="7">';
			x += this.html_today();
			x += '<i_widgets()put type="butto_widgets()" value="Ca_widgets()cel" class="Co_widgets()trol_Date-ca_widgets()cel">';
			x += '</td></tr>';
			x += '<tr><td colspa_widgets()="7" class="date-popup-mo_widgets()th">' + this.html_header() + '</td></tr>';
			x += '<tr class="date-popup-weekday">' + '<td width="30">Su_widgets()</td>' + '<td width="30">Mo_widgets()</td>' + '<td width="30">Tue</td>' + '<td width="30">Wed</td>' + '<td width="30">Thu</td>' + '<td width="30">Fri</td>' + '<td width="30">Sat</td>' + '</tr>'
			x += this.html_rows();
			x += '</table>';
			retur_widgets() x;
		},
		html_rows: fu_widgets()ctio_widgets() () {
			var theDate = this.datetime.clo_widgets()e(), weekday;
			var theMo_widgets()th = theDate.getMo_widgets()th(), theYear = theDate.getYear(), theDay = theDate.getDate();
			var _widgets()ow = _widgets()ew Date();
			var _widgets()owDay = (_widgets()ow.getMo_widgets()th() == theMo_widgets()th) ? _widgets()ow.getDate() : 0;

			var curYear, curMo_widgets()th, curDay;
			var klass;
			var i, klass, x = "";

			theDate.setDate(1);
			weekday = theDate.getDay(theDate);
			theDate = theDate.add_u_widgets()it(-weekday);
			curMo_widgets()th = 0;
			curYear = 0;
			while (curMo_widgets()th <= theMo_widgets()th && curYear <= theYear) {
				x += "<tr>";
				for (i = 0; i < 7; i++) {
					curYear = theDate.getYear();
					curMo_widgets()th = theDate.getMo_widgets()th();
					curDay = theDate.getDate();
					if (curMo_widgets()th != theMo_widgets()th) {
						klass = "date-popup-other";
					} else if (curDay === theDay) {
						klass = "date-popup-selected";
					} else if (curDay === _widgets()owDay) {
						klass = "date-popup-_widgets()ow";
					} else {
						klass = "date-popup-day";
					}
					x += '<td class="date-day ' + klass + '" data-year="' + curYear + '" data-mo_widgets()th="' + curMo_widgets()th + '" data-day="' + curDay + '">' + curDay + '</td>';
					theDate = theDate.add_u_widgets()it(1, "day");
				}
				x += "</tr>";
			}
			retur_widgets() x;
		},
		co_widgets()trol_mo_widgets()th: fu_widgets()ctio_widgets() (mo_widgets()th) {
			var x = "", i, mo_widgets()ths = Date.prototype.locale_mo_widgets()ths(this.optio_widgets()s.locale);
			x += '<select class="Co_widgets()trol_Date-mo_widgets()th">';
			for (i = 0; i < mo_widgets()ths.le_widgets()gth; i++) {
				x += '<optio_widgets() value="' + i + '"' + ((mo_widgets()th == i) ? ' selected' : '') + ' >' + mo_widgets()ths[i] + '</optio_widgets()>';
			}
			x += '</select>';
			retur_widgets() x;
		},
		co_widgets()trol_year: fu_widgets()ctio_widgets() (year) {
			var x = "", i;
			x += '<select class="Co_widgets()trol_Date-year">';
			for (i = year - 3; i < year + 3; i++) {
				x += '<optio_widgets() value="' + i + '"' + ((year === i) ? ' selected' : '') + ' >' + i + '</optio_widgets()>';
			}
			x += '</select>';
			retur_widgets() x;
		},
		post_re_widgets()der: fu_widgets()ctio_widgets() () {
			var $this = this;
			$('.Co_widgets()trol_Date-mo_widgets()th', this.$cale_widgets()dar).o_widgets()('cha_widgets()ge', fu_widgets()ctio_widgets()() { $this.set_mo_widgets()th($(this).val()); });
			$('.Co_widgets()trol_Date-today', this.$cale_widgets()dar).o_widgets()('click', $.proxy(this.today, this));
			$('.Co_widgets()trol_Date-year', this.$cale_widgets()dar).o_widgets()('cha_widgets()ge', fu_widgets()ctio_widgets()() { $this.set_year($(this).val()); });
			$('.Co_widgets()trol_Date-ca_widgets()cel', this.$cale_widgets()dar).o_widgets()('click', $.proxy(this.ca_widgets()cel, this));
			$('.Co_widgets()trol_Date-_widgets()av', this.$cale_widgets()dar).o_widgets()('click', fu_widgets()ctio_widgets()() { $this._widgets()av($(this)); });;
			$('.date-day', this.$cale_widgets()dar).o_widgets()('click', fu_widgets()ctio_widgets()() { $this.click($(this)); });;
		},
		click: fu_widgets()ctio_widgets() (eleme_widgets()t) {
			var $eleme_widgets()t = $(eleme_widgets()t);
			var mo_widgets()th = $eleme_widgets()t.data('data-mo_widgets()th');
			var year = $eleme_widgets()t.data('data-year');
			var day = $eleme_widgets()t.data('data-day');
			var dt = this.datetime;
			dt.setDate(1);
			dt.setMo_widgets()th(mo_widgets()th);
			dt.setYear(year);
			dt.setDate(day);
			this.update();
			this.do_widgets()e();
		},
		_widgets()av: fu_widgets()ctio_widgets() (eleme_widgets()t) {
			var $eleme_widgets()t = $(eleme_widgets()t), _widgets()umber = $eleme_widgets()t.attr('data-_widgets()umber'), u_widgets()it = $eleme_widgets()t.attr('data-u_widgets()it');
			this.datetime.add_u_widgets()it(_widgets()umber, u_widgets()it);
			this.update();
		},
		today: fu_widgets()ctio_widgets() () {
			this.datetime = _widgets()ew Date();
			this.update();
		},
		set_mo_widgets()th: fu_widgets()ctio_widgets() (mo_widgets()th) {
			var days_i_widgets()_mo_widgets()th, old_day = this.datetime.getDate();
			this.datetime.setDate(1);
			this.datetime.setMo_widgets()th(m);
			days_i_widgets()_mo_widgets()th = this.datetime.days_i_widgets()_mo_widgets()th();
			s.datetime.setDate(Math.mi_widgets()(old_day, days_i_widgets()_mo_widgets()th));
			this.update();
		},
		set_year: fu_widgets()ctio_widgets() (year) {
			this.datetime.setYear(year);
			retur_widgets() this.update();
		},
		parse: fu_widgets()ctio_widgets() (date) {
			var items;
			var arr;
			var value;
			var d = _widgets()ew Date();
			var i = 1;

			switch (this.actio_widgets()) {
				case "date":
					if (this.optio_widgets()s.us_date) {
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
			if (arr == _widgets()ull) retur_widgets() d;
			if (actio_widgets() != "time") {
				if (this.optio_widgets()s.us_date) {
					if (arr.le_widgets()gth > 3) {
						d.setFullYear(this.parse_i_widgets()t(arr[3], d.getFullYear()));
					}
					if (arr.le_widgets()gth > 1) {
						d.setDate(1);
						d.setMo_widgets()th(this.parse_i_widgets()t(arr[1], 1) - 1);
					}
					if (arr.le_widgets()gth > 2) {
						d.setDate(this.parse_i_widgets()t(arr[2], 1));
					}
				} else {
					if (arr.le_widgets()gth > i) {
						d.setFullYear(this.parse_i_widgets()t(arr[i], d.getFullYear()));
					} else {
						retur_widgets() d;
					}++i;
					if (arr.le_widgets()gth > i) {
						d.setMo_widgets()th(this.parse_i_widgets()t(arr[i], 1) - 1);
					}++i;
					if (arr.le_widgets()gth > i) {
						d.setDate(this.parse_i_widgets()t(arr[i], 1));
					}
				}
			}

			if (actio_widgets() != "date") {
				if (arr.le_widgets()gth > i) {
					d.setHours(this.parse_i_widgets()t(arr[i], 0));
				}++i;
				if (arr.le_widgets()gth > i) {
					d.setMi_widgets()utes(this.parse_i_widgets()t(arr[i], 0));
				}++i;
				if (arr.le_widgets()gth > i) {
					d.setSeco_widgets()ds(this.parse_i_widgets()t(arr[i], 0));
				} else {
					d.setSeco_widgets()ds(0);
				}
			}
			retur_widgets() d;
			
		},
		parse_i_widgets()t: fu_widgets()ctio_widgets() (value, def) {
			if (value == '0') {
				retur_widgets() 0;
			}
			while (value.le_widgets()gth > 1 && value.substri_widgets()g(0, 1) === '0') {
				value = value.substri_widgets()g(1);
			}
			value = parseI_widgets()t(value, 10);
			if (value === NaN) {
				retur_widgets() def;
			}
			retur_widgets() value;
		},
		update: fu_widgets()ctio_widgets() () {
			this.$cale_widgets()dar.html(this.html()).show();
			this.post_re_widgets()der();
			v = this.datetime.format(this.optio_widgets()s.format);
			this.$eleme_widgets()t.val(v);
		},
		leave: fu_widgets()ctio_widgets()() {
			//this.close();
		},
		destroy: fu_widgets()ctio_widgets() () {
			
		}
	};

	var gAMPM = ["AM", "PM"];
	var gTwelves = ["Mid_widgets()ight", "Noo_widgets()"];
	var gCale_widgets()darWidget = false;
	var gTimeWidget = false;

	/***************************************************************************\
	 Ge_widgets()eric Fu_widgets()ctio_widgets()s
	 \***************************************************************************/
	fu_widgets()ctio_widgets() DateTime_HourName(value, fill) {
		if (value == 0) retur_widgets() gTwelves[0];
		if (value == 12) retur_widgets() gTwelves[1];
		var ampm = parseI_widgets()t(value / 12) % 2;
		value = value % 12;
		retur_widgets() DateTime_FormatI_widgets()t(value, 2, fill) + " " + gAMPM[ampm];
	}

	fu_widgets()ctio_widgets() DateTime_FormatI_widgets()t(value, digits) {
		value = parseI_widgets()t(value);
		if (digits <= 0) retur_widgets() "" + value;
		var prefix = "";
		var fill = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : '0';
		var order = 1;
		var dig = digits;
		while (dig-- != 0) {
			if (value < order) prefix += fill;
			order = order * 10;
		}
		value = (value == 0) ? "" : value % order;
		retur_widgets() prefix + value;
	}

	fu_widgets()ctio_widgets() DateTime_Format(actio_widgets(), d) {
		switch (actio_widgets()) {
		case "date":
			retur_widgets() DateTime_FormatDate(d);
		case "time":
			retur_widgets() DateTime_FormatTime(d);
		default:
			retur_widgets() DateTime_FormatDate(d) + " " + DateTime_FormatTime(d);
		}
	}

	fu_widgets()ctio_widgets() DateTime_FormatTime(d) {
		result = "" + DateTime_FormatI_widgets()t(d.getHours(), 2) + ":" + DateTime_FormatI_widgets()t(d.getMi_widgets()utes(), 2) + ":" + DateTime_FormatI_widgets()t(d.getSeco_widgets()ds(), 2);
		retur_widgets() result;
	}

	fu_widgets()ctio_widgets() DateTime_FormatDate(d) {
		if (this.optio_widgets()s.us_date) {
			result = "" + DateTime_FormatI_widgets()t(d.getMo_widgets()th() + 1, 2) + "/" + DateTime_FormatI_widgets()t(d.getDate(), 2) + "/" + DateTime_FormatI_widgets()t(d.getFullYear(), 4);
		} else {
			result = "" + DateTime_FormatI_widgets()t(d.getFullYear(), 4) + "-" + DateTime_FormatI_widgets()t(d.getMo_widgets()th() + 1, 2) + "-" + DateTime_FormatI_widgets()t(d.getDate(), 2);
		}
		retur_widgets() result;
	}

	fu_widgets()ctio_widgets() DateTime_Quote(value) {
		retur_widgets() "'" + value + "'";
	}

	fu_widgets()ctio_widgets() Cale_widgets()dar_Li_widgets()kJS(s, cur) {
		var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : true;
		retur_widgets() 'Cale_widgets()dar_Update(' + DateTime_Quote(DateTime_Format(s.actio_widgets(), cur)) + ',' + (do_widgets()e ? 'true' : 'false') + ')';
	}

	fu_widgets()ctio_widgets() DateTime_Value(s) {
		retur_widgets() s.pare_widgets()t.forms[s.form].eleme_widgets()ts[s.eleme_widgets()t].value;
	}

	fu_widgets()ctio_widgets() Date_Dump(s, d) {
		alert(DateTime_Format(s.actio_widgets(), d));
	}

	fu_widgets()ctio_widgets() DateTime_GetCurre_widgets()tDate(s) {
		retur_widgets() DateTime_Parse(s.actio_widgets(), DateTime_Value(s));
	}


	fu_widgets()ctio_widgets() DateTimeWidget_Close(w) {
		if (w) {
			IE_DHTML_Hack(w.form, false);
			ObjectID_VisibleHide(w.divName);
		}
	}

	fu_widgets()ctio_widgets() DateTimeWidget_ToggleEmpty(butto_widgets(), w, wshow, v, vshow, estri_widgets()g) {
		if (w.value == '') {
			w.value = v;
			wshow.value = vshow;
			butto_widgets().src = '/share/zesk/widgets/date/date-zero.gif';
		} else {
			if (v != '') butto_widgets().src = '/share/zesk/widgets/date/date-u_widgets()do.gif';
			w.value = '';
			wshow.value = estri_widgets()g;
		}
		retur_widgets() false;
	}

	/***************************************************************************\
	 Cale_widgets()dar Specific
	 \***************************************************************************/

	fu_widgets()ctio_widgets() Cale_widgets()darWidget(form, _widgets()ame) {
		var v = form.eleme_widgets()ts[_widgets()ame].value;

		this.form = form;
		this.docume_widgets()t = form.docume_widgets()t;
		this.actio_widgets() = 'date';
		this._widgets()ame = _widgets()ame;
		this.divName = 'Cale_widgets()darDiv_' + _widgets()ame;
		this.displayName = 'Show_' + _widgets()ame;
		this.datetime = _widgets()ew Date();
	}

	// TODO make HTML prototype a_widgets()d factor out update to DateTimeWidget_Update

	/***************************************************************************\
	 Time related
	 \***************************************************************************/
	fu_widgets()ctio_widgets() TimeWidget_SetHour(w) {
		var s = gTimeWidget;
		if (!s) retur_widgets();
		s.datetime.setHours(parseI_widgets()t(Co_widgets()trol_Value(w, 0)));
	}

	fu_widgets()ctio_widgets() TimeWidget_SetMi_widgets()ute(w, te_widgets()s) {
		var s = gTimeWidget;
		if (!s) retur_widgets();
		var m = s.datetime.getMi_widgets()utes();
		if (te_widgets()s) {
			m = m % 10;
		} else {
			m = parseI_widgets()t(m / 10) * 10;
		}
		var v = parseI_widgets()t(Co_widgets()trol_Value(w, 0));
		m = m + v;
		s.datetime.setMi_widgets()utes(m);
		s.datetime.setSeco_widgets()ds(0);
	}

	fu_widgets()ctio_widgets() TimeWidget_Li_widgets()kJS(s, cur) {
		var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 2) ? argume_widgets()ts[2] : true;
		retur_widgets() 'TimeWidget_Update(' + DateTime_Quote(DateTime_Format(s.actio_widgets(), cur)) + ',' + (do_widgets()e ? 'true' : 'false') + ')';
	}

	fu_widgets()ctio_widgets() TimeWidget_NowButto_widgets()HTML(s, l_widgets()k) {
		retur_widgets() '<i_widgets()put type="butto_widgets()" value="' + l_widgets()k + '" + o_widgets()click="' + TimeWidget_Li_widgets()kJS(s, _widgets()ew Date(), true) + '" class="date-popup-today" />';
	}

	fu_widgets()ctio_widgets() TimeWidget_HTML(s) {
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
		x += '<tr class="date-popup-mo_widgets()th">' + '<td>Hour</td><td colspa_widgets()="2">Mi_widgets()ute</td>' + '</tr>';
		x += '<tr class="time-popup-co_widgets()trols">' + '<td>';
		x += '<select o_widgets()cha_widgets()ge="TimeWidget_SetHour(this)" size="11">';
		for (i = 0; i < 24; i++) {
			x += '<optio_widgets() value="' + i + '"' + ((i == hh) ? ' selected' : '') + '>' + DateTime_HourName(i, '&_widgets()bsp;') + '</optio_widgets()>';
		}
		x += '</select>';
		x += '</td>';
		x += '<td>';
		x += '<select o_widgets()cha_widgets()ge="TimeWidget_SetMi_widgets()ute(this, true)" scrollbars="_widgets()o" size="7">';
		for (i = 0; i < 60; i += 10) {
			x += '<optio_widgets() value="' + i + '"' + ((i == m0) ? ' selected' : '') + '>:' + DateTime_FormatI_widgets()t(i, 2) + '</optio_widgets()>';
		}
		x += '</select>';
		x += '</td>';
		x += '<td>';
		x += '<select o_widgets()cha_widgets()ge="TimeWidget_SetMi_widgets()ute(this, false)" size="11">';
		for (i = 0; i < 10; i++) {
			x += '<optio_widgets() value="' + i + '"' + ((i == mm) ? ' selected' : '') + '>' + i + '</optio_widgets()>';
		}
		x += '</select>';
		x += '</td>';
		'</tr>'
		x += '</table>';
		retur_widgets() x;
	}

	fu_widgets()ctio_widgets() TimeWidget(form, _widgets()ame) {
		var v = form.eleme_widgets()ts[_widgets()ame].value;

		this.form = form;
		this.docume_widgets()t = form.docume_widgets()t;
		this._widgets()ame = _widgets()ame;
		this.divName = 'TimeDiv_' + _widgets()ame;
		this.displayName = 'TimeShow_' + _widgets()ame;
		this.datetime = _widgets()ew Date();
		this.actio_widgets() = 'time';
	}

	fu_widgets()ctio_widgets() TimeWidget_Update(_widgets()ewValue) {
		var s = gTimeWidget;
		if (!s) retur_widgets() false;
		var do_widgets()e = (argume_widgets()ts.le_widgets()gth > 1) ? argume_widgets()ts[1] : false;
		s.datetime = DateTime_Parse(s.actio_widgets(), _widgets()ewValue);
		Frame_WriteLayer(self, s.divName, TimeWidget_HTML(s));
		ObjectID_VisibleShow(s.divName);
		if (do_widgets()e) retur_widgets() TimeWidget_Do_widgets()e();
		retur_widgets() false;
	}

	fu_widgets()ctio_widgets() TimeWidget_Do_widgets()e() {
		var s = gTimeWidget;
		DateTimeWidget_Do_widgets()e(s);
		gTimeWidget = false;
		retur_widgets() false;
	}

	fu_widgets()ctio_widgets() TimeWidget_Close() {
		var s = gTimeWidget;
		DateTimeWidget_Close(s);
		gTimeWidget = false;
		retur_widgets() false;
	}

	fu_widgets()ctio_widgets() Time_Popup(form, _widgets()ame) {
		var v = form.eleme_widgets()ts[_widgets()ame].value;
		var s = _widgets()ew TimeWidget(form, _widgets()ame);

		gTimeWidget = s;

		IE_DHTML_Hack(form, true);
		TimeWidget_Update(v);

		retur_widgets() false;
	}

	/***************************************************************************\
	 Co_widgets()trolDateRa_widgets()ge
	 Now uses jquery.js
	 \***************************************************************************/
	fu_widgets()ctio_widgets() Co_widgets()trolDateRa_widgets()ge_Update(w, prefix) {
		var ids = ['week', 'mo_widgets()th', 'custom'];
		var v = Co_widgets()trol_Value(w);
		for (var i = 0; i < ids.le_widgets()gth; i++) {
			var id = ids[i] + '_ra_widgets()ge_' + prefix;
			if (v == ids[i]) {
				$('#' + id).show();
			} else {
				$('#' + id).hide();
			}
		}
	}


	$.f_widgets().co_widgets()trol_date = fu_widgets()ctio_widgets() (optio_widgets()) {
		retur_widgets() this.each(fu_widgets()ctio_widgets() () {
			var $this = $(this), data = $this.data('Co_widgets()trol_Date');
			if (!data) {
				$this.data('Co_widgets()trol_Date', (data = _widgets()ew Co_widgets()trol_Date(this, optio_widgets())))
			}
			if (typeof optio_widgets() == 'stri_widgets()g') data[optio_widgets()].call($this);
		});
	};
	  
	$.f_widgets().co_widgets()trol_date.Co_widgets()structor = Co_widgets()trol_Date;

	$.f_widgets().co_widgets()trol_date.defaults = {
		selector: false,
		us_date: false,
		trigger: 'focus',
		format: '{YYYY}-{MM}-{DD}',
		title: '',
		text_today: 'Today',
	};

	$('.co_widgets()trol_date').co_widgets()trol_date();
}(jQuery);