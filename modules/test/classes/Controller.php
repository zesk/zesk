<?php

class Controller_Home extends Controller_Template {
	public $template = "body/default.tpl";

	function _action_default($action = null) {
		$this->template->content = "Action: " . _dump($action);
	}
}
