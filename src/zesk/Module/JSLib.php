<?php
declare(strict_types=1);

namespace zesk\Module;

use zesk\Interface\Module\Head;

use zesk\Module;
use zesk\Request;
use zesk\Response;
use zesk\Theme;

/**
 * For modules which install and use a JavaScript library
 *
 * @author kent
 */
abstract class JSLib extends Module implements Head {
	/**
	 * Array of css paths for this page
	 *
	 * @var array
	 */
	protected array $css_paths = [];

	/**
	 * Array of options to pass to Response::css for each css_paths
	 *
	 * @var array
	 */
	protected array $css_options = [];

	/**
	 * Array of options to pass to Response::javascript for each javascript_paths
	 *
	 * @var array
	 */
	protected array $javascript_options = [];

	/**
	 * Array of strings of JS to load, or array of path (key) => $options to load
	 *
	 * @var array
	 */
	protected array $javascript_paths = [];

	/**
	 * Settings which will be exposed in the client browser using the key
	 *
	 * modules.name.setting1
	 *
	 * @var array
	 */
	protected array $javascript_settings = [];

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
	protected array $javascript_settings_inherit = [];

	/**
	 * jQuery ready code
	 *
	 * @var array
	 */
	protected array $jquery_ready = [];

	/**
	 * Where the jQuery code should run (higher numbers are later)
	 * @var integer
	 */
	protected int $jquery_ready_weight = -1000;

	/**
	 * Disabled setter/getter
	 *
	 * @return bool
	 */
	public function disabled(): bool {
		return $this->optionBool('disabled');
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setDisabled(bool $set): self {
		$this->setOption('disabled', $set);
		return $this;
	}

	/**
	 * @return array
	 */
	public function javascript_settings(): array {
		return $this->compute_javascript_settings()->javascript_settings;
	}

	/**
	 * @return self
	 */
	public function compute_javascript_settings(): self {
		if ($this->javascript_settings_inherit) {
			foreach ($this->javascript_settings_inherit as $key => $value) {
				if ($this->hasOption($key)) {
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
	 * @param Theme $template
	 */
	public function hook_head(Request $request, Response $response, Theme $template): void {
		if (!$this->optionBool('disabled')) {
			$this->javascript_settings['enabled'] = true;
			$this->compute_javascript_settings();
			if ($this->javascript_settings) {
				$response->html()->addJavascriptSettings([
					'modules' => [
						$this->codename => $this->javascript_settings,
					],
				]);
			}
			foreach ($this->css_paths as $key => $value) {
				if (is_string($key) && is_array($value)) {
					$response->css($key, $value + $this->css_options);
				} elseif (is_numeric($key) && is_string($value)) {
					$response->css($value, $this->css_options + [
						'share' => true,
					]);
				} elseif (is_string($key) && is_string($value)) {
					$response->css($key, [
						$value => true,
					] + $this->css_options);
				}
			}
			foreach ($this->javascript_paths as $key => $value) {
				if (is_numeric($key) && is_string($value)) {
					$response->javascript($value, $this->javascript_options + [
						'share' => true,
					]);
				} elseif (is_string($key) && is_array($value)) {
					$response->javascript($key, $value + $this->javascript_options + [
						'share' => true,
					]);
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
	public function ready(Response $response): void {
		// TODO FIX
//		$this->invokeHooks(self::HOOK_READY);
//		foreach ($this->jquery_ready as $code) {
//			$response->inlineJavaScript($code, ['weight' => $this->jquery_ready_weight]);
//		}
	}

	public const HOOK_READY = __CLASS__ . '::ready';
}
