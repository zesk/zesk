<?php
class Module_LightSlider extends zesk\Module_JSLib {
	protected $css_paths = array(
		'/share/lightslider/css/lightSlider.css'
	);
	protected $javascript_paths = array(
		'/share/lightslider/js/jquery.lightSlider.min.js'
	);
	protected $javascript_settings = array();
	protected $jquery_ready = array(
		'$(".lightslider").lightSlider(zesk.get_path("modules.lightslider", {}));'
	);
}
