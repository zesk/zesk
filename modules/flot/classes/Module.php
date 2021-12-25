<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Flot;

use zesk\Request;
use zesk\Template;
use zesk\Response;
use zesk\ArrayTools;
use zesk\Directory;
use zesk\Exception_File_NotFound;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module implements \zesk\Interface_Module_Head {
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
	public function hook_head(Request $request, Response $response, Template $template): void {
		$this->_head($response);
	}

	/**
	 *
	 * @param Response $response
	 */
	private function _head(Response $response): void {
		$response->html()->jquery();
		$response->html()->javascript("/share/flot/jquery.flot.js", [
			"share" => true,
		]);
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

	/**
	 * Pass in null for plugins to retrieve a list of all plugins
	 * If plugin passed in, returns zesk\Response passed in with HTML correctly configured.
	 *
	 * @param Response $response
	 * @param unknown $plugins
	 * @throws Exception_File_NotFound
	 * @return string[]|zesk\Response
	 */
	public function plugin(Response $response, $plugins = null) {
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
				return $response->html()->javascript($plugin_path, [
					"share" => true,
				]);
			} else {
				throw new Exception_File_NotFound($full_path, "{class} plugin not found: {plugin}", [
					"class" => __CLASS__,
					"plugin" => $plugin_path,
				]);
			}
		}
	}
}
