function url_update_title(url_id, title_id, progress_id, error_id)
{
	var if_empty = arguments[4] || false;
	if (if_empty && $('#'+title_id).val() != "") return;
	$('#'+progress_id).html('<img src="/share/images/spinner/spinner-16x16.gif" width="16" height="16" border="0" alt="" />');
	$.get("./title.php?url=" + escape($('#'+url_id).val()), function (d) { $('#'+progress_id).html(""); if (d.substring(0,1) == '!') { $('#' + error_id).html(d.substring(1)) } else { $('#'+ title_id).val(d); } });
}
