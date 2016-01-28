<?php
class Control_Menu_Layout extends Control_Widgets {

	function _widgets() {
		$spec = array();

		$f = widgets::view_text("name", "Name");
		$spec[] = $f;

		$f = widgets::control_layout("layout", "Layout", false, "ContentObjects");
		$spec[] = $f;

		return $spec;
	}
}
