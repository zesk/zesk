<?php

/**
 * Class for module loading, management, and configuration
 *
 * Ideally we should be able to serialize this entire structure and load again from cache so side-effects should be
 * tracked when loading modules (hooks, etc.) or repeated upon __wakeup() in your module itself.
 */
namespace zesk;

/**
 *
 * @see Module
 * @author kent
 *        
 */
class Modules {
	/**
	 *
	 * @var string
	 */
	const status_failed = "failed";
	
	/**
	 *
	 * @var string
	 */
	const status_loaded = "loaded";
	
	/**
	 *
	 * @var Application
	 */
	private $application = null;
	
	/**
	 * Stack of currently loading modules.
	 * Top item is current module loading. First item is top of stack.
	 *
	 * @var array
	 */
	private $module_loader = array();
	
	/**
	 * Loaded modules in the system
	 *
	 * @var array of module name => array of module information (class of Module)
	 */
	private $modules = array();
	
	/**
	 * Loaded modules in the system
	 *
	 * @var array of hook name => list of module names (ordered)
	 */
	private $modules_with_hook = array();
	
	/**
	 *
	 * @var string
	 */
	private $module_class_prefix = "Module_";
	
	/**
	 * Create the Modules
	 *
	 * @param Application $application        	
	 */
	public function __construct(Application $application) {
		$this->application = $application;
		$this->module_class_prefix = $application->configuration->path_get("zesk\Modules::module_class_prefix", $this->module_class_prefix);
	}
	
	/**
	 * Dynamically determine the module version
	 *
	 * @param mixed $mixed
	 *        	Array of modules.
	 */
	public final function version($mixed = null) {
		$modules = ($mixed === null) ? array_keys($this->modules) : to_list($mixed);
		$result = array();
		foreach ($modules as $module) {
			$module = self::clean_name($module);
			$result[$module] = $version = $this->_module_version($module);
			if ($version) {
				$this->modules[$module]['version'] = $version;
			}
		}
		if (is_string($mixed) && count($result) === 1) {
			return $version;
		}
		return $result;
	}
	
	/**
	 * Return list of available modules
	 *
	 * @return array
	 */
	public final function available() {
		$module_paths = $this->application->module_path();
		$files = array();
		$options = array(
			'rules_file' => array(
				'#.*\.module\.(inc|conf|json)$#' => true,
				false
			),
			'rules_directory_walk' => array(
				'#/\.#' => false,
				true
			),
			'rules_directory' => false
		);
		foreach ($module_paths as $module_path) {
			$files[$module_path] = Directory::list_recursive($module_path, $options);
		}
		$dirs = array();
		foreach ($files as $module_path => $files) {
			foreach ($files as $file) {
				$module = dirname($file);
				$dirs[dirname($file)][$module] = $module;
			}
		}
		$available = array();
		foreach ($dirs as $path => $modules) {
			asort($modules);
			foreach ($modules as $module) {
				$available[$module] = $this->load($module, array(
					"load" => false
				));
			}
		}
		return $available;
	}
	
	/**
	 * What modules are loaded?
	 *
	 * @param string $mixed
	 *        	Check if one or more module is loaded
	 *        	
	 * @return array|boolean
	 */
	public final function loaded($mixed = null) {
		if ($mixed === null) {
			$result = $this->modules;
		} else {
			$result = array();
			$modules = to_list($mixed);
			foreach ($modules as $index => $module) {
				$module = $modules[$index] = self::clean_name($module);
				$result[$module] = avalue($this->modules, $module, array());
			}
		}
		$result = arr::collapse($result, self::status_loaded, false);
		if ($mixed !== null && count($modules) === 1) {
			return $result[$modules[0]];
		}
		return $result;
	}
	
	/**
	 * Load one or more modules
	 * Module load array is an array with the following keys:
	 * - loaded: mixed, false if not loaded, microtime if loaded
	 * - name: string, module name
	 * - include: string, module include file
	 * - path: string, path to module directory
	 * - configuration: array of module configuration file
	 * - configuration_file: The configuration file (full absolute path)
	 * - status: Most recent action on this module
	 *
	 * @param mixed $mixed
	 *        	Module name or array of module names
	 * @param array $options
	 *        	Loading options
	 *        	- "check loaded" set to true to just check if it's loaded.
	 *        	- "not loaded" set to a value (any value) which is returned when a module is not
	 *        	loaded
	 *        	- "check exists" set to true to check if the module exists. If it is, basic
	 *        	information is passed back (module configuation is not loaded, for example).
	 *        	- "load" set to false to gather only basic information (including configuration),
	 *        	but not load the module. (similar to check exists - duplicate functionality?)
	 *        	
	 * @return Module array of module => module_data as described above, or for single modules, just
	 *         the module data
	 *         array
	 */
	public final function load($mixed = null, array $options = array()) {
		if ($mixed === null) {
			return $this->modules;
		}
		$passed_modules = self::clean_name(to_list($mixed));
		$modules = self::_expand_modules($passed_modules);
		$result = array();
		$module_paths = $this->application->module_path();
		foreach ($modules as $index => $name) {
			$name = self::clean_name($name);
			$module_data = avalue($this->modules, $name);
			if (is_array($module_data)) {
				$result[$name] = $this->modules[$name] + array(
					'status' => 'already loaded'
				);
				continue;
			} else if (avalue($options, "check loaded")) {
				$result[$name] = avalue($options, "not loaded", null);
				continue;
			}
			$result += self::_load_one($name, $options);
		}
		$result = arr::filter($result, $passed_modules);
		if (count($passed_modules) === 1) {
			return $result[strtolower($passed_modules[0])];
		}
		return $result;
	}
	
	/**
	 * During module registration, register system paths automatically.
	 * Either a specified path
	 * or uses the current module's path, looks for the following directories and registers:
	 * classes/ - $zesk->autoloader->path
	 * theme/ - Application::theme_path
	 * share/ - Application::share_path
	 * command/ - Application::zesk_command_path
	 * bin/ - $zesk->paths->command_path
	 *
	 * @param string $module_path
	 *        	Directory to search for system paths
	 * @param string $module
	 *        	Module associated with the system path (used for share directory)
	 * @return array Array of actually registered paths
	 */
	public final function register_paths($module_path = null, $module = null) {
		$current_name = first($this->module_loader);
		$current = array();
		if ($current_name) {
			$current = avalue($this->modules, $current_name, array());
			if ($module_path === null) {
				$module_path = avalue($current, 'path');
			}
			if ($module === null) {
				$module = avalue($current, 'name');
			}
		} else if ($module == null) {
			throw new Exception_Parameter("Require a \$module path if not called during module load");
		} else if (isset($this->modules[$module])) {
			if (!is_dir($module_path)) {
				throw new Exception_Directory_NotFound($module_path);
			}
		} else {
			throw new Exception_Semantics("Can not call register paths unless during module load.");
		}
		$configuration = avalue($current, "configuration", array());
		$result = array();
		$autoload_class_prefix = avalue($configuration, "autoload_class_prefix");
		$autoload_path = avalue($configuration, "autoload_path", "classes");
		$autoload_options = to_array(avalue($configuration, "autoload_options", array()));
		$theme_path = avalue($configuration, "theme_path", "theme");
		$zesk_command_path = avalue($configuration, "zesk_command_path", "command");
		$zesk_command_path_class_prefix = apath($configuration, "zesk_command_path_class_prefix");
		$locale_path = apath($configuration, "locale_path", "etc/language");
		if (!$module_path) {
			return $result;
		}
		$path = path($module_path, $autoload_path);
		if (is_dir($path)) {
			$result['autoload_path'] = $path;
			if ($autoload_class_prefix) {
				$autoload_options += array(
					"class_prefix" => $autoload_class_prefix
				);
				zesk()->deprecated("Module configuration \"autoload_class_prefix\" is deprecated for module >>{module}<<, use \"autoload_options\": { \"class_prefix\": ... } instead", compact("module"));
			}
			$this->application->autoload_path($path, $autoload_options);
		}
		$path = path($module_path, $theme_path);
		if (is_dir($path)) {
			$result['theme_path'] = $path;
			$this->application->theme_path($path);
		}
		if (!$module) {
			return $result;
		}
		$path = path($module_path, "share");
		if (is_dir($path)) {
			$result['share_path'] = $path;
			$this->application->share_path($path, $module);
		}
		$path = path($module_path, $zesk_command_path);
		if (is_dir($path)) {
			if (!$current) {
				backtrace();
			}
			$result['zesk_command_path'] = $path;
			$prefix = "Command_";
			if ($zesk_command_path_class_prefix) {
				$prefix = $zesk_command_path_class_prefix;
				if ($prefix !== PHP::clean_class($prefix)) {
					$this->application->logger->error("zesk_command_path_class_prefix specified in module {name} configuration is not a valid class prefix \"{prefix}\"", array(
						"name" => $current['name'],
						'prefix' => $prefix
					));
				}
			}
			$this->application->zesk_command_path($path, $zesk_command_path_class_prefix);
		}
		$path = path($module_path, $locale_path);
		if (is_dir($path)) {
			$result['locale_path'] = $path;
			Locale::locale_path($path);
		}
		return $result;
	}
	
	/**
	 *
	 * @param unknown $include        	
	 * @param array $module_data        	
	 */
	private function _load_module_include($include, array $module_data) {
		$application = $this->application;
		$zesk = $application->zesk;
		$module_directory = dirname($include);
		assert($application instanceof Application);
		return require_once $include;
	}
	/**
	 * Load module based on setup options
	 *
	 * @param array $module_data        	
	 *
	 * @return number
	 */
	private function _load_module_configuration(array $module_data) {
		$name = $path = $include = null;
		extract($module_data, EXTR_IF_EXISTS);
		
		global $zesk;
		
		$this->modules[$name] = $module_data + array(
			'loading' => true
		);
		array_unshift($this->module_loader, $name);
		if ($include) {
			// This may be called recursively
			$this->_load_module_include($include, $module_data);
		} else {
			$this->register_paths();
		}
		array_shift($this->module_loader);
		unset($this->modules[$name]['loading']);
		
		$module_data['loaded'] = true;
		$module_data['loaded_time'] = microtime(true);
		$module_data += array(
			'status' => self::status_loaded
		);
		
		return $module_data;
	}
	private function _handle_share_path(array $module_data) {
		// Apply share_path automatically
		$share_path = apath($module_data, 'configuration.share_path');
		if ($share_path) {
			if (!Directory::is_absolute($share_path)) {
				$share_path = $this->application->path($share_path);
			}
			$name = $module_data['name'];
			if (!is_dir($share_path)) {
				$this->application->logger->critical("Module {module} share path \"{share_path}\" is not a directory", array(
					"module" => $name,
					"share_path" => $share_path
				));
			} else {
				$this->application->share_path($share_path, $name);
			}
		}
		return $module_data;
	}
	private function _handle_requires($requires, array $options) {
		// Load dependent modules
		$result = array();
		foreach (to_array($requires) as $required_module) {
			if (!apath($this->modules, array(
				$required_module,
				"loaded"
			))) {
				$result += self::_load_one($required_module, $options);
			}
		}
		return $result;
	}
	private function _apply_module_configuration(array $module_data) {
		$module_data = $this->_handle_share_path($module_data);
		return $module_data;
	}
	/**
	 * Load a single module by name
	 *
	 * @param string $name        	
	 * @param array $options        	
	 * @throws Exception_Directory_NotFound
	 * @return array
	 */
	private function _load_one($name, array $options) {
		$result = array();
		$base = self::module_base_name($name);
		$module_data = array(
			'loaded' => false,
			'name' => $name,
			'base' => $base
		);
		$module_data['path'] = $module_path = Directory::find_first($this->application->module_path(), $base);
		if ($module_path === null) {
			throw new Exception_Directory_NotFound(__CLASS__ . "::module($name) was not found");
		}
		if (avalue($options, "check exists")) {
			$result[$name] = $module_data;
			return $result;
		}
		$module_data += self::_find_module_include($module_data);
		
		if (to_bool(avalue($options, 'load', true))) {
			$module_data = $this->_load_module_configuration($module_data);
			$module_data = $this->_apply_module_configuration($module_data);
			$this->modules[$name] = $module_data;
			
			$requires = to_list(apath($module_data, 'configuration.requires'));
			if ($requires) {
				$result += $this->_handle_requires($requires, $options);
			}
			
			$this->modules[$name] = $module_data = $this->_load_module_object($module_data) + $module_data;
		}
		
		$result[$name] = $module_data;
		return $result;
	}
	
	/**
	 * Finds the module configuration file and loads it.
	 *
	 * @param array $module_data        	
	 * @param unknown $options        	
	 * @return array
	 */
	private static function _find_module_include(array $module_data) {
		$name = $base = $path = null;
		extract($module_data, EXTR_IF_EXISTS);
		$module_data['include'] = $module_include = File::find_first(array(
			$path
		), array(
			"$base.module.php",
			"$base.module.inc"
		));
		if ($module_include) {
			zesk()->deprecated("Module loader file ($module_include) is deprecated in Zesk 0.9, use subclass of \zesk\Module::initialize instead");
		}
		$module_variables = array(
			'module_path' => $path,
			'module' => $name
		);
		$module_config = self::module_configuration_options($module_variables);
		$module_config['settings'] = $settings = new Adapter_Settings_Array($module_variables);
		
		$module_confs = array(
			'json' => path($path, "$base.module.json"),
			'conf' => path($path, "$base.module.conf")
		);
		foreach ($module_confs as $extension => $module_conf) {
			if (file_exists($module_conf)) {
				$raw_module_conf = file_get_contents($module_conf);
				$configuration = new Configuration($module_variables);
				Configuration_Parser::factory($extension, $raw_module_conf, new Adapter_Settings_Configuration($configuration))->process();
				$module_data['configuration_file'] = $module_conf;
				$module_data['configuration'] = $configuration->to_array();
			}
		}
		return $module_data;
	}
	
	/**
	 * Instantiate the module object
	 *
	 * @param array $module_data        	
	 * @throws Exception_Semantics
	 * @return Module null
	 */
	private function _load_module_object(array $module_data) {
		$name = $class = null;
		$configuration = array();
		extract($module_data, EXTR_IF_EXISTS);
		if (!$class) {
			$class = avalue($configuration, "module_class", $this->module_class_prefix . PHP::clean_class($module_data['base']));
		}
		try {
			global $zesk;
			/* @var $zesk Kernel */
			/* @var $module_object Module */
			$module_object = $zesk->objects->factory($class, $this->application, $configuration, $module_data);
			if (!$module_object instanceof Module) {
				throw new Exception_Semantics("Module {class} must be a subclass of Module - skipping", array(
					"class" => get_class($module_object)
				));
			}
			if (method_exists($module_object, "hooks")) {
				$this->application->hooks->register_class($class);
			}
			$module_object->codename = $name;
			$result = array(
				'class' => $class
			);
			try {
				$module_object->initialize();
				return $result + array(
					'module' => $module_object
				);
			} catch (\Exception $e) {
				global $zesk;
				$zesk->hooks->call("exception", $e);
				return $result + array(
					"status" => "failed",
					"initialize_exception" => $e
				);
			}
		} catch (Exception_Class_NotFound $e) {
			return array(
				'module' => null,
				'class' => null,
				'missing_class' => $e->class
			);
		}
	}
	
	/**
	 * Given a list of:
	 * <code>
	 * [ "a/b/c", "dee/eff", "gee" ]
	 * </code>
	 * Convert to:
	 * <code>
	 * [ "a", "a/b", "a/b/c", "dee", "dee/eff", "gee" ]
	 * </code>
	 *
	 * @param array $modules        	
	 * @return array
	 */
	private static function _expand_modules(array $modules) {
		$result = array();
		foreach ($modules as $module) {
			$parts = explode("/", $module);
			$module = array();
			foreach ($parts as $part) {
				$module[] = $part;
				$result[] = implode("/", $module);
			}
		}
		return $result;
	}
	
	/**
	 * Given a module, find its version
	 *
	 * @param string $name        	
	 * @return string
	 */
	private function _module_version($name) {
		$module = $this->load($name, array(
			'load' => false
		));
		$configuration = avalue($module, "configuration", array());
		if (is_array($configuration)) {
			$version = avalue($configuration, "version");
			if ($version !== null) {
				return $version;
			}
			$version_data = avalue($configuration, "version_data");
			if (is_array($version_data)) {
				$file = $pattern = $key = null;
				extract($version_data, EXTR_IF_EXISTS);
				if ($file && file_exists($file)) {
					$contents = file_get_contents($file);
					if ($pattern) {
						$matches = null;
						if (preg_match($pattern, $contents, $matches)) {
							$version = avalue($matches, 1, $matches[0]);
							return $version;
						}
					}
					if ($key) {
						switch ($ext = file::extension($file)) {
							case "phps":
								$data = unserialize($contents);
								break;
							case "json":
								$data = JSON::decode($contents, true);
								break;
							default :
								return null;
						}
						$version = apath($data, $key, null);
						return $version;
					}
				}
			}
		}
		if (class_exists("Module_$name", false) && method_exists("Module_${name}", "version")) {
			return call_user_func(array(
				"Module_$name",
				"version"
			));
		}
		return null;
	}
	
	/**
	 * Module conf::load settings
	 *
	 * @param array $variables        	
	 */
	private static function module_configuration_options() {
		$options = array(
			'lower' => true,
			'trim' => true,
			'multiline' => true,
			'unquote' => '\'\'""'
		);
		return $options;
	}
	
	/**
	 * Is a module loaded?
	 *
	 * @param $mixed Modules
	 *        	to check
	 * @return array boolean
	 */
	public final function exists($mixed = null) {
		if ($mixed === null) {
			return array_fill_keys(array_keys($this->modules), true);
		}
		$result = array();
		$modules = to_list($mixed);
		$module_paths = $this->application->module_path();
		foreach ($modules as $module) {
			$module = self::clean_name($module);
			if (array_key_exists($module, $this->modules)) {
				$result[$module] = true;
			} else {
				$module_path = Directory::find_first($module_paths, $module);
				if (!$module_path) {
					$result[$module] = false;
				} else {
					$file = File::find_first(array(
						$module_path
					), array(
						"$module.module.inc",
						"$module.application.inc",
						"$module.module.json",
						"$module.module.conf"
					));
					$result[$module] = ($file !== null);
				}
			}
		}
		if (count($modules) === 1) {
			return $result[$modules[0]];
		}
		return $result;
	}
	
	/**
	 * Retrieve
	 * Used for filters where a specific result should be returned by each function
	 *
	 * @param string $hook        	
	 * @param array $arguments        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public final function all_modules() {
		$result = array();
		foreach ($this->modules as $name => $data) {
			if (!array_key_exists('module', $data)) {
				continue;
			}
			$module = $data['module'];
			/* @var $module Module */
			if ($module) {
				$result[$name] = $module;
			}
		}
		return $result;
	}
	
	/**
	 * Retrieve information about a loaded module
	 *
	 * @param string $module        	
	 * @param string $option        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public final function data($module, $option, $default = null) {
		return avalue($this->load($module, array(
			'check loaded' => true,
			'not loaded' => array()
		)), $option, $default);
	}
	
	/**
	 * Retrieve configuration information about a loaded module
	 *
	 * @param string $module        	
	 * @param string $option        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public final function configuration($module, $option_path, $default = null) {
		return apath($this->data($module, "configuration", array()), $option_path, $default);
	}
	
	/**
	 * Get full path to module
	 *
	 * @param string $module        	
	 * @param mixed $append
	 *        	Value to append to path using path()
	 * @return string|null Returns null if module not loaded
	 */
	public final function path($module, $append = null) {
		$path = $this->data($module, "path");
		if (!$path) {
			return null;
		}
		return path($path, $append);
	}
	
	/**
	 * Get Module
	 *
	 * @param string $module        	
	 * @param mixed $default
	 *        	Value to return if module is not loaded
	 * @return Module
	 * @throws Exception_NotFound
	 */
	public final function object($module, $default = null) {
		$result = $this->data($module, "module");
		if ($result instanceof Module) {
			return $result;
		}
		throw new Exception_NotFound("No module object for {module} found", compact("module"));
	}
	
	/**
	 * Run hooks across all modules loaded
	 *
	 * @param string $hook
	 *        	Hook name
	 * @return mixed
	 */
	public final function all_hook($hook) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->all_hook_arguments($hook, $arguments);
	}
	
	/**
	 *
	 * @deprecated 2016-09
	 * @param unknown $hook        	
	 * @param array $arguments        	
	 * @param unknown $default        	
	 * @param unknown $hook_callback        	
	 * @param unknown $result_callback        	
	 * @return mixed|unknown|string|number
	 */
	public final function all_hook_array($hook, array $arguments, $default = null, $hook_callback = null, $result_callback = null) {
		zesk()->deprecated();
		return $this->all_hook_arguments($hook, $arguments, $default, $hook_callback, $result_callback);
	}
	/**
	 * Partner to hook_all - runs with an arguments array command and a default return value
	 * Used for filters where a specific result should be returned by each function
	 *
	 * @param string $hook        	
	 * @param array $arguments        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public final function all_hook_arguments($hook, array $arguments, $default = null, $hook_callback = null, $result_callback = null, $return_hint = null) {
		$result = $default;
		$module_names = isset($this->modules_with_hook[$hook]) ? $this->modules_with_hook[$hook] : null;
		if (!is_array($module_names)) {
			$module_names = array();
			foreach ($this->modules as $name => $data) {
				if (!array_key_exists('module', $data)) {
					continue;
				}
				/* @var $module Module */
				if (($module = $data['module']) !== null && $module->has_hook($hook)) {
					$module_names[] = $name;
				}
			}
			$this->modules_with_hook[$hook] = $module_names;
		}
		foreach ($module_names as $module_name) {
			$module = $this->modules[$module_name]['module'];
			if ($module instanceof Module) {
				$new_result = $module->call_hook_arguments($hook, $arguments, $result, $hook_callback, $result_callback, $return_hint);
				$result = Hookable::combine_hook_results($result, $new_result, $arguments, $return_hint);
			} else {
				$this->application->logger->error("While calling hook {hook} for module for {module_name} is not of type zesk\Module ({value} is of type {type})", array(
					"hook" => $hook,
					"module_name" => $module_name,
					"value" => to_text($module),
					"type" => type($module)
				));
			}
		}
		return $result;
	}
	
	/**
	 * List all hooks which would be called by all modules.
	 *
	 * @todo This does not match all_hook_arguments called list? (Module::$hook, etc.)
	 * @todo Add test for this
	 * @param string $hook        	
	 * @param array $arguments        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public final function all_hook_list($hook) {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		$result = $zesk->hooks->find_all("zesk\\Module::$hook");
		if (count($result) > 0) {
			zesk()->deprecated("Static cache clear hook is deprecated: " . _dump($result));
		}
		foreach ($this->modules as $name => $data) {
			$module = avalue($data, 'module');
			/* @var $module Module */
			if ($module) {
				$result = array_merge($result, $module->hook_list($hook, true));
			}
		}
		return $result;
	}
	
	/**
	 * Clean a module name
	 *
	 * @param string $module        	
	 * @return string
	 */
	public static function clean_name($module) {
		if (is_array($module)) {
			foreach ($module as $index => $m) {
				$module[$index] = self::clean_name($m);
			}
			return $module;
		}
		return preg_replace('|[^-/_0-9a-z]|', '', strtolower($module));
	}
	
	/**
	 * Return the file name for the modules files
	 *
	 * In => Out:
	 *
	 * "demo" => "demo"
	 * "section/subsection" => "subsection"
	 * "a/b/c/d/e/f/gee" => "gee"
	 *
	 * @param string $module        	
	 * @return string
	 */
	private static function module_base_name($module) {
		return basename(self::clean_name($module));
	}
	
	/**
	 * Clean a class name
	 *
	 * @deprecated 2016-01-13
	 * @param string $name        	
	 * @return string
	 */
	private static function clean_class($name) {
		zesk()->deprecated();
		return trim(preg_replace('/-_+/', '_', preg_replace('/[^a-z0-9]/i', '_', $name)), "_");
	}
}
