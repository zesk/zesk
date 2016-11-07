<?php
class Module_JSPacker extends Module {
	public static function include_file() {
		$module_path = Module::path("jspacker");
		$php_file = path($module_path, 'lib/class.JavaScriptPacker.php');
		return $php_file;
	}
	public static function installed() {
		$module_path = Module::path("jspacker");
		$php_file = path($module_path, 'lib/class.JavaScriptPacker.php');
		if (!is_file($php_file)) {
			return "$php_file does not exist, run `zesk update jspacker` to fix.";
		}
	}
	public static function hooks(zesk\Hooks $hook) {
		$hook->add("zesk\Response_Text_HTML::compress_script", __CLASS__ . "::compress_script");
	}
	public static function compress_script(zesk\Response_Text_HTML $response, $contents) {
		require_once self::include_file();
		$packer = new JavaScriptPacker($contents);
		return $packer->pack();
	}
}
