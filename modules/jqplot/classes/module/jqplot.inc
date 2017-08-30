<?php
use zesk\arr;
use zesk\Module;
use zesk\Interface_Module_Head;
use zesk\Exception_Directory_NotFound;
use zesk\Request;
use zesk\Response_Text_HTML;
use zesk\Template;

/**
 * @package modules
 * @subpackage jQPlot
 * @author kent
 */
class Module_jQPlot extends zesk\Module implements zesk\Interface_Module_Head {
	public static $plugins = array(
		"BezierCurveRenderer",
		"barRenderer",
		"blockRenderer",
		"bubbleRenderer",
		"canvasAxisLabelRenderer",
		"canvasAxisTickRenderer",
		"canvasOverlay",
		"canvasTextRenderer",
		"categoryAxisRenderer",
		"ciParser",
		"cursor",
		"dateAxisRenderer",
		"donutRenderer",
		"dragable",
		"enhancedLegendRenderer",
		"funnelRenderer",
		"highlighter",
		"json2",
		"logAxisRenderer",
		"mekkoAxisRenderer",
		"mekkoRenderer",
		"meterGaugeRenderer",
		"mobile",
		"ohlcRenderer",
		"pieRenderer",
		"pointLabels",
		"pyramidAxisRenderer",
		"pyramidGridRenderer",
		"pyramidRenderer",
		"trendline"
	);
	private static $plugins_flip = null;
	
	/**
	 * Is this enabled on all pages
	 *
	 * @param string $set        	
	 * @return boolean
	 */
	public function enabled($set = null) {
		if ($set !== null) {
			$set = !!$set;
			$this->set_option("enabled", $set);
			return $set;
		}
		return $this->option_bool("enabled");
	}
	
	/**
	 * Implement Module::head
	 *
	 * @param Request $request        	
	 * @param zesk\Response_Text_HTML $response        	
	 * @param zesk\Template $template        	
	 */
	public function hook_head(Request $request, Response_Text_HTML $response, Template $template) {
		$response_option = $this->response_option();
		if ($response->has_option($response_option) || $this->enabled()) {
			$response->jquery();
			$response->css("/share/jqplot/jquery.jqplot.css", array(
				'share' => true
			));
			$jqplot_weight = $this->option("javascript_weight", 100);
			$response->javascript("/share/jquery-tools/compat.jquery.browser.js", array(
				"share" => true,
				'weight' => $jqplot_weight - 1
			));
			$response->javascript("/share/jqplot/jquery.jqplot.js", array(
				'share' => true,
				'weight' => $jqplot_weight
			));
			foreach ($this->plugin($response) as $plugin) {
				$response->javascript($this->plugin_uri($plugin), array(
					"share" => true,
					'weight' => ++$jqplot_weight
				));
			}
		}
	}
	
	/**
	 * Return the full path to the plugins directory
	 *
	 * @return string
	 */
	public function plugin_path() {
		return $this->application->application_root('vendor/components/jqplot/plugins');
	}
	
	/**
	 * Does an existing plugin exist? Plugins are case insensitive.
	 *
	 * @param string $plugin        	
	 * @throws Exception_Directory_NotFound If the plugin directory is not found
	 * @return boolean
	 */
	public function plugin_exists($plugin) {
		if (self::$plugins_flip === null) {
			self::$plugins_flip = arr::flip_copy(self::$plugins, true);
		}
		$path = $this->plugin_path();
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path, "Required for jQPlot plugin: {plugin}", array(
				"plugin" => $plugin
			));
		}
		$plugin = avalue(self::$plugins_flip, strtolower($plugin), $plugin);
		$file = path($path, "jqplot.$plugin.js");
		return is_file($file);
	}
	
	/**
	 * Retrieve the URI for a particular plugin.
	 * If file does not exist, returns null.
	 *
	 * @param string $plugin        	
	 * @return string
	 */
	public function plugin_uri($plugin) {
		$path = $this->plugin_path();
		$development = $this->application->development();
		$try = array();
		if (!$development) {
			$try[] = "jqplot.$plugin.min.js";
		}
		$try[] = "jqplot.$plugin.js";
		foreach ($try as $js) {
			if (is_file(path($path, $js))) {
				return path("/share/jqplot/plugins/", $js);
			}
		}
		return null;
	}
	
	/**
	 * Internal variable used in Response object to track plugins for this page request/response
	 *
	 * @return string
	 */
	private function response_option() {
		return __CLASS__ . '::plugin';
	}
	
	/**
	 * Add a plugin to this page.
	 *
	 * @param zesk\Response_Text_HTML $response        	
	 * @param string $add        	
	 * @throws Exception_Directory_NotFound
	 * @return zesk\Response_Text_HTML|array
	 */
	public function plugin(Response_Text_HTML $response, $add = null) {
		$option = self::response_option();
		if ($add !== null) {
			$path = $this->plugin_path();
			if (!is_dir($path)) {
				throw new Exception_Directory_NotFound($path, "Required for jQPlot plugin: {plugin}", array(
					"plugin" => $add
				));
			}
			$add = to_list($add);
			foreach ($add as $plugin) {
				if ($this->plugin_exists($plugin)) {
					$response->option_append($option, $plugin, $plugin);
				} else {
					$this->application->logger->error("jQPlot plugin not found: {file}", array(
						"file" => file
					));
				}
			}
			return $response;
		}
		return $response->option_array($option, array());
	}
}
