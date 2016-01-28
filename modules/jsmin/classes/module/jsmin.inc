<?php
class Module_JSMin extends Module {
	private static function jsmin_inc() {
		$module_path = Module::path("jsmin");
		$inc = path($module_path, 'lib/jsmin.php');
		return $inc;
	}
	public static function validate() {
		$jsmin_inc = self::jsmin_inc();
		// Something like valid_php_file($jsmin_inc), etc.
		return true;
	}
	public static function hooks() {
		zesk::add_hook("Response_HTML::compress_script", __CLASS__ . "::compress_script");
	}
	public static function compress_script(Response_HTML $response, $javascript) {
		require_once self::jsmin_inc();
		return JSMin::minify($javascript);
	}
}

