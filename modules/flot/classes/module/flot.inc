<?php
class Module_Flot extends Module implements Module_Interface_Head {
	
	/**
	 *
	 * @var unknown
	 */
	protected $plugins = null;
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Module_Interface_Head::hook_head()
	 */
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		$this->_head($response);
	}
	private function _head(Response_HTML $response) {
		$response->jquery();
		$response->cdn_javascript("/share/flot/jquery.flot.js", array(
			"share" => true
		));
	}
	
	/**
	 * Retrieve the JS path for this module
	 *
	 * @return string
	 */
	private function flot_js_path() {
		return $this->application->application_root($this->option("share_path"));
	}
	
	/**
	 */
	private function _plugins() {
		if ($this->plugins === null) {
			$result = dir::ls($this->flot_js_path(), '/jquery\.flot\.[a-zA-Z0-9]+]\.js/');
			$this->plugins = array_flip(arr::unwrap(arr::flip_copy($result), "jquery.flot.", ".js", true));
		}
		return $this->plugins;
	}
	public function plugin(Response_HTML $response, $plugins = null) {
		if ($plugins === null) {
			return $this->_plugins();
		}
		$plugins = to_list($plugins);
		$path = $this->flot_js_path();
		$this->_head($response);
		foreach ($plugins as $plugin) {
			$plugin = preg_replace('/[^a-z0-9_-]/', '', strtolower($plugin));
			$js_name = "jquery.flot.$plugin.js";
			$plugin_path = "/share/flot/$js_name";
			if ((is_array($this->plugins) && array_key_exists($plugin, $this->plugins)) || file_exists($full_path = path($path, $js_name))) {
				$response->cdn_javascript($plugin_path, array(
					"share" => true
				));
			} else {
				throw new Exception_File_NotFound($full_path, "Module_Flot plugin not found: {plugin}", array(
					"plugin" => $plugin_path
				));
			}
		}
	}
}
