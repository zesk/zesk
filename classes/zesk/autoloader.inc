<?php
/**
 * 
 */
namespace zesk;

/**
 * Handles autoloading for Zesk
 * 
 * @author kent
 */
class Autoloader {
	/**
	 * 
	 * @var boolean
	 */
	public $debug_file_search = false;
	/**
	 * 
	 * @var boolean
	 */
	public $debug = false;
	
	/**
	 * Set to true to NOT throw an Exception_Class_NotFound from autoloader
	 * 
	 * @var boolean
	 */
	public $no_exception = false;
	
	/**
	 * 
	 * @var array
	 */
	public $autoload_extensions = array(
		"inc"
	);
	
	/**
	 *
	 * @var boolean
	 */
	private $autoload_cache_changed = false;
	
	/**
	 *
	 * @var array
	 */
	private $autoload_cache_mtime = null;
	
	/**
	 *
	 * @var array
	 */
	private $autoload_cache = null;
	
	/**
	 *
	 * @var string
	 */
	private $autoload_cache_path = null;
	
	/**
	 * Create default autoloader for most of Zesk
	 */
	public function __construct(Kernel $kernel) {
		$this->path(ZESK_ROOT . 'classes', 'last');
		$this->autoload_register();
		$kernel->hooks->add(Hooks::hook_exit, array(
			$this,
			"save"
		));
	}
	
	/**
	 * Should be called once and only once.
	 * Registers zesk's autoloader.
	 */
	private function autoload_register() {
		if (PHP_VERSION_ID >= 50300) {
			spl_autoload_register(array(
				$this,
				"php_autoloader"
			), false, defined("ZESK_NO_CONFLICT") ? false : true);
		} else {
			spl_autoload_register(array(
				$this,
				"php_autoloader"
			), false);
		}
	}
	
	/**
	 * Autoload caching, only needs to reset when classes are added/removed
	 */
	private function _autoload_cache_path() {
		if ($this->autoload_cache_path === null) {
			global $zesk;
			// This is loaded early, so if you overwrite, it doesn't change.
			$this->autoload_cache_path = $zesk->paths->cache('autoload.cache');
		}
		return $this->autoload_cache_path;
	}
	
	/**
	 * Retrieve the autoload cache structure, optionally creating the autoload cache directory if
	 * needed.
	 *
	 * @return array
	 */
	private function &_autoload_cache() {
		if ($this->autoload_cache === null) {
			$dir = dirname($path = $this->_autoload_cache_path());
			if (!is_dir($dir) && is_writable(dirname($dir))) {
				global $zesk;
				/* @var $zesk Kernel */
				if ($zesk->maintenance || !@mkdir($dir, 0770, true)) {
					$this->autoload_cache_path = false;
					$this->autoload_cache = array();
					return $this->autoload_cache;
				}
			}
			if (is_file($path)) {
				$this->autoload_cache_mtime = filemtime($path);
				$this->autoload_cache = unserialize(file_get_contents($path));
			} else {
				$this->autoload_cache_mtime = null;
				$this->autoload_cache = array();
			}
		}
		return $this->autoload_cache;
	}
	
	/**
	 * PHP Autoloader call.
	 * Used in case PHP extends the autoloader to add a 2nd parameter - don't want
	 * it to conflict with our self::load 2nd parameter.
	 *
	 * @param string $class
	 * @return boolean
	 */
	public function php_autoloader($class) {
		global $zesk;
		if ($this->load($class)) {
			$zesk->hooks->register_class($class);
			$zesk->classes->register($class);
			return true;
		}
		return false;
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
	 * @global $this->no_exception
	 * @global define:ZESK_NO_CONFLICT
	 */
	public function load($class, $no_exception = false) {
		$lowclass = strtolower($class);
		$cache = & $this->_autoload_cache();
		$include = null;
		if (array_key_exists($lowclass, $cache)) {
			$include = $cache[$lowclass];
			if (!is_file($include)) {
				unset($this->autoload_cache[$lowclass]);
				$include = null;
			}
		}
		if (!$include) {
			$tried_path = null;
			$t = microtime(true);
			$include = $this->search($class, $this->extension(), $tried_path);
			if ($include === null) {
				if ($no_exception || defined('ZESK_NO_CONFLICT') || $this->no_exception) {
					return false;
				}
				throw new Exception_Class_NotFound($class, "Class {class} called from {calling_function} invoked from:\n{backtrace}\n{tried_path}", array(
					"class" => $class,
					"calling_function" => calling_function(2, true),
					"tried_path" => Text::indent(implode("\n", $tried_path)),
					"backtrace" => Text::indent(_backtrace(), 1)
				));
			}
			$cache[$lowclass] = $include;
			$this->autoload_cache_changed = true;
		}
		if ($this->debug) {
			echo "Include $include" . newline();
		}
		require_once $include;
		return true;
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
	public function file_search(array $paths, $file_prefix, array $extensions, &$tried_path = null) {
		$debug = $this->debug_file_search;
		$tried_path = array();
		foreach ($paths as $path => $options) {
			$options += array(
				'class_prefix' => '',
				'lower' => true,
				'extensions' => $extensions
			);
			if ($options === true) {
				$options = array(
					'lower' => true
				);
			} else if (!is_array($options)) {
				$options = array();
			}
			$class_prefix = rtrim($options['class_prefix'], '_');
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
			$file_parts = implode("/", explode("_", $options['lower'] ? strtolower($path_file_prefix) : $path_file_prefix));
			$exts = $options['extensions'];
			foreach ($exts as $extension) {
				$full_path = path($path, $file_parts) . "." . $extension;
				if ($debug) {
					zesk()->logger->debug("$file_prefix: $full_path");
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
	 * Search for a file in the autoload path (::path), looking for files with
	 * "extesion"
	 *
	 * @param unknown_type $class
	 * @param array $extensions
	 * @param unknown_type $tried_path
	 * @return unknown
	 */
	public function search($class, array $extensions, &$tried_path = null) {
		return $this->file_search($this->path(), $class, $extensions, $tried_path);
	}
	/**
	 * Add/remove an extension
	 * 
	 * @param unknown $add
	 * @return string[]
	 */
	public function extension($add = null) {
		if ($add === null) {
			return $this->autoload_extensions;
		}
		$add = trim($add, ". \t\r\n");
		if (!in_array($add, $this->autoload_extensions)) {
			$this->autoload_extensions[] = $add;
		}
		return $this->autoload_extensions;
	}
	public $first = array();
	public $paths = array();
	public $last = array();
	public $cached = null;
	
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
	 *        	$zesk->autoloader->path($zesk->paths->application('classes','first');
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
	public function path($add = null, $options = true) {
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
			if (isset($options['extensions'])) {
				$options['extensions'] = to_list($options['extensions']);
			}
			if (isset($options['first']) && $options['first']) {
				$this->first[$add] = $options;
				$this->cached = null;
			} else if (isset($options['last']) && $options['last']) {
				$this->last[$add] = $options;
				$this->cached = null;
			} else {
				$this->paths[$add] = $options;
				$this->cached = null;
			}
		}
		if ($this->cached) {
			return $this->cached;
		}
		$this->cached = array_merge($this->first, $this->paths, $this->last);
		return $this->cached;
	}
	
	/**
	 * Write autoload cache path (tests for writability and logs error in development environments)
	 *
	 * @see self::_autoload_cache_path()
	 */
	public function save() {
		if (!$this->autoload_cache_changed) {
			return;
		}
		$path = $this->_autoload_cache_path();
		if (!$path) {
			return;
		}
		if (file_exists("$path.lock")) {
			return;
		}
		clearstatcache(true, $path);
		// Do not overwrite if written since application load
		if (!is_file($path) || filemtime($path) <= $this->autoload_cache_mtime) {
			if (@file_put_contents($path, serialize($this->autoload_cache))) {
				return;
			}
			zesk()->logger->warning("Can not write autoload path to {path}", array(
				"path" => $path
			));
		}
	}
}
