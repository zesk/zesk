<?php
class Module_Flot extends Module {
	static $plugins = null;
	public static function head(Request $request, Response_HTML $response) {
		$response->jquery();
		$response->cdn_javascript("/share/flot/jquery.flot.js", array("share" => true));
	}
	private static function flot_js_path() {
		return path(Module::path('flot'), "share");
	}
	private static function _plugins() {
		if (self::$plugins === null) {
			$result = dir::ls(self::flot_js_path(), '/jquery\.flot\.[a-zA-Z0-9]+]\.js/');
			self::$plugins = array_flip(arr::unwrap(arr::flip_copy($result), "jquery.flot.", ".js", true));
		}
		return self::$plugins;
	}
	public static function plugin(Response_HTML $response, $plugins = null) {
		if ($plugins === null) {
			return self::_plugins();
		}
		$plugins = to_list($plugins);
		$path = self::flot_js_path();
		foreach ($plugins as $plugin) {
			$plugin = preg_replace('/[^a-z0-9_-]/', '', strtolower($plugin));
			$js_name = "jquery.flot.$plugin.js";
			$plugin_path = "/share/flot/$js_name";
			if ((is_array(self::$plugins) && array_key_exists($plugin, self::$plugins)) || file_exists($full_path = path($path, $js_name))) {
				$response->cdn_javascript($plugin_path, array(
					"share" => true
				));
			} else {
				throw new Exception_NotFound("Module_Flot plugin not found: {plugin}", array(
					"plugin" => $plugin_path
				));
			}
		}
	}
}
