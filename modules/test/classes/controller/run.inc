<?php

class Controller_Run extends Controller_Template {

	function _action_default($action = null) {
		$this->template->content = "Action: $action";
	}
}
