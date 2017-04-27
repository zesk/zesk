<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Configuration_Parser_JSON extends Configuration_Parser {
	protected $options = array(
		"overwrite" => true,
		"lower" => true,
		"interpolate" => true
	);
	
	/**
	 *
	 */
	public function initialize() {
	}
	
	/**
	 * @return boolean
	 */
	public function validate() {
		try {
			return is_array(JSON::decode($this->content));
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 *
	 */
	public function process() {
		$lower = $overwrite = $interpolate = null;
		extract($this->options, EXTR_IF_EXISTS);
		
		$result = JSON::decode($this->content);
		
		if (!is_array($result)) {
			zesk()->logger->warning("{method} JSON::decode returned non-array {type}", array(
				"method" => __METHOD__,
				"type" => type($result)
			));
			return false;
		}
		if ($lower && is_array($result)) {
			$result = array_change_key_case($result);
		}
		$settings = $this->settings;
		$dependency = $this->dependency;
		
		$include = null;
		if (array_key_exists("include", $result) && $this->loader) {
			$include = $result["include"];
			unset($result["include"]);
		}
		$result = $this->merge_results($result, array(), $interpolate);
		if ($include) {
			$this->handle_include($include);
		}
		return $result;
	}
	
	/**
	 * Handle include files specially
	 * 
	 * @param string $file Name of additional include file
	 */
	private function handle_include($file) {
		if (File::is_absolute($file)) {
			$this->loader->append_files(array(
				$file
			));
			return;
		}
		$files = array();
		$paths = $this->loader->paths();
		foreach ($paths as $path) {
			$files[] = path($path, $file);
		}
		$this->loader->append_files($files);
	}
	
	/**
	 *
	 * @param array $results
	 * @param array $path
	 * @param boolean $interpolate
	 */
	private function merge_results(array $results, array $path = array(), $interpolate) {
		$dependency = $this->dependency;
		$settings = $this->settings;
		foreach ($results as $key => $value) {
			$matches = null;
			$current_path = array_merge($path, array(
				$key
			));
			if (is_array($value)) {
				$this->merge_results($value, $current_path, $interpolate);
			} else if (is_string($value) && $interpolate && preg_match_all('/\$\{([^\}]+)\}/', $value, $matches, PREG_SET_ORDER)) {
				$dependencies = array();
				foreach ($matches as $match) {
					list($token, $variable) = $match;
					$map[$token] = strval($settings->get($variable));
					$dependencies[$variable] = true;
				}
				$value = strtr($value, $map);
				$variable = implode(Configuration::key_separator, $current_path);
				$settings->set($variable, $value);
				if ($dependency) {
					$dependency->defines($variable, array_keys($dependencies));
				}
			} else {
				$variable = implode(Configuration::key_separator, $current_path);
				$settings->set($variable, $value);
				if ($dependency) {
					$dependency->defines($variable);
				}
			}
		}
	}
}
