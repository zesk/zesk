<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/module.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */

/**
 * Module loading and management
 *
 * @see Modules
 * @author kent
 */
abstract class Module extends Hookable {
	/**
	 *
	 * @var Application
	 */
	protected $application = null;

	/**
	 * Module code name
	 *
	 * @var string
	 */
	protected $codename = null;

	/**
	 * Path to this module
	 *
	 * @var string
	 */
	protected $path = null;

	/**
	 * List of associated classes
	 *
	 * @var array
	 */
	protected $classes = array();

	/**
	 * Create Module
	 *
	 * @param string $options
	 */
	public function __construct(Application $application, $options = null, array $module_data = array()) {
		parent::__construct($options);
		$this->application = $application;
		$this->inherit_global_options();
		$this->path = avalue($module_data, 'path');
	}

	/**
	 * Override in subclasses - called upon load
	 */
	public function initialize() {
	}

	/**
	 * Override in subclasses - called upon Application::classes
	 */
	public function classes() {
		return $this->classes;
	}

	/*
	 * public function hook_head(Request $request, Response_HTML $response, Template $template) {
	 *
	 * }
	 */

	/**
	 * Run hooks across all modules loaded
	 * @deprecated 2016-01-13
	 *
	 * @param string $hook
	 *        	Hook name
	 * @return mixed
	 */
	public final static function all_hook($hook) {
		$arguments = func_get_args();
		array_shift($arguments);
		return Application::instance()->module->all_hook_array($hook, $arguments);
	}

	/**
	 * Retrieve
	 * Used for filters where a specific result should be returned by each function
	 *
	 * @deprecated 2016-01-13
	 * @param string $hook
	 * @param array $arguments
	 * @param mixed $default
	 * @return mixed
	 */
	public final static function all_modules() {
		return Application::instance()->module->all_modules();
	}

	/**
	 * Partner to hook_all - runs with an arguments array command and a default return value
	 * Used for filters where a specific result should be returned by each function
	 *
	 * @deprecated 2016-01-13
	 * @param string $hook
	 * @param array $arguments
	 * @param mixed $default
	 * @return mixed
	 */
	public final static function all_hook_array($hook, array $arguments, $default = null, $hook_callback = null, $result_callback = null) {
		return Application::instance()->module->all_hook_array($hook, $arguments, $default, $hook_callback, $result_callback);
	}

	/**
	 * List all hooks which would be called by all modules.
	 *
	 * @deprecated 2016-01-13
	 * @param string $hook
	 * @param array $arguments
	 * @param mixed $default
	 * @return mixed
	 */
	public final static function all_hook_list($hook) {
		return Application::instance()->module->all_hook_list($hook);
	}

	/**
	 * Is a module loaded?
	 *
	 * @deprecated 2016-01-13
	 * @param $mixed Modules
	 *        	to check
	 * @return array boolean
	 */
	public final static function exists($mixed = null) {
		return Application::instance()->module->exists($mixed);
	}

	/**
	 * List available modules
	 * @deprecated 2016-01-13
	 */
	public final static function available() {
		return Application::instance()->module->available();
	}

	/**
	 * What modules are loaded?
	 *
	 * @deprecated 2016-01-13
	 * @param string $mixed
	 *        	Check if one or more module is loaded
	 *
	 * @return array|boolean
	 */
	public final static function loaded($mixed = null) {
		return Application::instance()->module->loaded($mixed);
	}

	/**
	 * Dynamically determine the module version
	 *
	 * @deprecated 2016-01-13
	 * @param mixed $mixed
	 *        	Array of modules.
	 */
	public final static function version($mixed = null) {
		return Application::instance()->module->version($mixed);
	}

	/**
	 * Retrieve information about a loaded module
	 *
	 * @deprecated 2016-01-13
	 * @param string $module
	 * @param string $option
	 * @param mixed $default
	 * @return mixed
	 */
	public final static function data($module, $option, $default = null) {
		return Application::instance()->module->data($module, $option, $default);
	}

	/**
	 * Get full path to module
	 *
	 * @deprecated 2016-01-13
	 * @param string $module
	 * @param mixed $default
	 *        	Value to return if module is not loaded
	 * @return string
	 */
	public final static function path($module, $default = null) {
		return self::data($module, "path", $default);
	}

	/**
	 * Get Module
	 *
	 * @deprecated 2016-01-13
	 * @param string $module
	 * @param mixed $default
	 *        	Value to return if module is not loaded
	 * @return Module|null
	 */
	public final static function object($module, $default = null) {
		return self::data($module, "module", $default);
	}

	/**
	 * Clean a class name
	 *
	 * @deprecated 2016-01-13
	 * @param string $name
	 * @return string
	 */
	private static function clean_class($name) {
		return trim(preg_replace('/-_+/', '_', preg_replace('/[^a-z0-9]/i', '_', $name)), "_");
	}

	/**
	 * Load an internal module - differs in that it uses autoload path to find Module_$name and
	 * registers it first.
	 *
	 * @param mixed $modules
	 * @deprecated 2016-01-13
	 * @return array
	 */
	public final static function internal($modules) {
		//zesk::deprecated(); TODO
		$result = array();
		foreach (to_list($modules) as $name) {
			zesk::register_class("Module_" . self::clean_class($name));
		}
		return self::load($modules, array(
			'internal' => true
		));
	}

	/**
	 * @deprecated 2016-01-13
	 * @param unknown $mixed
	 * @param array $options
	 */
	public static final function load($mixed = null, array $options = array()) {
		return Application::instance()->module->load($mixed, $options);
	}

	/**
	 * During module registration, register system paths automatically.
	 * Either a specified path
	 * or uses the current module's path, looks for the following directories and registers:
	 * classes/ - zesk::autoload_path
	 * theme/ - Application::theme_path
	 * share/ - zesk::share_path
	 * command/ - zesk::zesk_command_path
	 * bin/ - zesk::command_path
	 *
	 * @param string $module_path
	 *        	Directory to search for system paths
	 * @param string $module
	 *        	Module associated with the system path (used for share directory)
	 * @return array Array of actually registered paths
	 */
	public final static function register_paths($module_path = null, $module = null) {
		return Application::instance()->module->register_paths($module_path, $module);
	}

	/**
	 * Clean a module name
	 *
	 * @deprecated 2016-01-13
	 * @see Modules::clean_name
	 * @param string $module
	 * @return string
	 */
	public static function clean_name($module) {
		return Modules::clean_name($module);
	}

}