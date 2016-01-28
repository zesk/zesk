<?php

class Controller_Index extends Controller_Template {

	public function _action_default($action = null) {
		$this->template->content = "arguments_named" . html::tag('pre', text::format_pairs($this->route->arguments_named()));
		$this->template->content .= "arguments_indexed" . html::tag('pre', text::format_pairs($this->route->arguments_indexed()));
	}
}