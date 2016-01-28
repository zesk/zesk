<?php
class Module_jQueryUI extends Module_JSLib {
	static $debug = false;
	protected $css_paths = array(
		"/share/jqueryui/themes/{theme}/jquery-ui.min.css",
		"/share/jqueryui/themes/{theme}/jquery-ui.css"
	);
	protected $javascript_paths = array(
		"/share/jqueryui/jquery-ui.min.js"
	);
	public function hook_configured() {
		if ($this->has_option("extras")) {
			log::warning("Configuration setting Module_jQueryUI::extras is deprecated");
		}
		$this->css_paths = map($this->css_paths, $this->option() + array(
			"theme" => "base"
		));
	}
	public static function add() {
		zesk::deprecated();
	}
}

