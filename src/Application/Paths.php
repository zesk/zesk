<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage kernel
 * @author     kent
 * @copyright  &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Application;

use zesk\Application;
use zesk\Configuration;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\NotFoundException;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Paths
{
	/**
	 * @var string
	 */
	public const ENVIRONMENT_HOME = 'HOME';

	/**
	 * Directory to store temporary files
	 */
	public const ENVIRONMENT_XDG_CACHE_HOME = 'XDG_CACHE_HOME';

	/**
	 * Directory to store data
	 */
	public const ENVIRONMENT_XDG_DATA_HOME = 'XDG_DATA_HOME';

	/**
	 * List of data directory paths separated by `:`
	 */
	public const ENVIRONMENT_XDG_DATA_DIRS = 'XDG_DATA_DIRS';

	/**
	 * List of configuration paths separated by `:`
	 */
	public const ENVIRONMENT_XDG_CONFIG_DIRS = 'XDG_CONFIG_DIRS';

	/**
	 * Value is `:`-separated (*nix) or `;`-separated (MS) list of paths
	 *
	 * @see self::command()
	 * @see self::addCommand()
	 * @var string
	 */
	public const ENVIRONMENT_PATH = 'PATH';

	/**
	 * @see self::expand()
	 * @see self::setUserHome()
	 * @see self::userHome()
	 */
	public const DEFAULT_USER_HOME = '~/.zesk';

	/**
	 * Debug various aspects of Paths
	 *
	 * @var bool
	 */
	public bool $debug = false;

	/**
	 * Root application directory
	 *
	 * @var string
	 */
	protected string $application = '';

	/**
	 * Temporary files directory
	 *
	 * @var string
	 */
	protected string $temporary = '';

	/**
	 * Data files directories
	 *
	 * @var string[]
	 */
	protected array $data = [];

	/**
	 * Current user home directory. Set based on $_SERVER['HOME']
	 *
	 * @var string
	 */
	protected string $home = '';

	/**
	 * Current user home directory
	 *
	 * @var string
	 */
	protected string $userHome = self::DEFAULT_USER_HOME;

	/**
	 *
	 * @var array
	 */
	private array $which_cache = [];

	protected int $directoryMode;

	/**
	 * @var bool
	 */
	protected bool $configured;

	/**
	 * `Paths` constructor.
	 *
	 * Modifies $configuration by setting keys in Application::class and self::ENVIRONMENT_HOME
	 *
	 * Adds a configured hook
	 *
	 */
	public function __construct()
	{
		$this->configured = false;
	}

	/**
	 * @param Configuration $configuration
	 * @param array $server
	 * @return void
	 * @throws DirectoryNotFound
	 */
	public function inheritEnvironment(Configuration $configuration, array $server): void
	{
		$serverVariables = [
			self::ENVIRONMENT_HOME => Application::OPTION_USER_HOME,
			self::ENVIRONMENT_XDG_CACHE_HOME => Application::OPTION_CACHE_HOME,
			self::ENVIRONMENT_XDG_DATA_HOME => Application::OPTION_DATA_HOME,
		];
		foreach ($serverVariables as $environmentName => $optionName) {
			$value = $server[$environmentName] ?? null;
			if ($value !== null) {
				$configuration->set($optionName, $value);
			}
		}
		$serverListVariables = [
			self::ENVIRONMENT_XDG_DATA_DIRS => Application::OPTION_DATA_DIRECTORY_LIST,
			self::ENVIRONMENT_XDG_CONFIG_DIRS => Application::OPTION_CONFIGURATION_DIRECTORY_LIST,
		];
		foreach ($serverListVariables as $environmentName => $optionName) {
			$value = $server[$environmentName] ?? null;
			if ($value !== null) {
				$paths = [];
				foreach (Types::toList($value, [], ':') as $directory) {
					$expanded = $this->expand($directory);
					Directory::must($expanded);
					$paths[] = $directory;
				}
				$configuration->set($optionName, $paths);
			}
		}
	}

	/**
	 * Set up ZESK_ROOT and ensure we are not insane.
	 *
	 * @return array
	 */
	private function _initializeZesk(): array
	{
		$zeskRoot = realpath(dirname(__DIR__, 2)) . '/';
		if (!defined('ZESK_ROOT')) {
			define('ZESK_ROOT', $zeskRoot);
		} elseif (ZESK_ROOT !== $zeskRoot) {
			die('Two versions of zesk: First "' . ZESK_ROOT . "\", then as \"$zeskRoot\"\n");
		}
		return [Application::OPTION_ZESK_ROOT => $zeskRoot];
	}

	/**
	 * @throws DirectoryCreate
	 */
	private function fetchFirstDefault(array|string $paths, string $defaultPath): string
	{
		foreach ($paths as $path) {
			$path = $this->expand($path);
			if (is_dir($path)) {
				return $path;
			}
		}
		if ($defaultPath) {
			$defaultPath = $this->expand($defaultPath);
			Directory::create($defaultPath, $this->directoryMode);
		}
		return $defaultPath;
	}

	/**
	 * @throws DirectoryCreate
	 */
	private function dataDirectory(): string
	{
		$appHomeData = Directory::path($this->application, '.data');
		return $this->fetchFirstDefault($this->data, $appHomeData);
	}

	public function setConfigured(bool $configured): void
	{
		$this->configured = $configured;
	}

	/**
	 *
	 */
	public function configured(): bool
	{
		return $this->configured;
	}

	/**
	 * configure
	 *
	 * @param Configuration $options
	 * @return void
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 */
	public function configure(Configuration $options): void
	{
		$setters = [
			Application::OPTION_PATH => $this->setApplication(...),
			Application::OPTION_CACHE_HOME => $this->setTemporary(...),
			Application::OPTION_COMMAND_HOME => $this->addCommand(...),
			Application::OPTION_DATA_HOME => $this->setData(...),
			Application::OPTION_DATA_DIRECTORY_LIST => $this->setDataList(...),
			Application::OPTION_USER_HOME => $this->setHome(...),
			Application::OPTION_USER_CONFIGURE_HOME => $this->setUserHome(...),
		];
		foreach ($setters as $key => $setter) {
			$path = $options->get($key);
			if ($path) {
				$setter(strval($path));
			}
		}
		$this->directoryMode = $options->getInt(Application::OPTION_DIRECTORY_CREATE_MODE, Application::MODE_DIRECTORY_CREATE);
		$options->set($this->_initializeZesk() + $this->_updateConfiguration());
		if (!$this->temporary) {
			$this->_temporaryDefault($options->getString(Application::OPTION_CACHE_SUFFIX, self::CACHE_SUFFIX));
		}
		$this->configured = true;
	}

	public const CACHE_SUFFIX = '.zeskCache';

	/**
	 * @param array $directories
	 * @return string|null
	 */
	private static function _temporaryDirectory(array $directories = []): string|null
	{
		$directories = array_merge([$_SERVER['TMPDIR'] ?? null, '/var/tmp', '/tmp'], $directories);
		foreach ($directories as $try) {
			if (is_string($try) && is_dir($try)) {
				return "$try";
			}
		}
		return null;
	}

	/**
	 * @param string $suffix
	 * @return void
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 */
	private function _temporaryDefault(string $suffix = self::CACHE_SUFFIX): void
	{
		$temp = self::_temporaryDirectory([$this->home]);
		if ($temp) {
			$this->temporary = Directory::path($temp, $suffix);
			Directory::depend($this->temporary);
		}
	}

	/**
	 * @return void
	 */
	public function shutdown(): void
	{
		// Pass. Maybe delete temporary directories?
	}

	/**
	 * Set the application path. Does NOT support expand paths.
	 *
	 * @param string $set
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setApplication(string $set): self
	{
		if (!is_dir($set)) {
			throw new DirectoryNotFound($set);
		}
		$this->application = rtrim($set, '/');
		return $this;
	}

	/**
	 * Set the home path for the user. Supports expand paths.
	 * Stored as the expanded path.
	 *
	 * @param string $home
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setHome(string $home): self
	{
		$home = $this->expand($home);
		if (!is_dir($home)) {
			throw new DirectoryNotFound($home);
		}
		$this->home = $home;
		return $this;
	}

	/**
	 * Set the home user configuration path for the user. Supports expand paths.
	 * Default is `~/.zesk`
	 *
	 * @param string $home
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setUserHome(string $home): self
	{
		if (!is_dir($this->expand($home))) {
			throw new DirectoryNotFound($home);
		}
		$this->userHome = $home;
		return $this;
	}

	/**
	 * Set the home user configuration path for the user. Supports expand paths.
	 * Default is `./cache/temp`
	 *
	 * @param string $path
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setTemporary(string $path): self
	{
		if (!is_dir($this->expand($path))) {
			throw new DirectoryNotFound($path);
		}
		$this->temporary = $path;
		return $this;
	}

	/**
	 * Set the data directory
	 * Default is `./data`
	 *
	 * @param string $path
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setData(string $path): self
	{
		if (!is_dir($this->expand($path))) {
			throw new DirectoryNotFound($path);
		}
		if (in_array($path, $this->data)) {
			return $this;
		}
		$this->data = array_merge([$path], $this->data);
		return $this;
	}

	/**
	 * Set the data directories
	 *
	 * @param array $paths
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setDataList(array $paths): self
	{
		foreach ($paths as $path) {
			if (!is_dir($this->expand($path))) {
				throw new DirectoryNotFound($path);
			}
		}
		$this->data = $paths;
		return $this;
	}

	/**
	 * Return a path relative to Zesk root
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function zesk(string $suffix = ''): string
	{
		return Directory::path(ZESK_ROOT, $suffix);
	}

	/**
	 * Get application path directory where foo.application.php resides.
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function application(string $suffix = ''): string
	{
		return Directory::path($this->application, $suffix);
	}

	/**
	 * Get the system command path, usually defined by the system environment variable PATH
	 * The path is set from `$_SERVER['PATH']`, and it is assumed that paths are separated with `PATH_SEPARATOR` token
	 *
	 * @return array
	 * @see self::which
	 */
	public function command(): array
	{
		return Types::toList($_SERVER[self::ENVIRONMENT_PATH], [], PATH_SEPARATOR);
	}

	/**
	 * Add to the system command path, usually defined by the system environment variable PATH
	 * The path is set from `$_SERVER['PATH']`, and it is assumed that paths are separated with `PATH_SEPARATOR` token
	 *
	 * @param string $add
	 * @return $this
	 * @see self::which
	 * @see self::ENVIRONMENT_PATH
	 */
	public function addCommand(string $add): self
	{
		$command_paths = $this->command();
		if (is_dir($add)) {
			$add = realpath($add);
		}
		if (!in_array($add, $command_paths)) {
			$command_paths[] = $add;
			$_SERVER[self::ENVIRONMENT_PATH] = implode(PATH_SEPARATOR, $command_paths);
		}
		return $this;
	}

	/**
	 * Get the temporary path, optionally adding a suffix to the path.
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function temporary(string $suffix = ''): string
	{
		return Directory::path($this->expand($this->temporary), $suffix);
	}

	/**
	 * Get/Set data storage path
	 *
	 * @param string $suffix
	 * @return string
	 * @throws DirectoryCreate
	 */
	public function data(string $suffix = ''): string
	{
		return Directory::path($this->expand($this->dataDirectory()), $suffix);
	}

	/**
	 * Home directory of current process user, generally passed via the $_SERVER['HOME']
	 * global.
	 *
	 * If not a directory, or global not set, returns null
	 *
	 * @param string $suffix
	 *            Added file or directory to add to the home path
	 * @return string Path to file within the current user's home path
	 */
	public function home(string $suffix = ''): string
	{
		return $this->home ? Directory::path($this->home, $suffix) : '';
	}

	/**
	 * User configuration path - place to put configuration files, etc.
	 * for this user
	 *
	 * Defaults to $HOME/.zesk/
	 *
	 * @param string $suffix Append to the user home path.
	 * @return string
	 */
	public function userHome(string $suffix = ''): string
	{
		return $this->userHome ? Directory::path($this->expand($this->userHome), $suffix) : '';
	}

	/**
	 * Return an update to the global configuration object
	 *
	 * @return array
	 */
	private function _updateConfiguration(): array
	{
		$_SERVER[self::ENVIRONMENT_HOME] = $this->home();
		return [
			Application::OPTION_USER_HOME => $this->home(),
			Application::OPTION_USER_CONFIGURE_HOME => $this->userHome(),
		];
	}

	/**
	 * Expand paths using magic tokens
	 *
	 *     "/" Absolute path
	 *     "~/" User directory (if exists)
	 *     "./" Application path
	 *     "~zesk/" Zesk code path
	 *
	 * @param mixed $file
	 * @return string
	 */
	public function expand(string $file): string
	{
		if ($file === '') {
			return $file;
		}
		if ($file[0] === '/') {
			return $file;
		}
		$prefix = '~/';
		if (str_starts_with($file, $prefix)) {
			return $this->home(substr($file, strlen($prefix)));
		}
		$prefix = './';
		if (str_starts_with($file, $prefix)) {
			return $this->application(substr($file, strlen($prefix)));
		}
		$prefix = '~zesk/';
		if (str_starts_with($file, $prefix)) {
			return $this->zesk(substr($file, strlen($prefix)));
		}
		return $file;
	}

	/**
	 * Similar to which command-line command.
	 * Returns executable path for command.
	 *
	 * @param string $command
	 * @return string
	 * @throws NotFoundException
	 */
	public function which(string $command): string
	{
		if (array_key_exists($command, $this->which_cache)) {
			return $this->which_cache[$command];
		}
		foreach ($this->command() as $path) {
			if (!is_dir($path)) {
				continue;
			}
			$path = Directory::path($path, $command);
			if (is_executable($path)) {
				return $this->which_cache[$command] = $path;
			}
		}

		throw new NotFoundException('{command} not found in {paths}', [
			'command' => $command,
			'paths' => $this->command(),
		]);
	}

	/**
	 * Retrieve path settings as variables
	 *
	 * @return string[]
	 */
	public function variables(): array
	{
		return [
			'zesk' => ZESK_ROOT,
			'application' => $this->application,
			'temporary' => $this->temporary,
			'data' => $this->data,
			'home' => $this->home,
			'uid' => $this->userHome,
			'command' => $this->command(),
		];
	}
}
