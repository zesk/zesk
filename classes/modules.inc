<?php

/**
 * Class for module loading, management, and configuration
 *
 * Ideally we should be able to serialize this entire structure and load again from cache so side-effects should be
 * tracked when loading modules (hooks, etc.) or repeated upon __wakeup() in your module itself.
 */
class Modules {
	const status_failed = "failed";
	const status_loaded = "loaded";
	
	/**
	 *
	 * @var zesk\Application
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
	 * Create the Modules
	 *
	 * @param Application $application        	
	 */
	public function __construct(zesk\Application $application) {
		$this->application = $application;
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
		$module_paths = zesk::module_path();
		$files = array();
		$options = array(
			'rules_file' => array(
				'#.*\.module\.(inc|conf)$#' => true,
				false
			),
			'rules_directory_walk' => array(
				'#/\.#' => false,
				true
			),
			'rules_directory' => false
		);
		foreach ($module_paths as $module_path) {
			$files[$module_path] = dir::list_recursive($module_path, $options);
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
		$module_paths = zesk::module_path();
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
	public final function register_paths($module_path = null, $module = null) {
		$current_name = first($this->module_loader);
		if ($current_name) {
			$current = avalue($this->modules, $current_name);
			if ($module_path === null) {
				$module_path = avalue($current, 'path');
			}
			if ($module === null) {
				$module = avalue($current, 'name');
			}
		}
		$result = array();
		if ($module_path) {
			$path = path($module_path, "classes");
			if (is_dir($path)) {
				$result['autoload_path'] = $path;
				$this->application->autoload_path($path);
			}
			$path = path($module_path, "theme");
			if (is_dir($path)) {
				$result['theme_path'] = $path;
				$this->application->theme_path($path);
			}
			if ($module) {
				$path = path($module_path, "share");
				if (is_dir($path)) {
					$result['share_path'] = $path;
					$this->application->share_path($path, $module);
				}
				$path = path($module_path, "command");
				if (is_dir($path)) {
					$result['zesk_command_path'] = $path;
					$this->application->zesk_command_path($path);
				}
				$path = path($module_path, "etc/language");
				if (is_dir($path)) {
					$result['locale_path'] = $path;
					zesk\Locale::locale_path($path);
				}
			}
		}
		return $result;
	}
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
	private function _load_module(array $module_data) {
		$name = $path = $class = $include = null;
		extract($module_data, EXTR_IF_EXISTS);
		
		global $zesk;
		
		$internal = $class === null ? false : class_exists($class, false);
		
		$this->modules[$name] = $module_data + array(
			'loading' => true
		);
		if ($include) {
			$zesk->hooks->call("module::load;$name::load", $name);
			array_unshift($this->module_loader, $name);
			// This may be called recursively
			$this->_load_module_include($include, $module_data);
			array_shift($this->module_loader);
		} else if (!$internal) {
			$result = $this->register_paths($path, $name);
			if (count($result) === 0) {
				$zesk->autoloader->path($path);
			}
			$module_data['status'] = 'loaded paths';
		}
		unset($this->modules[$name]['loading']);
		
		$module_data['loaded'] = true;
		$module_data['loaded_time'] = microtime(true);
		$module_data += array(
			'status' => self::status_loaded
		);
		$module_data = $this->_load_module_object($module_data) + $module_data;
		
		zesk::add(__CLASS__ . "::loaded", $name);
		
		$this->modules[$name] = $module_data;
		
		$this->application->hooks->call("Module_$name::loaded;Module::loaded", $name);
		
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
		if (class_exists(($class = "Module_" . $name), false)) {
			$module_data['class'] = $class;
			$module_data['internal'] = true;
		} else {
			$module_data['internal'] = false;
			$module_data['path'] = $module_path = zesk::find_directory(zesk::module_path(), $name);
			if ($module_path === null) {
				throw new Exception_Directory_NotFound(__CLASS__ . "::module($name) was not found");
			}
			if (avalue($options, "check exists")) {
				$result[$name] = $module_data;
				return $result;
			}
			$module_data += self::_find_module_include($module_data);
		}
		
		if (to_bool(avalue($options, 'load', true))) {
			$result[$name] = $this->_load_module($module_data);
			// Apply share_path automatically
			$share_path = apath($module_data, 'configuration.share_path');
			if ($share_path) {
				if (!dir::is_absolute($share_path)) {
					$share_path = $this->application->application_root($share_path);
				}
				if (!is_dir($share_path)) {
					log::warning("Module {module} share path {share_path} is not a directory", $module_data);
				} else {
					$this->application->share_path($share_path, $name);
				}
			}
			// Load dependent modules
			$requires = apath($module_data, 'configuration.requires');
			foreach (to_array($requires) as $required_module) {
				if (!apath($this->modules, array(
					$required_module,
					"loaded"
				))) {
					$result += self::_load_one($required_module, $options);
				}
			}
		} else {
			$result[$name] = $module_data;
		}
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
		$module_data['include'] = $module_include = zesk::find_file(array(
			$path
		), array(
			"$base.module.inc",
			"$base.application.inc"
		));
		
		$module_variables = array(
			'module_path' => $path,
			'module' => $name
		);
		$module_config = self::module_configuration_options($module_variables);
		$module_config['settings'] = $settings = new Adapter_Settings_Array($module_variables);
		$module_conf = path($path, "$base.module.json");
		$module_data['configuration_file'] = $module_conf;
		if (file_exists($module_conf)) {
			try {
				$configuration = json::decode(file_get_contents($module_conf));
			} catch (Exception_Parse $e) {
				throw new Exception_File_Format($module_conf);
			}
			$module_data['configuration_raw'] = $configuration;
			$module_data['configuration'] = zesk\bash::substitute($configuration, $settings);
		} else {
			$module_conf = path($path, "$base.module.conf");
			if (file_exists($module_conf)) {
				$module_data['configuration_file'] = $module_conf;
				$module_data['configuration_options'] = $module_config;
				$module_data['configuration'] = file_exists($module_conf) ? conf::load($module_conf, $module_config) : null;
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
		$name = $class = $configuration = null;
		extract($module_data, EXTR_IF_EXISTS);
		if (!$class) {
			$class = "Module_" . self::clean_class($module_data['base']);
		}
		try {
			/* @var $module_object Module */
			$module_object = zesk::factory($class, $this->application, $configuration, $module_data);
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
			} catch (Exception $e) {
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
				'class' => null
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
								$data = json::decode($contents, true);
								break;
							default :
								return null;
						}
						$version = arr::path($data, $key, null);
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
		$module_paths = zesk::module_path();
		foreach ($modules as $module) {
			$module = self::clean_name($module);
			if (array_key_exists($module, $this->modules)) {
				$result[$module] = true;
			} else {
				$module_path = zesk::find_directory($module_paths, $module);
				if (!$module_path) {
					$result[$module] = false;
				} else {
					$file = zesk::find_file(array(
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
	 * Get full path to module
	 *
	 * @param string $module        	
	 * @param mixed $default
	 *        	Value to return if module is not loaded
	 * @return string
	 */
	public final function path($module, $default = null) {
		return $this->data($module, "path", $default);
	}
	
	/**
	 * Get Module
	 *
	 * @param string $module        	
	 * @param mixed $default
	 *        	Value to return if module is not loaded
	 * @return Module|null
	 */
	public final function object($module, $default = null) {
		return $this->data($module, "module", $default);
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
		return $this->all_hook_array($hook, $arguments);
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
	public final function all_hook_array($hook, array $arguments, $default = null, $hook_callback = null, $result_callback = null) {
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
			$new_result = $module->hook_array($hook, $arguments, $default, $hook_callback, $result_callback);
			$result = Hookable::combine_hook_results($result, $new_result);
		}
		return $result;
		
		return $result;
	}
	
	/**
	 * List all hooks which would be called by all modules.
	 *
	 * @param string $hook        	
	 * @param array $arguments        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public final function all_hook_list($hook) {
		$result = zesk::find_all_hooks("Module::$hook");
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
		return trim(preg_replace('/-_+/', '_', preg_replace('/[^a-z0-9]/i', '_', $name)), "_");
	}
}