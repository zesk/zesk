function iplist_add(id, ip)
{
	var v = $('#'+id).val();
	v = v.replace(/[\s,]+/, " ");
	v = v.split(" ");
	for (var i = 0; i < v.length; i++) {
		if (v[i] == ip) return;
	}
	$('#'+id).val(ip + "\n" + $('#'+id).val());
}