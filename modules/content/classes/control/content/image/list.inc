<?php
class Control_Content_Image_List extends Control_Object_List {
	function _widgets() {
		$spec = array();

		$f = widgets::view_link("Name", "Title", "{Name}", "");
		$spec[$f->column()] = $f;

		$f = widgets::view_date("Released", "Released", "{mm}/{dd}/{yyyy}");
		$spec[$f->column()] = $f;

		$f = widgets::view_text("Summary", "Summary", 200);
		$spec[$f->column()] = $f;

		$f = widgets::control_order("OrderIndex", "Order", $this->table());
		$spec[$f->column()] = $f;

		$spec["actions"] = widgets::actions();

		return $spec;
	}
}