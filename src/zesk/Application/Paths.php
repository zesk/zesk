<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Application;

use zesk\Application;
use zesk\Configuration;
use zesk\Directory;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\NotFoundException;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Paths {
	/**
	 * @see self::__construct()
	 * @see self::setHome()
	 * @see self::_updateConfiguration()
	 * @var string
	 */
	public const ENVIRONMENT_HOME = 'HOME';

	/**
	 * Value is : or ; separated list of paths
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
	protected string $temporary = './cache/temp';

	/**
	 * Data files directory
	 *
	 * @var string
	 */
	protected string $data = './data';

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

	/**
	 * Paths constructor.
	 *
	 * Modifies $configuration by setting keys in Application::class and self::ENVIRONMENT_HOME
	 *
	 * Adds a configured hook
	 *
	 * @param Configuration $configuration Root configuration
	 * @see self::ENVIRONMENT_HOME
	 * @see Application::OPTION_HOME_PATH
	 */
	public function __construct(Configuration $configuration) {
		$env = [];
		if (array_key_exists(self::ENVIRONMENT_HOME, $_SERVER)) {
			$env += [Application::OPTION_HOME_PATH => $_SERVER[self::ENVIRONMENT_HOME]];
		}
		$appConfiguration = $configuration->path(Application::class);
		$result = $this->_initializeZesk() + $this->configure($appConfiguration->toArray(0) + $env);
		$appConfiguration->set($result);
		$configuration->set(self::ENVIRONMENT_HOME, $this->home());
	}

	/**
	 * Set up ZESK_ROOT and ensure we are not insane.
	 *
	 * @return array
	 */
	private function _initializeZesk(): array {
		$zeskRoot = realpath(dirname(__DIR__, 3)) . '/';
		if (!defined('ZESK_ROOT')) {
			define('ZESK_ROOT', $zeskRoot);
		} elseif (ZESK_ROOT !== $zeskRoot) {
			die('Two versions of zesk: First "' . ZESK_ROOT . "\", then us \"$zeskRoot\"\n");
		}
		return [Application::OPTION_ZESK_ROOT => $zeskRoot];
	}


	/**
	 */

	/**
	 * configure
	 *
	 * @param array $options
	 * @return array
	 */
	public function configure(array $options): array {
		$setters = [
			Application::OPTION_PATH => $this->setApplication(...),
			Application::OPTION_CACHE_PATH => $this->setTemporary(...),
			Application::OPTION_COMMAND_PATH => $this->addCommand(...),
			Application::OPTION_DATA_PATH => $this->setData(...),
			Application::OPTION_HOME_PATH => $this->setHome(...),
			Application::OPTION_USER_HOME_PATH => $this->setUserHome(...),
		];
		foreach ($setters as $key => $setter) {
			if (array_key_exists($key, $options)) {
				$setter(strval($options[$key]));
			}
		}
		return $this->_updateConfiguration();
	}

	/**
	 * @return void
	 */
	public function shutdown(): void {
		// Pass. Maybe delete temporary directories?
	}

	/**
	 * Set the application path. Does NOT support expand paths.
	 *
	 * @param string $set
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setApplication(string $set): self {
		if (!is_dir($set)) {
			throw new DirectoryNotFound($set);
		}
		$this->application = rtrim($set, '/');
		return $this;
	}

	/**
	 * Set the home path for the user. Supports expand paths.
	 * Stored as expanded path.
	 *
	 * @param string $home
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	public function setHome(string $home): self {
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
	public function setUserHome(string $home): self {
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
	public function setTemporary(string $path): self {
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
	public function setData(string $path): self {
		if (!is_dir($this->expand($path))) {
			throw new DirectoryNotFound($path);
		}
		$this->data = $path;
		return $this;
	}

	/**
	 * Return a path relative to Zesk root
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function zesk(string $suffix = ''): string {
		return Directory::path(ZESK_ROOT, $suffix);
	}

	/**
	 * Get application path directory where foo.application.php resides.
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function application(string $suffix = ''): string {
		return Directory::path($this->application, $suffix);
	}

	/**
	 * Get the system command path, usually defined by the system environment variable PATH
	 * The path is set from $_SERVER['PATH'] and it is assumed that paths are separated with PATH_SEPARATOR token
	 *
	 * @return array
	 * @see self::which
	 */
	public function command(): array {
		return Types::toList($_SERVER[self::ENVIRONMENT_PATH], [], PATH_SEPARATOR);
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
	 * Get temporary path, optionally adding a suffix to the path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function temporary(string $suffix = ''): string {
		return Directory::path($this->expand($this->temporary), $suffix);
	}

	/**
	 * Get/Set data storage path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function data(string $suffix = ''): string {
		return Directory::path($this->expand($this->data), $suffix);
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
		return $this->home ? Directory::path($this->home, $suffix) : '';
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
	public function userHome(string $suffix = ''): string {
		return $this->userHome ? Directory::path($this->expand($this->userHome), $suffix) : '';
	}

	/**
	 * created hook
	 *
	 * @param Application $application
	 */
	public function created(Application $application): void {
		$application->configuration->merge(new Configuration($this->_updateConfiguration()));
	}

	/**
	 * Return an update to the global configuration object
	 *
	 * @return array
	 */
	private function _updateConfiguration(): array {
		$_SERVER[self::ENVIRONMENT_HOME] = $this->home();
		return [Application::OPTION_HOME_PATH => $this->home()];
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
	 * @throws NotFoundException
	 */
	public function which(string $command): string {
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
			'data' => $this->data, 'home' => $this->home, 'uid' => $this->userHome, 'command' => $this->command(),
		];
	}
}
