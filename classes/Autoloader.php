<?php

/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Handles autoloader for Zesk
 *
 * @author kent
 */
class Autoloader {
	/**
	 * Used in ->path("path/to", [ Autoloader::CLASS_PREFIX => "foo\\", Autoloader::LOWER => false ]);
	 *
	 * @var string
	 */
	const OPTION_CLASS_PREFIX = "class_prefix";

	/**
	 * Used in ->path("path/to", [ Autoloader::CLASS_PREFIX => "foo\\", Autoloader::LOWER => false ]);
	 *
	 * @var string
	 */
	const OPTION_LOWER = "lower";

	/**
	 * Used in ->path(..., $options); Make this path first in the list. (Default is added to the middle)
	 *
	 * @var string
	 */
	const OPTION_FIRST = "first";

	/**
	 * Used in ->path(..., $options); Make this path last in the list. (Default is added to the end)
	 *
	 * @var string
	 */
	const OPTION_LAST = "last";

	/**
	 * Used in ->path(..., $options); List of array of valid extensions, characters only, in order of search priority. e.g. ["php", "php7", "inc"]
	 *
	 * @var string
	 */
	const OPTION_EXTENSIONS = "extensions";

	/**
	 * Default OPTION_CLASS_PREFIX
	 *
	 * @var string
	 */
	const OPTION_CLASS_PREFIX_DEFAULT = "";

	/**
	 *
	 * DEFAULT OPTION_LOWER
	 *
	 * @var boolean
	 */
	const OPTION_LOWER_DEFAULT = true;

	/**
	 *
	 * @var array[]
	 */
	private $first = array();

	/**
	 *
	 * @var array[]
	 */
	private $paths = array();

	/**
	 *
	 * @var array[]
	 */
	private $last = array();

	/**
	 *
	 * @var array[]
	 */
	private $cached = null;

	/**
	 *
	 * @var boolean
	 */
	public $debug_search = false;

	/**
	 *
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * Set to false to throw an Exception_Class_NotFound from autoloader.
	 * Useful when only using Zesk autoloader or is guaranteed to run last.
	 *
	 * @var boolean
	 */
	public $no_exception = true;

	/**
	 * Default OPTION_EXTENSIONS
	 *
	 * @var array
	 */
	public $autoload_extensions = array(
		"php",
		"inc",
	);

	/**
	 * Link back to zesk Kernel
	 *
	 * @var Kernel
	 */
	private $kernel;

	/**
	 * Create default autoloader for most of Zesk
	 * @param Kernel $kernel
	 */
	public function __construct(Kernel $kernel) {
		$this->kernel = $kernel;
		$this->path(ZESK_ROOT . 'classes', array(
			self::OPTION_LAST => true,
			self::OPTION_LOWER => false,
			self::OPTION_EXTENSIONS => array(
				"php",
			),
			self::OPTION_CLASS_PREFIX => __NAMESPACE__ . '\\',
		));
		$this->autoload_register();
	}

	/**
	 * Should be called once and only once.
	 * Registers Autoloader for Zesk.
	 */
	private function autoload_register() {
		spl_autoload_register(array(
			$this,
			"php_autoloader",
		));
	}

	/**
	 * Retrieve the autoload cache structure, optionally creating the autoload cache directory if
	 * needed.
	 *
	 * @return CacheItemInterface
	 */
	private function _autoload_cache() {
		try {
			return $this->kernel->cache->getItem("autoload_cache");
		} catch (InvalidArgumentException $e) {
			return null;
		}
	}

	private function _autoload_cache_save(CacheItemInterface $item) {
		$this->kernel->cache->saveDeferred($item);
	}

	/**
	 * PHP Autoloader call.
	 * Used in case PHP extends the autoloader to add a 2nd parameter - don't want
	 * it to conflict with our self::load 2nd parameter.
	 *
	 * @param string $class
	 * @return boolean
	 * @throws Exception_Class_NotFound|Exception_Semantics
	 */
	public function php_autoloader($class) {
		if ($this->load($class)) {
			$this->kernel->hooks->register_class($class);
			$this->kernel->classes->register($class);
			return true;
		}
		return false;
	}

	/**
	 * Autoloader for Zesk
	 *
	 * When a PHP class is encountered which can't be found, this function tries to find it and
	 * include the file.
	 *
	 * @param string $class
	 * @param boolean $no_exception
	 *        	Do not throw an exception if class is not found
	 * @return string|null
	 * @see $this->no_exception
	 * @see ZESK_NO_CONFLICT
	 * @throws Exception_Semantics|Exception_Class_NotFound
	 */
	public function load($class, $no_exception = false) {
		$lowercase_class = strtolower($class);
		$cache = $this->_autoload_cache();
		$include = null;
		$cache_items = $cache ? $cache->get() : null;

		if (!is_array($cache_items)) {
			$cache_items = array();
		}
		if (array_key_exists($lowercase_class, $cache_items)) {
			$include = $cache_items[$lowercase_class];
			if (!is_file($include)) {
				unset($cache_items[$lowercase_class]);
				$include = null;
			}
		}
		if (!$include) {
			$tried_path = null;
			$include = $this->search($class, null, $tried_path);
			if ($include === null) {
				if ($this->no_exception || $no_exception) {
					return null;
				}

				throw new Exception_Class_NotFound($class, "Class {class} called from {calling_function} invoked from:\n{backtrace}\n{tried_path}", array(
					"class" => $class,
					"calling_function" => calling_function(2, true),
					"tried_path" => Text::indent(implode("\n", $tried_path)),
					"backtrace" => Text::indent(_backtrace(), 1),
				));
			}
			$cache_items[$lowercase_class] = $include;
			$cache->set($cache_items);
			$this->_autoload_cache_save($cache);
		}
		if ($this->debug) {
			ob_start();
		}
		require_once($include);
		if ($this->debug) {
			$content = ob_get_clean();
			if ($content !== "") {
				throw new Exception_Semantics("Include file {include} should not output text", array(
					"include" => $include,
				));
			}
		}
		return $include;
	}

	/**
	 * Search for a file in the given paths, converting filename to a directory path by converting _
	 * to /, and look for
	 * files with the given extensions, in order.
	 *
	 * @param string $file_prefix
	 *        	The file name to search for, without the extension
	 * @param array $extensions
	 *        	A list of extensions to search for in each target path. If supplied, is forced.
	 * @return array[string]
	 */
	public function possibilities($file_prefix, array $extensions = null) {
		$result = array();
		foreach ($this->path() as $path => $options) {
			$class_prefix = rtrim($options[self::OPTION_CLASS_PREFIX], '_');
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
			$file_parts = implode("/", explode("_", $options[self::OPTION_LOWER] ? strtolower($path_file_prefix) : $path_file_prefix));
			if ($extensions) {
				$iterate_extensions = $extensions;
			} elseif (isset($options[self::OPTION_EXTENSIONS])) {
				$iterate_extensions = $options[self::OPTION_EXTENSIONS];
			} else {
				$iterate_extensions = $this->extension();
			}
			$prefix = path($path, $file_parts);
			foreach ($iterate_extensions as $ext) {
				$result[] = "$prefix.$ext";
			}
		}
		return $result;
	}

	/**
	 * Search for a file in the autoload path (::path), looking for files with
	 * "extensions"
	 *
	 * @param string $class
	 * @param array $extensions
	 * @param array $tried_path Return tried paths
	 * @return string
	 */
	public function search($class, array $extensions = null, &$tried_path = null) {
		$possibilities = $this->possibilities($class, $extensions);
		$tried_path = array();
		foreach ($possibilities as $path) {
			$tried_path[] = $path;
			if (file_exists($path)) {
				return $path;
			}
		}
		return null;
	}

	/**
	 * Add/remove an extension
	 *
	 * @param string $add
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

	/**
	 * Retrieve the list of autoload paths, or add one.
	 *
	 * 2017-03 Autoload paths support PSR-4 by default, so lowercase is not ON anymore by default.
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
	 *        	$application->autoloader->path($application->path('classes'),'first');
	 *        	</code>
	 *
	 *        	Is a very common usage pattern.
	 *
	 *        	Options are:
	 *        	- lower - Lowercase the class name (defaults to false) to find the files for this
	 *        	path only
	 *        	- first - Set as first autoload path. If first and last are set, first wins, last
	 *        	is ignored.
	 *        	- last - Set as last autoload path.
	 *        	- extensions - Array or ;-separated string containing extensions to look for
	 *			- class_prefix - Only load classes which match this prefix from this path
	 *
	 * @return array The ordered list of paths to search for class names.
	 */
	public function path($add = null, $options = false) {
		if ($add) {
			if (is_string($options)) {
				$options = array(
					$options => true,
				);
			} elseif (!is_array($options)) {
				$options = array(
					self::OPTION_LOWER => to_bool($options),
				);
			}
			if (isset($options[self::OPTION_EXTENSIONS])) {
				$options[self::OPTION_EXTENSIONS] = to_list($options[self::OPTION_EXTENSIONS]);
			}
			// Defaults (extension
			$options += array(
				self::OPTION_CLASS_PREFIX => self::OPTION_CLASS_PREFIX_DEFAULT,
				self::OPTION_LOWER => self::OPTION_LOWER_DEFAULT,
			);
			if (isset($options[self::OPTION_FIRST]) && $options[self::OPTION_FIRST]) {
				$this->first[$add] = $options;
				$this->cached = null;
			} elseif (isset($options[self::OPTION_LAST]) && $options[self::OPTION_LAST]) {
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
}
