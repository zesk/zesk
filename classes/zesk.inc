<?php
/**
 * $URL: https://code.marketacumen.com/zesk/tags/0.8.1/classes/zesk.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
use zesk\PHP;
use zesk\Exception_Semantics;

/**
 * Core class in zesk; required for any other functionality.
 * Handles globals, hooks, some simple process
 * tools, and the hook system.
 *
 * @author kent
 * @deprecated 2016-08
 */
class zesk {
	/**
	 * @deprecated 2016-09
	 * @var string
	 */
	const global_separator = "::";
	/**
	 * @deprecated 2016-09
	 * @var string
	 */
	const global_document_cache = "document_cache";
	
	/**
	 * Determine which functions are calling another function and their frequency
	 * @deprecated 2016-01
	 * @var array
	 */
	static $profiler = null;
	
	/**
	 * Key for setting internal globals
	 * @deprecated 2016-01
	 * @var string
	 */
	private static $global_key = null;
	
	/**
	 * Deprecated function flag.
	 * Can be true, "exception", "log", or "backtrace"
	 * @deprecated 2016-01
	 * @var mixed
	 */
	private static $deprecated = null;
	
	/**
	 * Turn on settings upon initialization to determine if values are set already
	 * @deprecated 2016-01
	 * @var boolean
	 */
	public static $debug_set_already = false;
	
	/**
	 * Debug loader problems
	 * @deprecated 2016-01
	 * @var boolean
	 */
	public static $debug_loader = false;
	
	/**
	 * Is this a console invokation of Zesk?
	 *
	 * @var boolean
	 * @deprecated 2016-08
	 * @see zesk()->console
	 */
	public static $console = false;
	
	/**
	 * Are we on a Windows system?
	 *
	 * @deprecated 2016-08
	 * @see zesk()->is_windows
	 */
	public static $is_windows = false;
	
	/**
	 * Global singletons.
	 * Not cached.
	 * @deprecated 2016-01
	 * @var array
	 */
	private static $singletons = array();
	
	/**
	 * Cache normalize_global_key calls
	 * @deprecated 2016-01
	 * @var array
	 */
	private static $key_map = array();
	
	/**
	 * Get/set console
	 *
	 * @param string $set        	
	 * @return boolean
	 * @deprecated 2016-08
	 */
	public static function console($set = null) {
		if ($set !== null) {
			zesk()->console = $set;
		}
		return zesk()->console;
	}
	
	/**
	 * Create a new class based on name
	 *
	 * @param string $class        	
	 * @return stdClass
	 * @throws Exception
	 * @deprecated 2016-08
	 */
	public static function factory($class) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$args = func_get_args();
		array_shift($args);
		return $zesk->objects->factory_arguments($class, $args);
	}
	
	/**
	 * Create a new class based on name
	 * @deprecated 2016-01
	 *
	 * @param string $class        	
	 * @param array $args        	
	 * @return stdClass
	 * @throws Exception
	 */
	public static function factory_array($class, array $args) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->objects->factory_arguments($class, $args);
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
	 * @deprecated 2016-08
	 */
	public static function singleton($class) {
		global $zesk;
		$arguments = func_get_args();
		array_shift($arguments);
		/* @var $zesk zesk\Kernel */
		return $zesk->objects->singleton_arguments($class, $arguments);
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		zesk()->deprecated();
		return $zesk->load($__file__);
	}
	
	/**
	 * Get/Set temporary path
	 *
	 * @deprecated 2016-08 
	 * @param string $add        	
	 * @return string
	 */
	public static function temporary_path($add = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		zesk()->deprecated();
		return $zesk->paths->temporary($add);
	}
	
	/**
	 * Get/Set data storage path
	 *
	 * @deprecated 2016-08 
	 * @param string $add        	
	 * @return string
	 */
	public static function data_path($add = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		zesk()->deprecated();
		return $zesk->paths->data($add);
	}
	
	/**
	 * Get/Set library file path (NOTE: May be part of source control)
	 *
	 * @param string $add        	
	 * @return string
	 * @deprecated 2016-08 No replacement
	 */
	public static function library_path($add = null) {
		zesk::obsolete();
	}
	
	/**
	 *
	 * @see self::which
	 * @deprecated 2016-08
	 */
	public static function command_path($add = null) {
		global $zesk;
		zesk()->deprecated();
		/* @var $zesk zesk\Kernel */
		return $zesk->paths->command($add);
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
	 * @deprecated 2016-08
	 */
	public static function zesk_command_path($add = null) {
		zesk()->deprecated();
		return app()->zesk_command_path($add);
	}
	
	/**
	 * Find a command using the comand path
	 * @deprecated 2016-01
	 *
	 * @param string $command        	
	 */
	public static function which($command) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->paths->which($command);
	}
	public static function backtrace() {
		print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
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
	 * @deprecated 2016-09
	 */
	public static function calling_function($depth = 1, $include_line = true) {
		zesk()->deprecated();
		return calling_function($depth + 1, $include_line);
	}
	
	/**
	 * Time a function call
	 *
	 * @deprecated 2016-10
	 * @param string $item
	 *        	Key
	 * @param double $seconds
	 *        	How long it took
	 */
	public static function profile_timer($item, $seconds) {
		zesk()->profile_timer($item, $seconds);
	}
	
	/**
	 * Internal profiler to determine who is calling what function how often.
	 * Debugging only
	 * @deprecated 2016-10
	 * @param numeric $depth        	
	 */
	public static function profiler($depth = 2) {
		zesk()->profiler($depth);
	}
	
	/**
	 * Reset all globals to null and call hook('reset')
	 *
	 * @deprecated 2016-08
	 */
	public static function reset(array $globals = array()) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$zesk->reset($globals);
	}
	
	/**
	 * Retrieve a global setting.
	 * Includes environment values for this system.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return mixed
	 * @deprecated 2016-08
	 */
	public static function get($key = null, $default = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($key === null) {
			$zesk->logger->warning("Fetching zesk globals as array - slow, do not do this! {trace}", array(
				"trace" => _backtrace()
			));
			return $zesk->configuration->to_array();
		} else if (is_array($key)) {
			$result = array();
			foreach ($key as $k => $v) {
				$result[$k] = self::get($k, $v);
			}
			return $result;
		}
		return self::_get($key, $default);
	}
	
	/**
	 * Grab a reference to a point in the configuration tree
	 *
	 * @param mixed $key        	
	 * @return array (reference)
	 * @deprecated 2016-08
	 * @see zesk\Configuration
	 */
	public static function &reference($key) {
		zesk::obsolete();
	}
	/**
	 * Get a global value
	 *
	 * @param string $key
	 *        	Global to fetch
	 * @param mixed $default
	 *        	Default value if not found
	 * @return mixed
	 */
	private static function _get($key, $default) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		assert(is_scalar($key));
		if (!is_scalar($key)) {
			backtrace();
		}
		if (array_key_exists($key, self::$key_map)) {
			$key_path = self::$key_map[$key];
		} else {
			$key_path = _zesk_global_key($key);
			self::$key_map[$key] = count($key_path) === 1 ? $key_path[0] : $key_path;
		}
		if (is_string($key_path)) {
			return $zesk->configuration->get($key_path, $default);
		}
		$key = array_pop($key_path);
		$result = $zesk->configuration->walk($key_path);
		if ($result instanceof zesk\Configuration) {
			$result = $result->get($key, $default);
			return $result instanceof zesk\Configuration ? $result->to_array() : $result;
		}
		return $default;
	}
	/**
	 * Retrieve a global which should be a boolean.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return boolean in the global context, or $default if it's not set
	 * @deprecated 2016-08
	 */
	public static function getb($key, $default = false) {
		return to_bool(self::_get($key, $default), $default);
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
	 * @deprecated 2016-08
	 */
	public static function geti($key, $default = null) {
		return to_integer(self::_get($key, null), $default);
	}
	
	/**
	 * Retrieve a global which should be a boolean.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return The boolean in the global context, or $default if it's not set
	 * @deprecated 2016-08
	 */
	public static function getl($key, $default = array(), $delimiter = ";") {
		return to_list(self::_get($key, $default), $default, $delimiter);
	}
	
	/**
	 * Retrieve a global which should be an array.
	 *
	 * @param string $key
	 *        	A key to retrieve from the global context
	 * @param mixed $default
	 *        	The default value if not set
	 * @return The boolean in the global context, or $default if it's not set
	 * @deprecated 2016-08
	 */
	public static function geta($key, $default = array()) {
		if ($key === null) {
			return $default;
		}
		return to_array(self::_get($key, $default), $default);
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
			// Pass self::$global_key as default to discern empty values
			$value = self::_get($key, self::$global_key);
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
	 * Is the system currently in maintenance mode?
	 * @deprecated 2016-01
	 *
	 * @return boolean
	 */
	public static function maintenance() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->maintenance;
	}
	
	/**
	 * Path relative to the Zesk Application Root
	 *
	 * @see self::root
	 * @param string $path
	 *        	Path to add to the application root
	 * @return string
	 * @deprecated 2016-08
	 */
	public static function application_root($path = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return path($zesk->paths->application, $path);
	}
	
	/**
	 * Path relative to the Zesk Root
	 *
	 * @see self::application_root
	 * @param string $path        	
	 * @return string
	 * @deprecated 2016-08
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
	 * @deprecated 2016-09
	 * @see $zesk->application_class
	 */
	public static function application_class($set = null) {
		global $zesk;
		zesk()->deprecated();
		/* @var $zesk zesk\Kernel */
		if ($set !== null) {
			$zesk->application_class = $set;
			return $set;
		}
		return $zesk->application_class;
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
	 * @deprecated 2016-09
	 */
	public static function find_directory(array $paths, $directory = null) {
		zesk()->deprecated();
		return zesk\Directory::find($paths, $directory);
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
	 * @deprecated 2016-09
	 */
	public static function find_file(array $paths, $file) {
		zesk()->deprecated();
		return zesk\File::find_first($paths, $file);
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
	 * @deprecated 2016-09
	 */
	public static function find_files(array $paths, $file) {
		zesk()->deprecated();
		return zesk\File::find_all($paths, $file);
	}
	/**
	 * Search for a file in the given paths, converting filename to a directory path by converting _
	 * to /, and look for
	 * files with the given extensions, in order.
	 *
	 * @deprecated 2016-08
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
		global $zesk;
		zesk()->deprecated();
		/* @var $zesk zesk\Kernel */
		return $zesk->autoloader->file_search($paths, $file_prefix, $extensions, $tried_path);
	}
	
	/**
	 * Generic function to create paths correctly
	 *
	 * @param
	 *        	string separator Token used to divide path
	 * @param
	 *        	array mixed List of path items, or array of path items to concatenate
	 * @return string with a properly formatted path
	 * @deprecated 2016-08
	 */
	public static function path($separator = '/', array $mixed) {
		zesk()->deprecated();
		return path_from_array($separator, $mixed);
	}
	
	/**
	 * Load the bare minimum settings for Zesk to work.
	 * Sets a few "standard" globals: - ZESK_ROOT for use in other
	 * loaded globals Sets up the autoloader for loading classes based on a search path. Sets up the
	 * base theme path
	 * as self::theme_path_default() Sets up the base module path as self::module_path_default() -
	 *
	 * @see constant ZESK_DEBUG_SET_ALREADY
	 */
	public static function bootstrap(zesk\Kernel $zesk) {
		// Deprecated fields
		self::$console = $zesk->console;
		self::$is_windows = $zesk->is_windows;
		
		if (defined("ZESK_DEBUG_SET_ALREADY") && ZESK_DEBUG_SET_ALREADY) {
			self::$debug_set_already = true;
		}
	}
	
	/**
	 * Convert a link href into a full path.
	 *
	 * @todo Move this elsewhere. HTML::?
	 * @todo is this used?
	 *      
	 * @param string $path        	
	 * @return string
	 */
	public static function href($path) {
		if (URL::valid($path)) {
			return $path;
		}
		$prefix = zesk::document_root_prefix();
		return $prefix ? path($prefix, $path) : $path;
	}
	
	/**
	 * Set a PHP feature.
	 *
	 * @param string $feature        	
	 * @param mixed $value
	 *        	Value to set it to
	 *        	
	 * @return boolean True if successful
	 * @deprecated 2016-08
	 */
	public static function php_set($feature, $value) {
		return zesk\PHP::feature($feature, $value);
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
	 * @deprecated 2016-08
	 * @return integer
	 */
	public static function pid() {
		zesk()->deprecated();
		return intval(getmypid());
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @param unknown $pid        	
	 * @throws Exception_Unimplemented
	 */
	public static function running($pid = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($pid === null) {
			return $zesk->process->id();
		}
		return $zesk->process->alive($pid);
	}
	
	/**
	 * Determines if a process is ailve or not
	 *
	 * @param integer $pid        	
	 * @deprecated 2016-08
	 * @return boolean
	 */
	public static function is_alive($pid) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->process->alive($pid);
	}
	
	/**
	 * For cordoning off old, dead code
	 * @deprecated 2016-09
	 */
	public static function obsolete() {
		zesk()->obsolete();
	}
	
	/**
	 * Enables a method to be tagged as "deprecated" To disabled deprecated function, call with
	 * boolean value "false"
	 *
	 * @deprecated 2016-09
	 * @param mixed $set
	 *        	Value indicating how to handle deprecated functions: "exception" throws an
	 *        	exception, "log"
	 *        	logs to php error log, "backtrace" to backtrace immediately
	 * @return mixed Current value
	 */
	public static function deprecated($set = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$zesk->deprecated();
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
	 * @deprecated 2016-09
	 * @param string $func
	 *        	String to clean
	 * @return string
	 */
	public static function clean_function($func) {
		return zesk\PHP::clean_function($func);
	}
	
	/**
	 * Convert a string into a valid path suitable for all platforms.
	 * Useful for cleaning user input for conversion to a
	 * path or file name
	 *
	 * @deprecated 2016-09
	 * @param string $func
	 *        	String to clean
	 * @return string
	 */
	public static function clean_path($path) {
		return zesk\File::clean_path($path);
	}
	
	/* ===============================================================================================
	 * ===============================================================================================
	 * ===============================================================================================
	 * 
	 * Deprecated below this line
	 * 
	 * ===============================================================================================
	 * ===============================================================================================
	 * ===============================================================================================
	 */
	/**
	 *
	 * @deprecated 2016-08
	 * @param string $class        	
	 * @return mixed[]
	 */
	public static function all_subclasses($class) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		zesk()->deprecated();
		return $zesk->classes->subclasses($class);
	}
	
	/**
	 * Invoke a global hook by type
	 *
	 * @deprecated 2016-08
	 */
	public static function all_hook($method) {
		zesk()->deprecated();
		$arguments = func_get_args();
		array_shift($arguments);
		return self::all_hook_array($method, $arguments);
	}
	
	/**
	 * Find all hooks given a class::method string - finds all items of class which have method
	 * method.
	 *
	 * Warning: SLOW - do not use this in regular page loads
	 *
	 * @param mixed $methods
	 *        	List of methods (array or ;-separated string)
	 * @deprecated 2016-08
	 * @see zesk\Hooks::find_all
	 */
	public static function find_all_hooks($methods) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		zesk()->deprecated();
		return $zesk->hooks->find_all($methods);
	}
	
	/**
	 * Set a global setting.
	 *
	 * @param mixed $key
	 *        	A key to store in the global context, or an array of name/values pairs to set
	 * @param mixed $value
	 *        	The value to set
	 * @return mixed The set value of the global
	 * @deprecated 2016-08
	 */
	public static function set($key, $value = null, $overwrite = true) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if (is_array($key)) {
			$result = array();
			foreach ($key as $n => $v) {
				$result[$n] = self::set($n, $v, $overwrite);
			}
			return $result;
		}
		$key_path = _zesk_global_key($key);
		if (!$overwrite && self::has($key)) {
			return self::get($key);
		}
		if (self::$debug_set_already) {
			$old_value = self::get($key);
			if ($old_value === $value) {
				log::warning("$key wrote same value again " . $value);
			} else {
				log::notice("$key overwrite old value {old_value} with new value {value}", compact("old_value", "value"));
			}
		}
		$last = array_pop($key_path);
		if (count($key_path) > 0) {
			$zesk->configuration->pave($key_path)->$last = $value;
		} else {
			$zesk->configuration->$last = $value;
		}
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
	 * @deprecated 2016-08
	 */
	public static function setk($key, $k1, $value) {
		$value = self::get($key);
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
	 * @deprecated 2016-08
	 */
	public static function add($k, $v) {
		$value = self::get($k);
		if ($value instanceof zesk\Configuration) {
			$value = $value->to_array();
		} else if (!is_array($value)) {
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
	 * @deprecated 2016-08
	 */
	public static function has($key, $check_empty = true) {
		$value = self::get($key, self::$global_key);
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
	 * @deprecated 2016-08
	 */
	static function normalize_global_keys(array $keys) {
		$result = array();
		foreach ($keys as $key => $value) {
			$result[implode(ZESK_GLOBAL_KEY_SEPARATOR, _zesk_global_key($key))] = $value;
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
	 * @deprecated 2016-10
	 */
	public static function sort_weight_array(array $a, array $b) {
		zesk()->deprecated();
		// Get weight a, convert to double
		$aw = array_key_exists('weight', $a) ? $a['weight'] : 0;
		$aw = doubleval(array_key_exists("$aw", zesk\Kernel::$weight_specials) ? zesk\Kernel::$weight_specials[$aw] : $aw);
		
		// Get weight b, convert to double
		$bw = array_key_exists('weight', $b) ? $b['weight'] : 0;
		$bw = doubleval(array_key_exists("$bw", zesk\Kernel::$weight_specials) ? zesk\Kernel::$weight_specials[$bw] : $bw);
		
		// a < b -> -1
		// a > b -> 1
		// a === b -> 0
		return $aw < $bw ? -1 : ($aw > $bw ? 1 : 0);
	}
	
	/**
	 * Convert a value automatically into a native PHP type
	 *
	 * @deprecated 2016-09
	 * @param mixed $value        	
	 * @return mixed
	 */
	public static function autotype($value) {
		return zesk\PHP::autotype($value);
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
	 * @deprecated 2016-09
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
	 * @deprecated 2016-09
	 * @return array Lines output by the command (returned by exec)
	 * @see exec
	 */
	public static function execute_array($command, array $args = array(), $passthru = false) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->process->execute_arguments($command, $args, $passthru);
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
	
	/**
	 * Format a number using locale settings
	 *
	 * @todo Move to zesk\Locale
	 * @param unknown $number        	
	 * @param number $decimals        	
	 * @return string
	 */
	public static function number_format($number, $decimals = 0) {
		return number_format($number, $decimals, __('Number::decimal_point:=.'), __('Number::thousands_separator:=,'));
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
	 * @deprecated 2016-08
	 *            
	 * @param string $document_root_prefix        	
	 */
	public static function document_init($document_root = null, $prefix = null) {
		global $zesk;
		zesk()->deprecated();
		/* @var $zesk zesk\Kernel */
		$zesk->logger->error("{method} is now a no-op, use \$zesk->paths->document()", array(
			'method' => __METHOD__
		));
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
	 * @deprecated 2016-08
	 */
	public static function document_root($set = null, $prefix = null) {
		global $zesk;
		zesk()->deprecated();
		/* @var $zesk zesk\Kernel */
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
	 * @deprecated 2016-08
	 */
	public static function document_root_prefix($set = null) {
		global $zesk;
		zesk()->deprecated();
		/* @var $zesk zesk\Kernel */
		return app()->document_root_prefix($set);
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
		zesk()->deprecated();
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
		zesk()->deprecated();
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
		zesk()->deprecated();
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
		zesk()->deprecated();
		return _zesk_global_key($key);
	}
	
	/**
	 * Convert a callable to a string for output/debugging
	 *
	 * @param mixed $callable        	
	 * @return string
	 * @deprecated 2016-08
	 * @see zesk()->hooks->callable_string
	 */
	public static function callable_string($callable) {
		self::deprecated();
		return zesk()->hooks->callable_string($callable);
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk()->hooks->register_class
	 */
	public static function hooks($classes = null, $options = null) {
		zesk()->deprecated();
		return zesk()->hooks->register_class($classes, $options);
	}
	
	/**
	 * Are we on a production machine?
	 *
	 * @deprecated 2016-01-27
	 * @return boolean
	 */
	public static function production() {
		zesk()->deprecated();
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
		zesk()->deprecated();
		if (is_bool($set)) {
			self::set('development', $set);
		}
		return self::getb('development');
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk()->hooks->has
	 */
	public static function has_hook($hooks = null) {
		zesk()->deprecated();
		return zesk()->hooks->has($hooks);
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk()->hooks->remove
	 */
	public static function clear_hook($hook) {
		zesk()->deprecated();
		return zesk()->hooks->remove($hook);
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk()->hooks->remove
	 */
	public static function unhook($hook) {
		zesk()->deprecated();
		zesk()->hooks->remove($hook);
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk()->hooks->add
	 */
	public static function add_hook($hook, $function = null, $options = array()) {
		zesk()->deprecated();
		return zesk()->hooks->add($hook, $function, $options);
	}
	/**
	 *
	 * @see zesk()->hooks->alias
	 * @deprecated 2016-08
	 * @param string $oldname        	
	 * @param string $newname        	
	 * @return mixed
	 */
	public static function hook_alias($oldname = null, $newname = null) {
		zesk()->deprecated();
		return zesk()->hooks->alias($oldname, $newname);
	}
	
	/**
	 *
	 * @see zesk()->hooks->call
	 * @deprecated 2016-08
	 * @param unknown $hooks        	
	 * @param array $arguments        	
	 * @param unknown $default        	
	 * @param unknown $hook_callback        	
	 * @param unknown $result_callback        	
	 * @return string|NULL|mixed|string|number
	 */
	public static function hook_array($hook, $arguments = array(), $default = null, $hook_callback = null, $result_callback = null) {
		zesk()->deprecated();
		return zesk()->hooks->call_arguments($hook, $arguments, $default, $hook_callback, $result_callback);
	}
	
	/**
	 *
	 * @see zesk()->hooks->call
	 * @deprecated 2016-08
	 * @param unknown $hooks        	
	 * @param array $arguments        	
	 * @param unknown $default        	
	 * @param unknown $hook_callback        	
	 * @param unknown $result_callback        	
	 * @return string|NULL|mixed|string|number
	 */
	public static function hook($hook) {
		$arguments = func_get_args();
		array_shift($arguments);
		return zesk()->hooks->call_arguments($hook, $arguments);
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
	 * @deprecated 2016-08
	 * @param mixed $paths
	 *        	An array of paths to search for environment files, or a string containing the
	 *        	absolute path
	 *        	of an environment file to add
	 * @return string
	 */
	public static function initialize($paths = false, $options = array()) {
		/* @var $zesk Zesk */
		global $zesk;
		zesk()->deprecated();
		conf::globals($paths, $options);
	}
	
	/**
	 * Zesk version
	 *
	 * @deprecated 2016-08
	 * @see zesk\Version::release()
	 */
	public static function version() {
		/* @var $zesk Zesk */
		global $zesk;
		zesk()->deprecated();
		return zesk\Version::release();
	}
	
	/**
	 * Zesk version
	 *
	 * @deprecated 2016-08
	 */
	public static function version_string() {
		/* @var $zesk Zesk */
		global $zesk;
		zesk()->deprecated();
		return zesk\Version::string();
	}
	
	/**
	 * Zesk version date
	 *
	 * @deprecated 2016-08
	 */
	public static function version_date() {
		/* @var $zesk Zesk */
		global $zesk;
		zesk()->deprecated();
		return zesk\Version::date();
	}
	
	/**
	 * Register a global hook by class
	 *
	 * @deprecated 2016-08
	 */
	public static function register_class($class = null, $scopes = null) {
		/* @var $zesk Zesk */
		global $zesk;
		zesk()->deprecated();
		return $zesk->classes->register($class, $scopes);
	}
	
	/**
	 * Retrieve a class hierarchy from leaf to base
	 *
	 * @param mixed $mixed
	 *        	An object or string to find class hierarchy for
	 * @param string $stop_class
	 *        	Return up to and including this class
	 * @deprecated 2016-08
	 * @return array
	 */
	public static function class_hierarchy($mixed = null, $stop_class = null) {
		/* @var $zesk Zesk */
		global $zesk;
		zesk()->deprecated();
		return $zesk->classes->hierarchy($mixed, $stop_class);
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk\Autoloader::path
	 */
	public static function autoload_path($add = null, $options = true) {
		/* @var $zesk Zesk */
		global $zesk;
		zesk()->deprecated();
		return $zesk->autoloader->path($add, $options);
	}
	
	/**
	 * Invoke a global hook by type
	 *
	 * @deprecated 2016-08 Too slow
	 */
	public static function all_hook_array($methods, array $arguments = array(), $default = null, $hook_callback = null, $result_callback = null) {
		zesk()->deprecated();
		$methods = self::find_all_hooks($methods);
		$result = $default;
		foreach ($methods as $class_method) {
			$result = zesk\Hookable::hook_results($result, $class_method, $arguments, $hook_callback, $result_callback);
			$result = self::hook_array($class_method, $arguments, $result, $hook_callback, $result_callback);
		}
		return $result;
	}
	
	/**
	 * Retrieve the list of autoload file extensions, or add one.
	 *
	 * @param string $add
	 *        	(Optional) Path to add to the theme path. Pass in null to do nothing.
	 * @return array The ordered list of paths to search for theme files.
	 * @deprecated 2016-08
	 */
	public static function autoload_extension($add = null) {
		global $zesk;
		zesk()->deprecated();
		return $zesk->autoloader->extension($add);
	}
	
	/**
	 * Get or set the module search path
	 *
	 * @param string $add        	
	 * @return array List of paths searched
	 * @deprecated 2016-08
	 */
	public static function module_path($add = null) {
		global $zesk;
		zesk()->deprecated();
		return $zesk->paths->module($add);
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk\Paths
	 */
	public static function theme_path_default() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->paths->theme_default();
	}
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk\Paths
	 */
	public static function module_path_default() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->paths->module_default();
	}
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk\Paths
	 */
	public static function share_path_default() {
		global $zesk;
		return $zesk->paths->share_default();
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
	 * @deprecated 2016-08
	 */
	public static function share_path($add = null, $name = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->paths->share($add, $name);
	}
	
	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk\Paths
	 */
	public static function cache_path($add = null) {
		global $zesk;
		return $zesk->paths->cache($add);
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
	 * @deprecated 2016-08
	 */
	public static function document_cache($set = null) {
		global $zesk;
		zesk()->deprecated();
		if ($set !== null) {
			app()->set_document_cache($set);
			return $set;
		}
		return $zesk->paths->document_cache;
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
	 * @deprecated 2016-08
	 */
	public static function home_path($add = null) {
		global $zesk;
		zesk()->deprecated();
		return $zesk->paths->home($add);
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
	 * @deprecated 2016-08
	 */
	public static function uid_path($add = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		zesk()->deprecated();
		return $zesk->paths->uid($add);
	}
}

/**
 * Shortcut for generating URLs - allows global manipulation of URLs in the system
 *
 * @deprecated 2016-08
 */
function u($url, $options = null) {
	zesk()->deprecated();
	$model = new Model_URL(zesk::href($url), $options);
	zesk()->hooks->call('url', $model);
	return $model->url;
}

/**
 * Alias for Template::instance
 *
 * @param string $path
 *        	Path to template, may be relative or absolute
 * @param array $variables
 *        	List of variables to define in zesk\Template scope
 * @param mixed $default
 *        	Value returned if template is not found
 * @param array $values
 *        	Return value of values set at end of template invocation.
 * @return mixed The template output
 * @deprecated 2016-01-12
 */
function tpl($path, $variables = null, $default = null, &$values = null) {
	zesk()->deprecated();
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

