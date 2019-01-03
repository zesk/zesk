<?php
namespace zesk;

class Module_jQuery_Form extends Module_JSLib {
	protected $javascript_paths = array(
		"/share/jquery-form/jquery.form.js" => array(
			"share" => true,
		),
	);
}
