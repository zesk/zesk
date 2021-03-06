<?php
namespace zesk;

/**
 * For modules which install and use a JavaScript library
 *
 * @author kent
 */
abstract class Module_JSLib extends Module implements Interface_Module_Head {
	/**
	 * Array of css paths for this page
	 *
	 * @var array
	 */
	protected $css_paths = array();

	/**
	 * Array of options to pass to Response::css for each css_paths
	 *
	 * @var array
	 */
	protected $css_options = array();

	/**
	 * Array of options to pass to Response::javascript for each javascript_paths
	 *
	 * @var array
	 */
	protected $javascript_options = array();

	/**
	 * Array of strings of JS to load, or array of path (key) => $options to load
	 *
	 * @var array
	 */
	protected $javascript_paths = array();

	/**
	 * Settings which will be exposed in the client browser using the key
	 *
	 * modules.name.setting1
	 *
	 * @var array
	 */
	protected $javascript_settings = array();

	/**
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

	/**
	 * jQuery ready code
	 *
	 * @var array
	 */
	protected $jquery_ready = array();

	/**
	 * Where the jQuery code should run (higher numbers are later)
	 * @var integer
	 */
	protected $jquery_ready_weight = -1000;

	/**
	 * Disabled setter/getter
	 *
	 * @param unknown $set
	 * @return Module_jQuery_Unveil|mixed|boolean
	 */
	public function disabled($set = null) {
		if ($set) {
			$this->set_option('disabled', to_bool($set));
			return $this;
		}
		return $this->option_bool('disabled', false);
	}

	public function javascript_settings() {
		return $this->compute_javascript_settings()->javascript_settings;
	}

	public function compute_javascript_settings() {
		if ($this->javascript_settings_inherit) {
			foreach ($this->javascript_settings_inherit as $key => $value) {
				if ($this->has_option($key)) {
					$global = $this->option($key);
					if ($global !== $value) {
						$this->javascript_settings[$key] = $global;
					}
				} else {
					$this->javascript_settings[$key] = $value;
				}
			}
		}
		return $this;
	}

	/**
	 * HTML Page head
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param Template $template
	 */
	public function hook_head(Request $request, Response $response, Template $template) {
		if (!$this->option_bool("disabled")) {
			$this->javascript_settings['enabled'] = true;
			$this->compute_javascript_settings();
			if ($this->javascript_settings) {
				$response->javascript_settings(array(
					'modules' => array(
						$this->codename => $this->javascript_settings,
					),
				));
			}
			foreach ($this->css_paths as $key => $value) {
				if (is_string($key) && is_array($value)) {
					$response->css($key, $value + $this->css_options);
				} elseif (is_numeric($key) && is_string($value)) {
					$response->css($value, $this->css_options + array(
						'share' => true,
					));
				} elseif (is_string($key) && is_string($value)) {
					$response->css($key, array(
						$value => true,
					) + $this->css_options);
				}
			}
			foreach ($this->javascript_paths as $key => $value) {
				if (is_numeric($key) && is_string($value)) {
					$response->javascript($value, $this->javascript_options + array(
						'share' => true,
					));
				} elseif (is_string($key) && is_array($value)) {
					$response->javascript($key, $value + $this->javascript_options + array(
						'share' => true,
					));
				}
			}
			$this->ready($response);
		}
	}

	/**
	 * Able to call this from theme templates for AJAX calls
	 *
	 * @param Response $response
	 */
	public function ready(Response $response) {
		$this->call_hook('ready');
		foreach ($this->jquery_ready as $code) {
			$response->jquery($code, $this->jquery_ready_weight);
		}
	}
}
