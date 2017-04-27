<?php

namespace zesk;

abstract class Control_List_InPlace extends Control_List {
	protected $theme_empty = "zesk/control/list/inplace/empty";
	protected $theme_widgets = "zesk/control/list/inplace/widgets";
	protected $theme_prefix = "zesk/control/list/inplace/prefix";
	protected $theme_suffix = "zesk/control/list/inplace/suffix";
	protected $theme_header = "zesk/control/list/inplace/header";
	protected $theme_footer = "zesk/control/list/inplace/footer";
	protected $widget_attributes = array();
	protected $list_tag = "div";
	protected $list_attributes = array(
		"class" => "list list-inplace"
	);

	protected $total_title = null;

	protected $hierarchy_column = null;

	protected $row_attributes = array(
		"class" => "row",
		"id" => "row-{id}",
		"data-id" => "{id}",
	);

	protected function initialize() {
		parent::initialize();
		$id = $this->id();
		if (!$id) {
			$this->id($id = 'control-list-inplace-' . $this->response->id_counter());
		}
		$this->list_attributes['id'] = $id;
		$this->wrap('div', '.control-list-inplace');
	}
	protected function hook_widgets() {
		$widgets[] = $w = $this->widget_factory('zesk\Control_Hidden')->names('id', __('ID'), 'id[]')->id(false)->add_class('selection-id');
		$widgets[] = $w = $this->widget_factory('zesk\View_Text')->names('name');

		return $widgets;
	}

	public function theme_variables() {
		return array(
			'hierarchy_column' => $this->hierarchy_column,
			'total_title' => __($this->total_title),
			'target' => $this->option('target'),
			'dismiss_button' => $this->option_bool("dismiss_button", true),
			'current_user' => $this->user()
		) + parent::theme_variables();
	}

	public function hierarchy_column($set = null) {
		if ($set === null) {
			return $this->hierarchy_column;
		}
		$this->hierarchy_column = $set;
		return $this;
	}
}
