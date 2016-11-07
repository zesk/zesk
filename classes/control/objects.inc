<?php

class Control_Objects extends Control_Text {

	protected $class = null;

	protected $class_results = "";

	protected $theme_object = null;
	protected $controller = null;
	protected $controller_url = null;

	protected function initialize() {
		parent::initialize();
		if ($this->theme_object === null) {
			$this->theme_object = $this->theme . "/object";
		}
		if ($this->controller === null) {
			$this->controller = str::unprefix(strtolower(get_class($this)), "control_");
		}
		if ($this->controller_url === null) {
			$this->controller_url = "/control/$this->controller/" . $this->column() . "/" . $this->name();
		}
	}
	protected function load() {
		$value = $this->request->geta($this->name());
		$objects = array();
		foreach ($value as $id) {
			try {
				$objects[$id] = $objects = $this->application->object_factory($this->class, $id)->fetch();
			} catch (Exception $e) {
				global $zesk;
				$zesk->hooks->call("exception", $e);
				return false;
			}
		}
		$this->value($objects);
		return true;
	}

	public function theme_variables() {
		return array(
			'theme_object' => $this->theme_object,
			'controller' => $this->controller,
			'controller_url' => $this->controller_url
		) + parent::theme_variables();
	}
}
