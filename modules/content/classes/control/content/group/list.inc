<?php
class Control_Group_List extends Control_Object_List {

	protected $group_class = null;

	protected function _query() {
		$query = parent::_query();
		$query->what_object($this->class);
		$query->what("*Total", "COUNT(G.ID");
		$query->link($this->group_class);
		$query->group_by("X.ID");
	}

	function listWidgetList() {
		$spec = array();
		
		$f = widgets::view_link("Name", "Name", "{Name}", avalue($this->_Spec, 'group_list_uri'));
		$spec[$f->column()] = $f;
		
		$f = widgets::view_text("Total", "Total");
		$f->set_option("empty_string", "<em>None</em>");
		$f->set_option("align", "center");
		$f->set_option("width", "1%");
		$spec[$f->column()] = $f;
		
		$spec["actions"] = widgets::actions();
		
		return $spec;
	}
}
