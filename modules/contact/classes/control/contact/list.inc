<?php

class Control_Contact_List extends Control_Object_List {

	protected $class = "Contact";

	function _widgets() {
		$widgets = array();

		$widgets[] = widgets::control_text("Name");

		return $widgets;
	}

}
