<?php

namespace zesk;

class Control_Content_Group_Edit extends Control_Object_Edit {
	function newWidgetList() {
		$spec = $this->editWidgetList();
		unset($spec["Created"]);
		unset($spec["Modified"]);
		return $spec;
	}

	function editWidgetList() {
		$spec = array();
		
		$f = widgets::control_text("CodeName", "CodeName", true, 1, 255);
		$spec[$f->column()] = $f;
		
		$f = widgets::control_text("Name", "Name", true, 1, 255);
		$spec[$f->column()] = $f;
		
		$f = widgets::control_richtext("Body", "Body", false, -1, -1);
		$f->set_option("rows", 10);
		$f->set_option("cols", 80);
		$spec[$f->column()] = $f;
		
		$f = widgets::control_select("OrderMethod", "Ordering", $this->orderMethods());
		$spec[$f->column()] = $f;
		
		$f = widgets::control_image("ImagePath", "Image", false);
		$spec[$f->column()] = $f;
		
		$f = widgets::view_date("Created", "Created", "");
		$spec[$f->column()] = $f;
		
		$f = widgets::view_date("Modified", "Modified", "");
		$spec[$f->column()] = $f;
		
		return $spec;
	}

}
