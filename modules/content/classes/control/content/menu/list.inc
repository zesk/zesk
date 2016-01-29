<?php

class Control_Menu_List extends Control_List {

	function _query() {
		$query = parent::_query();
		$query->what("ID", "X.ID");
		$query->what("Name", "X.Name");
		$query->what("*Submenus", "COUNT(SUB.ID)");
		$query->group_by("X.ID");
		$query->join("LEFT OUTER JOIN {Menu} SUB ON SUB.Parent=X.ID");
		return parent::query();
	}

	function _widgets() {
		$spec = array();

		$f = widgets::view_link("Name", "Title", "{Name}", "");
		$spec[$f->column()] = $f;

		if ($this->request->get("parent") === false) {
			$f = $this->widget_factory("View_Text")->names("submenus", __("Submenus"));
			$f->set_option("format", "{submenus} <a href=\"?Parent={id}\">Manage</a>");
			$f->set_option("list_order_by", "COUNT(SUB.ID)");
			$spec[$f->column()] = $f;
		}

		$f = $this->widget_factory('Control_Order')->names("order_index", "Order");
		$f->set_option("where", array(
			"parent" => $this->request->get("parent", null)
		));
		$f->set_option("default_list_order_by", true);
		$spec[$f->column()] = $f;

		$spec["actions"] = $this->widget_factory('View_Actions');

		return $spec;
	}
}
