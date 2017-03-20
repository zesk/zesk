<?php
class Module_Selenium extends zesk\Module {
	public static function is_enabled() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->configuration->path_get(array(
			"Test_Selenium",
			"enabled"
		));
	}
}
