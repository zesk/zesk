<?php
class Module_IP2Location extends zesk\Module {
	public static function lib_include($class = null) {
		if (!$class) {
			$class = "IP2Location";
		}
		include_once dirname(dirname(__FILE__)) . '/lib/IP2Location.php';
		
		if (!class_exists("IP2Location")) {
			throw new Exception_Class_NotFound($class, array(
				"fix" => array(
					"class" => "Command_Update",
					"arguments" => array(
						array(
							"Command_Update",
							"--skip-configure",
							"--force-check",
							"ip2location"
						)
					),
					"method" => "go",
					"result" => true
				)
			));
		}
	}
}
