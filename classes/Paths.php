<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
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
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * Root application directory
	 *
	 * @var string
	 */
	public $application = null;

	/**
	 * Temporary files directory
	 *
	 * @var string
	 */
	public $temporary = null;

	/**
	 * Data files directory
	 *
	 * @var string
	 */
	public $data = null;

	/**
	 * Cache files directory
	 *
	 * @var string
	 */
	public $cache = null;

	/**
	 * Current user home directory
	 *
	 * @var string
	 */
	public $home = null;

	/**
	 * Current user home directory
	 *
	 * @var string
	 */
	public $uid = null;

	/**
	 *
	 * @var array
	 */
	private $which_cache = array();

	/**
	 * Constuct a new Paths manager
	 *
	 * Modifies and initializes global HOME
	 *
	 * Adds a configured hook
	 *
	 * @global HOME
	 * @param Configuration $config
	 */
	public function __construct(Kernel $zesk) {
		$config = $zesk->configuration;

		$this->_init_zesk_root($config);

		$this->_init_system_paths();

		$config->home = $this->home;

		$zesk->hooks->add(Hooks::hook_configured, array(
			$this,
			"configured"
		));
	}

	/**
	 * Get/Set data storage path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function application($suffix = null) {
		return path($this->application, $suffix);
	}

	/**
	 * configured hook
	 */
	public function configured(Application $application) {
		$configuration = $application->configuration;

		$paths = $configuration->path(__CLASS__);

		if ($paths->has('zesk_command_paths')) {
			$this->zesk_command($paths->zesk_command_paths);
		}
		// cache
		if ($paths->cache) {
			$this->cache = $paths->cache;
		}
		// data
		if (isset($paths->data)) {
			$this->data = $paths->data;
		}
		if (isset($paths->home)) {
			$this->home = $paths->home;
		}
		if (isset($paths->uid)) {
			$this->uid = $paths->uid;
		}
	}
	/**
	 * Return a path relative to Zesk root
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function zesk($suffix = null) {
		return path(ZESK_ROOT, $suffix);
	}

	/**
	 *
	 * @param Configuration $config
	 */
	private function _init_zesk_root(Configuration $config) {
		$zesk_root = dirname(__DIR__) . "/";
		if (!defined('ZESK_ROOT')) {
			define('ZESK_ROOT', $zesk_root);
		} else if (ZESK_ROOT !== $zesk_root) {
			die("Two versions of zesk: First \"" . ZESK_ROOT . "\", then us \"$zesk_root\"\n");
		}
		$config->path(__CLASS__)->root = ZESK_ROOT;
	}
	public function set_application($set, $update = true) {
		$this->application = rtrim($set, "/");
		if ($update) {
			$this->_init_app_paths();
		}
		return $this;
	}

	/**
	 */
	private function _init_system_paths() {
		$this->which_cache = array();
		$this->home = avalue($_SERVER, 'HOME');
		$this->uid = $this->home(".zesk");
	}

	/**
	 */
	private function _init_app_paths() {
		if ($this->application) {
			$this->temporary = path($this->application, "cache/temp");
			$this->data = path($this->application, "data");
			$this->cache = path($this->application, "cache");
		}
	}

	/**
	 * Get the system command path, usually defined by the system environment variable PATH
	 * The path is set from $_SERVER['PATH'] and it is assumed that paths are separated with PATH_SEPARATOR token
	 *
	 * @return array
	 * @see self::which
	 */
	public function command($add = null) {
		$command_paths = to_list(avalue($_SERVER, 'PATH'), array(), PATH_SEPARATOR);
		if ($add !== null) {
			$command_paths[] = $add;
			$_SERVER['PATH'] = implode(PATH_SEPARATOR, $command_paths);
		}
		return $command_paths;
	}

	/**
	 * Get temporary path, optionally adding a suffix to the path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function temporary($suffix = null) {
		return path($this->temporary, $suffix);
	}

	/**
	 * Get/Set data storage path
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function data($suffix = null) {
		return path($this->data, $suffix);
	}

	/**
	 * Directory for storing temporary cache files
	 *
	 * @param string $suffix
	 *        	Added file or directory to add to cache page
	 * @return string Path to file within the cache paths
	 */
	public function cache($suffix = null) {
		return path($this->cache, $suffix);
	}

	/**
	 * Home directory of current process user, generally passed via the $_SERVER['HOME']
	 * superglobal.
	 *
	 * If not a directory, or superglobal not set, returns null
	 *
	 * @param string $suffix
	 *        	Added file or directory to add to home path
	 * @return string Path to file within the current user's home path
	 */
	public function home($suffix = null) {
		return $this->home ? path($this->home, $suffix) : null;
	}

	/**
	 * User configuration path - place to put configuration files, etc.
	 * for this user
	 *
	 * Defaults to $HOME/.zesk/
	 *
	 * @return string|null
	 */
	public function uid($suffix = null) {
		return $this->uid ? path($this->uid, $suffix) : null;
	}

	/**
	 * Expand paths using magic tokens
	 *
	 *     "/" Absolute path
	 *     "~/" User directory (if exists)
	 *     "./" Application path
	 *
	 * @param mixed $file
	 * @return string
	 */
	public function expand($file) {
		$file = strval($file);
		if ($file === "") {
			return $file;
		}
		if ($file[0] === '/') {
			return $file;
		}
		if (begins($file, "~/")) {
			return $this->home(substr($file, 2));
		}
		if (begins($file, "./")) {
			return $this->application(substr($file, 2));
		}
		return $file;
	}

	/**
	 * Similar to which command-line command.
	 * Returns executable path for command.
	 *
	 * @param string $command
	 * @return string|NULL
	 */
	public function which($command) {
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
		return null;
	}

	/**
	 * Retrieve path settings as variables
	 *
	 * @return string[]
	 */
	public function variables() {
		return array(
			'zesk' => ZESK_ROOT,
			'application' => $this->application,
			'temporary' => $this->temporary,
			'data' => $this->data,
			'cache' => $this->cache,
			'home' => $this->home,
			'uid' => $this->uid,
			'command' => $this->command_path
		);
	}
}
