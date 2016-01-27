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

function zesk_datepicker_configure(input_name, start_column, end_column)
{
	var dateopts = zesk_datepicker_options(input_name);
	try { dateopts.minDate = $.datepicker.parseDate('yy-mm-dd', $('#'+input_name+'_minDate').val()); } catch (e) {}
	try { dateopts.maxDate = $.datepicker.parseDate('yy-mm-dd', $('#'+input_name+'_maxDate').val()); } catch (e) {}
	try { dateopts.defaultDate = dateopts.maxDate; } catch (e) {}
	dateopts.dateFormat = 'yy-mm-dd';
	dateopts.onSelect = function(dateText) {
		dateText = dateText.split('|');
		$('#'+input_name+'_select').val('custom');
		$('#'+start_column).val(dateText[0]);
		$('#'+end_column).val(dateText[1]);
		zesk_datepicker_update_locale_string(input_name, start_column, end_column);
	}
	dateopts.rangeSeparator = '|';
	$('#'+input_name).datepicker("destroy");
	if (dateopts.minDate && dateopts.maxDate) {
		var nmo = ((dateopts.maxDate.getYear() * 12) + dateopts.maxDate.getMonth()) - ((dateopts.minDate.getYear() * 12) + dateopts.minDate.getMonth()) + 1;
		if (nmo < 1) nmo = 1;
		if (nmo > 3) nmo = 5;
		dateopts.numberOfMonths = nmo;
		dateopts.stepMonths = 1;
		$('#'+input_name).datepicker(dateopts);
	}

	zesk_datepicker_set_range(input_name, start_column, end_column);
}

function zesk_datepicker_update_locale_string(input_name, start_column, end_column)
{
	var language = zesk_datepicker_options(input_name, "language", "en");
	var start_date = new Date();
	var end_date = new Date();
	try { start_date = $.datepicker.parseDate('yy-mm-dd', $('#'+start_column+'').val()); } catch (e) {}
	try { end_date = $.datepicker.parseDate('yy-mm-dd', $('#'+end_column+'').val()); } catch (e) {}

	$("#"+input_name+"_locale_date").html(start_date.formatRange(end_date, language));

}
function zesk_datepicker_set_range(input_name, start_column, end_column)
{
	var language = zesk_datepicker_options(input_name, "language", "en");
	var start_date = new Date();
	var end_date = new Date();
	try { start_date = $.datepicker.parseDate('yy-mm-dd', $('#'+start_column+'').val()); } catch (e) {}
	try { end_date = $.datepicker.parseDate('yy-mm-dd', $('#'+end_column+'').val()); } catch (e) {}

	$('#'+input_name).datepicker('setDate', null, null);
	$('#'+input_name).datepicker('setDate', start_date, end_date);

	zesk_datepicker_update_locale_string(input_name, start_column, end_column, language);
}

function zesk_datepicker_update(v, input_name, start_column, end_column)
{
	var dateopts = zesk_datepicker_options(input_name);
	v = v.split('-');
	if (v == 'custom') {
		return;
	}
	var unit = v[0];
	var soff = parseInt(v[1]);
	var eoff = parseInt(v[2]);

	var startDate 	= new Date();
	startDate.setHours(0);
	startDate.setUTCMinutes(0);
	startDate.setUTCSeconds(0);
	startDate.setUTCMilliseconds(0);

	var endDate		= new Date();
	endDate.setTime(startDate.getTime() + 1);

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
	$('#'+end_column).val(endDate.dateString());

	zesk_datepicker_set_range(input_name, start_column, end_column);
}
