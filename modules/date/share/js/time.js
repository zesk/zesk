
	/*
	function datetime_hour_name(value, fill) {
		if (value == 0) return gTwelves[0];
		if (value == 12) return gTwelves[1];
		var ampm = parseInt(value / 12) % 2;
		value = value % 12;
		return datetime_formatInt(value, 2, fill) + " " + gAMPM[ampm];
	}

	function datetime_formatInt(value, digits) {
		value = parseInt(value);
		if (digits <= 0) return "" + value;
		var prefix = "";
		var fill = (arguments.length > 2) ? arguments[2] : '0';
		var order = 1;
		var dig = digits;
		while (dig-- != 0) {
			if (value < order) prefix += fill;
			order = order * 10;
		}
		value = (value == 0) ? "" : value % order;
		return prefix + value;
	}

	function datetime_format(action, d) {
		switch (action) {
		case "date":
			return datetime_formatDate(d);
		case "time":
			return datetime_formatTime(d);
		default:
			return datetime_formatDate(d) + " " + datetime_formatTime(d);
		}
	}

	function datetime_formatTime(d) {
		result = "" + datetime_formatInt(d.getHours(), 2) + ":" + datetime_formatInt(d.getMinutes(), 2) + ":" + datetime_formatInt(d.getSeconds(), 2);
		return result;
	}

	function datetime_formatDate(d) {
		var result;
		if (this.options.us_date) {
			result = "" + datetime_formatInt(d.getMonth() + 1, 2) + "/" + datetime_formatInt(d.getDate(), 2) + "/" + datetime_formatInt(d.getFullYear(), 4);
		} else {
			result = "" + datetime_formatInt(d.getFullYear(), 4) + "-" + datetime_formatInt(d.getMonth() + 1, 2) + "-" + datetime_formatInt(d.getDate(), 2);
		}
		return result;
	}

	function datetime_quote(value) {
		return "'" + value + "'";
	}

	function Calendar_LinkJS(s, cur) {
		var done = (arguments.length > 2) ? arguments[2] : true;
		return 'Calendar_Update(' + datetime_quote(datetime_format(s.action, cur)) + ',' + (done ? 'true' : 'false') + ')';
	}

	function DateTime_Value(s) {
		return s.parent.forms[s.form].elements[s.element].value;
	}

	function Date_Dump(s, d) {
		alert(datetime_format(s.action, d));
	}

	function DateTime_GetCurrentDate(s) {
		return datetime_parse(s.action, DateTime_Value(s));
	}


//	function DateTimeWidget_Close(w) {
//		if (w) {
//			IE_DHTML_Hack(w.form, false);
//			ObjectID_VisibleHide(w.divName);
//		}
//	}

//	function DateTimeWidget_ToggleEmpty(button, w, wshow, v, vshow, estring) {
//		if (w.value == '') {
//			w.value = v;
//			wshow.value = vshow;
//			button.src = '/share/zesk/widgets/date/date-zero.gif';
//		} else {
//			if (v != '') button.src = '/share/zesk/widgets/date/date-undo.gif';
//			w.value = '';
//			wshow.value = estring;
//		}
//		return false;
//	}

	function CalendarWidget(element) {
		var v = form.elements[name].value;

		this.form = form;
		this.document = form.document;
		this.action = 'date';
		this.name = name;
		this.divName = 'CalendarDiv_' + name;
		this.displayName = 'Show_' + name;
		this.datetime = new Date();
	}
	function TimeWidget(element) {
		var $elem = this.elem = $(element);
		this.id = $elem.data("id") || $elem.data("name");
		$(element).append("<div id=\""+this.id+"-overlay\"></div>");
		this.overlay = $('#'+this.id+'-overlay');
		this.datetime = new Date();
		this.action = 'time';
	}

	$.extend(TimeWidget.prototype, {
		update: function (newValue, done) {
			this.datetime = datetime_parse(this.action, newValue);
			this.overlay.html(this.html()).show();
			if (done) {
				return this.done();
			}
			return false;
		},
		done: function () {
//			var s = gTimeWidget;
//			DateTimeWidget_Done(s);
//			gTimeWidget = false;
			return false;
		},
		close: function () {
//			var s = gTimeWidget;
//			DateTimeWidget_Close(s);
//			gTimeWidget = false;
			return false;
		},
		html: function () {
			var
			i,
			d = this.datetime,
			hh = d.getHours(),
			m0 = parseInt(d.getMinutes() / 10) * 10,
			mm = d.getMinutes() % 10;

			var x = "";
			x += '<table cellspacing="1" cellpadding="2" border="0" class="date-popup-table">';
			x += '<tr><td class="date-popup-buttons" colspan="3">';
			x += this.now_button_html("Now", "date-popup-today");
			x += '<input type="button" value="Done" class="date-popup-done" onclick="return TimeWidget_Done()">';
			x += '<input type="button" value="Cancel" class="date-popup-cancel" onclick="return TimeWidget_Close();">';
			x += '</td></tr>';
			x += '<tr class="date-popup-month">' + '<td>Hour</td><td colspan="2">Minute</td>' + '</tr>';
			x += '<tr class="time-popup-controls">' + '<td>';
			x += '<select onchange="TimeWidget_SetHour(this)" size="11">';
			for (i = 0; i < 24; i++) {
				x += '<option value="' + i + '"' + ((i === hh) ? ' selected' : '') + '>' + datetime_hour_name(i, '&nbsp;') + '</option>';
			}
			x += '</select>';
			x += '</td>';
			x += '<td>';
			x += '<select onchange="TimeWidget_SetMinute(this, true)" scrollbars="no" size="7">';
			for (i = 0; i < 60; i += 10) {
				x += '<option value="' + i + '"' + ((i === m0) ? ' selected' : '') + '>:' + datetime_formatInt(i, 2) + '</option>';
			}
			x += '</select>';
			x += '</td>';
			x += '<td>';
			x += '<select onchange="TimeWidget_SetMinute(this, false)" size="11">';
			for (i = 0; i < 10; i++) {
				x += '<option value="' + i + '"' + ((i === mm) ? ' selected' : '') + '>' + i + '</option>';
			}
			x += '</select>';
			x += '</td>';
			x += '</tr>';
			x += '</table>';
			return x;
		},
		hour: function (hour) {
			if (hour === null || hour === undefined) {
				return this.datetime.getHours();
			}
			this.datetime.setHours(parseInt($(this.elem).val() || 0));
			return this;
		},
		minute: function (w, tens) {
			var m = this.datetime.getMinutes();
			if (tens) {
				m = m % 10;
			} else {
				m = parseInt(m / 10) * 10;
			}
			var v = parseInt(this.elem.val() || 0);
			m = m + v;
			this.datetime.setMinutes(m);
			this.datetime.setSeconds(0);
		},
		link_js: function (cur, done) {
			return 'TimeWidget_Update(' + datetime_quote(datetime_format(this.action, cur)) + ',' + (done ? 'true' : 'false') + ')';
		},
		now_button_html: function (lnk) {
			return '<input type="button" value="' + lnk + '" + onclick="' + this.link_js(new Date(), true) + '" class="date-popup-today" />';
		}
	});

//	function Time_Popup(form, name) {
//		var
//		v = form.elements[name].value,
//		s = new TimeWidget(form, name);
//
//		gTimeWidget = s;
//
////		IE_DHTML_Hack(form, true);
//		s.update(v);
//
//		return false;
//	}

//	function ControlDateRange_Update(w, prefix) {
//		var
//		i, id,
//		ids = ['week', 'month', 'custom'],
//		v = Control_Value(w);
//		for (i = 0; i < ids.length; i++) {
//			id = ids[i] + '_range_' + prefix;
//			if (v === ids[i]) {
//				$('#' + id).show();
//			} else {
//				$('#' + id).hide();
//			}
//		}
//	}
*/
