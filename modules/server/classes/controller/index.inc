<?php

class Controller_Index extends Controller_Template {

	protected $template = "page/manage.tpl";

	function action_index() {
		$widgets = to_list("disk;services;load;apache;php;configuration");
		$this->template->content = Template::instance("body/dashboard.tpl", array("widgets" => $widgets));
	}
}

