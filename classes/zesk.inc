<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * Stuff that should probably just be part of PHP, but isn't.
 */
require_once dirname(__FILE__) . "/kernel.inc";

/**
 * Core class in zesk; required for any other functionality.
 * Handles globals, autoloading, hooks, some simple process
 * tools, and the hook system.
 *
 * @author kent
 */
class zesk {
	const global_separator = "::";
	const global_document_cache = "document_cache";
	
	/**
	 * Output a debug log when a class is called with ::hooks but does not implement it
	 *
	 * @var boolean
	 */
	public static $debug_hooks = false;
	
	/**
	 * Determine which hooks are looked at/tested for existence.
	 * Retrieve with zesk::has_hook()
	 *
	 * @var boolean
	 */
	public static $profile_hooks = false;
	
	/**
	 * Determine which functions are calling another function and their frequency
	 *
	 * @var array
	 */
	static $profiler = null;
	
	/**
	 * Key for setting internal globals
	 *
	 * @var string
	 */
	private static $global_key = null;
	
	/**
	 * Zesk commands path for zesk-command.php
	 *
	 * @var array
	 */
	private static $zesk_command_path = null;
	
	/**
	 * System command path for shell
	 *
	 * @var array
	 */
	private static $command_path = null;
	
	/**
	 * Cache of command => path for zesk::which
	 *
	 * @var array
	 */
	private static $which_cache = array();
	
	/**
	 * List of paths to search when enabling modules
	 */
	private static $module_path = array();
	
	/**
	 * System hooks for adding custom functionality throughout the system
	 *
	 * @var array
	 */
	private static $hooks = array();
	
	/**
	 * Hook alias table for old-call to new-call.
	 *
	 * @var array of oldname => newname
	 */
	private static $hook_aliases = array();
	
	/**
	 * Argument definitions for hooks
	 *
	 * @var array
	 */
	private static $hook_definitions = array();
	
	/**
	 * Registry of class names
	 *
	 * @var array:array
	 */
	private static $registry = array();
	
	/**
	 * Deprecated function flag.
	 * Can be true, "exception", "log", or "backtrace"
	 *
	 * @var mixed
	 */
	private static $deprecated = null;
	
	/**
	 * Turn on settings upon initialization to determine if values are set already
	 *
	 * @var boolean
	 */
	public static $debug_set_already = false;
	
	/**
	 * Debug loader problems
	 *
	 * @var boolean
	 */
	public static $debug_loader = false;
	
	/**
	 * Is this a console invokation of Zesk?
	 *
	 * @var boolean
	 */
	public static $console = false;
	
	/**
	 * Are we on a Windows system?
	 */
	public static $is_windows = false;
	
	/**
	 * Global singletons.
	 * Not cached.
	 *
	 * @var array
	 */
	private static $singletons = array();
	
	/**
	 *
	 * @var boolean
	 */
	private static $autoload_cache_changed = false;
	
	/**
	 *
	 * @var array
	 */
	private static $autoload_cache_mtime = null;
	
	/**
	 *
	 * @var array
	 */
	private static $autoload_cache = null;
	
	/**
	 *
	 * @var string
	 */
	private static $autoload_cache_path = null;
	
	/**
	 * Special values used in weights to sort things first or last always
	 *
	 * @see zesk::sort_weight_array
	 * @var array
	 */
	private static $weight_specials = array(
		'zesk-first' => -1e300,
		'first' => -1e299,
		'last' => 1e299,
		'zesk-last' => 1e300
	);
	
	/**
	 * Return the SVN version number of this library
	 *
	 * @return integer version of this library
	 */
	public static function _version_string() {
		return explode(' ', '$Id: zesk.inc 3575 2016-01-28 16:08:42Z kent $', 4);
	}
	
	/**
	 * Zesk version
	 */
	public static function version() {
		$result = self::_version_string();
		return intval($result[2]);
	}
	
	/**
	 * Zesk version date
	 */
	public static function version_date() {
		$result = self::_version_string();
		return intval($result[3]);
	}
	
	/**
	 * Retrieve a class hierarchy from leaf to base
	 *
	 * @param mixed $mixed
	 *        	An object or string to find class hierarchy for
	 * @param string $stop_class
	 *        	Return up to and including this class
	 * @return array
	 */
	public static function class_hierarchy($mixed = null, $stop_class = null) {
		static $cache_object = null;
		if ($cache_object === null) {
			$cache_object = Cache::register(__METHOD__);
		}
		$cache = $cache_object->hierarchy;
		if (!$cache) {
			$cache = $cache_object->hierarchy = array();
		}
		if ($mixed === null) {
			return $cache;
		}
		if (is_object($mixed)) {
			$mixed = get_class($mixed);
		} else if (is_array($mixed)) {
			$result = array();
			foreach ($mixed as $key) {
				$result[$key] = self::class_hierarchy($key, $stop_class);
			}
			return $result;
		}
		if (array_key_exists($mixed, $cache)) {
			$result = $cache[$mixed];
		} else {
			$parent = get_parent_class($mixed);
			$result = array(
				$mixed
			);
			if ($parent !== false) {
				$result = array_merge($result, self::class_hierarchy($parent));
			}
			$cache[$mixed] = $result;
			$cache_object->hierarchy = $cache;
		}
		if ($stop_class === null) {
			return $result;
		}
		$stop_result = array();
		foreach ($result as $class) {
			$stop_result[] = $class;
			if ($class === $stop_class) {
				return $stop_result;
			}
		}
		return $stop_result;
	}
	
	/**
	 * Get/set console
	 *
	 * @param string $set        	
	 * @return boolean
	 */
	public static function console($set = null) {
		if ($set !== null) {
			self::$console = $set;
		}
		return self::$console;
	}
	
	/**
	 * Create a new class based on name
	 *
	 * @param string $class        	
	 * @return stdClass
	 * @throws Exception
	 */
	public static function factory($class) {
		$args = func_get_args();
		array_shift($args);
		return self::factory_array($class, $args);
	}
	
	/**
	 * Create a new class based on name
	 *
	 * @param string $class        	
	 * @param array $args        	
	 * @return stdClass
	 * @throws Exception
	 */
	public static function factory_array($class, array $args) {
		if (!is_string($class)) {
			throw new Exception(__CLASS__ . "::factory(" . _dump($class) . ") not a class name");
		}
		try {
			$rc = new ReflectionClass($class);
			if ($rc->isAbstract()) {
				throw new Exception_Semantics("{this_method}({class}) is abstract - can not instantiate", array(
					"this_method" => __METHOD__,
					"class" => $class
				));
			}
			return $rc->newInstanceArgs($args);
		} catch (ReflectionException $e) {
		} catch (LogicException $e) {
		}
		throw new Exception_Class_NotFound($class, "zesk::factory_array({class}, {args}) {message}", array(
			"class" => $class,
			"args" => array_keys($args),
			"message" => $e->getMessage()
		));
	}
	
	/**
	 * Create a new instance based on name (singleton)
	 * Invokes a "singleton" or "instance" method
	 * "instance" is for wanna-be-nerds.
	 * "singleton" is for uber-nerds.
	 * "master" is for folks who like that kind of thing.
	 * If you implement all three, "instance" wins for compatibility.
	 *
	 * @param string $class        	
	 * @return stdClass
	 * @throws Exception
	 */
	public static function singleton($class) {
		if (!is_string($class)) {
			throw new Exception(__CLASS__ . "::factory(" . _dump($class) . ") not a class name");
		}
		$low_class = strtolower($class);
		if (array_key_exists($low_class, self::$singletons)) {
			return self::$singletons[$low_class];
		}
		$args = func_get_args();
		array_shift($args);
		try {
			$static_methods = to_list("instance;singleton;master");
			$rc = new ReflectionClass($class);
			foreach ($static_methods as $method) {
				if ($rc->hasMethod($method)) {
					$method = $rc->getMethod($method);
					/* @var $method ReflectionMethod */
					if ($method->isStatic()) {
						return self::$singletons[$low_class] = $object = $method->invokeArgs(null, $args);
					}
				}
			}
			return self::$singletons[$low_class] = $rc->newInstance();
		} catch (ReflectionException $e) {
			throw new Exception_Class_NotFound($class, null, $e);
		} catch (LogicException $e) {
			throw new Exception_Class_NotFound($class, null, $e);
		}
	}
	
	/**
	 * Initialize the globals.
	 * We load a series of files named "environment.sh" which is a bash-style initialization
	 * file like:
	 * <code>
	 * DEVELOPMENT=true
	 * MAINTENANCE=false
	 * DB_URL=mysql://user:pass@localhost/dbname
	 * Name="Value doesn't include outside quotes"
	 * SHELL_SUB=${Name}$Name
	 * VAR=Not this value
	 * VAR=But this value overrides the previous one
	 * </code>
	 *
	 * Specify a list of directories to load, in order
	 * "override" files which are useful when on a development system, or a shared hosting
	 * environment.
	 *
	 * @param mixed $paths
	 *        	An array of paths to search for environment files, or a string containing the
	 *        	absolute path
	 *        	of an environment file to add
	 * @return string
	 */
	public static function initialize($paths = false, $options = array()) {
		conf::globals($paths, $options);
	}
	
	/**
	 * This loads an include without any variables defined, except super globals Handy when the file
	 * is meant to return
	 * a value, or has its own "internal" variables which may corrupt the global or current scope of
	 * a function, for
	 * example.
	 *
	 * @param string $__file__
	 *        	File to include
	 * @return mixed Whatever is returned by the include file
	 */
	public static function load($__file__) {
		return include $__file__;
	}
	
	/**
	 * Get/Set temporary path
	 *
	 * @param string $add        	
	 * @return string
	 */
	public static function temporary_path($add = null) {
		return path(self::get('zesk::temporary_path', self::application_root("cache/temp/")), $add);
	}
	
	/**
	 * Get/Set data storage path
	 *
	 * @param string $add        	
	 * @return string
	 */
	public static function data_path($add = null) {
		return path(self::get('zesk::data_path', self::application_root("data/")), $add);
	}
	
	/**
	 * Get/Set library file path (NOTE: May be part of source control)
	 *
	 * @param string $add        	
	 * @return string
	 */
	public static function library_path($add = null) {
		return path(self::get('zesk::library_path', self::application_root("lib/")), $add);
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
	public static function command_path($add = null) {
		$debug = self::getb('debug.command_path');
		if (self::$command_path === null) {
			$paths = to_list(avalue($_SERVER, 'PATH'), array(), ':');
			self::$command_path = array();
			foreach ($paths as $path) {
				self::$command_path[] = $path;
				if (!is_dir($path) && $debug) {
					log::debug(__CLASS__ . "::command_path: system path \"{path}\" was not found", array(
						"path" => $path
					));
				}
			}
		}
		if ($add !== null) {
			$add = to_list($add, array());
			foreach ($add as $path) {
				if (!in_array($path, self::$command_path)) {
					if (!is_dir($path) && $debug) {
						log::warning(__CLASS__ . "::command_path: adding path \"{path}\" was not found", array(
							"path" => $path
						));
					}
					self::$command_path[] = $add;
				} else if ($debug) {
					log::debug(__CLASS__ . "::command_path: did not add \"{path}\" because it already exists", array(
						"path" => $path
					));
				}
			}
		}
		return self::$command_path;
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
	 */
	public static function zesk_command_path($add = null) {
		if (self::$zesk_command_path === null) {
			self::$zesk_command_path = array(
				ZESK_ROOT . 'command'
			);
		}
		if ($add !== null) {
			$debug = self::getb('debug.zesk_command_path');
			$add = to_list($add, array(), ":");
			foreach ($add as $path) {
				if ($debug && !is_dir($path)) {
					log::warning("zesk::zesk_command_path: adding path \"{path}\" was not found", array(
						"path" => $path
					));
				}
				if (!in_array($path, self::$zesk_command_path)) {
					self::$zesk_command_path[] = $path;
				} else if ($debug) {
					log::debug("zesk::zesk_command_path: did not add \"{path}\" because it already exists", array(
						"path" => $path
					));
				}
			}
			zesk::set('zesk_command_path', self::$zesk_command_path);
		}
		return self::$zesk_command_path;
	}
	
	/**
	 * Find a command using the comand path
	 *
	 * @param string $command        	
	 */
	public static function which($command) {
		if (array_key_exists($command, self::$which_cache)) {
			return self::$which_cache[$command];
		}
		foreach (self::command_path() as $path) {
			$path = path($path, $command);
			if (is_executable($path)) {
				return self::$which_cache[$command] = $path;
			}
		}
		return null;
	}
	
	/**
	 * Returns the name of the function or class/method which called the current code.
	 * Useful for debugging.
	 *
	 * Moved from debug:: class to assist in profiling bootstrap functions (zesk::get, for example)
	 * which don't have the autoloader set yet.
	 *
	 * @return string
	 * @param unknown $depth        	
	 * @see debug_backtrace()
	 * @see zesk::profiler
	 * @see debug::calling_function
	 */
	public static function calling_function($depth = 1, $include_line = true) {
		$bt = debug_backtrace();
		array_shift($bt); // Remove this function from the stack
		if ($depth > 0) {
			while ($depth-- !== 0) {
				array_shift($bt);
			}
		}
		$top = array_shift($bt);
		if (!$top) {
			return '-no calling function $depth deep-';
		}
		return @$top["class"] . @$top["type"] . $top["function"] . ($include_line ? ':' . avalue($top, 'line') : '');
	}
	
	/**
	 * Internal call to initialize profiler structure
	 */
	private static function _profiler() {
		if (self::$profiler === null) {
			self::$profiler = new stdClass();
			self::$profiler->calls = array();
			self::$profiler->times = array();
			self::add_hook("</body>", function () {
				echo "<pre>";
				asort(zesk::$profiler->calls);
				asort(zesk::$profiler->times);
				print_r(zesk::$profiler);
				echo "</pre>";
			});
		}
		return self::$profiler;
	}
	
	/**
	 * Time a function call
	 *
	 * @param string $item
	 *        	Key
	 * @param double $seconds
	 *        	How long it took
	 */
	public static function profile_timer($item, $seconds) {
		$profiler = self::_profiler();
		if (array_key_exists($item, self::$profiler)) {
			$profiler->times[$item] += $seconds;
		} else {
			$profiler->times[$item] = $seconds;
		}
	}
	
	/**
	 * Internal profiler to determine who is calling what function how often.
	 * Debugging only
	 *
	 * @param numeric $depth        	
	 */
	public static function profiler($depth = 2) {
		$profiler = self::_profiler();
		$fkey = self::calling_function($depth + 1, false);
		if (array_key_exists($fkey, self::$profiler->calls)) {
			$profiler->calls[$fkey]++;
		} else {
			$profiler->calls[$fkey] = 1;
		}
	}
	
	/**
	 * Reset all globals to null and call hook('reset')
	 */
	public static function reset(array $globals = array()) {
		global $_ZESK;
		$_ZESK = $globals + arr::filter($_ZESK, 'zesk_command;init;zesk');
		self::set(self::defaults(), null, false);
		zesk::hook('reset');
	}
	
	/**
	 * Cache normalize_global_key calls
	 *
	 * @var array
	 */
	private static $key_map = array();
	
	/**
	 * Retrieve a global setting.
	 * Includes environment values for this system.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return mixed
	 */
	public static function get($key = null, $default = null) {
		global $_ZESK;
		if ($key === null) {
			return $_ZESK;
		} else if (is_array($key)) {
			$result = array();
			foreach ($key as $k => $v) {
				$result[$k] = self::get($k, $v);
			}
			return $result;
		}
		if (array_key_exists($key, self::$key_map)) {
			$normal_key = self::$key_map[$key];
		} else {
			self::$key_map[$key] = $normal_key = to_list(_zesk_global_key($key), array(), ZESK_GLOBAL_KEY_SEPARATOR);
		}
		return apath($_ZESK, $normal_key, $default, ZESK_GLOBAL_KEY_SEPARATOR);
	}
	
	/**
	 * Retrieve a global which should be a boolean.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return boolean in the global context, or $default if it's not set
	 */
	public static function getb($key, $default = false) {
		return to_bool(self::get($key, $default), $default);
	}
	
	/**
	 * Retrieve globals from this class to parent class, e.g.
	 * CurrentClass::member, then ParentClass::member, then ...
	 * BaseClass::member
	 *
	 * @param mixed $class
	 *        	Object or name of class
	 * @param string $member
	 *        	Member to retrieve global
	 * @param mixed $default
	 *        	If not found at any level, return this value
	 * @param mixed $check_empty
	 *        	If a value is set to null
	 */
	public static function class_get($class, $member, $default = null, $check_empty = true) {
		$class = is_object($class) ? get_class($class) : $class;
		while (is_string($class)) {
			if (self::has("$class::$member", $check_empty)) {
				return self::get("$class::$member");
			}
			$class = get_parent_class($class);
		}
		return $default;
	}
	
	/**
	 * Retrieve a global which should be an integer.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return The integer in the global context, or $default if it's not set
	 */
	public static function geti($key, $default = null) {
		return to_integer(self::get($key, null), $default);
	}
	
	/**
	 * Retrieve a global which should be a boolean.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return The boolean in the global context, or $default if it's not set
	 */
	public static function getl($key, $default = array(), $delimiter = ";") {
		return to_list(self::get($key, $default), $default, $delimiter);
	}
	
	/**
	 * Retrieve a global which should be an array.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return The boolean in the global context, or $default if it's not set
	 */
	public static function geta($key, $default = array()) {
		if ($key === null) {
			return $default;
		}
		return to_array(self::get($key, $default), $default);
	}
	
	/**
	 * Retrieve the first non-empty global setting.
	 *
	 * @param string $keys
	 *        	A key or list of keys to retrieve from the global context. If a string, converted
	 *        	to a list
	 *        	with to_list
	 * @param mixed $default
	 *        	The default value if no value is set
	 * @param boolean $skip_empty
	 *        	If true, skips over empty values which are set
	 * @return mixed
	 * @see to_list
	 */
	public static function get1($keys = null, $default = null, $skip_empty = true) {
		$keys = to_list($keys, array());
		foreach ($keys as $key) {
			$key = _zesk_global_key($key);
			$value = zesk::get($key, self::$global_key);
			if ($value === self::$global_key) {
				continue;
			}
			if ($skip_empty && empty($value)) {
				continue;
			}
			return $value;
		}
		return $default;
	}
	
	/**
	 * For external libraries which are based on global define's, this tool will
	 * define the needed constants from global variables.
	 * Optionally will throw errors when these globals are already defined.
	 *
	 * @param array $keys_defaults
	 *        	Array of case-sensistive keys and default values if the global is not defined.
	 * @param boolean $defined_error
	 *        	When true, throws an error when a global is already defined.
	 * @return array Array of keys => values defined in the PHP constant table.
	 */
	public static function define_globals(array $keys_defaults, $defined_error = true) {
		$result = array();
		foreach ($keys_defaults as $key => $default) {
			if (defined($key)) {
				if ($defined_error) {
					throw new Exception_Semantics("$key is already defined");
				}
				continue;
			}
			$value = self::get($key, $default);
			define($key, $value);
			$result[$key] = $value;
		}
		return $result;
	}
	
	/**
	 * Are we on a production machine?
	 *
	 * @return boolean
	 */
	public static function production() {
		return !self::development();
	}
	
	/**
	 * Are we on a development machine?
	 *
	 * @deprecated 2016-01-27
	 * @see zesk\Application::development
	 * @return boolean
	 */
	public static function development($set = null) {
		zesk::deprecated();
		if (is_bool($set)) {
			self::set('development', $set);
		}
		return self::getb('development');
	}
	
	/**
	 * Is the system currently in maintenance mode?
	 *
	 * @return boolean
	 */
	public static function maintenance() {
		return self::getb('MAINTENANCE');
	}
	
	/**
	 * Path relative to the Zesk Application Root
	 *
	 * @todo Do I need the constant?
	 * @see self::root
	 * @param string $path
	 *        	Path to add to the application root
	 * @return string
	 */
	public static function application_root($path = null) {
		if (defined('ZESK_APPLICATION_ROOT')) {
			return path(ZESK_APPLICATION_ROOT, $path);
		}
		return path(self::get('zesk::application_root', ZESK_ROOT), $path);
	}
	
	/**
	 * Path relative to the Zesk Root
	 *
	 * @see self::application_root
	 * @param string $path        	
	 * @return string
	 */
	public static function root($path = null) {
		return path(ZESK_ROOT, $path);
	}
	
	/**
	 * Return the global Zesk Application class name.
	 *
	 * @param string $set
	 *        	Set the global Zesk Application class name
	 * @return string class name of the application use for the current application
	 * @global Application::class
	 */
	public static function application_class($set = null) {
		if ($set !== null) {
			self::set('Application::class', $set);
			return $set;
		}
		return self::get('Application::class', 'Application');
	}
	
	/**
	 * Get or set the module search path
	 *
	 * @param string $add        	
	 * @return array List of paths searched
	 */
	public static function module_path($add = null) {
		if ($add !== null) {
			if (!is_dir($add)) {
				throw new Exception_Directory_NotFound($add);
			}
			self::$module_path[] = $add;
		}
		return self::$module_path;
	}
	
	/**
	 * Given a list of paths and a directory name, find the first occurrance of the named directory.
	 *
	 * @param array $paths
	 *        	List of strings representing file system paths
	 * @param mixed $directory
	 *        	Directory to search for, or list of directories to search for (array)
	 * @return string Full path of found directory, or null if not found
	 * @see self::find_file
	 */
	public static function find_directory(array $paths, $directory = null) {
		if (is_array($directory)) {
			foreach ($paths as $path) {
				foreach ($directory as $d) {
					$the_path = path($path, $d);
					if (is_dir($the_path)) {
						return $the_path;
					}
				}
			}
		} else {
			foreach ($paths as $path) {
				$the_path = path($path, $directory);
				if (is_dir($the_path)) {
					return $the_path;
				}
			}
		}
		return null;
	}
	
	/**
	 * Given a list of paths and a file name, find the first occurrance of the file.
	 *
	 * @param array $paths
	 *        	List of strings representing file system paths
	 * @param mixed $file
	 *        	File name to search for, or list of file names to search for (array)
	 * @return string Full path of found file, or null if not found
	 * @see self::find_directory
	 */
	public static function find_file(array $paths, $file) {
		if (is_array($file)) {
			foreach ($paths as $path) {
				foreach ($file as $f) {
					$the_path = path($path, $f);
					if (is_file($the_path)) {
						return $the_path;
					}
				}
			}
		} else {
			foreach ($paths as $path) {
				$the_path = path($path, $file);
				if (is_file($the_path)) {
					return $the_path;
				}
			}
		}
		return null;
	}
	
	/**
	 * Given a list of paths and a file name, find all occurrance of the named file.
	 *
	 * @param array $paths
	 *        	List of strings representing file system paths
	 * @param mixed $file
	 *        	File name to search for, or list of file names to search for (array)
	 * @return array list of files found, in order
	 * @see self::find_directory
	 */
	public static function find_files(array $paths, $file) {
		$result = array();
		if (is_array($file)) {
			foreach ($paths as $path) {
				foreach ($file as $f) {
					$the_path = path($path, $f);
					if (is_file($the_path)) {
						$result[] = $the_path;
					}
				}
			}
		} else {
			foreach ($paths as $path) {
				$the_path = path($path, $file);
				if (is_file($the_path)) {
					$result[] = $the_path;
				}
			}
		}
		return $result;
	}
	
	/**
	 * Retrieve the list of autoload paths, or add one.
	 *
	 * @param string $add
	 *        	(Optional) Path to add to the autoload path. Pass in null to do nothing.
	 * @param mixed $options
	 *        	(Optional) Boolean value, string or array. If you pass in a string, it sets that
	 *        	flag to true.
	 *        	
	 *        	So:
	 *        	
	 *        	<code>
	 *        	zesk::autoload_path(zesk::application_root('classes','first');
	 *        	</code>
	 *        	
	 *        	Is a very common usage pattern.
	 *        	
	 *        	Options are:
	 *        	- lower - Lowercase the class name (defaults to true) to find the files for this
	 *        	path only
	 *        	- first - Set as first autoload path. If first and last are set, first wins, last
	 *        	is ignored.
	 *        	- last - Set as last autoload path.
	 *        	- extensions - Array or ;-separated string containing extensions to look for
	 *        	
	 * @return array The ordered list of paths to search for class names.
	 */
	public static function autoload_path($add = null, $options = true) {
		static $first = array();
		static $paths = array();
		static $last = array();
		static $cached = null;
		if ($add) {
			if (is_string($options)) {
				$options = array(
					$options => true
				);
			} elseif (!is_array($options)) {
				$options = array(
					'lower' => to_bool($options)
				);
			}
			if (array_key_exists('extensions', $options)) {
				$options['extensions'] = to_list($options['extensions']);
			}
			if (to_bool(avalue($options, 'first'))) {
				$first[$add] = $options;
				$cached = null;
			} else if (to_bool(avalue($options, 'last'))) {
				$last[$add] = $options;
				$cached = null;
			} else {
				$paths[$add] = $options;
				$cached = null;
			}
		}
		if ($cached) {
			return $cached;
		}
		$cached = array_merge($first, $paths, $last);
		return $cached;
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
	 */
	public static function share_path($add = null, $name = null) {
		$list = self::geta("share_path", array());
		if ($add) {
			if (!is_dir($add)) {
				throw new Exception_Directory_NotFound($add);
			}
			$list[$name] = $add;
			self::set('share_path', $list);
			if ($name === null) {
				cdn::add("/share/", "/share/", $add, false);
			} else {
				cdn::add("/share/$name/", "/share/$name/", $add, false);
			}
		}
		return $list;
	}
	
	/**
	 * Retrieve the list of configuration file paths, or add one.
	 *
	 * @param string $add
	 *        	(Optional) Path to add to the config path. Pass in null to do nothing.
	 * @return array The ordered list of paths to search for config files.
	 */
	public static function configuration_path($add = null) {
		$list = self::getl("configuration_path", array(
			ZESK_ROOT
		));
		if ($add) {
			return self::add('configuration_path', $add);
		}
		return $list;
	}
	
	/**
	 * Retrieve the list of autoload file extensions, or add one.
	 *
	 * @param string $add
	 *        	(Optional) Path to add to the theme path. Pass in null to do nothing.
	 * @return array The ordered list of paths to search for theme files.
	 */
	public static function autoload_extension($add = null) {
		$list = self::getl("autoload_extensions", array(
			'inc'
		));
		$add = trim($add, ". \t\r\n");
		if ($add && !in_array($add, $list)) {
			$list[] = $add;
			zesk::set('autoload_extensions', $list);
		}
		return $list;
	}
	
	/**
	 * Directory for storing temporary cache files
	 *
	 * Default cache path is ZESK_APPLICATION_ROOT/cache
	 *
	 * @param string $add
	 *        	Added file or directory to add to cache page
	 * @return string Path to file within the cache paths
	 * @global "Cache::path"
	 */
	public static function cache_path($add = null) {
		zesk::profiler();
		return path(zesk::get("Cache::path", zesk::application_root("cache")), $add);
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
	 */
	public static function document_cache($set = null) {
		return $set !== null ? zesk::set(self::global_document_cache, $set) : zesk::get(self::global_document_cache, path(zesk::document_root(), "cache"));
	}
	/**
	 * Home directory of current process user, generally passed via the $_SERVER['HOME']
	 * superglobal.
	 *
	 * If not a directory, or superglobal not set, returns null
	 *
	 * @param string $add
	 *        	Added file or directory to add to home path
	 * @return string Path to file within the current user's home path
	 */
	public static function home_path($add = null) {
		$home = avalue($_SERVER, 'HOME');
		return $home && is_dir($home) ? path($home, $add) : null;
	}
	
	/**
	 * User configuration path - place to put configuration files, etc.
	 * for this user
	 *
	 * Defaults to $HOME/.zesk/
	 *
	 * Override by setting global "uid_path"
	 *
	 * @global uid_path
	 * @return string|null
	 */
	public static function uid_path($add = null) {
		static $default = false;
		if ($default === false) {
			$default = self::home_path('.zesk/');
		}
		return path(zesk::get("uid_path", $default), $add);
	}
	
	/**
	 * Autoload caching, only needs to reset when classes are added/removed
	 */
	private static function _autoload_cache_path() {
		if (self::$autoload_cache_path === null) {
			// This is loaded early, so if you overwrite, it doesn't change.
			self::$autoload_cache_path = self::cache_path('autoload.cache');
		}
		return self::$autoload_cache_path;
	}
	
	/**
	 * Retrieve the autoload cache structure, optionally creating the autoload cache directory if
	 * needed.
	 *
	 * @return array
	 */
	private static function &_autoload_cache() {
		if (self::$autoload_cache === null) {
			$dir = dirname($path = self::_autoload_cache_path());
			if (!is_dir($dir) && is_writable(dirname($dir))) {
				if (zesk::maintenance() || !@mkdir($dir, 0770, true)) {
					self::$autoload_cache_path = false;
					self::$autoload_cache = array();
					return self::$autoload_cache;
				}
			}
			if (is_file($path)) {
				self::$autoload_cache_mtime = filemtime($path);
				self::$autoload_cache = unserialize(file_get_contents($path));
			} else {
				self::$autoload_cache_mtime = null;
				self::$autoload_cache = array();
			}
		}
		return self::$autoload_cache;
	}
	
	/**
	 * PHP Autoloader call.
	 * Used in case PHP extends the autoloader to add a 2nd parameter - don't want
	 * it to conflict with our zesk::autoloader 2nd parameter.
	 *
	 * @param string $class        	
	 * @return boolean
	 */
	public static function _autoloader($class) {
		return self::autoloader($class);
	}
	/**
	 * Zesk's autoloader.
	 * When a PHP class is encountered which can't be found, this function tries to find it and
	 * include the file.
	 *
	 * @param string $class        	
	 * @param boolean $no_exception
	 *        	Do not throw an exception if class is not found
	 * @return boolean
	 * @global zesk::autoloader_no_exception
	 * @global define:ZESK_NO_CONFLICT
	 */
	public static function autoloader($class, $no_exception = false) {
		$lowclass = strtolower($class);
		$cache = & self::_autoload_cache();
		$include = null;
		if (array_key_exists($lowclass, $cache)) {
			$include = $cache[$lowclass];
			if (!is_file($include)) {
				unset(self::$autoload_cache[$lowclass]);
				$include = null;
			}
		}
		if (!$include) {
			$tried_path = null;
			$t = microtime(true);
			$include = self::autoload_search($class, self::autoload_extension(), $tried_path);
			if ($include === null) {
				if ($no_exception || defined('ZESK_NO_CONFLICT') || self::getb(__CLASS__ . '::autoloader_no_exception')) {
					return false;
				}
				throw new Exception_Class_NotFound($class, "Class {class} not found in:\n{tried_path}\nInvoked from:\n{backtrace}", array(
					"class" => $class,
					"tried_path" => text::indent(implode("\n", $tried_path)),
					"backtrace" => text::indent(_backtrace(), 1)
				));
			}
			$cache[$lowclass] = $include;
			self::$autoload_cache_changed = true;
		}
		if (self::$debug_loader) {
			echo "Include $include" . newline();
		}
		require_once $include;
		return true;
	}
	
	/**
	 * Search for a file in the autoload path (self::autoload_path), looking for files with
	 * "extesion"
	 *
	 * @param unknown_type $class        	
	 * @param array $extensions        	
	 * @param unknown_type $tried_path        	
	 * @return unknown
	 */
	public static function autoload_search($class, array $extensions, &$tried_path = null) {
		$paths = self::autoload_path();
		return self::file_search($paths, $class, $extensions, $tried_path);
	}
	
	/**
	 * Search for a file in the given paths, converting filename to a directory path by converting _
	 * to /, and look for
	 * files with the given extensions, in order.
	 *
	 * @param array $paths
	 *        	An array of path keys with boolean values indicating whether the search should be
	 *        	case-sensitive or not
	 * @param string $file_prefix
	 *        	The file name to search for, without the extension
	 * @param array $extensions
	 *        	A list of extensions to search for in each target path
	 * @param array $tried_path
	 *        	A list of paths which were tried to find the file
	 * @return string The found path, or null if not found
	 */
	public static function file_search(array $paths, $file_prefix, array $extensions, &$tried_path = null) {
		static $debug = null;
		if ($debug === null) {
			$debug = zesk::getb('zesk::file_search_debug');
		}
		$tried_path = array();
		foreach ($paths as $path => $options) {
			if ($options === true) {
				$options = array(
					'lower' => true
				);
			} else if (!is_array($options)) {
				$options = array();
			}
			$class_prefix = rtrim(avalue($options, 'class_prefix', ''), '_');
			if ($class_prefix !== "") {
				if (substr($class_prefix, -1) !== "\\") {
					$class_prefix .= "_";
				}
				$len = strlen($class_prefix);
				if (strcasecmp(substr($file_prefix, 0, $len), $class_prefix) === 0) {
					$path_file_prefix = substr($file_prefix, $len);
				} else {
					// Class doesn't begin with prefix, skip
					continue;
				}
			} else {
				$path_file_prefix = $file_prefix;
			}
			$path_file_prefix = strtr($path_file_prefix, '\\', '_');
			$file_parts = implode("/", explode("_", avalue($options, 'lower', true) ? strtolower($path_file_prefix) : $path_file_prefix));
			$exts = avalue($options, 'extensions', $extensions);
			foreach ($exts as $extension) {
				$full_path = path($path, $file_parts) . "." . $extension;
				if ($debug) {
					log::debug("$file_prefix: $full_path");
				}
				if (file_exists($full_path)) {
					return $full_path;
				}
				$tried_path[] = $full_path;
			}
		}
		return null;
	}
	
	/**
	 * Generic function to create paths correctly
	 *
	 * @param
	 *        	string separator Token used to divide path
	 * @param
	 *        	array mixed List of path items, or array of path items to concatenate
	 * @return string with a properly formatted path
	 */
	public static function path($separator = '/', $mixed) {
		$r = array_shift($mixed);
		if (is_array($r)) {
			$r = self::path($separator, $r);
		}
		foreach ($mixed as $p) {
			if ($p === null) {
				continue;
			}
			if (is_array($p)) {
				$p = self::path($separator, $p);
			}
			$r .= ((substr($r, -1) === $separator || substr($p, 0, 1) === $separator)) ? $p : $separator . $p;
		}
		$separatorq = preg_quote($separator);
		$r = preg_replace("|$separatorq$separatorq+|", $separator, $r);
		return $r;
	}
	
	/**
	 * Creates a new configuration object for the requested group.
	 *
	 * @param
	 *        	string group name
	 * @return array
	 */
	public static function configure($group) {
		static $config = array();
		
		// Split the config group and path
		list($group, $path) = pair($group, '.', $group, null);
		
		if (!isset($config[$group])) {
			$paths = self::configuration_path();
			$configuration = array();
			foreach ($paths as $config_path) {
				$config_path = path($config_path, $group) . '.inc';
				if (is_file($config_path)) {
					$configuration = $configuration + self::load($config_path);
				}
			}
			// Load the config group into the cache
			$config[$group] = $configuration;
		}
		
		if ($path !== null) {
			return apath($config[$group], $path);
		}
		return $config[$group];
	}
	public static function theme_path_default() {
		return self::root('theme/zesk');
	}
	public static function module_path_default() {
		return self::root('modules');
	}
	public static function share_path_default() {
		return self::root('share');
	}
	private static function bootstrap_zesk_root() {
		$zesk_root = dirname(dirname(__FILE__)) . "/";
		if (!defined('ZESK_ROOT')) {
			define('ZESK_ROOT', $zesk_root);
		} else if (ZESK_ROOT !== $zesk_root) {
			die("Two versions of zesk: First \"" . ZESK_ROOT . "\", then us \"$zesk_root\"\n");
		}
		self::set("zesk::root", ZESK_ROOT, self::$global_key);
	}
	private static function bootstrap_app_root() {
		if (defined('ZESK_SITE_ROOT') && !defined('ZESK_APPLICATION_ROOT')) {
			define('ZESK_APPLICATION_ROOT', ZESK_SITE_ROOT);
		}
		if (defined('ZESK_APPLICATION_ROOT')) {
			self::set("zesk::application_root", ZESK_APPLICATION_ROOT, self::$global_key);
			self::set("zesk::log_path", ZESK_APPLICATION_ROOT . "var/log/", false);
			self::set("zesk::run_path", ZESK_APPLICATION_ROOT . "var/run/", false);
		}
	}
	
	/**
	 * Should be called once and only once.
	 * Registers zesk's autoloader.
	 */
	static private function autoload_register() {
		if (PHP_VERSION_ID >= 50300) {
			spl_autoload_register(array(
				__CLASS__,
				"_autoloader"
			), false, true);
		} else {
			spl_autoload_register(array(
				__CLASS__,
				"_autoloader"
			), false);
		}
	}
	
	/**
	 * Load the bare minimum settings for Zesk to work.
	 * Sets a few "standard" globals: - ZESK_ROOT for use in other
	 * loaded globals Sets up the autoloader for loading classes based on a search path. Sets up the
	 * base theme path
	 * as self::theme_path_default() Sets up the base module path as self::module_path_default() -
	 *
	 * @see constant ZESK_COMMAND
	 * @see constant ZESK_DEBUG_SET_ALREADY
	 */
	public static function bootstrap() {
		static $strapped = false;
		if ($strapped) {
			/* Only once */
			return false;
		}
		self::$console = PHP_SAPI === 'cli';
		self::$is_windows = PATH_SEPARATOR === '\\';
		
		// Allow us to set certain globals only
		self::$global_key = microtime(true);
		
		self::autoload_register();
		
		self::bootstrap_zesk_root();
		if (!defined('ZESK_COMMAND')) {
			self::bootstrap_app_root();
		}
		
		self::autoload_path(ZESK_ROOT . 'classes', 'last');
		self::module_path(self::module_path_default());
		self::share_path(self::share_path_default(), 'zesk');
		
		if (defined("ZESK_DEBUG_SET_ALREADY") && ZESK_DEBUG_SET_ALREADY) {
			self::$debug_set_already = true;
		}
		compat::install();
		
		if (PHP_VERSION_ID < 50000) {
			die("Zesk works in PHP 5 only.");
		}
		
		self::set(self::defaults());
		
		self::_hook_aliases();
		self::_internal_hooks();
		
		/* @deprecated */
		register_shutdown_function('zesk::hook', 'exit');
		
		/* Shutdown */
		register_shutdown_function('zesk::hook', 'shutdown');
		
		/* Only once */
		$strapped = true;
		return true;
	}
	
	/**
	 * Write autoload cache path (tests for writability and logs error in development environments)
	 *
	 * @see zesk::_autoload_cache_path()
	 */
	public static function autoload_save() {
		if (!self::$autoload_cache_changed) {
			return;
		}
		$path = self::_autoload_cache_path();
		if (!$path) {
			return;
		}
		if (file_exists("$path.lock")) {
			return;
		}
		clearstatcache(true, $path);
		// Do not overwrite if written since application load
		if (!is_file($path) || filemtime($path) <= self::$autoload_cache_mtime) {
			if (@file_put_contents($path, serialize(self::$autoload_cache))) {
				return;
			}
			if (!self::development()) {
				return;
			}
			log::warning("Can not write autoload path to {path}", array(
				"path" => $path
			));
		}
	}
	
	/**
	 * Initialize web root to enable non-rooted web sites.
	 * This should be called from any script which interacts with
	 * files on the web path or any script which is invoked from the web. Ideally, it should be in
	 * your application
	 * initialization code. It determines the web root from $_SERVER['DOCUMENT_ROOT'] so if your web
	 * server doesn't
	 * support setting this or you are invoking it from a script (which, for example, manipulates
	 * files which depend on
	 * this being set correctly) then you should initialize it with zesk::web_root(...) and
	 * zesk::web_root_prefix(...)
	 * Currently things which use this are: TODO
	 *
	 * @see zesk::web_root
	 * @see zesk::web_root_prefix
	 * @throws Exception_Directory_NotFound
	 *
	 * @param string $document_root_prefix        	
	 */
	public static function document_init($document_root = null, $prefix = null) {
		zesk::set('http_document_root', $http_document_root = rtrim(avalue($_SERVER, 'DOCUMENT_ROOT'), '/'));
		zesk::set('script_filename', $script_filename = $_SERVER['SCRIPT_FILENAME']);
		if ($document_root !== null) {
			$document_root = self::document_root($document_root);
		} else {
			$document_root = $http_document_root;
			if ($document_root) {
				$document_root = self::document_root($document_root);
			}
		}
		if ($prefix === null) {
			$prefix = str::unprefix(self::document_root(), $http_document_root);
			if ($prefix === "/") {
				$prefix = "";
			}
			self::document_root_prefix($prefix);
		}
	}
	
	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 * To ensure all URLs are generated correctly, you can set zesk::web_root_prefix(string) to set
	 * a portion of
	 * the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return string The directory
	 */
	public static function document_root($set = null, $prefix = null) {
		if ($set !== null) {
			if (!is_dir($set)) {
				throw new Exception_Directory_NotFound($set);
			}
			$set = rtrim($set, '/');
			return self::set('document_root', $set);
		}
		if ($prefix !== null) {
			self::document_root_prefix($prefix);
		}
		return self::get('document_root');
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
	 */
	public static function document_root_prefix($set = null) {
		if ($set !== null) {
			$set = rtrim($set, '/');
			return self::set('document_root_prefix', $set);
		}
		return self::get('document_root_prefix');
	}
	public static function href($path) {
		if (url::valid($path)) {
			return $path;
		}
		$prefix = zesk::document_root_prefix();
		return $prefix ? path($prefix, $path) : $path;
	}
	public static function running($pid = null) {
		if ($pid === null) {
			return self::pid();
		}
		if (!function_exists("posix_kill")) {
			throw new Exception_Unimplemented("Need --with-pcntl");
		}
		return posix_kill($pid, 0) ? true : false;
	}
	public static function php_set($feature, $value) {
		$feature = strtolower($feature);
		switch ($feature) {
			case "time_limit":
				set_time_limit(intval($value));
				return true;
		}
		return null;
	}
	
	/**
	 * Test PHP for presence of various features
	 *
	 * @param mixed $features        	
	 * @param boolean $die
	 *        	Die if features aren't present
	 * @return mixed
	 */
	public static function php_has($features, $die = false) {
		$features = to_list($features);
		$results = array();
		$errors = array();
		foreach ($features as $feature) {
			switch ($feature) {
				case "pcntl":
					$results[$feature] = $result = function_exists('pcntl_exec');
					if (!$result) {
						$errors[] = __("Need pcntl extensions for PHP\nphp.ini at {0}\n", get_cfg_var('cfg_file_path'));
					}
					break;
				case "time_limits":
					$results[$feature] = $result = !to_bool(ini_get('safe_mode'));
					if (!$result) {
						$errors[] = __("PHP safe mode prevents removing time limits on pages\nphp.ini at {0}\n", get_cfg_var('safe_mode'));
					}
					break;
				case "posix":
					$results[$feature] = $result = function_exists('posix_getpid');
					if (!$result) {
						$errors[] = __("Need POSIX extensions to PHP (posix_getpid)");
					}
					break;
				default :
					$results[$feature] = $result = false;
					$errors[] = "Unknown feature \"$feature\"";
					break;
			}
		}
		if (count($errors) > 0) {
			if ($die) {
				die(implode("\n", $errors));
			}
		}
		if (count($features) === 1) {
			return $result;
		}
		return $results;
	}
	/**
	 * Current process ID
	 *
	 * @return integer
	 */
	public static function pid() {
		return intval(getmypid());
	}
	public static function is_alive($pid) {
		if (function_exists("posix_kill")) {
			return posix_kill($pid, 0);
		}
		log::warning("{method}({pid}) not implemented, returning false", array(
			"method" => __METHOD__,
			"pid" => $pid
		));
		return false;
	}
	private static function _hook_aliases() {
		//self::hook_alias('configured', "Application::configured");
	}
	private static function _internal_hooks() {
		self::add_hook('configured', "zesk::configured");
		self::add_hook('exit', "zesk::autoload_save");
		self::hooks('Template;log;Database');
	}
	public static function configured() {
		if (self::has('deprecated')) {
			$deprecated = self::get("deprecated");
			log::debug("Setting deprecated handling to {deprecated}", compact("deprecated"));
			self::deprecated($deprecated);
		}
		if (self::has('assert')) {
			$ass_settings = array(
				'active' => ASSERT_ACTIVE,
				'warning' => ASSERT_WARNING,
				'bail' => ASSERT_BAIL,
				'quiet' => ASSERT_QUIET_EVAL
			);
			foreach (array_values($ass_settings) as $what) {
				assert_options($what, 0);
			}
			$assopt = self::getl('assert');
			foreach ($assopt as $code) {
				if (array_key_exists($code, $ass_settings)) {
					assert_options($ass_settings[$code], 1);
				} else {
					log::warning("Invalid assert option: {code}, valid options: {settings}", array(
						"code" => $code,
						"settings" => array_keys($ass_settings)
					));
				}
			}
		}
		if (self::has('assert_callback')) {
			assert_options(ASSERT_CALLBACK, self::get('assert_callback'));
		}
		if (self::has('command_path')) {
			self::command_path(self::get('command_path'));
		}
		if (self::has('zesk_command_path')) {
			self::zesk_command_path(self::get('zesk_command_path'));
		}
		if (self::has('zesk::module')) {
			$modules = self::getl('zesk::module');
			log::debug("Loading modules: " . implode(", ", $modules));
			Module::load($modules);
		}
		foreach (get_declared_classes() as $class) {
			self::register_class($class);
		}
	}
	
	/**
	 * Called to register various hooks throughout the system.
	 * A list of classes is passeed in which are autoloaded and
	 * then ::hooks is called for them. Every call is called once and only once, order must not
	 * matter, but can be
	 * enforced by calling zesk::hooks('dependency1;dependency2'); as the first line to your hooks
	 * registration call.
	 * Note that the chosen "::hooks" calls should pretty much do one thing: call
	 * `zesk::add_hook(...)` and that's it,
	 * and should do the
	 * bare minimum registration to operate correctly.
	 * Generally, classes will do:
	 * <code>
	 * class foo {
	 * public static function hooks() {
	 * zesk::add_hook('configured', __CLASS__ . "::configured");
	 * }
	 * public static function configured() {
	 * if (self::getb('foo::enabled')) {
	 * // Do something important
	 * }
	 * }
	 * }
	 * </code>
	 *
	 * @param mixed $hooks
	 *        	List of hooks to register.
	 * @return array Hook class name eith the time invoked, or an Exception if an error occurred.
	 */
	static $hooks_called = array(
		'zesk' => 0
	);
	public static function hooks($hooks = null, $options = null) {
		if ($hooks === null) {
			return self::$hooks_called;
		}
		$hooks = to_list($hooks, array());
		$result = array();
		foreach ($hooks as $class) {
			$class = strtolower($class);
			
			if (array_key_exists($class, self::$hooks_called)) {
				$result[$class] = self::$hooks_called[$class];
			} else if (method_exists($class, "hooks")) {
				try {
					call_user_func(array(
						$class,
						"hooks"
					));
					self::$hooks_called[$class] = $result[$class] = microtime(true);
				} catch (Exception $e) {
					zesk::hook("exception", $e);
					self::$hooks_called[$class] = $result[$class] = $e;
				}
			} else if (self::$debug_hooks) {
				log::debug("{__CLASS__}::{__FUNCTION__} Class {class} does not have method hooks", array(
					"__CLASS__" => __CLASS__,
					"__FUNCTION__" => __FUNCTION__,
					"class" => $class
				));
			}
		}
		return $result;
	}
	
	/**
	 * Does a hook exist? Logs all hook name requests.
	 * To retrieve them just call zesk::has_hook() to get the currently
	 * requested list. Returns an array with hook name and the number of times called, the time it
	 * was requested the
	 * first time and last time, e.g. $hook => array(45, 0, 10.42)
	 *
	 * @param mixed $hooks
	 *        	A hook name, or a list of hooks, separated by ";", or an array of hook names
	 * @return true If any hook exists. If null passed then returns an array of keys => arrays
	 *         described above.
	 */
	public static function has_hook($hooks = null) {
		static $hook_cache = array();
		if ($hooks === null) {
			return $hook_cache;
		}
		$hooks = to_list($hooks);
		foreach ($hooks as $hook) {
			$ding = microtime(true);
			$hook = self::_hook_name($hook);
			if (zesk::$profile_hooks) {
				if (!array_key_exists($hook, $hook_cache)) {
					$hook_cache[$hook] = array(
						1,
						$ding,
						$ding
					);
				} else {
					$hook_cache[$hook][0]++;
					$hook_cache[$hook][2] = $ding;
				}
			}
			if (array_key_exists($hook, self::$hooks)) {
				return $hook;
			}
		}
		return null;
	}
	
	/**
	 * Define a hook - this needs to be defined, actually
	 *
	 * @param string $hook        	
	 * @param array $arguments_definition        	
	 * @param string $return        	
	 */
	public static function define_hook($hook, array $options = array()) {
		zesk::obsolete();
		$hook = self::_hook_name($hook);
		self::$hook_definitions[$hook] = $options;
		if (array_key_exists($hook, self::$hooks)) {
			foreach (self::$hooks[$hook] as $callable_string => $options) {
				self::$hooks[$hook][$callable_string] += $options;
			}
		}
	}
	
	/**
	 * Removes all hooks of a particular name
	 *
	 * @param string $hook        	
	 */
	public static function clear_hook($hook) {
		$hook = self::_hook_name($hook);
		if (array_key_exists($hook, self::$hooks)) {
			unset(self::$hooks[$hook]);
			return true;
		}
		return false;
	}
	
	/**
	 * Given a passed-in hook name, normalize it and return the internal name
	 *
	 * @param string $name
	 *        	Hook name
	 * @param unknown_type $alias        	
	 */
	private static function _hook_name($name, $alias = true) {
		$name = strtolower($name);
		return !$alias ? $name : array_key_exists($name, self::$hook_aliases) ? self::$hook_aliases[$name] : $name;
	}
	
	/**
	 * Remove hooks - use with caution
	 *
	 * @param string $hook        	
	 */
	public static function unhook($hook) {
		$hook = self::_hook_name($hook);
		self::$hooks[$hook] = array();
	}
	/**
	 * Hooks are very flexible, and each hook determines how it is combined with the next hook.
	 *
	 * @param string $hook
	 *        	Hook name. Can be any string. Typically of the form CLASS::method
	 * @param mixed $function
	 *        	A function or class name, or an array to specify an object method or object static
	 *        	method.
	 * @param array $options
	 *        	Return value handling
	 * @param mixed $return
	 *        	DEPRECATED, ignored
	 * @param boolean $first
	 *        	DEPRECATED, ignored
	 */
	public static function add_hook($hook, $function = null, $options = array(), $return = "IGNORE", $first = "IGNORE") {
		if ($return !== "IGNORE" || $first !== "IGNORE") {
			echo __CLASS__ . "::add_hook deprecated parameters \$return=$return \$first=$first\n";
			backtrace();
		}
		if (is_string($options)) {
			$options = array(
				$options => true
			);
		} else if (!is_array($options)) {
			$options = array();
		}
		$hook = self::_hook_name($hook);
		if (!array_key_exists($hook, self::$hooks)) {
			self::$hooks[$hook] = array();
		}
		if (!is_callable($function)) {
			throw new Exception_Semantics(zesk::callable_string($function) . " is not callable");
		}
		$callable_string = self::callable_string($function);
		if (array_key_exists($callable_string, self::$hooks[$hook])) {
			log::debug("Duplicate registration of hook {callable}", array(
				"callable" => $callable_string
			));
			return;
		}
		$options['callable'] = $callable = ($function === null ? $hook : $function);
		$options += avalue(self::$hook_definitions, $hook, array());
		$n = count(self::$hooks[$hook]);
		$options['weight'] = avalue($options, 'first') ? -1000 + $n : avalue($options, 'last') ? 1000 + $n : $n;
		self::$hooks[$hook][$callable_string] = $options;
		uasort(self::$hooks[$hook], 'zesk::sort_weight_array');
	}
	
	/**
	 * Allow easy migration from old names to new
	 * Retrieve all aliases:
	 * <code>
	 * $all_aliases = zesk::hook_alias();
	 * </code>
	 * Retrieve a single alias:
	 * <code>
	 * $alias = zesk::hook_alias('configured');
	 * </code>
	 * Delete an alias:
	 * <code>
	 * $old_alias = zesk::hook_alias('configured', false);
	 * </code>
	 * Add an alias:
	 * <code>
	 * $previous_alias = zesk::hook_alias('configured', 'Application::configured');
	 * </code>
	 * Bulk actions:
	 * <code>
	 * $results = zesk::hook_alias(
	 * array(
	 * 'setone' => 'newvalue',
	 * 'getone' => null,
	 * 'getanother' => null,
	 * 'unsetone' => false,
	 * )
	 * );
	 * </code>
	 *
	 * @param string $oldname        	
	 * @param string $newname        	
	 * @return mixed
	 */
	public static function hook_alias($oldname = null, $newname = null) {
		if (is_array($oldname)) {
			$result = array();
			foreach ($oldname as $old => $new) {
				$result[$old] = self::hook_alias($old, $new);
			}
			return $result;
		}
		if ($oldname === null) {
			return self::$hook_aliases;
		} else if ($newname === null) {
			$oldname = self::_hook_name($oldname, false);
			return avalue(self::$hook_aliases, $oldname);
		} else {
			$previous = avalue(self::$hook_aliases, $oldname);
			if ($newname === false) {
				unset(self::$hook_aliases[$oldname]);
			} else {
				$newname = self::_hook_name($newname);
				if ($oldname === $newname) {
					return $previous;
				}
				self::$hook_aliases[$oldname] = $newname;
				if (array_key_exists($oldname, self::$hooks)) {
					$oldhooks = avalue(self::$hooks, $oldname, array());
					$newhooks = avalue(self::$hooks, $newname, array());
					self::$hooks[$newname] = array_merge($newhooks, $oldhooks);
					unset(self::$hooks[$oldname]);
				}
			}
			return $previous;
		}
	}
	private static function hook_load_definitions($hooks) {
		$definitions = array();
		$found = array();
		foreach (to_list($hooks) as $hook) {
			$hook = self::_hook_name($hook);
			if (array_key_exists($hook, $found)) {
				continue;
			}
			$found[$hook] = true;
			if (array_key_exists($hook, self::$hooks)) {
				$definitions += self::$hooks[$hook];
			}
		}
		return $definitions;
	}
	public static function hook_array($hooks, $arguments = array(), $default = null, $hook_callback = null, $result_callback = null) {
		if (!self::has_hook($hooks)) {
			return $default;
		}
		
		$definitions = self::hook_load_definitions($hooks);
		if (count($definitions) === 0) {
			return $default;
		}
		$result = null;
		// 		log::debug("hook definitions: {hooks} => {def}", array(
		// 			"hooks" => $hooks,
		// 			"def" => $definitions
		// 		));
		foreach ($definitions as $callable_string => $options) {
			$return = avalue($options, 'return', '+');
			$result = Hookable::hook_results($result, $options['callable'], $arguments, $hook_callback, $result_callback, $return);
		}
		return $result;
	}
	public static function hook($hook) {
		$arguments = func_get_args();
		array_shift($arguments);
		return self::hook_array($hook, $arguments);
	}
	private static function add_class($class, $scopes = null) {
		$parent_classes = self::class_hierarchy($class);
		if ($scopes !== null) {
			$scopes = arr::change_value_case(to_list($scopes));
		}
		$class = strtolower($class);
		if (!array_key_exists($class, self::$registry)) {
			self::$registry[$class] = array();
		}
		array_shift($parent_classes);
		foreach ($parent_classes as $parent_class) {
			$parent_class = strtolower($parent_class);
			if ($scopes === null || in_array($parent_class, $scopes)) {
				self::$registry[$parent_class][$class] = 1;
			}
			$class = $parent_class;
		}
	}
	public static function all_subclasses($class) {
		$classes = array(
			$class
		);
		$result = array();
		while (count($classes) > 0) {
			$class = array_shift($classes);
			if (empty($class)) {
				continue;
			}
			$result[] = $class;
			$subclasses = self::register_class($class);
			if (is_array($subclasses)) {
				$classes = array_merge($classes, $subclasses);
			}
		}
		return $result;
	}
	
	/**
	 * Register a global hook by class
	 */
	public static function register_class($class = null, $scopes = null) {
		if (is_array($class)) {
			$result = array();
			foreach ($class as $classy) {
				$result[$classy] = self::register_class($classy, $scopes);
			}
			return $result;
		}
		if ($class === null) {
			return self::$registry;
		}
		if (empty($class)) {
			// Do we need to warn? Not sure if silent failure is best. Probably for now.
			return null;
		}
		if (!is_subclass_of($class, "Hookable")) {
			return null;
		}
		$low_class = strtolower($class);
		if (!array_key_exists($low_class, self::$registry)) {
			self::add_class($class, $scopes);
		}
		return array_keys(self::$registry[$low_class]);
	}
	
	/**
	 * Invoke a global hook by type
	 */
	public static function all_hook($method) {
		$arguments = func_get_args();
		array_shift($arguments);
		return self::all_hook_array($method, $arguments);
	}
	private static function _register_all_hooks($class) {
		$refl = new ReflectionClass($class);
		$method = 'register_all_hooks';
		if ($refl->hasMethod($method)) {
			$refl->getMethod($method)->invoke(null);
		}
		zesk::hook("$class::register_all_hooks");
	}
	
	/**
	 * Find all hooks given a class::method string - finds all items of class which have method
	 * method
	 *
	 * @param mixed $methods
	 *        	List of methods (array or ;-separated string)
	 */
	public static function find_all_hooks($methods) {
		static $all_hook_classes = array();
		$class_methods = to_list($methods);
		$methods = array();
		foreach ($class_methods as $class_method) {
			list($class, $method) = pair($class_method, "::", null, $class_method);
			if ($class === null) {
				continue;
			}
			$class = strtolower($class);
			if (!array_key_exists($class, $all_hook_classes) && $method !== "hooks") {
				$all_hook_classes[$class] = true;
				self::_register_all_hooks($class);
			}
			$classes = zesk::all_subclasses($class);
			if ($classes === null) {
				continue;
			}
			//echo "register_class($class) -> "; dump($classes);
			foreach ($classes as $class) {
				$class = strtolower($class);
				//				echo "$class\n";
				$refl = new ReflectionClass($class);
				if (!$refl->hasMethod($method)) {
					//echo " - no $method\n";
					continue;
				}
				$refl_method = $refl->getMethod($method);
				if (!$refl_method->isStatic()) {
					// Only run on static methods
					//					echo " - $method exists but is NOT static\n";
					continue;
				}
				//echo " - has $method\n";
				/* @var $refl_method ReflectionMethod */
				$declaring = strtolower($refl_method->getDeclaringClass()->name);
				//	echo "$class ($declaring) -> ";
				if (strcasecmp($declaring, $class) === 0) {
					//$methods[] = "$declaring*$class::$method";
					$full_method = "$class::$method";
				} else {
					$full_method = "$declaring::$method";
				}
				$methods[$full_method] = $full_method;
			}
		}
		return $methods;
	}
	
	/**
	 * Invoke a global hook by type
	 */
	public static function all_hook_array($methods, array $arguments = array(), $default = null, $hook_callback = null, $result_callback = null) {
		$methods = self::find_all_hooks($methods);
		$result = $default;
		foreach ($methods as $class_method) {
			$result = Hookable::hook_results($result, $class_method, $arguments, $hook_callback, $result_callback);
			$result = self::hook_array($class_method, $arguments, $result, $hook_callback, $result_callback);
		}
		return $result;
	}
	
	/**
	 * For cordoning off old, dead code
	 */
	public static function obsolete() {
		log::error("Obsolete function called {function}", array(
			'function' => self::calling_function(2)
		));
		if (Application::instance()->development()) {
			backtrace();
		}
	}
	
	/**
	 * Enables a method to be tagged as "deprecated" To disabled deprecated function, call with
	 * boolean value "false"
	 *
	 * @param mixed $set
	 *        	Value indicating how to handle deprecated functions: "exception" throws an
	 *        	exception, "log"
	 *        	logs to php error log, "backtrace" to backtrace immediately
	 * @return mixed Current value
	 */
	public static function deprecated($set = null) {
		if ($set !== null) {
			self::$deprecated = $set;
		} else if (self::$deprecated) {
			switch (self::$deprecated) {
				case "exception":
					throw new Exception_Deprecated(self::calling_function());
				case "log":
					log::error("deprecated function called: {calling_function}\n{backtrace}", array(
						"calling_function" => self::calling_function(),
						"backtrace" => _backtrace()
					));
					break;
				default :
					backtrace();
					exit();
			}
		}
		return self::$deprecated;
	}
	
	/**
	 * zesk global default values - need to deprecate this for local versions
	 */
	public static function defaults() {
		return arr::clean(array(
			'home' => avalue($_SERVER, 'HOME'),
			'argv' => avalue($_SERVER, 'argv')
		));
	}
	
	/**
	 * Convert a string into a valid PHP function or class name.
	 * Useful for cleaning hooks generated automatically or
	 * from user input.
	 *
	 * @param string $func
	 *        	String to clean
	 * @return string
	 */
	public static function clean_function($func) {
		return preg_replace("/[^a-zA-Z0-9_]/", '_', $func);
	}
	
	/**
	 * Convert a string into a valid path suitable for all platforms.
	 * Useful for cleaning user input for conversion to a
	 * path or file name
	 *
	 * @param string $func
	 *        	String to clean
	 * @return string
	 */
	public static function clean_path($path) {
		return preg_replace("%[^-_./a-zA-Z0-9]%", '_', str_replace("_", "/", $path));
	}
	
	/**
	 * Set a global setting.
	 *
	 * @param mixed $key
	 *        	A key to store in the global context, or an array of name/values pairs to set
	 * @param mixed $value
	 *        	The value to set
	 * @return mixed The set value of the global
	 */
	public static function set($key, $value = null, $overwrite = true) {
		global $_ZESK;
		static $locks = array();
		if (is_array($key)) {
			$result = array();
			foreach ($key as $n => $v) {
				$result[$n] = self::set($n, $v, $overwrite);
			}
			return $result;
		}
		$key = _zesk_global_key($key);
		if ($overwrite === self::$global_key) {
			apath_set($_ZESK, $key, $value, ZESK_GLOBAL_KEY_SEPARATOR);
			$key = to_list($key, array(), ZESK_GLOBAL_KEY_SEPARATOR);
			while (count($key) > 0) {
				$locks[implode(ZESK_GLOBAL_KEY_SEPARATOR, $key)] = microtime(true);
				array_pop($key);
			}
			return $value;
		}
		if (array_key_exists($key, $locks)) {
			throw new Exception_Semantics("Can not overwrite internal global {key}", compact("key"));
		}
		if (!$overwrite && apath($_ZESK, $key, null, ZESK_GLOBAL_KEY_SEPARATOR)) {
			return zesk::get($key);
		}
		if (self::$debug_set_already) {
			$old_value = zesk::get($key);
			if ($old_value === $value) {
				log::warning("$key wrote same value again " . $value);
			} else {
				log::notice("$key overwrite old value {old_value} with new value {value}", compact("old_value", "value"));
			}
		}
		apath_set($_ZESK, $key, $value, ZESK_GLOBAL_KEY_SEPARATOR);
		return $value;
	}
	
	/**
	 * Set a global array key.
	 *
	 * @param string $k
	 *        	First key
	 * @param string $k1
	 *        	2nd key
	 * @param mixed $v        	
	 * @return mixed
	 */
	public static function setk($key, $k1, $value) {
		$value = zesk::get($key);
		$value[$k1] = $value;
		zesk::set($key, $value);
	}
	
	/**
	 * Add to a global array
	 *
	 * @param string $k
	 *        	Key to add to
	 * @param string $v
	 *        	Value to add. If it's a string, it's appended to the array. If it's an array, it's
	 *        	array_merge($existing, $v)
	 * @param unknown_type $v        	
	 */
	public static function add($k, $v) {
		$value = zesk::get($k);
		if (!is_array($value)) {
			$value = array();
		}
		if (is_array($v)) {
			$value = array_merge($value, $v);
		} else {
			$value[] = $v;
		}
		return zesk::set($k, $value);
	}
	
	/**
	 * Is a global setting set?
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return mixed
	 */
	public static function has($key, $check_empty = true) {
		$value = zesk::get($key, self::$global_key);
		if ($value === self::$global_key) {
			return false;
		}
		if (!$check_empty) {
			return true;
		}
		return !empty($value);
	}
	
	/**
	 * Normalize a group of keys
	 *
	 * @param array $keys        	
	 * @return multitype:unknown
	 */
	static function normalize_global_keys(array $keys) {
		$result = array();
		foreach ($keys as $key => $value) {
			$result[_zesk_global_key($key)] = $value;
		}
		return $result;
	}
	
	/**
	 * Sort an array based on the weight array index
	 * Support special terms such as "first" and "last"
	 *
	 * use like:
	 *
	 * usort($this->links_sorted, "zesk::sort_weight_array");
	 * uasort($this->links_sorted, "zesk::sort_weight_array");
	 *
	 * @param array $a        	
	 * @param array $b        	
	 * @see usort
	 * @see uasort
	 * @return integer
	 */
	public static function sort_weight_array(array $a, array $b) {
		// Get weight a, convert to double
		$aw = array_key_exists('weight', $a) ? $a['weight'] : 0;
		$aw = doubleval(array_key_exists("$aw", self::$weight_specials) ? self::$weight_specials[$aw] : $aw);
		
		// Get weight b, convert to double
		$bw = array_key_exists('weight', $b) ? $b['weight'] : 0;
		$bw = doubleval(array_key_exists("$bw", self::$weight_specials) ? self::$weight_specials[$bw] : $bw);
		
		// a < b -> -1
		// a > b -> 1
		// a === b -> 0
		return $aw < $bw ? -1 : ($aw > $bw ? 1 : 0);
	}
	
	/**
	 * Convert a value automatically into a native PHP type
	 *
	 * @param mixed $value        	
	 * @return mixed
	 */
	public static function autotype($value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = self::autotype($v);
			}
			return $value;
		}
		if (!is_string($value)) {
			return $value;
		}
		// Convert numeric types first, then boolean
		if (preg_match('/^[0-9]+$/', $value)) {
			return to_integer($value);
		}
		if (is_numeric($value)) {
			return to_double($value);
		}
		if (($b = to_bool($value, null)) !== null) {
			return $b;
		}
		if ($value === 'null') {
			return null;
		}
		if (unquote($value, '{}[]') !== $value) {
			return json::decode($value, true);
		}
		return $value;
	}
	
	/**
	 * Execute a shell command.
	 *
	 * Usage is:
	 * <pre>
	 * zesk::execute("ls -d {0}", $dir);
	 * </pre>
	 * Arguments are indexed and passed through. If you'd prefer named arguments, use execute_array
	 *
	 * @param string $command        	
	 * @return array Lines output by the command (returned by exec)
	 * @see exec
	 * @see zesk::execute_array
	 */
	public static function execute($command) {
		$args = func_get_args();
		array_shift($args);
		return self::execute_array($command, $args);
	}
	
	/**
	 * Execute a shell command with arguments supplied as an array
	 *
	 * Usage is:
	 * <pre>
	 * zesk::execute("ls -d {dir}", array("dir" => $dir));
	 * </pre>
	 *
	 * Non-zero output status of the command throws an exception, always. If you expect failures,
	 * catch the exception:
	 *
	 * <code>
	 * try {
	 * zesk::execute("mount {0}", $volume);
	 * } catch (Exception_Command $e) {
	 * echo "Volume mount failed: $volume\n" . $e->getMessage(). "\n";
	 * }
	 * </code>
	 *
	 * @param string $command
	 *        	Command to run
	 * @param array $args
	 *        	Arguments to escape and pass into the command
	 * @param boolean $passthru
	 *        	Whether to use passthru vs exec
	 * @throws Exception_Command
	 * @return array Lines output by the command (returned by exec)
	 * @see exec
	 */
	public static function execute_array($command, array $args = array(), $passthru = false) {
		foreach ($args as $i => $arg) {
			$args[$i] = escapeshellarg($arg);
		}
		$raw_command = map($command, $args);
		$result = 0;
		$output = array();
		if (self::getb('zesk::debug_execute')) {
			log::debug("Running command: {raw_command}", compact("raw_command"));
		}
		if ($passthru) {
			passthru($raw_command, $result);
			$output = null;
		} else {
			exec($raw_command, $output, $result);
		}
		if (intval($result) !== 0) {
			throw new Exception_Command($raw_command, $result, is_array($output) ? $output : array());
		}
		return $output;
	}
	
	/**
	 * Convert a callable to a string for output/debugging
	 *
	 * @param mixed $callable        	
	 * @return string
	 */
	public static function callable_string($callable) {
		if (is_array($callable)) {
			return is_object($callable[0]) ? strtolower(get_class($callable[0])) . "::" . $callable[1] : implode("::", $callable);
		} else if (is_string($callable)) {
			return $callable;
		} else if (gettype($callable) === "function") {
			return "Closure: " . strval($callable);
		}
		return "Unknown: " . type($callable);
	}
	
	/**
	 * Turn on database schema debugging
	 *
	 * @todo Move to Database
	 * @param unknown $set        	
	 */
	public static function debug_schema($set = null) {
		if (is_bool($set)) {
			self::set('Database_Schema::debug', $set);
		}
		return self::getb('Database_Schema::debug', false);
	}
	public static function number_format($number, $decimals = 0) {
		return number_format($number, $decimals, __('Number::decimal_point:=.'), __('Number::thousands_separator:=,'));
	}
	
	/**
	 * Does one or more themes exist?
	 *
	 * @param mixed $types
	 *        	List of themes
	 * @deprecated 2016-01-10 Use $application->theme_exists() and $template->theme_exists()
	 *             instead.
	 * @return boolean If all exist, returns true, otherwise false
	 */
	public static function theme_exists($types, $args = array()) {
		zesk::deprecated();
		return Application::instance()->theme_exists($types, $args);
	}
	
	/**
	 * theme an element
	 *
	 * @deprecated 2016-01-10 Use $application->theme() and $template->theme() instead.
	 * @param string $type        	
	 * @return string
	 */
	public static function theme($types, $arguments = array(), array $options = array()) {
		zesk::deprecated();
		return Application::instance()->theme($types, $arguments, $options);
	}
	
	/**
	 * Retrieve the list of theme file paths, or add one.
	 *
	 * @deprecated 2015-01-12
	 * @see zesk\Application::
	 * @param string $add
	 *        	(Optional) Path to add to the theme path. Pass in null to do nothing.
	 * @param boolean $first
	 *        	(Optional) Add this path to the beginning of the list (not at the end)
	 * @return array The ordered list of paths to search for theme files.
	 */
	public static function theme_path($add = null, $first = true) {
		zesk::deprecated();
		return Application::instance()->theme_path($add, $first);
	}
	
	/**
	 * Convert a global name to a standard internal format.
	 *
	 *
	 * @param string $key        	
	 * @deprecated 2016-01-13
	 * @return string
	 */
	static function normalize_global_key($key) {
		zesk::deprecated();
		return _zesk_global_key($key);
	}
}

/**
 * Shortcut for generating URLs - allows global manipulation of URLs in the system
 *
 * TODO - This isn't used much, should probably use it or lose it - KMD
 */
function u($url, $options = null) {
	$model = new Model_URL(zesk::href($url), $options);
	zesk::hook('url', $model);
	return $model->url;
}

/**
 * Alias for Template::instance
 *
 * @param string $path
 *        	Path to template, may be relative or absolute
 * @param array $variables
 *        	List of variables to define in Template scope
 * @param mixed $default
 *        	Value returned if template is not found
 * @param array $values
 *        	Return value of values set at end of template invocation.
 * @return mixed The template output
 * @deprecated 2016-01-12
 */
function tpl($path, $variables = null, $default = null, &$values = null) {
	zesk::deprecated();
	backtrace();
	return Template::instance($path, $variables, $default, $values);
}

if (!defined('ZESK_NO_CONFLICT')) {
	
	/**
	 * Basically calls hook for theme_$type Themes
	 *
	 * @deprecated 2016-01-12 Moving away from globals
	 * @param string $type        	
	 * @return string
	 */
	function theme($types, $arguments = array(), array $options = array()) {
		return zesk::theme($types, $arguments, $options);
	}
}
