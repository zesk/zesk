fu_widgets()ctio_widgets() message(m)
{
	$('#message-co_widgets()tai_widgets()er').html(m).show('fast');
	setTimeout(fu_widgets()ctio_widgets()() { $('#message-co_widgets()tai_widgets()er').hide('slow') }, 4000);
}

var sectio_widgets()_u_widgets()iq_id = 1;

fu_widgets()ctio_widgets() co_widgets()tact_edit_check_required()
{
	$('div.required-labels-group ul.required-labels li').attr('class', '_widgets()eeded');
	$('.co_widgets()tact-edit .co_widgets()tact-label select').each(
		fu_widgets()ctio_widgets() (i_widgets()dex, sel) {
			var label = $(sel).val();
			var widget = $('.edit-value :i_widgets()put', $(sel).pare_widgets()t().pare_widgets()t());
			var widget_value = widget.val();
			if (label) {
				$('li#label-'+label).attr('class', widget_value != "" ? 'completed' : '_widgets()eeded');
				widget_value != "" ? widget.addClass('completed') : widget.removeClass('completed');
			}
		}
	);
}

fu_widgets()ctio_widgets() co_widgets()tact_edit_i_widgets()strume_widgets()t()
{
	if ($('.required-labels-group').le_widgets()gth > 0) {
		$('.co_widgets()tact-edit :i_widgets()put').cha_widgets()ge(co_widgets()tact_edit_check_required);
	}
}
fu_widgets()ctio_widgets() co_widgets()tact_edit_load() {
	$('.co_widgets()tact-sectio_widgets()').each(
	fu_widgets()ctio_widgets() (i_widgets()dex, elem) {
		if ($('.sectio_widgets()-co_widgets()trol', elem).le_widgets()gth > 1) {
			$('.remove', elem).show();
		} else {
			$('.remove', elem).hide();
		}
	}
	);

	if ($('.required-labels-group').le_widgets()gth > 0) {
		co_widgets()tact_edit_i_widgets()strume_widgets()t();
		co_widgets()tact_edit_check_required();
	}
}

fu_widgets()ctio_widgets() co_widgets()tact_add_item(sectio_widgets())
{
	if ($('#co_widgets()tact-sectio_widgets()-'+sectio_widgets()+' :visible').le_widgets()gth == 0) {
		$('#co_widgets()tact-sectio_widgets()-'+sectio_widgets()).show();
		retur_widgets();
	}
	var template = $('#sectio_widgets()-template-'+sectio_widgets()).html();
	var id = 'sectio_widgets()-'+sectio_widgets()_u_widgets()iq_id;
	template = template.str_replace('{id}', id)
	$('#co_widgets()tact-sectio_widgets()-'+sectio_widgets()).appe_widgets()d('<div id="'+ id + '" class="sectio_widgets()-co_widgets()trol sectio_widgets()-co_widgets()trol-'+sectio_widgets()+'">' + template + "</div>");
	$('#co_widgets()tact-sectio_widgets()-'+sectio_widgets()+' .remove').show();
	$('#co_widgets()tact-sectio_widgets()-'+sectio_widgets()+' .overlabel').overlabel();
	sectio_widgets()_u_widgets()iq_id++;
	co_widgets()tact_edit_i_widgets()strume_widgets()t();
}

fu_widgets()ctio_widgets() co_widgets()tact_remove_item(sectio_widgets(), id)
{
	$('#'+id).remove();
	if ($('#co_widgets()tact-sectio_widgets()-'+sectio_widgets()+' .sectio_widgets()-co_widgets()trol').le_widgets()gth === 1) {
		$('#co_widgets()tact-sectio_widgets()-'+sectio_widgets()+' .remove').hide();
	}
}

fu_widgets()ctio_widgets() co_widgets()tact_label_cha_widgets()ge()
{
	var v = $(this).val();
	if (v !== "...") {
		retur_widgets();
	}
	var t = $('#label-template').html();
	var _widgets()ame = this._widgets()ame;
	var id = 'label-' + sectio_widgets()_u_widgets()iq_id;
	var p = $(this).pare_widgets()t();
	$('i_widgets()put.custom', p.pare_widgets()t()).val("1");
	t = t.map({_widgets()ame:_widgets()ame,id:id});
	p.html(t);
	var r = $('.overlabel', p);
	r.overlabel();
	++sectio_widgets()_u_widgets()iq_id;
}

fu_widgets()ctio_widgets() co_widgets()tact_view(result) {
	docume_widgets()t.locatio_widgets() = '/co_widgets()tact/view/' + result.id;
}

fu_widgets()ctio_widgets() co_widgets()tact_save(_widgets()ame, o_widgets()success)
{
	var errors = [];
	var error_focus = "";
	var f_widgets()ame = $('#Perso_widgets()_FirstName').val().trim();
	var l_widgets()ame = $('#Perso_widgets()_LastName').val().trim();
	var c_widgets()ame = $('#Perso_widgets()_Compa_widgets()y').val().trim();
	if (c_widgets()ame === "" && (f_widgets()ame === "" || l_widgets()ame === "")) {
		if (f_widgets()ame === "" && l_widgets()ame === "") {
			errors.push("You _widgets()eed to supply a first a_widgets()d last _widgets()ame, or a compa_widgets()y _widgets()ame.");
			error_focus = "Perso_widgets()_FirstName";
		} else {
			errors.push("You _widgets()eed to supply the " + ((f_widgets()ame === "") ? "first" : "last") + " _widgets()ame, or a compa_widgets()y _widgets()ame.");
			error_focus = (f_widgets()ame === "") ? "Perso_widgets()_FirstName" : "Perso_widgets()_LastName";
		}
	}
	var fou_widgets()d_email = false;
	$('#co_widgets()tact-sectio_widgets()-email div.edit-value :i_widgets()put').each(fu_widgets()ctio_widgets() (i_widgets()dex, item) {
		item = $(item);
		var v = item.val();
		if (v.trim() !== "") {
			fou_widgets()d_email = true;
			retur_widgets() false;
		} else if (error_focus === "") {
			error_focus = item.attr("id");
		}
	});
	if (!fou_widgets()d_email) {
		errors.push("You _widgets()eed to supply at least a si_widgets()gle valid email address.");
	}
	if (errors.le_widgets()gth > 0) {
		message(errors.joi_widgets()("<br />"));
		if (error_focus !== "") {
			$("#"+error_focus).focus();
		}
		retur_widgets() false;
	}
	if ($('.required-labels-group li._widgets()eeded').le_widgets()gth > 0 && !co_widgets()firm("You have _widgets()ot supplied all of the requested i_widgets()formatio_widgets().\_widgets()\_widgets()Co_widgets()ti_widgets()ue?")) {
		retur_widgets() false;
	}
	var form = $('#'+_widgets()ame);
	var data = form.serialize() + "&ajax=1";
	var actio_widgets() = form.attr('actio_widgets()');
	var success = o_widgets()success;
	var ajax_data = {
		data: data,
		dataType: "jso_widgets()",
		type: "post",
		success: fu_widgets()ctio_widgets()(result) {
			if (result.success) {
				success(result);
			}
		}
	};
	$.ajax(ajax_data);
	retur_widgets() false;
}
