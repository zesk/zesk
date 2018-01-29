<?php
/**
 * 
 */
use zesk\ArrayTools;
use zesk\Directory;
use zesk\Exception_File_NotFound;

/**
 * 
 * @author kent
 *
 */
class Module_Flot extends zesk\Module implements \zesk\Interface_Module_Head {
	
	/**
	 *
	 * @var unknown
	 */
	protected $plugins = null;
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \zesk\Interface_Module_Head::hook_head()
	 */
	public function hook_head(zesk\Request $request, zesk\Response $response, zesk\Template $template) {
		$this->_head($response);
	}
	private function _head(zesk\Response $response) {
		$response->jquery();
		$response->javascript("/share/flot/jquery.flot.js", array(
			"share" => true
		));
	}
	
	/**
	 * Retrieve the JS path for this module
	 *
	 * @return string
	 */
	private function flot_js_path() {
		return $this->application->path($this->option("share_path"));
	}
	
	/**
	 */
	private function _plugins() {
		if ($this->plugins === null) {
			$result = Directory::ls($this->flot_js_path(), '/jquery\.flot\.[a-zA-Z0-9]+]\.js/');
			$this->plugins = array_flip(ArrayTools::unwrap(ArrayTools::flip_copy($result), "jquery.flot.", ".js", true));
		}
		return $this->plugins;
	}
	public function plugin(zesk\Response $response, $plugins = null) {
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
				$response->javascript($plugin_path, array(
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
