<?php

namespace server;

class Controller_Index extends \zesk\Controller_Template {

	protected $template = "page/manage.tpl";

	function action_index() {
		$widgets = to_list("disk;services;load;apache;php;configuration");
		$this->template->content = \zesk\Template::instance("body/dashboard.tpl", array("widgets" => $widgets));
	}
}

