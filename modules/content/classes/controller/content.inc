<?php

class Controller_Content extends Controller_Template_Login {
	
	function _action_default($action=null) {
		$this->template->content = "$action - " . get_class($this);
	}
}
