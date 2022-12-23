function markdown_preview(id) {
	var data_key = 'markdown-timer';
	var last_value = $(id).data(data_key+'-last-value');
	var value = $(this).val();
	if (value === last_value) {
		return;
	}
	var timer = $(id).data(data_key);
	var now = (new Date()).getTime();
	if (timer) {
		var last = $(id).data(data_key + '-last');
		console.log('now='+ now);
		if (!last || (now - last < 2500)) {
			clearTimeout(timer);
			console.log('cleared');
		} else {
			return;
		}
	}
	timer = setTimeout(function() {
		jQuery.post('/markdown', {markdown: value}, function (data) {
		$(id).data(data_key + '-last', now);
			console.log('last-post=' + now);
			$(id).data(data_key + '-last-value', value);
			$(id).html(data);
			$(id).data(data_key, null);
		});
	}, 600);
	$(id).data(data_key, timer);
}

$(document).ready(function () {
	$('textarea.markdown').each(markdown_preview);
	$('textarea.markdown').autoResize();
});
