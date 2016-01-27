function zesk_datepicker_options(input_name/*, option, default=null */)
{
	var dateopts = window[input_name+"_datepicker_options"];
	var option = arguments[1] || null;
	var def = arguments[2] || null;
	if (!dateopts) return {};
	if (option) {
		return dateopts[option] || def;
	}
	return dateopts;
}

function zesk_datepicker_sync_date(dateopts, name) {
	try {
		var d = $.datepicker.parseDate(dateopts.dateFormat, $('#'+name+'_display').val());
		if (d < dateopts.minDate) {
			d = dateopts.minDate;
		}
		if (d > dateopts.maxDate) {
			d = dateopts.maxDate;
		}
		$('#'+name).val($.datepicker.formatDate(dateopts.altFormat, d));
		$('#'+name+'_display').val($.datepicker.formatDate(dateopts.dateFormat, d));
	} catch (e) {
	}
}
function zesk_datepicker_sync_dates(input_name) {
	var dateopts = zesk_datepicker_options(input_name);
	zesk_datepicker_sync_date(dateopts, dateopts.start_column);
	zesk_datepicker_sync_date(dateopts, dateopts.end_column);
	zesk_datepicker_update_locale_string(dateopts);
}
function zesk_datepicker_configure(dateopts)
{
	var input_name = dateopts.input_name;
	try { dateopts.minDate = $.datepicker.parseDate('yy-mm-dd', $('#'+input_name+'_minDate').val()); } catch (e) {}
	try { dateopts.maxDate = $.datepicker.parseDate('yy-mm-dd', $('#'+input_name+'_maxDate').val()); } catch (e) {}
	try { dateopts.defaultDate = dateopts.maxDate; } catch (e) {}

	dateopts.altFormat = 'yy-mm-dd';

	dateopts.altField = '#'+dateopts.start_column;
	$(dateopts.altField+'_display').datepicker(dateopts);
	$(dateopts.altField+'_display').blur(function() { zesk_datepicker_sync_dates(input_name) });

	dateopts.altField = '#'+dateopts.end_column;
	$(dateopts.altField+'_display').datepicker(dateopts);
	$(dateopts.altField+'_display').blur(function() { zesk_datepicker_sync_dates(input_name) });

	zesk_datepicker_update_locale_string(dateopts);
}

function zesk_datepicker_update_locale_string(dateopts)
{
	var input_name = dateopts.input_name
	var language = zesk_datepicker_options(input_name, "language", "en");
	var start_date = new Date();
	var end_date = new Date();
	try { start_date = $.datepicker.parseDate('yy-mm-dd', $('#'+dateopts.start_column+'').val()); } catch (e) {}
	try { end_date = $.datepicker.parseDate('yy-mm-dd', $('#'+dateopts.end_column+'').val()); } catch (e) {}
	if (start_date > end_date) {
		var t = end_date;
		end_date = start_date;
		start_date = t;
	}
	$("#"+input_name+"_locale_date").html(start_date.formatRange(end_date, language));

}

function zesk_datepicker_update(v, input_name)
{
	var dateopts = zesk_datepicker_options(input_name);
	var start_column = dateopts.start_column, end_column = dateopts.end_column;
	v = v.split('-');
	if (v == 'custom') {
		return;
	}
	var unit = v[0];
	var soff = parseInt(v[1]);
	var eoff = parseInt(v[2]);

	var startDate 	= new Date();
	startDate.setUTCHours(0);
	startDate.setUTCMinutes(0);
	startDate.setUTCSeconds(0);
	startDate.setUTCMilliseconds(0);

	var endDate		= new Date();
	endDate.setTime(startDate.getTime());
	endDate.setUTCHours(23);
	endDate.setUTCMinutes(59);
	endDate.setUTCSeconds(59);
	endDate.setUTCMilliseconds(999);

	switch (unit) {
		case "week":
			var weekday = (startDate.getUTCDay() - dateopts.first_day_of_week);
			if (weekday < 0) { weekday += 7; }
			startDate.add(0,0,-weekday);
			endDate.add(0,0,-weekday+6);
			startDate.add(0,0,-7*soff);
			endDate.add(0,0,-7*eoff);
			break;
		case "quarter":
			var m = startDate.getUTCMonth();
			m = (parseInt((m) / 3) * 3);
			startDate.setUTCMonth(m);
			startDate.setUTCDate(1);
			endDate.setUTCMonth(m);
			endDate.setUTCDate(1);
			startDate.add(0,-soff * 3,0);
			endDate.add(0,-eoff * 3,0);
			// Add three months and subtract one last. Avoids Q1 ending on March 30th (June 30th - 3 months)
			endDate.add(0,3,-1);
			break;
		case "month":
			startDate.setUTCDate(1);
			endDate.setUTCDate(1);
			startDate.add(0,-soff);
			endDate.add(0,-eoff+1,-1);
			break;
		case "year":
			startDate.setUTCMonth(0);
			startDate.setUTCDate(1);
			endDate.setUTCMonth(0);
			endDate.setUTCDate(1);
			endDate.add(1,0,-1);
			startDate.add(soff * -1);
			endDate.add(eoff * -1);
			break;
		case "day":
		default:
			dateUnit = "day";
			startDate.add(0,0,-soff);
			endDate.add(0,0,-eoff);
			break;
	}

	$('#'+start_column).val(startDate.dateString());
	$('#'+start_column+'_display').val($.datepicker.formatDate(dateopts.dateFormat,startDate));
	$('#'+end_column).val(endDate.dateString());
	$('#'+end_column+'_display').val($.datepicker.formatDate(dateopts.dateFormat,endDate));

	zesk_datepicker_update_locale_string(dateopts);
	//zesk_datepicker_sync_dates(input_name);
}
