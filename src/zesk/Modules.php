<?php
declare(strict_types=1);
/**
 * Class for module loading, management, and configuration
 *
 * Ideally we should be able to serialize this entire structure and load again from cache so side effects should be
 * tracked when loading modules (hooks, etc.) or repeated upon __wakeup() in your module itself.
 *
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Throwable;

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
	 * @var boolean
	 */
	private bool $debug = false;

	/**
	 *
	 * @var Application
	 */
	private Application $application;

	/**
	 * Loaded modules in the system
	 */
	private CaseArray $modules;

	/**
	 * Hooks to call for modules when loaded
	 *
	 * @var array
	 */
	private array $loadHooks = [];

	/**
	 * Loaded modules in the system
	 *
	 * @var array of hook name => list of module names (ordered)
	 */
	private array $modulesWithHook = [];

	/**
	 *
	 * @var string
	 */
	private string $moduleClassPrefix;

	/**
	 * Create the Modules
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$this->application = $application;
		$this->modules = new CaseArray();
		$this->moduleClassPrefix = $application->configuration->getPath([
			__CLASS__, 'moduleClassPrefix',
		], Module::class . '_');
		$this->debug = $application->configuration->getPath([
			__CLASS__, 'debug',
		], $this->debug);
	}

	public function shutdown(): void {
		if ($this->debug) {
			$this->application->logger->debug(__METHOD__);
		}
		foreach ($this->modules as $module) {
			$module->shutdown();
		}
		$this->modules = new CaseArray();
		$this->modulesWithHook = [];
		$this->moduleClassPrefix = '';
	}

	/**
	 * Current list of loaded modules in the system
	 *
	 * @return array
	 */
	final public function moduleNames(): array {
		return $this->modules->keys();
	}

	/**
	 * Dynamically determine the module version
	 *
	 * @param string $module
	 * @return string
	 * @throws Exception_NotFound
	 */
	final public function version(string $module): string {
		return $this->module($module)->version();
	}

	/**
	 * Return list of available modules (list of names)
	 *
	 * @return array
	 */
	final public function available(): array {
		return array_keys($this->availableConfiguration());
	}

	/**
	 * Return [moduleName => configurationFilePath]
	 *
	 * @return array
	 */
	final public function availableConfiguration(): array {
		$module_paths = $this->application->modulePath();
		$files = [];
		/* Walk all non-dot directories, looking for .module.json files */
		$options = [
			Directory::LIST_RULE_FILE => [
				'#\.module\.json$#' => true, false,
			], Directory::LIST_RULE_DIRECTORY_WALK => [
				'#/\.#' => false, true,
			], Directory::LIST_RULE_DIRECTORY => false,
			Directory::LIST_ADD_PATH => true,
		];
		foreach ($module_paths as $module_path) {
			try {
				$files[$module_path] = Directory::listRecursive($module_path, $options);
			} catch (Exception_Parameter) {
			}
		}
		$available = [];
		foreach ($files as $module_files) {
			foreach ($module_files as $module_file) {
				$module = trim(StringTools::removePrefix(dirname($module_file), $module_paths), '/');
				$available[$module] = $module_file;
			}
		}
		return $available;
	}

	/**
	 * Is a module loaded?
	 *
	 * @param string $name
	 * @return bool
	 */
	final public function loaded(string $name): bool {
		return $this->modules->offsetExists($name);
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
	 * @param string $name Module name
	 * @return Module
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_Unsupported
	 */
	final public function load(string $name): Module {
		$name = self::cleanName($name);
		if ($this->modules->offsetExists($name)) {
			return $this->modules[$name];
		}

		try {
			$module = $this->modules[$name] = $this->_loadModule($name);
			$this->modulesWithHook = [];
			return $module;
		} catch (Exception_Class_NotFound|Exception_Directory_NotFound|Exception_Invalid $e) {
			/*
			 * Exception_Class_NotFound requirements not found, or module class not found
			 * Exception_Directory_NotFound means no path found in modules which matches the name
			 * Exception_Invalid bad configuration file format
			 */
			throw new Exception_NotFound('Unable to load {name} {exceptionClass} {message}', ['name' => $name] + $e->variables(), 0, $e);
		}
	}

	/**
	 * @throws Exception_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Unsupported
	 */
	final public function loadMultiple(array $names): array {
		$result = [];
		foreach ($names as $name) {
			$result[$name] = $this->load($name);
		}
		return $result;
	}

	/**
	 *
	 * @return Modules
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Invalid
	 * @throws Exception_Unsupported
	 */
	final public function reload(): self {
		foreach ($this->modules as $codename => $module) {
			$this->modules[$codename] = $this->_reloadModule($module);
		}
		return $this;
	}

	/**
	 * During module registration, register system paths automatically.
	 * Either a specified path
	 * or uses the current module's path, looks for the following directories and registers:
	 * classes/ - $application->autoloader->path
	 * theme/ - Application::themePath
	 * share/ - Application::sharePath
	 * command/ - Application::zeskCommandPath
	 * bin/ - $application->paths->command_path
	 *
	 * @param string $name Directory to search for system paths
	 * @param string $modulePath Module associated with the system path (used for share directory)
	 * @return void Array of actually registered paths
	 * @throws Exception_Directory_NotFound
	 */
	private function registerPaths(string $name, string $modulePath, array $configuration): void {
		if (!is_dir($modulePath)) {
			throw new Exception_Directory_NotFound($modulePath);
		}
		$this->_handleAutoloadPath($modulePath, $configuration);
		$this->_handleThemePath($modulePath, $configuration);
		$this->_handleLocalePath($modulePath, $configuration);
		$this->_handleZeskCommandPath($modulePath, $configuration);
	}

	/**
	 * @param string $modulePath
	 * @param array $moduleConfiguration
	 * @return void
	 * @throws Exception_Directory_NotFound
	 */
	private function _handleAutoloadPath(string $modulePath, array $moduleConfiguration): void {
		$app = $this->application;
		$autoload = toArray($moduleConfiguration['autoload'] ?? []);
		$this->_handleModuleDefaults($modulePath, 'path', $autoload, 'classes', function ($path) use ($app, $autoload): void {
			$app->addAutoloadPath($path, $autoload);
		});
	}

	/**
	 * @param string $modulePath
	 * @param array $moduleConfiguration
	 * @return void
	 * @throws Exception_Directory_NotFound
	 */
	private function _handleThemePath(string $modulePath, array $moduleConfiguration): void {
		$app = $this->application;
		$themePathPrefix = $moduleConfiguration['themePathPrefix'] ?? '';
		$this->_handleModuleDefaults($modulePath, 'themePath', $moduleConfiguration, 'theme', function ($path) use ($app, $themePathPrefix): void {
			$app->addThemePath($path, $themePathPrefix);
		});
	}

	/**
	 * @param string $modulePath
	 * @param array $moduleConfiguration
	 * @return void
	 * @throws Exception_Directory_NotFound
	 */
	private function _handleLocalePath(string $modulePath, array $moduleConfiguration): void {
		$app = $this->application;
		$this->_handleModuleDefaults($modulePath, 'localePath', $moduleConfiguration, 'etc/language', function ($path) use ($app): void {
			$app->addLocalePath($path);
		});
	}

	/**
	 * @param string $modulePath
	 * @param array $moduleConfiguration
	 * @return void
	 * @throws Exception_Directory_NotFound
	 */
	private function _handleZeskCommandPath(string $modulePath, array $moduleConfiguration): void {
		$app = $this->application;
		$this->_handleModuleDefaults($modulePath, 'zeskCommandPath', $moduleConfiguration, 'command', function ($path) use ($app): void {
			$app->addZeskCommandPath($path);
		});
	}

	/**
	 * @param string $modulePath
	 * @param string $configurationKey
	 * @param array $configuration
	 * @param string $default
	 * @param callable $adder
	 * @return void
	 * @throws Exception_Directory_NotFound
	 */
	private function _handleModuleDefaults(string $modulePath, string $configurationKey, array $configuration, string $default, callable $adder): void {
		if ($configurationValue = $configuration[$configurationKey] ?? null) {
			$path = path($modulePath, $configurationValue);
			$adder($path);
		} else {
			$path = path($modulePath, $default);
			if (is_dir($path)) {
				$adder($path);
			}
		}
	}

	/**
	 * @param string|array $requires List of required modules
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Invalid
	 * @throws Exception_Unsupported
	 */
	private function _handleRequires(string|array $requires): void {
		foreach (toArray($requires) as $required_module) {
			$required_module = self::cleanName($required_module);
			if (!$this->modules->offsetExists($required_module)) {
				$this->_loadModule($required_module);
			}
		}
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws Exception_Directory_NotFound
	 */
	private function _findModulePath(string $name): string {
		$modulePath = $this->application->modulePath();

		try {
			return Directory::findFirst($modulePath, $name);
		} catch (Exception_NotFound) {
			throw new Exception_Directory_NotFound($name, '{name} was not found in {modulePath}', [
				'class' => get_class($this), 'name' => $name, 'modulePath' => $modulePath, 'base' =>
					self::moduleBaseName($name),
			]);
		}
	}

	/**
	 * @param string $name
	 * @return Module
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Invalid
	 * @throws Exception_Unsupported
	 */
	private function _loadModule(string $name): Module {
		assert(!str_contains($name, '\\'));

		$moduleFactoryState['path'] = $path = $this->_findModulePath($name);
		$moduleFactoryState['configurationFile'] = $file = first(glob("$path/*\.module\.json"));
		[$data, $configuration] = self::_loadModuleJSON($file);

		$this->registerPaths($name, $path, $configuration);

		$requires = toList($configuration['requires'] ?? []);
		$this->_handleRequires($requires);

		$moduleFactoryState['configurationData'] = $data;
		$moduleFactoryState['configuration'] = $configuration;

		$module = $this->modules[$name] = $this->_moduleInitialize($this->_createModuleObject($name, $moduleFactoryState));
		if (count($this->loadHooks)) {
			$module->callHook(array_keys($this->loadHooks), $this->application);
		}
		return $module;
	}

	/**
	 * @param Module $module
	 * @return Module
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Invalid
	 * @throws Exception_Unsupported
	 */
	public function _reloadModule(Module $module): Module {
		// Paths are taken from the module configuration which comes from a file. Should not have to re-do, ever.
		// $this->registerPaths($module->name(), $module->path(), $module->moduleConfiguration());
		$this->_handleRequires(toList($module->moduleConfiguration()['requires'] ?? []));
		return $this->_moduleInitialize($module);
	}

	/**
	 * @param string $string
	 * @return void
	 */
	public function addLoadHook(string $string): void {
		$this->loadHooks[$string] = true;
	}

	/**
	 * Finds the module configuration file and loads it.
	 *
	 * @throws Exception_Invalid
	 */
	private static function _loadModuleJSON(string $file): array {
		$raw_module_conf = file_get_contents($file);
		$extension = File::extension($file);
		$config = [];

		try {
			Configuration_Parser::factory($extension, $raw_module_conf, new Adapter_Settings_Array($config))->process();
		} catch (Exception_Parse $e) {
			throw new Exception_Invalid('{message} loading configuration file {file}', [
				'message' => $e->getMessage(), 'file' => $file,
			], 0, $e);
		} catch (Exception_Class_NotFound $e) {
			throw new Exception_Invalid('Unable to load extension {extension} for configuration file {file}', [
				'extension' => $extension, 'file' => $file,
			], 0, $e);
		}
		return [$raw_module_conf, $config];
	}

	private function defaultModuleClass(string $name): string {
		return $this->moduleClassPrefix . PHP::cleanClass(self::moduleBaseName($name));
	}

	/**
	 * Instantiate the module object. Does NOT call initialize, calling function MUST call initialize when object
	 * is ready to be initialized.
	 *
	 * @param string $name
	 * @param array $moduleFactoryState
	 * @return Module
	 * @throws Exception_Class_NotFound
	 */
	private function _createModuleObject(string $name, array $moduleFactoryState): Module {
		$class = $moduleFactoryState['class'] ?? $moduleFactoryState['configuration']['moduleClass'] ?? '';
		if (!$class) {
			$class = $this->defaultModuleClass($name);
			$configurationPath = [$class];

			try {
				return $this->_moduleFactory($class, $configurationPath, $moduleFactoryState);
			} catch (Exception_Class_NotFound) {
			}
			$class = Module::class;
			$configurationPath = [Module::class, $name];
		} else {
			$configurationPath = [$class];
		}
		$module_object = $this->_moduleFactory($class, $configurationPath, $moduleFactoryState);
		assert($module_object instanceof Module);
		$this->application->hooks->registerClass($class);
		return $module_object;
	}

	/**
	 * @param string $class
	 * @param array $moduleOptionsConfigurationPath
	 * @param array $moduleFactoryState
	 * @return Module
	 * @throws Exception_Class_NotFound
	 */
	private function _moduleFactory(string $class, array $moduleOptionsConfigurationPath, array $moduleFactoryState): Module {
		$moduleFactoryState['class'] = $class;
		$moduleFactoryState['optionsPath'] = $moduleOptionsConfigurationPath;
		$module = $this->application->factory($class, $this->application, $this->application->configuration->path($moduleOptionsConfigurationPath)->toArray(), $moduleFactoryState);
		assert($module instanceof Module);
		return $module;
	}

	/**
	 * @param Module $object
	 * @return Module
	 * @throws Exception_Configuration
	 * @throws Exception_Unsupported
	 */
	private function _moduleInitialize(Module $object): Module {
		try {
			$object->initialize();
			if ($this->debug) {
				$this->application->logger->debug('Initialized module object {class}', [
					'class' => $object::class,
				]);
			}
			return $object;
		} catch (Exception_Configuration|Exception_Unsupported $e) {
			$this->application->logger->error('Failed to initialize module object {class}: {message}', [
				'class' => $object::class, 'message' => $e->getMessage(),
			]);
			$this->application->hooks->call('exception', $e);

			throw $e;
		}
	}

	/**
	 * Can a module be loaded?
	 *
	 * @param string $name
	 * @return bool
	 */
	final public function exists(string $name): bool {
		try {
			$this->_findModulePath($name);
			return true;
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * @return array
	 */
	final public function all(): array {
		return $this->modules->getArrayCopy();
	}

	/**
	 * Retrieve configuration information about a loaded module
	 *
	 * @param string $module
	 * @return array
	 * @throws Exception_NotFound
	 */
	final public function configuration(string $module): array {
		return $this->module($module)->moduleConfiguration();
	}

	/**
	 * Get full path to module
	 *
	 * @param string $module
	 * @param string $append Value to append to path using path()
	 * @return string
	 * @throws Exception_NotFound
	 */
	final public function path(string $module, string $append = ''): string {
		return $this->module($module)->path($append);
	}

	/**
	 * Get Module
	 *
	 * @param string $module
	 * @return Module
	 * @throws Exception_NotFound
	 */
	final public function object(string $module): Module {
		$module = self::cleanName($module);
		if ($this->modules->offsetExists($module)) {
			return $this->modules[$module];
		}

		throw new Exception_NotFound('No module object for {module} found', ['module' => $module]);
	}

	/**
	 * @param string $module
	 * @return Module
	 * @throws Exception_NotFound
	 */
	final public function get(string $module): Module {
		return $this->object($module);
	}

	/**
	 * @param string $module
	 * @return Module
	 * @throws Exception_NotFound
	 */
	final public function module(string $module): Module {
		return $this->object($module);
	}

	/**
	 * Run hooks across all modules loaded
	 *
	 * @param string $hook Hook name
	 * @return mixed
	 */
	final public function allHook(string $hook): mixed {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->allHookArguments($hook, $arguments);
	}

	/**
	 * Partner to hook_all - runs with an arguments array command and a default return value
	 * Used for filters where a specific result should be returned by each function
	 *
	 * @param string $hook
	 * @param array $arguments
	 * @param mixed $default
	 * @param callable|null $hook_callback
	 * @param callable|null $result_callback
	 * @return mixed
	 */
	final public function allHookArguments(string $hook, array $arguments = [], mixed $default = null, callable $hook_callback = null, callable $result_callback = null): mixed {
		$hooks = $this->collectAllHooks($hook, $arguments);
		$result = $default;
		foreach ($hooks as $item) {
			[$callable, $arguments] = $item;
			$result = Hookable::hookResults($result, $callable, $arguments, $hook_callback, $result_callback);
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
	final public function collectAllHooks(string $hook, array $arguments): array {
		$module_names = $this->modulesWithHook[$hook] ?? null;
		if (!is_array($module_names)) {
			$module_names = [];
			foreach ($this->modules as $name => $module) {
				if ($module->hasHook($hook)) {
					$module_names[] = $name;
				}
			}
			$this->modulesWithHook[$hook] = $module_names;
		}
		$hooks = [];
		foreach ($module_names as $module_name) {
			$module = $this->modules[$module_name];
			$hooks = array_merge($hooks, $module->collectHooks($hook, $arguments));
		}
		return $hooks;
	}

	/**
	 * List all hooks which would be called by all modules.
	 *
	 * @param string $hook
	 * @return array
	 * @todo This does not match all_hook_arguments called list? (Module::$hook, etc.)
	 * @todo Add test for this
	 */
	final public function listAllHooks(string $hook): array {
		$result = [];
		foreach ($this->modules as $module) {
			$result = array_merge($result, $module->listHooks($hook, true));
		}
		return $result;
	}

	/**
	 * Clean a module name
	 *
	 * @param array|string $modules
	 * @return array|string
	 */
	public static function cleanName(array|string $modules): array|string {
		if (is_string($modules)) {
			return Module::cleanName($modules);
		}
		foreach ($modules as $k => $name) {
			$modules[$k] = Module::cleanName($name);
		}
		return $modules;
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
	private static function moduleBaseName(string $module): string {
		return basename(self::cleanName($module));
	}
}
