<?php

class Module_Picker extends zesk\Module_JSLib {
	protected $javascript_paths = array(
		'/share/picker/js/picker.js'
	);

	protected $css_paths = array(
		'/share/picker/css/picker.css'
	);

	function hook_cron() {
		__("No matches found for search &ldquo;{q}&rdquo;.");
	}
}
