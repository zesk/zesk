<?php

class Module_Chosen extends Module_JSLib {

	protected $css_paths = array(
		'/share/chosen/chosen.css'
	);

	protected $javascript_paths = array(
		'/share/chosen/chosen.jquery.js'
	);

	protected $javascript_settings_inherit = array(
		"width" => null,
		"disable_search_threshold" => 5
	);

	protected $jquery_ready = array(
		"\$('.chosen-select').chosen(zesk.get_path('modules.chosen'));"
	);

// 	public static function hooks() {
// 		zesk::add_hook('Control_Select::initialized', function (Control_Select $select) {
// 			$select->add_class('chosen-select');
// 		});
// 	}
	public function initialize() {
		parent::initialize();
		$classes = $this->option_list("hook_classes", "Control_Select");
		foreach ($classes as $class) {
			zesk::add_hook("$class::initialized", function (Control_Select $widget) {
				$widget->add_class("chosen-select");
			});
		}
	}
}
