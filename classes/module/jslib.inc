<?php

/**
 * For modules which install and use a JavaScript library
 *
 * @author kent
 */
abstract class Module_JSLib extends Module {

	protected $css_paths = array();

	protected $javascript_paths = array();

	protected $javascript_settings = array();

	/*
	 * An array of key => value pairs which are set as globals for this module
	 * and taken from zesk globals.
	 *
	 * So, in your application configuration:
	 *
	 * Module_Foo::value = true
	 *
	 * In the page:
	 *
	 * zesk.get_path('modules.foo.value') === true
	 *
	 * If the zesk global matches the value, then nothing is set
	 * If the zesk global is not set, then nothing is set
	 *
	 * @var array
	 */
	protected $javascript_settings_inherit = array();

	protected $jquery_ready = array();

	protected $jquery_ready_weight = -1000;

	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		$conf = get_class($this) . "::disabled";
		if (!zesk::getb($conf, false)) {
			if ($this->javascript_settings_inherit) {
				$prefix = get_class($this) . '::';
				foreach ($this->javascript_settings_inherit as $key => $value) {
					if (zesk::has($prefix . $key)) {
						$global = zesk::get($prefix . $key);
						if ($global !== $value) {
							$this->javascript_settings[$key] = $global;
						}
					}
				}
			}
			if ($this->javascript_settings) {
				$response->javascript_settings(array(
					'modules' => array(
						$this->codename => $this->javascript_settings
					)
				));
			}
			foreach ($this->css_paths as $path) {
				$response->cdn_css($path, array(
					'share' => true
				));
			}
			foreach ($this->javascript_paths as $key => $value) {
				if (is_numeric($key) && is_string($value)) {
					$response->cdn_javascript($value, array(
						'share' => true
					));
				} else if (is_string($key) && is_array($value)) {
					$response->cdn_javascript($key, $value + array(
						'share' => true
					));

				}
			}
			$this->ready($response);
		}
	}

	/**
	 * Able to call this from theme templates for AJAX calls
	 *
	 * @param Response_HTML $response
	 */
	public function ready(Response_HTML $response) {
		$this->hook('ready');
		foreach ($this->jquery_ready as $code) {
			$response->jquery($code, $this->jquery_ready_weight);
		}
	}
}

