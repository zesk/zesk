<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Paths {
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
	public string $application = '';

	/**
	 * Temporary files directory
	 *
	 * @var string
	 */
	public string $temporary = '';

	/**
	 * Data files directory
	 *
	 * @var string
	 */
	public string $data = '';

	/**
	 * Cache files directory
	 *
	 * @var string
	 */
	public string $cache = '';

	/**
	 * Current user home directory
	 *
	 * @var string
	 */
	public string $home = '';

	/**
	 * Current user home directory
	 *
	 * @var string
	 */
	public string $uid = '';

	/**
	 *
	 * @var array
	 */
	private array $which_cache = [];

	/**
	 * Paths constructor.
	 *
	 * Modifies and initializes global HOME
	 *
	 * Adds a configured hook
	 *
	 * @param Kernel $zesk
	 * @see $_SERVER['HOME']
	 */
	public function __construct(Kernel $zesk) {
		$config = $zesk->configuration;
		$this->_initializeZesk($config);
		$this->_initializeSystem();

		$zesk->hooks->add(Hooks::HOOK_CONFIGURED, $this->configured(...));
	}

	/**
	 * Get/Set data storage path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function application(string $suffix = ''): string {
		return path($this->application, $suffix);
	}

	/**
	 * Set the application path, updating temp, data, and cache paths
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setApplication(string $set): self {
		$this->application = rtrim($set, '/');
		$this->_initializeApplication();
		return $this;
	}

	/**
	 * configured hook
	 *
	 * @param Application $application
	 * @throws Exception_Directory_NotFound
	 */
	public function configured(Application $application): void {
		$configuration = $application->configuration;

		$paths = $configuration->path(__CLASS__);

		if (isset($paths->command)) {
			$this->addCommand(strval($paths->command));
		}
		// cache
		if (isset($paths->cache)) {
			$this->cache = strval($paths->cache);
		}
		// data
		if (isset($paths->data)) {
			$this->data = strval($paths->data);
		}
		if (isset($paths->home)) {
			$this->setHome(strval($paths->home));
		}
		if (isset($paths->uid)) {
			$this->uid = strval($paths->uid);
		}
		$this->_updateConfiguration($application);
	}

	/**
	 * created hook
	 *
	 * @param Application $application
	 */
	public function created(Application $application): void {
		$this->_updateConfiguration($application);
	}

	private function _updateConfiguration(Application $application): void {
		$application->configuration->set('HOME', $this->home);
	}

	/**
	 * @param string $home
	 * @return $this
	 * @throws Exception_Directory_NotFound
	 */
	public function setHome(string $home): self {
		if (!is_dir($home)) {
			throw new Exception_Directory_NotFound($home);
		}
		$this->home = $home;
		return $this;
	}

	/**
	 * Return a path relative to Zesk root
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function zesk(string $suffix = ''): string {
		return path(ZESK_ROOT, $suffix);
	}

	/**
	 *
	 * @param Configuration $config
	 */
	private function _initializeZesk(Configuration $config): void {
		$zesk_root = dirname(__DIR__) . '/';
		if (!defined('ZESK_ROOT')) {
			define('ZESK_ROOT', $zesk_root);
		} elseif (ZESK_ROOT !== $zesk_root) {
			die('Two versions of zesk: First "' . ZESK_ROOT . "\", then us \"$zesk_root\"\n");
		}
		$config->path(__CLASS__)->set('root', $zesk_root);
	}

	/**
	 */
	private function _initializeSystem(): void {
		$this->which_cache = [];
		$this->home = $_SERVER['HOME'] ?? '';
		$this->uid = $this->home('.zesk');
	}

	/**
	 */
	private function _initializeApplication(): void {
		if ($this->application) {
			$this->temporary = path($this->application, 'cache/temp');
			$this->data = path($this->application, 'data');
			$this->cache = path($this->application, 'cache');
		}
	}

	/**
	 * Get the system command path, usually defined by the system environment variable PATH
	 * The path is set from $_SERVER['PATH'] and it is assumed that paths are separated with PATH_SEPARATOR token
	 *
	 * @return array
	 * @see self::which
	 */
	public function command(): array {
		return toList($_SERVER['PATH'], [], PATH_SEPARATOR);
	}

	/**
	 * Get the system command path, usually defined by the system environment variable PATH
	 * The path is set from $_SERVER['PATH'] and it is assumed that paths are separated with PATH_SEPARATOR token
	 *
	 * @param string $add
	 * @return $this
	 * @see self::which
	 */
	public function addCommand(string $add): self {
		$command_paths = $this->command();
		$command_paths[] = $add;
		$_SERVER['PATH'] = implode(PATH_SEPARATOR, $command_paths);
		return $this;
	}

	/**
	 * Get temporary path, optionally adding a suffix to the path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function temporary(string $suffix = ''): string {
		return path($this->temporary, $suffix);
	}

	/**
	 * @return void
	 */
	public function shutdown(): void {
		// Pass. Maybe delete temporary directory.
	}

	/**
	 * Get/Set data storage path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function data(string $suffix = ''): string {
		return path($this->data, $suffix);
	}

	/**
	 * Directory for storing temporary cache files
	 *
	 * @param string $suffix
	 *            Added file or directory to add to cache page
	 * @return string Path to file within the cache paths
	 */
	public function cache(string $suffix = ''): string {
		return path($this->cache, $suffix);
	}

	/**
	 * Home directory of current process user, generally passed via the $_SERVER['HOME']
	 * global.
	 *
	 * If not a directory, or global not set, returns null
	 *
	 * @param string $suffix
	 *            Added file or directory to add to home path
	 * @return string Path to file within the current user's home path
	 */
	public function home(string $suffix = ''): string {
		return $this->home ? path($this->home, $suffix) : '';
	}

	/**
	 * User configuration path - place to put configuration files, etc.
	 * for this user
	 *
	 * Defaults to $HOME/.zesk/
	 *
	 * @param string $suffix Append to uid path
	 * @return string
	 */
	public function uid(string $suffix = ''): string {
		return $this->uid ? path($this->uid, $suffix) : '';
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
	public function expand(string $file): string {
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
	 * @throws Exception_NotFound
	 */
	public function which(string $command): string {
		if (array_key_exists($command, $this->which_cache)) {
			return $this->which_cache[$command];
		}
		foreach ($this->command() as $path) {
			if (!is_dir($path)) {
				continue;
			}
			$path = path($path, $command);
			if (is_executable($path)) {
				return $this->which_cache[$command] = $path;
			}
		}

		throw new Exception_NotFound('{command} not found in {paths}', [
			'command' => $command, 'paths' => $this->command(),
		]);
	}

	/**
	 * Retrieve path settings as variables
	 *
	 * @return string[]
	 */
	public function variables(): array {
		return [
			'zesk' => ZESK_ROOT, 'application' => $this->application, 'temporary' => $this->temporary,
			'data' => $this->data, 'cache' => $this->cache, 'home' => $this->home, 'uid' => $this->uid,
			'command' => implode(':', $this->command()),
		];
	}
}
