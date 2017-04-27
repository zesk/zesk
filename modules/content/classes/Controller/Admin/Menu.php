<?php

use zesk\Controller_Template_Login;

class Controller_Admin_Menu extends Controller_Template_Login {
	
	function action_index() {
		$options = array();
		$options[''] = '';
		
		$widget = new Control_Content_Menu_Tree($options);
		$this->template->content = $widget->execute();
	}
}
