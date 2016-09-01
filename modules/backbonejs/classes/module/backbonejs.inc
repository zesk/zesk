<?php
class Module_BackboneJS extends Module_JSLib {
	protected $javascript_paths = array(
		"/share/backbonejs/backbone.js" => array(
			"after" => "underscorejs"
		)
	);
}
