var layouts = {};

function list_remove(list, item)
{
	var newlist = new Array();
	list = list.split(";");
	for (var i = 0; i < list.length; i++) { if (list[i] != item) { newlist.push(list[i]); } }
	return newlist.join(";");
}


function list_contains(list, item)
{
	var newlist = list.split(";");
	for (var i = 0; i < newlist.length; i++) { if (newlist[i] == item) return true; }
	return false;
}

function layout_cell_height(list)
{
	var h = 0;
	$.each(list.split(";"), function (i,e) { h += $("#"+e).outerHeight(); });
	return h;
}

function ControlLayout(name, n_objects)
{
	this.name = name;
	this.n_objects = n_objects;
	this.grid_state = {}

	$('.layout-object').draggable({
		revert: "invalid"
	});
	this.update();
	this.adjustCells(0);
	layouts[name] = this;
}

ControlLayout.selected = function (w)
{
	var name = $(this.target).parent().attr("id").split("-")[0];
	layouts[name].selected(this.target);
};

ControlLayout.prototype = {
	drop: function (ev, ui) {
		var drop_id = $(ui.element).attr("id");
		var pos = $(ui.element).position();
		var width = $(ui.element).width() - 10;
		var drag_id = $(ui.draggable).attr("id");

		var grid_state = this.grid_state;
		var from_drop_id = null

		$.each(grid_state, function (idrop_id,vals) {
			if (list_contains(vals, drag_id)) {
				from_drop_id = idrop_id;
				grid_state[idrop_id] = list_remove(vals, drag_id);
				var height = Math.max(layout_cell_height(vals),50);
				if (grid_state[idrop_id].length > 0) {
					$("#"+idrop_id).animate({height: height + "px"},250);
				}
			}
		});
		if (from_drop_id == drop_id) from_drop_id = null;
		var cell = grid_state[drop_id];
		var new_cell = new Array();
		var new_height = 0;
		if (cell.length > 0) {
			$.each(
				cell.split(";"),
				function(off,cell_id) {
					if (cell_id != drag_id) {
						var height = $("#"+cell_id).outerHeight();
						new_height += height;
						//console.log(cell_id + " height is " + height);
						pos.top += height;
						new_cell.push(cell_id);
					}
				}
			);
		}
		new_cell.push(drag_id);
		if (from_drop_id) {
			this.adjustCell(from_drop_id);
		}
		new_height += $("#"+drag_id).outerHeight();
		this.grid_state[drop_id] = new_cell.join(";");
		$("#"+drop_id).animate({height: new_height + "px"},250);
		$(ui.draggable).animate({left: pos.left, top: pos.top, width: width + "px"}, 250);
//		console.log('x=' + pos.left + ' y=' + pos.top);
//		console.log($(ui.element).attr("id"));

		this.updateField();
	},
	adjustCells: function () {
		var speed = arguments[0] ? arguments[0] : 250;
		$.each(this.grid_state, function (idrop_id,vals) {
			var height = Math.max(layout_cell_height(vals),50);
			$("#"+idrop_id).animate({height: height + "px"},250);
			var pos = $('#'+idrop_id).position();
			pos.width = $('#'+idrop_id).innerWidth() + "px";
			$.each(vals.split(';'),function (i,idrag_id) {
				$('#'+ idrag_id).animate(pos, speed);
				pos.top += $('#'+idrag_id).outerHeight();
			});
		});
	},
	adjustCell: function (idrop_id) {
		var vals = this.grid_state[idrop_id];
		var pos = $('#'+idrop_id).position();
		var total_height = 0, height;
		$.each(vals.split(';'),function (i,idrag_id) {
			$('#'+ idrag_id).animate(pos);
			height = $('#'+idrag_id).outerHeight();
			pos.top += height;
			total_height += height;
		});
		total_height = Math.max(total_height, 70);
		$("#"+idrop_id).animate({height: total_height});
	},
	selected: function(w) {
		this.opt = $(w).val();
		var opt = this.opt;
		opt = opt.split("|",2);
		$("#Widths_" + this.name).val(opt[1]);
		opt = opt[0].split("x",2);
		$("#Cols_" + this.name).val(opt[0]);
		$("#Rows_" + this.name).val(opt[1]);
		this.grid_state = {};
		this.update();
		this.adjustCells();
	},
	selector: function() {
		var opts = {};
		if (this.n_objects >= 1) {
			opts['1x1|1'] = "Single Column";
		}
		if (this.n_objects >= 2) {
			opts['2x1|1;1'] = "Two Column (1:1)";
			opts['2x1|2;1'] = "Two Column (2:1)";
			opts['2x1|3;1'] = "Two Column (3:1)";
			opts['2x1|1;2'] = "Two Column (1:2)";
			opts['2x1|1;3'] = "Two Column (1:3)";
		}
		if (this.n_objects >= 3) {
			opts['3x1|1;1;1'] = "Three Column (1:1:1)";
			opts['3x1|2;1;1'] = "Three Column (2:1:1)";
			opts['3x1|3;1;1'] = "Three Column (3:1:1)";
			opts['3x1|1;2;1'] = "Three Column (1:2:1)";
			opts['3x1|1;3;1'] = "Three Column (1:3:1)";
			opts['3x1|1;1;2'] = "Three Column (1:1:2)";
			opts['3x1|1;1;3'] = "Three Column (1:1:3)";
		}
		if (this.n_objects >= 4) {
			opts['2x2|1;1'] = "2x2 Grid (1:1)";
			opts['2x2|2;1'] = "2x2 Grid (2:1)";
			opts['2x2|3;1'] = "2x2 Grid (3:1)";
			opts['2x2|1;1'] = "2x2 Grid (1:1)";
			opts['2x2|1;2'] = "2x2 Grid (1:2)";
			opts['2x2|1;3'] = "2x2 Grid (1:3)";

			opts['3x2|1;1;1'] = "3x2 Grid (1:1:1)";
			opts['3x2|2;1;1'] = "3x2 Grid (2:1:1)";
			opts['3x2|1;2;1'] = "3x2 Grid (1:2:1)";
			opts['3x2|1;3;1'] = "3x2 Grid (1:3:1)";
			opts['3x2|1;1;2'] = "3x2 Grid (1:1:2)";
		}
		var html = "<select name=\"" + this.name + "_Select\" id=\"" + this.name + "_Select\">";
		for (var opt in opts) {
			html += "<option value=\"" + opt + "\""+((opt == this.opt) ? ' selected="selected"' : '')+">" + opts[opt] + "</option>";
		}
		return html + "</select>";
	},
	update: function ()
	{
		var name 	= this.name;
		var cols 	= $('#Cols_'+name).val();
		var rows	= $('#Rows_'+name).val();
		var widths	= $('#Widths_'+name).val();
		var objects	= ($('#Objects_'+name).val()+'').split('|');

		var x, y, w, wtot, ratio;
		var table;

		this.opt = cols + "x" + rows + "|" + widths;

		widths = widths.split(';');
		if (widths.length < cols) {
			widths.push("1");
		}
		wtot = 0;
		for (w = 0; w < widths.length; w++) {
			if (widths[w] == "") widths[w] = 1;
			wtot += parseInt(widths[w]);
		}
		wtot = Math.max(parseInt(wtot),1);
		ratio = 600 / wtot;
		for (w = 0; w < widths.length; w++) {
			widths[w] = parseInt(widths[w] * ratio);
		}
		table = "<table class=\"layout-table\">";
		var i = 0;
		var id;
		for (y = 0; y < rows; y++) {
			table += "<tr>";
			var x_state = new Array();
			for (x = 0; x < cols; x++) {
				id = "grid-"+x+"x"+y;
				w = widths[x];
				table += "<td valign=\"top\" style=\"width: "+w+"px\"><div class=\"layout-droppable\" id=\""+id+"\"></div></td>";
				this.grid_state[id] = objects[i] ? objects[i] : "";
				i++;
			}
			table += "</tr>";
		}
		table += "</table>";
		while (i < objects.length) {
			this.grid_state[id] += ";" + objects[i];
			i++;
		}
		$('#'+name+"-grid").html(this.selector() + table);
		$('#'+name+"_Select").bind("change", function(e,w) { ControlLayout.selected.call(e,w); });
		$('.layout-droppable').droppable({
			accept: ".layout-object",
			activeClass: 'layout-droppable-active',
			hoverClass: 'layout-droppable-hover',
			drop: function (ev, ui) {
				var name = $(ui.draggable).parent().attr("id").split("-")[0];
				var layout = layouts[name];
				return layout.drop(ev, ui);
			}
		});
	},
	updateField: function() {
		var r = new Array();
		for (var obj in this.grid_state) {
			r.push(this.grid_state[obj]);
		}
		$("#Objects_"+this.name).val(r.join('|'));
	}
}