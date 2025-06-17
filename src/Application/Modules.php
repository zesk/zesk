<?php
declare(strict_types=1);
/**
 * Class for module loading, management, and configuration
 *
 * Ideally, we should be able to serialize this entire structure and load again from cache so side effects should be
 * tracked when loading modules (hooks, etc.) or repeated upon __wakeup() in your module itself.
 *
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Application;

use Throwable;
use zesk\Application;
use zesk\CaseArray;
use zesk\Configuration;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\File;
use zesk\Module;
use zesk\JSON;

/**
 * Module loading and management
 *
 * @see    Module
 * @author kent
 *
 */
class Modules {
	
	private array $moduleClasses = [];
	
	private bool $moduleClassesLoaded = false;
	
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
	 * Create the Modules
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application)
	{
		$this->application = $application;
		$this->modules = new CaseArray();
		$this->debug = $application->configuration->getPath([
			__CLASS__,
			'debug',
		], $this->debug);
	}
	
	public function shutdown(): void
	{
		if ($this->debug) {
			$this->application->debug(__METHOD__);
		}
		foreach ($this->modules as $module) {
			$module->shutdown();
		}
		$this->modules = new CaseArray();
	}
	
	/**
	 * Current list of loaded modules in the system
	 *
	 * @return array
	 */
	final public function moduleNames(): array
	{
		return $this->modules->keys();
	}
	
	/**
	 * Dynamically determine the module version
	 *
	 * @param string $module
	 * @return string
	 * @throws NotFoundException
	 */
	final public function version(string $module): string
	{
		return $this->module($module)->version();
	}
	
	/**
	 * Return list of available modules (list of names)
	 *
	 * @return array
	 */
	final public function available(): array
	{
		try {
			return array_keys($this->availableConfiguration());
		} catch (Throwable) {
			return [];
		}
	}
	
	/**
	 * @return array
	 * @throws ParseException
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	final public function availableConfiguration(): array
	{
		if ($this->moduleClassesLoaded) {
			return $this->moduleClasses;
		}
		return $this->moduleClasses = JSON::decode(File::contents('./etc/modules.json'));
	}
	
	/**
	 * @param string $module
	 * @return bool
	 */
	final public function validModule(string $module): bool
	{
		try {
			$configuration = $this->availableConfiguration();
			return boolval($configuration["$module"] ?? false);
		} catch (Throwable) {
			return false;
		}
	}
	
	/**
	 * Is a module loaded?
	 *
	 * @param string $name
	 * @return bool
	 */
	final public function loaded(string $name): bool
	{
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
	 * @throws NotFoundException|SemanticsException
	 */
	final public function load(string $name): Module
	{
		$name = self::cleanName($name);
		if ($this->modules->offsetExists($name)) {
			return $this->modules[$name];
		}
		
		try {
			return $this->modules[$name] = $this->_loadModule($name);
		} catch (ClassNotFound $e) {
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
	 * @return array
	 * @throws ConfigurationException
	 * @throws NotFoundException|SemanticsException
	 */
	final public function loadMultiple(array $names): array
	{
		$result = [];
		foreach ($names as $name) {
			$result[$name] = $this->load($name);
		}
		return $result;
	}
	
	/**
	 * @param string $name
	 * @return Module
	 * @throws NotFoundException|ClassNotFound|ConfigurationException|SemanticsException
	 */
	private function _loadModule(string $name): Module
	{
		assert(!str_contains($name, '\\'));
		
		$lowerName = strtolower($name);
		$module = $this->modules[$lowerName] ?? null;
		if ($module) {
			throw new SemanticsException("$module already loaded");
		}
		if (!$this->validModule($lowerName)) {
			throw new NotFoundException('{name} not found', ['name' => $name]);
		}
		$module = $this->application->factory($this->moduleClasses[$lowerName], $this->application);
		$module->checkDependencies();
		$module->initialize();
		
		$this->modules[$lowerName] = $module;
		if (count($this->loadHooks)) {
			foreach (array_keys($this->loadHooks) as $hookName) {
				$module->invokeHooks($hookName, [$this->application]);
				$module->invokeObjectHooks($hookName, [$this->application]);
			}
		}
		return $module;
	}
	
	/**
	 * @param string $string
	 * @return void
	 */
	public function addLoadHook(string $string): void
	{
		$this->loadHooks[$string] = true;
	}
	
	/**
	 * Can a module be loaded?
	 *
	 * @param string $name
	 * @return bool
	 */
	final public function exists(string $name): bool
	{
		try {
			return $this->availableConfiguration()["$name"] !== null ?? false;
		} catch (Throwable) {
			return false;
		}
	}
	
	/**
	 * @return Module[]
	 */
	final public function all(): array
	{
		return $this->modules->getArrayCopy();
	}
	
	/**
	 * Retrieve configuration information about a loaded module
	 *
	 * @param string $module
	 * @return Configuration
	 * @throws NotFoundException
	 */
	final public function configuration(string $module): Configuration
	{
		return $this->module($module)->moduleConfiguration();
	}
	
	/**
	 * Get Module
	 *
	 * @param string $module
	 * @return Module
	 * @throws NotFoundException
	 */
	final public function object(string $module): Module
	{
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
	final public function get(string $module): Module
	{
		return $this->object($module);
	}
	
	/**
	 * @param string $module
	 * @return Module
	 * @throws NotFoundException
	 */
	final public function module(string $module): Module
	{
		return $this->object($module);
	}
	
	/**
	 * Clean a module name
	 *
	 * @param array|string $modules
	 * @return array|string
	 */
	public static function cleanName(array|string $modules): array|string
	{
		if (is_string($modules)) {
			return Module::cleanName($modules);
		}
		foreach ($modules as $k => $name) {
			$modules[$k] = Module::cleanName($name);
		}
		return $modules;
	}
	
	/**
	 * @return void
	 */
	public function configure(): void
	{
		foreach ($this->modules as $module) {
			$module->configure();
		}
	}
}
