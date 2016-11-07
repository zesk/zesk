<?php
/**
 * 
 * @copyright Copyright &copy; 2016 Market Acumen, Inc.
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
	 * Current web document root directory
	 * @deprecated 2016-09
	 *
	 * @var string
	 */
	public $document = null;
	
	/**
	 * Current web document prefix
	 * @deprecated 2016-09
	 *
	 * @var string
	 */
	public $document_prefix = null;
	
	/**
	 * Document cache files directory (accessible from web application)
	 * @deprecated 2016-09
	 *
	 * @var string
	 */
	public $document_cache = null;
	
	/**
	 *
	 * @deprecated 2016-09
	 * @var array
	 */
	protected $module_path = array();
	
	/**
	 *
	 * @deprecated 2016-09
	 * @var array
	 */
	protected $share_path = array();
	
	/**
	 * Zesk commands path for zesk-command.php
	 *
	 * @deprecated 2016-09
	 * @var array
	 */
	private $zesk_command_path = null;
	
	/**
	 * System command path for shell
	 *
	 * @var array
	 */
	private $command_path = null;
	
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
		$this->_init_application_root($config);
		
		$this->_init_paths();
		
		$zesk->configuration->home = $this->home;
		
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
	public function configured() {
		global $zesk;
		
		$configuration = $zesk->configuration;
		$paths = $configuration->zesk->paths;
		
		if ($paths->has('commands')) {
			$this->command($paths->command_path);
		}
		if ($paths->has('zesk_commands')) {
			$this->zesk_command($paths->zesk_command_path);
		}
		// cache
		if ($paths->cache) {
			$this->cache = $paths->cache;
		} else if ($configuration->cache && $configuration->cache->path) {
			// TODO Cache::path deprecated - used in existing apps
			$this->cache = $configuration->cache->path;
		}
		// data
		if (isset($paths->data)) {
			$this->data = $paths->data;
		}
		if (isset($paths->document_cache)) {
			$this->document_cache = $paths->document_cache;
		} else if (isset($configuration->document_cache)) {
			$this->document_cache = $configuration->document_cache;
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
		$zesk_root = dirname(dirname(dirname(__FILE__))) . "/";
		if (!defined('ZESK_ROOT')) {
			define('ZESK_ROOT', $zesk_root);
		} else if (ZESK_ROOT !== $zesk_root) {
			die("Two versions of zesk: First \"" . ZESK_ROOT . "\", then us \"$zesk_root\"\n");
		}
		$config->zesk->root = ZESK_ROOT;
	}
	
	/**
	 *
	 * @param Configuration $config        	
	 */
	private function _init_application_root(Configuration $config) {
		if (defined('ZESK_SITE_ROOT') && !defined('ZESK_APPLICATION_ROOT')) {
			define('ZESK_APPLICATION_ROOT', ZESK_SITE_ROOT);
		}
		if (!defined('ZESK_APPLICATION_ROOT')) {
			define('ZESK_APPLICATION_ROOT', ZESK_ROOT);
		}
		$this->application = ZESK_APPLICATION_ROOT;
		
		$config->zesk->application_root = ZESK_APPLICATION_ROOT;
		
		/**
		 * @deprecated 2016-09
		 */
		$this->run = ZESK_APPLICATION_ROOT . 'var/run/';
	}
	
	/**
	 * 
	 */
	private function _init_paths() {
		$this->temporary = path(ZESK_APPLICATION_ROOT, "cache/temp");
		$this->data = path(ZESK_APPLICATION_ROOT, "data");
		
		$this->_init_command();
		
		$this->cache = path(ZESK_APPLICATION_ROOT, "cache");
		
		$this->home = avalue($_SERVER, 'HOME');
		$this->uid = $this->home(".zesk");
	}
	
	/**
	 * Initialize the command path
	 */
	private function _init_command() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		$paths = to_list(avalue($_SERVER, 'PATH'), array(), ':');
		$this->command_path = array();
		$this->which_cache = array();
		foreach ($paths as $path) {
			if (!is_dir($path) && $this->debug) {
				$zesk->logger->debug(__CLASS__ . "::command_path: system path \"{path}\" was not found", array(
					"path" => $path
				));
			} else {
				$this->command_path[] = $path;
			}
		}
	}
	
	/**
	 * Get or set the system command path, usually defined by the system environment variable PATH
	 * On *nix systems, the
	 * path is set from $_SERVER['PATH'] and it is assumed that paths are separated with ':' token
	 * Note that adding a
	 * path does not affect the system environment at all.
	 * This call always returns the complete path, even when adding.
	 *
	 * @param mixed $add
	 *        	A path or array of paths to add. Multiple paths may be passed as a string
	 *        	separated by ':'.
	 * @global boolean debug.command_path Set to true to debug errors in this call
	 * @return array
	 * @see self::which
	 */
	public function command($add = null) {
		if ($add !== null) {
			global $zesk;
			/* @var $zesk \zesk\Kernel */
			$add = to_list($add, array());
			foreach ($add as $path) {
				if (!in_array($path, $this->command_path)) {
					if (!is_dir($path) && $this->debug) {
						$zesk->logger->warning(__CLASS__ . "::command_path: adding path \"{path}\" was not found", array(
							"path" => $path
						));
					}
					$this->command_path[] = $add;
					$this->which_cache = array();
				} else if ($this->debug) {
					$zesk->logger->debug(__CLASS__ . "::command_path: did not add \"{path}\" because it already exists", array(
						"path" => $path
					));
				}
			}
		}
		return $this->command_path;
	}
	
	/**
	 * Get/Set temporary path
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
	 * Default cache path is ZESK_APPLICATION_ROOT/cache
	 *
	 * @param string $suffix
	 *        	Added file or directory to add to cache page
	 * @return string Path to file within the cache paths
	 * @global "Cache::path"
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
	 * Similar to which command-line command. Returns executable path for command.
	 * 
	 * @param string $command
	 * @return string|NULL
	 */
	public function which($command) {
		if (array_key_exists($command, $this->which_cache)) {
			return $this->which_cache[$command];
		}
		foreach ($this->command() as $path) {
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
			'uid' => $this->uid
		);
	}
	
	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set zesk::document_root_prefix(string) to
	 * set
	 * a portion of
	 * the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return string The directory
	 * @deprecated 2016-09
	 */
	public function document($set = null, $prefix = null) {
		zesk()->deprecated();
		return app()->document_root($set, $prefix);
	}
	
	/**
	 * Your web root may be served from an aliased or shared directory and as such may not appear at
	 * the web server's
	 * root.
	 * To ensure all URLs are generated correctly, you can set zesk::web_root_prefix(string) to set
	 * a portion of
	 * the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return string The directory
	 * @todo should this be urlescpaed by web_root_prefix function to avoid & and + to be set?
	 * @deprecated 2016-09
	 */
	public function document_prefix($set = null) {
		zesk()->deprecated();
		return app()->document_root_prefix($set);
	}
	
	/**
	 * Directory of the path to files which can be served from the webserver.
	 * Used for caching CSS or
	 * other resources. Should not serve any links to this path.
	 *
	 * Default document cache path is path(zesk::document_root(), 'cache')
	 *
	 * @param string $set
	 *        	Set the document cache
	 * @return string
	 * @see Controller_Cache, Controller_Content_Cache, Command_Cache
	 * @deprecated 2016-09
	 */
	public function document_cache($suffix = null) {
		zesk()->deprecated();
		return app()->document_cache($suffix);
	}
	
	/**
	 * Get or set the module search path
	 *
	 * @param string $add
	 * @deprecated 2016-09
	 * @return array List of paths searched
	 */
	public function module($add = null) {
		zesk()->deprecated();
		return app()->module_path($add);
	}
	
	/**
	 * Retrieve the list of shared content paths, or add one.
	 * Basic layout is: /share/* -> ZESK_ROOT . 'share/'
	 * /share/$name/file.js -> $add . 'file.js' /share/$name/css/my.css -> $add . 'css/my.css'
	 *
	 * @param string $add
	 *        	(Optional) Path to add to the share path. Pass in null to do nothing.
	 * @param string $name
	 *        	(Optional) Subpath name to add, only relevant if $add is non-null.
	 * @return array The ordered list of paths to search for content
	 * @deprecated 2016-09
	 */
	public function share($add = null, $name = null) {
		zesk()->deprecated();
		return app()->share_path($add, $name);
	}
	
	/**
	 * Get or set the zesk command path, which is where Zesk searches for commands from the
	 * command-line tool.
	 *
	 * The default path is ZESK_ROOT 'classes/command', but applications can add their own tools
	 * upon initialization.
	 *
	 * This call always returns the complete path, even when adding. Note that adding a path which
	 * does not exist has no effect.
	 *
	 * @param mixed $add
	 *        	A path or array of paths to add. (Optional)
	 * @global boolean debug.zesk_command_path Whether to log errors occurring during this call
	 * @return array
	 * @throws Exception_Directory_NotFound
	 * @deprecated 2016-09
	 */
	public function zesk_command($add = null) {
		return app()->zesk_command_path($add);
	}
}
