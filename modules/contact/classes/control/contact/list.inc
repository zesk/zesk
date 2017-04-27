<?php
namespace zesk;

class Control_Contact_List extends Control_List {
	protected $class = "zesk\\Contact";
	function hook_widgets() {
		$widgets = array();
		
		$widgets[] = $this->widget_factory("zesk\\View_Text")->names("name");
		
		return $widgets;
	}
}
