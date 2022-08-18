<?php
declare(strict_types=1);
/**
 * Class for module loading, management, and configuration
 *
 * Ideally we should be able to serialize this entire structure and load again from cache so side-effects should be
 * tracked when loading modules (hooks, etc.) or repeated upon __wakeup() in your module itself.
 *
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Module loading and management
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
	public const status_failed = 'failed';

	/**
	 *
	 * @var string
	 */
	public const status_loaded = 'loaded';

	/**
	 *
	 * @var boolean
	 */
	private bool $debug = false;

	/**
	 *
	 * @var Application
	 */
	private Application $application;

	/**
	 * Stack of currently loading modules.
	 * Top item is current module loading. First item is top of stack.
	 *
	 * @var array
	 */
	private array $module_loader = [];

	/**
	 * Loaded modules in the system
	 *
	 * @var array of module name => array of module information (class of Module)
	 */
	private array $modules = [];

	/**
	 * Loaded modules in the system
	 *
	 * @var array of hook name => list of module names (ordered)
	 */
	private array $modules_with_hook = [];

	/**
	 *
	 * @var string
	 */
	private string $module_class_prefix = 'Module_';

	/**
	 * Create the Modules
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$this->application = $application;
		$this->module_class_prefix = $application->configuration->path_get([
			__CLASS__,
			'module_class_prefix',
		], $this->module_class_prefix);
		$this->debug = $application->configuration->path_get([
			__CLASS__,
			'debug',
		], $this->debug);
	}

	/**
	 * Dynamically determine the module version
	 *
	 * @param mixed $mixed
	 *            Array of modules.
	 */
	final public function version($mixed = null) {
		$modules = ($mixed === null) ? array_keys($this->modules) : to_list($mixed);
		$result = [];
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
	final public function available(): array {
		$module_paths = $this->application->modulePath();
		$files = [];
		$options = [
			'rules_file' => [
				'#.*\.module\.(inc|conf|json)$#' => true,
				false,
			],
			'rules_directory_walk' => [
				'#/\.#' => false,
				true,
			],
			'rules_directory' => false,
		];
		foreach ($module_paths as $module_path) {
			$files[$module_path] = Directory::list_recursive($module_path, $options);
		}
		$dirs = [];
		foreach ($files as $module_path => $files) {
			foreach ($files as $file) {
				$module = dirname($file);
				$dirs[dirname($file)][$module] = $module;
			}
		}
		$available = [];
		foreach ($dirs as $path => $modules) {
			asort($modules);
			foreach ($modules as $module) {
				$available[$module] = $this->load($module, [
					'load' => false,
				]);
			}
		}
		return $available;
	}

	/**
	 * What modules are loaded?
	 *
	 * @param string $mixed
	 *            Check if one or more module is loaded
	 *
	 * @return array
	 */
	final public function loaded(string|array $mixed = null): array {
		if ($mixed === null) {
			$result = $this->modules;
		} else {
			$result = [];
			$modules = toList($mixed);
			foreach ($modules as $index => $module) {
				$module = $modules[$index] = self::clean_name($module);
				$result[$module] = $this->modules[$module] ?? [];
			}
		}
		return ArrayTools::collapse($result, self::status_loaded, false);
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
	 *            Module name or array of module names
	 * @param array $options
	 *            Loading options
	 *            - "check loaded" set to true to just check if it's loaded.
	 *            - "not loaded" set to a value (any value) which is returned when a module is not
	 *            loaded
	 *            - "check exists" set to true to check if the module exists. If it is, basic
	 *            information is passed back (module configuation is not loaded, for example).
	 *            - "load" set to false to gather only basic information (including configuration),
	 *            but not load the module. (similar to check exists - duplicate functionality?)
	 *
	 * @return Module array of module => module_data as described above, or for single modules, just
	 *         the module data
	 *         array
	 */
	final public function load(string|array $mixed, array $options = []) {
		$passed_modules = self::clean_name(toList($mixed));
		$modules = self::_expand_modules($passed_modules);
		$result = [];
		$module_paths = $this->application->modulePath();
		foreach ($modules as $index => $name) {
			$name = self::clean_name($name);
			$module_data = $this->modules[$name] ?? null;
			if (is_array($module_data)) {
				$result[$name] = $this->modules[$name] + [
						'status' => 'already loaded',
					];

				continue;
			} else if ($options['check loaded'] ?? false) {
				$result[$name] = $options['not loaded'] ?? null;

				continue;
			}
			$result += $this->_loadFile($name, $options);
		}
		$result = ArrayTools::filter($result, $passed_modules);
		if (count($passed_modules) === 1) {
			return $result[strtolower($passed_modules[0])];
		}
		return $result;
	}

	/**
	 *
	 * @param array $options
	 * @return \zesk\Modules
	 */
	final public function reload(array $options = []) {
		$this->application->logger->debug(__METHOD__);
		$modules = [];
		foreach ($this->modules as $codename => $module_data) {
			$modules[$codename] = $this->_reloadFile($module_data);
		}
		$this->modules = $modules;
		return $this;
	}

	/**
	 * During module registration, register system paths automatically.
	 * Either a specified path
	 * or uses the current module's path, looks for the following directories and registers:
	 * classes/ - $application->autoloader->path
	 * theme/ - Application::theme_path
	 * share/ - Application::share_path
	 * command/ - Application::zesk_command_path
	 * bin/ - $application->paths->command_path
	 *
	 * @param string $module_path
	 *            Directory to search for system paths
	 * @param string $module
	 *            Module associated with the system path (used for share directory)
	 * @return array Array of actually registered paths
	 */
	final public function register_paths($module_path = null, $module = null) {
		$current_name = first($this->module_loader);
		$current = [];
		if ($current_name) {
			$current = avalue($this->modules, $current_name, []);
			if ($module_path === null) {
				$module_path = avalue($current, 'path');
			}
			if ($module === null) {
				$module = avalue($current, 'name');
			}
		} else if ($module == null) {
			throw new Exception_Parameter('Require a $module path if not called during module load');
		} else if (isset($this->modules[$module])) {
			if (!is_dir($module_path)) {
				throw new Exception_Directory_NotFound($module_path);
			}
		} else {
			throw new Exception_Semantics('Can not call register paths unless during module load.');
		}
		$configuration = avalue($current, 'configuration', []);
		$result = [];
		$autoload_class_prefix = avalue($configuration, 'autoload_class_prefix');
		$autoload_path = avalue($configuration, 'autoload_path', 'classes');
		$autoload_options = to_array(avalue($configuration, 'autoload_options', []));
		$theme_path = avalue($configuration, 'theme_path', 'theme');
		$theme_path_prefix = avalue($configuration, 'theme_path_prefix', null);
		$zesk_command_path = avalue($configuration, 'zesk_command_path', 'command');
		$deprecated_class_prefix = apath($configuration, 'zesk_command_path_class_prefix');
		if ($deprecated_class_prefix) {
			$this->application->deprecated("$module_path uses deprecated zesk_command_path_class_prefix. Use zesk_command_class_prefix instead.");
		}
		$zesk_command_path_class_prefix = apath($configuration, 'zesk_command_class_prefix', $deprecated_class_prefix);
		$locale_path = apath($configuration, 'locale_path', 'etc/language');
		if (!$module_path) {
			return $result;
		}
		$path = path($module_path, $autoload_path);
		if (is_dir($path)) {
			$result['autoload_path'] = $path;
			if ($autoload_class_prefix) {
				$autoload_options += [
					'class_prefix' => $autoload_class_prefix,
				];
				zesk()->deprecated('Module configuration "autoload_class_prefix" is deprecated for module >>{module}<<, use "autoload_options": { "class_prefix": ... } instead', compact('module'));
			}
			$this->application->addAutoloadPath($path, $autoload_options);
		}
		$path = path($module_path, $theme_path);
		if (is_dir($path)) {
			$result['theme_path'] = $path;
			$result['theme_path_prefix'] = $theme_path_prefix;
			$this->application->theme_path($path, $theme_path_prefix);
		}
		if (!$module) {
			return $result;
		}
		$path = path($module_path, 'share');
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
			$prefix = 'Command_';
			if ($zesk_command_path_class_prefix) {
				$prefix = $zesk_command_path_class_prefix;
				if ($prefix !== PHP::clean_class($prefix)) {
					$this->application->logger->error('zesk_command_path_class_prefix specified in module {name} configuration is not a valid class prefix "{prefix}"', [
						'name' => $current['name'],
						'prefix' => $prefix,
					]);
				}
			}
			$this->application->zesk_command_path($path, $zesk_command_path_class_prefix);
		}
		$path = path($module_path, $locale_path);
		if (is_dir($path)) {
			$result['locale_path'] = $path;
			$this->application->locale_path($path);
		}
		return $result;
	}

	/**
	 * Load module based on setup options
	 *
	 * @param array $module_data
	 *
	 * @return array
	 */
	private function _load_module_configuration(array $module_data) {
		$name = $path = null;
		extract($module_data, EXTR_IF_EXISTS);

		$this->modules[$name] = $module_data + [
				'loading' => true,
			];
		array_unshift($this->module_loader, $name);
		$this->register_paths();

		array_shift($this->module_loader);
		unset($this->modules[$name]['loading']);

		$module_data['loaded'] = true;
		$module_data['loaded_time'] = microtime(true);
		$module_data += [
			'status' => self::status_loaded,
		];

		return $module_data;
	}

	private function _handle_share_path(array $module_data) {
		// Apply share_path automatically
		$share_path = apath($module_data, 'configuration.share_path');
		if ($share_path) {
			if (!Directory::isAbsolute($share_path)) {
				$share_path = $this->application->path($share_path);
			}
			$name = apath($module_data, 'configuration.share_path_name', $module_data['name']);
			if (!is_dir($share_path)) {
				$this->application->logger->critical('Module {module} share path "{share_path}" is not a directory', [
					'module' => $name,
					'share_path' => $share_path,
				]);
			} else {
				$this->application->share_path($share_path, $name);
			}
		}
		return $module_data;
	}

	private function _handle_requires($requires, array $options) {
		// Load dependent modules
		$result = [];
		foreach (toArray($requires) as $required_module) {
			$required_module = self::clean_name($required_module);
			if (!apath($this->modules, [
				$required_module,
				'loaded',
			])) {
				$result += $this->_loadFile($required_module, $options);
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
	 * @param array $options Flags
	 *  "check exists" => true to test for existence of module class (just class_exists)
	 *  "load" => defaults to true => Load the module and create the Module object and return the initialized structure
	 * @return array
	 * @throws Exception_Directory_NotFound
	 */
	private function _loadFile($name, array $options) {
		if (str_contains($name, '\\')) {
			return $this->_autoloadFile($name, $options);
		}

		$result = [];
		$base = self::module_base_name($name);
		$module_data = [
			'loaded' => false,
			'name' => $name,
			'base' => $base,
		];

		$module_data['path'] = $module_path = Directory::find_first($this->application->modulePath(), $name);
		if ($module_path === null) {
			throw new Exception_Directory_NotFound($base, '{class}::module({name}) was not found at {name}', [
				'class' => get_class($this),
				'name' => $name,
				'base' => $base,
			]);
		}
		if ($options['check exists'] ?? false) {
			$result[$name] = $module_data;
			return $result;
		}
		$module_data += self::_find_module_include($module_data);
		if (toBool($options['load'] ?? true)) {
			$module_data = $this->_load_module_configuration($module_data);
			$module_data = $this->_apply_module_configuration($module_data);

			$this->modules[$name] = $module_data;
			$result += $this->_handle_configuration_requires($module_data, $options);
			$this->modules[$name] = $module_data = $this->_load_module_object($module_data) + $module_data;

			$module_data = $this->_initialize_module_object($module_data);
		}

		$result[$name] = $module_data;
		return $result;
	}

	/**
	 *
	 * @param array $module_data
	 * @param array $options
	 * @return array
	 */
	private function _handle_configuration_requires(array $module_data, array $options) {
		$result = [];
		$requires = toList(apath($module_data, 'configuration.requires'));
		if ($requires) {
			$result += $this->_handle_requires($requires, $options);
		}
		return $result;
	}

	/**
	 * Return an array to build the modules structure, specifically for class-based module names
	 *
	 * @param string $name Module name
	 * @param array $options Flags
	 *  "check exists" => true to test for existence of module class (just class_exists)
	 *  "load" => defaults to true => Load the module and create the Module object and return the initialized structure
	 *
	 * @return array
	 * @throws Exception_Class_NotFound
	 */
	private function _autoloadFile($name, array $options) {
		$module_data = [
			'loaded' => false,
			'name' => $name,
			'class' => $name,
		];
		if (avalue($options, 'check exists')) {
			if (class_exists($name, true)) {
				return $module_data;
			}

			throw new Exception_Class_NotFound($name, 'Loading module');
		}
		$result = [];
		if (toBool(avalue($options, 'load', true))) {
			$this->modules[$name] = $module_data = $this->_load_module_object($module_data) + $module_data;
			/* @var $object Module */
			$object = $module_data['object'];
			if ($object) {
				$module_data['path'] = $object->path();
				$module_data += self::_find_module_include($module_data);
				$module_data = $this->_load_module_configuration($module_data);
				$result += $this->_handle_configuration_requires($module_data, $options);
				$module_data = $this->_apply_module_configuration($module_data);

				$module_data = $this->_initialize_module_object($module_data);
			}
		}
		return $result + [
				$name => $module_data,
			];
	}

	public function _reloadFile($module_data) {
		$object = $module_data['object'];
		if ($object) {
			$module_data = $this->_load_module_configuration($module_data);
			$module_data = $this->_apply_module_configuration($module_data);

			$module_data = $this->_initialize_module_object($module_data);
		}
		return $module_data;
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
		$module_variables = [
			'module_path' => $path,
			'name' => $name,
			'module' => $name,
		];
		$module_config = self::module_configuration_options($module_variables);
		$module_config['settings'] = $settings = new Adapter_Settings_Array($module_variables);

		$module_confs = [
			'json' => path($path, "$base.module.json"),
			'conf' => path($path, "$base.module.conf"),
		];
		foreach ($module_confs as $extension => $module_conf) {
			if (file_exists($module_conf)) {
				$raw_module_conf = file_get_contents($module_conf);
				$configuration = new Configuration($module_variables);
				$module_data['configuration'] = [];
				Configuration_Parser::factory($extension, $raw_module_conf, new Adapter_Settings_Array($module_data['configuration']))->process();
				$module_data['configuration_file'] = $module_conf;
			}
		}
		return $module_data;
	}

	/**
	 * Instantiate the module object. Does NOT call initialize, calling function MUST call initialize when object
	 * is ready to be initialized.
	 *
	 * @param array $module_data
	 * @return Module null
	 * @throws Exception_Semantics
	 */
	private function _load_module_object(array $module_data) {
		$name = $class = null;
		$configuration = [];
		extract($module_data, EXTR_IF_EXISTS);
		if (!$class) {
			$class = avalue($configuration, 'module_class', $this->module_class_prefix . PHP::clean_class($module_data['base']));
		}

		try {
			/* @var $module_object Module */
			$module_object = $this->application->factory($class, $this->application, $configuration, $module_data);
			if (!$module_object instanceof Module) {
				throw new Exception_Semantics('Module {class} must be a subclass of Module - skipping', [
					'class' => get_class($module_object),
				]);
			}
			if (method_exists($module_object, 'hooks')) {
				$this->application->hooks->registerClass($class);
			}
			$module_object->codename = $name;
			$result = [
				'class' => $class,
			];
			return $result + [
					'path' => $module_object->path(),
					'object' => $module_object,
				];
		} catch (Exception_Class_NotFound $e) {
			return [
				'object' => null,
				'class' => null,
				'missing_class' => $e->class,
			];
		}
	}

	private function _initialize_module_object(array $module_data) {
		$object = $module_data['object'];

		try {
			if ($object) {
				$object->initialize();
				if ($this->debug) {
					$this->application->logger->debug('Initialized module object {class}', [
						'class' => get_class($object),
					]);
				}
			}
			return $module_data;
		} catch (\Exception $e) {
			$this->application->logger->error('Failed to initialize module object {class}: {message}', [
				'class' => get_class($object),
				'message' => $e->getMessage(),
			]);
			$this->application->hooks->call('exception', $e);
			return [
					'object' => null,
					'status' => 'failed',
					'initialize_exception' => $e,
				] + $module_data;
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
		$result = [];
		foreach ($modules as $module) {
			$parts = explode('/', $module);
			$module = [];
			foreach ($parts as $part) {
				$module[] = $part;
				$result[] = implode('/', $module);
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
		$module = $this->load($name, [
			'load' => false,
		]);
		$configuration = avalue($module, 'configuration', []);
		if (is_array($configuration)) {
			$version = avalue($configuration, 'version');
			if ($version !== null) {
				return $version;
			}
			$version_data = avalue($configuration, 'version_data');
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
						switch ($ext = File::extension($file)) {
							case 'phps':
								$data = unserialize($contents);

								break;
							case 'json':
								$data = JSON::decode($contents, true);

								break;
							default:
								return null;
						}
						$version = apath($data, $key, null);
						return $version;
					}
				}
			}
		}

		try {
			$object = $this->object($name);
			return $object->option('version');
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * Module conf::load settings
	 *
	 * @param array $variables
	 */
	private static function module_configuration_options() {
		$options = [
			'lower' => true,
			'trim' => true,
			'multiline' => true,
			'unquote' => '\'\'""',
		];
		return $options;
	}

	/**
	 * Is a module loaded?
	 *
	 * @param $mixed Modules
	 *            to check
	 * @return array boolean
	 */
	final public function exists($mixed = null) {
		if ($mixed === null) {
			return array_fill_keys(array_keys($this->modules), true);
		}
		$result = [];
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
					$file = File::find_first([
						$module_path,
					], [
						"$module.module.inc",
						"$module.application.inc",
						"$module.module.json",
						"$module.module.conf",
					]);
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
	final public function all_modules() {
		$result = [];
		foreach ($this->modules as $name => $data) {
			if (!array_key_exists('object', $data)) {
				continue;
			}
			$module = $data['object'];
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
	final public function data($module, $option, $default = null) {
		return avalue($this->load($module, [
			'check loaded' => true,
			'not loaded' => [],
		]), $option, $default);
	}

	/**
	 * Retrieve configuration information about a loaded module
	 *
	 * @param string $module
	 * @param string $option
	 * @param mixed $default
	 * @return mixed
	 */
	final public function configuration($module, $option_path, $default = null) {
		return apath($this->data($module, 'configuration', []), $option_path, $default);
	}

	/**
	 * Get full path to module
	 *
	 * @param string $module
	 * @param mixed $append
	 *            Value to append to path using path()
	 * @return string|null Returns null if module not loaded
	 */
	final public function path($module, $append = null) {
		$path = $this->data($module, 'path');
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
	 *            Value to return if module is not loaded
	 * @return Module
	 * @throws Exception_NotFound
	 */
	final public function object($module, $default = null) {
		$result = $this->data($module, 'object');
		if ($result instanceof Module) {
			return $result;
		}

		throw new Exception_NotFound('No module object for {module} found', compact('module'));
	}

	/**
	 * Run hooks across all modules loaded
	 *
	 * @param string $hook
	 *            Hook name
	 * @return mixed
	 */
	final public function all_hook($hook) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->all_hook_arguments($hook, $arguments);
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
	final public function all_hook_arguments($hook, array $arguments, $default = null, $hook_callback = null, $result_callback = null) {
		$hooks = $this->collect_all_hooks($hook, $arguments);
		$result = $default;
		foreach ($hooks as $item) {
			[$callable, $arguments] = $item;
			$result = Hookable::hook_results($result, $callable, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 * Collects all hooks
	 *
	 * @param string $hook
	 * @param array $arguments
	 * @return callable[]
	 */
	final public function collect_all_hooks(string $hook, array $arguments): array {
		$module_names = $this->modules_with_hook[$hook] ?? null;
		if (!is_array($module_names)) {
			$module_names = [];
			foreach ($this->modules as $name => $data) {
				if (!array_key_exists('object', $data)) {
					continue;
				}
				/* @var $module Module */
				if (($module = $data['object']) !== null && $module->has_hook($hook)) {
					$module_names[] = $name;
				}
			}
			$this->modules_with_hook[$hook] = $module_names;
		}
		$hooks = [];
		foreach ($module_names as $module_name) {
			$module = $this->modules[$module_name]['object'];
			if ($module instanceof Module) {
				$hooks = array_merge($hooks, $module->collect_hooks($hook, $arguments));
			} else {
				$this->application->logger->error("While calling hook {hook} for module for {module_name} is not of type zesk\Module ({value} is of type {type})", [
					'hook' => $hook,
					'module_name' => $module_name,
					'value' => to_text($module),
					'type' => type($module),
				]);
			}
		}
		return $hooks;
	}

	/**
	 * List all hooks which would be called by all modules.
	 *
	 * @param string $hook
	 * @param array $arguments
	 * @param mixed $default
	 * @return mixed
	 * @todo This does not match all_hook_arguments called list? (Module::$hook, etc.)
	 * @todo Add test for this
	 */
	final public function all_hook_list(string $hook) {
		$result = $this->application->hooks->find_all(["zesk\\Module::$hook"]);
		if (count($result) > 0) {
			$this->application->deprecated('Static cache clear hook is deprecated: ' . _dump($result));
		}
		foreach ($this->modules as $name => $data) {
			$module = avalue($data, 'object');
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
	 * @param string|array $module
	 * @return string|array
	 */
	public static function clean_name($module) {
		if (is_array($module)) {
			foreach ($module as $index => $m) {
				$module[$index] = self::clean_name($m);
			}
			return $module;
		}
		if (str_contains($module, '\\')) {
			return PHP::clean_class($module);
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
}
