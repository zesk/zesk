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

namespace zesk\Application;

use Throwable;
use zesk\Adapter\SettingsArray;
use zesk\Application;
use zesk\ArrayTools;
use zesk\CaseArray;
use zesk\Configuration\Parser;
use zesk\Directory;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\UnsupportedException;
use zesk\File;
use zesk\Module;
use zesk\PHP;
use zesk\StringTools;
use zesk\Types;

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
			$this->application->debug(__METHOD__);
		}
		foreach ($this->modules as $module) {
			$module->shutdown();
		}
		$this->modules = new CaseArray();
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
	 * @throws NotFoundException
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
			], Directory::LIST_RULE_DIRECTORY => false, Directory::LIST_ADD_PATH => true,
		];
		foreach ($module_paths as $module_path) {
			try {
				$files[$module_path] = Directory::listRecursive($module_path, $options);
			} catch (ParameterException) {
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
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParseException
	 * @throws UnsupportedException
	 */
	final public function load(string $name): Module {
		$name = self::cleanName($name);
		if ($this->modules->offsetExists($name)) {
			return $this->modules[$name];
		}

		try {
			return $this->modules[$name] = $this->_loadModule($name);
		} catch (ClassNotFound|DirectoryNotFound $e) {
			/*
			 * ClassNotFound requirements not found, or module class not found
			 * DirectoryNotFound means no path found in modules which matches the name
			 * Exception_Invalid bad configuration file format
			 */
			throw new NotFoundException('Unable to load {name} {exceptionClass} {message}', ['name' => $name] + $e->variables(), 0, $e);
		}
	}

	/**
	 * @param array $names
	 * @return array
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParseException
	 * @throws UnsupportedException
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
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws ParseException
	 * @throws UnsupportedException
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
	 * @param string $modulePath Module associated with the system path (used for share directory)
	 * @return void Array of actually registered paths
	 * @throws DirectoryNotFound
	 */
	private function registerPaths(string $modulePath, array $configuration): void {
		if (!is_dir($modulePath)) {
			throw new DirectoryNotFound($modulePath);
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
	 * @throws DirectoryNotFound
	 */
	private function _handleAutoloadPath(string $modulePath, array $moduleConfiguration): void {
		$app = $this->application;
		$autoload = Types::toArray($moduleConfiguration['autoload'] ?? []);
		$this->_handleModuleDefaults($modulePath, 'path', $autoload, 'classes', function ($path) use ($app, $autoload): void {
			$app->addAutoloadPath($path, $autoload);
		});
	}

	/**
	 * @param string $modulePath
	 * @param array $moduleConfiguration
	 * @return void
	 * @throws DirectoryNotFound
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
	 * @throws DirectoryNotFound
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
	 * @throws DirectoryNotFound
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
	 * @throws DirectoryNotFound
	 */
	private function _handleModuleDefaults(string $modulePath, string $configurationKey, array $configuration, string $default, callable $adder): void {
		if ($configurationValue = $configuration[$configurationKey] ?? null) {
			$path = Directory::path($modulePath, $configurationValue);
			$adder($path);
		} else {
			$path = Directory::path($modulePath, $default);
			if (is_dir($path)) {
				$adder($path);
			}
		}
	}

	/**
	 * @param string|array $requires List of required modules
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws ParseException
	 * @throws UnsupportedException
	 */
	private function _handleRequires(string|array $requires): void {
		foreach (Types::toList($requires) as $required_module) {
			$required_module = self::cleanName($required_module);
			if (!$this->modules->offsetExists($required_module)) {
				$this->_loadModule($required_module);
			}
		}
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws DirectoryNotFound
	 */
	private function _findModulePath(string $name): string {
		$modulePath = $this->application->modulePath();

		try {
			return Directory::findFirst($modulePath, $name);
		} catch (NotFoundException) {
			throw new DirectoryNotFound($name, '{name} was not found in {modulePath}', [
				'class' => get_class($this), 'name' => $name, 'modulePath' => $modulePath,
				'base' => self::moduleBaseName($name),
			]);
		}
	}

	/**
	 * @param string $name
	 * @return Module
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws UnsupportedException|ParseException
	 */
	private function _loadModule(string $name): Module {
		assert(!str_contains($name, '\\'));

		$moduleFactoryState['path'] = $path = $this->_findModulePath($name);
		$moduleFactoryState['configurationFile'] = $file = ArrayTools::first(glob("$path/*\.module\.json"));
		[$data, $configuration] = self::_loadModuleJSON($file);

		$this->registerPaths($path, $configuration);

		$requires = Types::toList($configuration['requires'] ?? []);
		$this->_handleRequires($requires);

		$moduleFactoryState['configurationData'] = $data;
		$moduleFactoryState['configuration'] = $configuration;

		$module = $this->modules[$name] = $this->_moduleInitialize($this->_createModuleObject($name, $moduleFactoryState));
		if (count($this->loadHooks)) {
			foreach (array_keys($this->loadHooks) as $hookName) {
				$module->invokeHooks($hookName, [$this->application]);
				$module->invokeObjectHooks($hookName, [$this->application]);
			}
		}
		return $module;
	}

	/**
	 * @param Module $module
	 * @return Module
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws UnsupportedException
	 * @throws ParseException
	 */
	public function _reloadModule(Module $module): Module {
		$this->_handleRequires(Types::toList($module->moduleConfiguration()['requires'] ?? []));
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
	 * @throws ParseException
	 */
	private static function _loadModuleJSON(string $file): array {
		$raw_module_conf = file_get_contents($file);
		$extension = File::extension($file);
		$config = [];

		try {
			Parser::factory($extension, $raw_module_conf, new SettingsArray($config))->process();
		} catch (ClassNotFound $e) {
			throw new ParseException('Unable to load extension {extension} for configuration file {file}', [
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
	 * @throws ClassNotFound
	 */
	private function _createModuleObject(string $name, array $moduleFactoryState): Module {
		$class = $moduleFactoryState['class'] ?? $moduleFactoryState['configuration']['moduleClass'] ?? '';
		if (!$class) {
			$class = $this->defaultModuleClass($name);
			$configurationPath = [$class];

			try {
				return $this->_moduleFactory($class, $configurationPath, $moduleFactoryState);
			} catch (ClassNotFound) {
			}
			$class = Module::class;
			$configurationPath = [Module::class, $name];
		} else {
			$configurationPath = [$class];
		}
		$module_object = $this->_moduleFactory($class, $configurationPath, $moduleFactoryState);
		assert($module_object instanceof Module);
		return $module_object;
	}

	/**
	 * @param string $class
	 * @param array $moduleOptionsConfigurationPath
	 * @param array $moduleFactoryState
	 * @return Module
	 * @throws ClassNotFound
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
	 * @throws ConfigurationException
	 * @throws UnsupportedException
	 */
	private function _moduleInitialize(Module $object): Module {
		try {
			$object->initialize();
			if ($this->debug) {
				$this->application->debug('Initialized module object {class}', [
					'class' => $object::class,
				]);
			}
			return $object;
		} catch (ConfigurationException|UnsupportedException $e) {
			$this->application->error('Failed to initialize module object {class}: {message}', [
				'class' => $object::class, 'message' => $e->getMessage(),
			]);
			$this->application->invokeHooks(Application::HOOK_EXCEPTION, [$this->application, $e]);

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
	 * @throws NotFoundException
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
	 * @throws NotFoundException
	 */
	final public function path(string $module, string $append = ''): string {
		return $this->module($module)->path($append);
	}

	/**
	 * Get Module
	 *
	 * @param string $module
	 * @return Module
	 * @throws NotFoundException
	 */
	final public function object(string $module): Module {
		$module = self::cleanName($module);
		if ($this->modules->offsetExists($module)) {
			return $this->modules[$module];
		}

		throw new NotFoundException('No module object for {module} found', ['module' => $module]);
	}

	/**
	 * @param string $module
	 * @return Module
	 * @throws NotFoundException
	 */
	final public function get(string $module): Module {
		return $this->object($module);
	}

	/**
	 * @param string $module
	 * @return Module
	 * @throws NotFoundException
	 */
	final public function module(string $module): Module {
		return $this->object($module);
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
