<?php
class Module_UnderscoreJS extends zesk\Module_JSLib {
	protected $javascript_paths = array(
		'/share/underscorejs/underscore.js' => array(
			"weight" => "first"
		)
	);
}
