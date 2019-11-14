<?php
/**
 *
 */
namespace zesk;

/**
 *
 */
use \DirectoryIterator;
use \UnexpectedValueException;
use Psr;

/**
 *
 */
class Directory extends Hookable {
	/**
	 *
	 * @var integer
	 */
	public static $default_mode = 0770;

	/**
	 * Implement hooks
	 */
	public static function hooks(Application $application) {
		$application->hooks->add('configured', __CLASS__ . '::configured');
	}

	/**
	 * configured hook
	 */
	public static function configured(Application $application) {
		self::$default_mode = $application->configuration->path(__CLASS__)->get("default_mode", self::$default_mode);
	}

	/**
	 * If a directory does not exist, create it. If an error occurs
	 * @param unknown $path
	 * @param string $mode
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @return unknown
	 */
	public static function depend($path, $mode = null) {
		if ($mode === null) {
			$mode = self::default_mode();
		}
		if (!self::create($path, $mode)) {
			throw new Exception_Directory_Create($path);
		}
		$perms = File::stat($path, 'perms');
		if (!self::octal_equal($perms['octal'], File::mode_to_octal($mode))) {
			if (!chmod($path, $mode)) {
				throw new Exception_Directory_Permission($path, __("Setting {filename} to mode {0}", sprintf("%04o", $mode)));
			}
		}
		return $path;
	}

	/**
	 * Require a directory to exist at $path and throw an Exception_Directory_NotFound if it does not.
	 *
	 * @param string $path
	 * @throws Exception_Directory_NotFound
	 * @return string
	 */
	public static function must($path) {
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		return $path;
	}

	/**
	 * If debugging is enabled, log a debug message
	 *
	 * @param unknown $message
	 * @param array $args
	 */
	public static function debug($message, array $args = array()) {
		if (self::$debug) {
			self::$application->logger->debug($message, $args);
		}
	}

	/**
	 * The default directory mode for new directories
	 *
	 * @return integer
	 */
	public static function default_mode() {
		return self::$default_mode;
	}

	/**
	 * Create a directory if it does not exist
	 * @param string $path Path to create
	 * @param integer $mode Default mode of directory (UNIX permissions)
	 * @return string $path of directory if created, null if not
	 */
	public static function create($path, $mode = null) {
		if ($mode === null) {
			$mode = self::default_mode();
		}
		if (is_dir($path)) {
			return $path;
		}
		if (!@mkdir($path, $mode, true)) {
			clearstatcache();

			throw new Exception_Directory_Create($path);
		}
		return $path;
	}

	/**
	 *
	 * @param string $source
	 * @param string $destination
	 * @param boolean $recursive
	 * @param Closure $file_copy_function
	 * @throws Exception_Parameter
	 * @throws Exception_Directory_NotFound
	 */
	public static function duplicate($source, $destination, $recursive = true, $file_copy_function = null) {
		if (empty($source)) {
			throw new Exception_Parameter("self::duplicate: Source is empty");
		}
		if (empty($destination)) {
			throw new Exception_Parameter("self::duplicate: Destination is empty");
		}
		if (!is_dir($destination)) {
			if (!mkdir($destination, self::default_mode(), true)) {
				throw new Exception_Directory_NotFound("Can't create $destination");
			}
		}
		$d = new \DirectoryIterator($source);
		foreach ($d as $f) {
			/* @var $f FileInfo */
			if ($f->isDot()) {
				continue;
			}
			$fpath = path($source, $f->getFilename());
			if (is_dir($fpath)) {
				if ($recursive) {
					self::duplicate($fpath, path($destination, $f), $recursive, $file_copy_function);
				}
			} else {
				if ($file_copy_function) {
					$file_copy_function($fpath, path($destination, $f));
				} else {
					copy($fpath, path($destination, $f));
				}
			}
		}
		unset($d);
		return $destination;
	}

	/**
	 *
	 * @param string $path
	 * @throws Exception_Directory_NotFound
	 * @return boolean
	 */
	public static function is_empty($path) {
		if (!is_dir($path)) {
			return true;
		}
		$d = opendir($path);
		if (!is_resource($d)) {
			throw new Exception_Directory_NotFound("Can't opendir on $path");
		}
		while (($f = readdir($d)) !== false) {
			if ($f === "." || $f === "..") {
				continue;
			}
			closedir($d);
			return false;
		}
		closedir($d);
		return true;
	}

	/**
	 *
	 */
	public static function delete($path) {
		if (!is_dir($path)) {
			return true;
		}
		self::delete_contents($path);
		if (!rmdir($path)) {
			throw new Exception_Directory_Permission($path, __METHOD__ . " rmdir returned false");
		}
		return true;
	}

	/**
	 *
	 */
	public static function delete_contents($path) {
		$x = array();

		try {
			$x = new DirectoryIterator($path);
		} catch (UnexpectedValueException $e) {
			return true;
		}
		foreach ($x as $f) {
			if ($f->isDot()) {
				continue;
			}
			$full_path = path($path, $f->getFilename());
			if ($f->isDir()) {
				if (!self::delete($full_path)) {
					throw new Exception_File_Permission($full_path, __CLASS__ . "::delete($full_path) failed");
				}
			} else {
				if (!unlink($full_path)) {
					throw new Exception_File_Permission($full_path, __METHOD__ . " unlink($full_path) failed");
				}
			}
		}
		return true;
	}

	/**
	 *
	 */
	public static function make_absolute($absolute_root, $mixed) {
		if (!is_dir($absolute_root)) {
			throw new Exception_Directory_NotFound($absolute_root);
		}
		if (is_array($mixed)) {
			foreach ($mixed as $k => $path) {
				$mixed[$k] = self::make_absolute($absolute_root, $path);
			}
			return $mixed;
		} else {
			if (self::is_absolute($mixed)) {
				return $mixed;
			}
			return path($absolute_root, $mixed);
		}
	}

	/**
	 * Synonym for File::is_absolute
	 *
	 * @param string $f
	 * @return boolean
	 * @see File::is_absolute
	 */
	public static function is_absolute($f) {
		return File::is_absolute($f);
	}

	/**
	 * Covert old-style options into new
	 *
	 * @param array $options
	 * @param unknown $prefix
	 */
	private static function _legacy_parse_options(array $options, $prefix) {
		$options = ArrayTools::kunprefix($options, $prefix . "_", true);
		$include_pattern = $exclude_pattern = null;
		$default = true;
		extract($options, EXTR_IF_EXISTS);
		if ($include_pattern === false) {
			return array(
				false
			);
		}
		if ($exclude_pattern === true) {
			return array(
				false
			);
		}
		if ($exclude_pattern !== null) {
			$result[$exclude_pattern] = false;
		}
		if ($include_pattern !== null) {
			$result[$include_pattern] = true;
		}
		$result[] = to_bool($default);
		return $result;
	}

	/**
	 *
	 */
	private static function _list_recursive_rules(array $options, $name) {
		$k = "rules_" . $name;
		if (array_key_exists($k, $options)) {
			if (is_bool($options[$k])) {
				return array(
					$options[$k]
				);
			}
			if (!is_array($options[$k])) {
				throw new Exception_Parameter("Recursive rules {key} must be boolean or an array, {type} passed", array(
					"key" => $k,
					"type" => type($options[$k])
				));
			}
			return $options[$k];
		}
		return self::_legacy_parse_options($options, $name);
	}

	/**
	 * List a directory recursively
	 *
	 * Options work as follows:
	 *		rules_file = list of rules => true/false - Whether to include a file in the results (matched against FULL PATH)
	 *		rules_directory = Whether to include a directory in the result (matched against FULL PATH)
	 *		rules_directory_walk = Whether to walk a directory (matched against FULL PATH)
	 *
	 * Options take the form:
	 *  (file|directory|directory_walk)_(include_pattern|exclude_pattern|default)
	 *
	 * Exclude patterns take precedence
	 * It uses StringTools::filter to check a path against the patterns. All patterns/defaults are true.
	 *
	 * @param string $path
	 * @param array $options
	 * @return array
	 */
	public static function list_recursive($path, array $options = array()) {
		$options = !is_array($options) ? array() : $options;
		$options = array_change_key_case($options);
		$progress = to_bool(avalue($options, 'progress'));
		$rules_file = self::_list_recursive_rules($options, "file");
		$rules_dir = self::_list_recursive_rules($options, "directory");
		$rules_dir_walk = self::_list_recursive_rules($options, "directory_walk");

		$max_results = avalue($options, "maximum_results", -1);
		$addpath = to_bool(avalue($options, "add_path", false));

		$path = rtrim($path, "/");
		$d = @opendir($path);
		if (!$d) {
			return array();
		}
		$r = array();
		$options['add_path'] = false;
		$prefix = $addpath ? (substr($path, -1) === '/' ? $path : "$path/") : "";
		while (($x = readdir($d)) !== false) {
			if ($x === "." || $x === "..") {
				continue;
			}
			$full_path = path($path, $x);
			if (is_dir($full_path)) {
				$full_path .= "/";
				if (StringTools::filter($full_path, $rules_dir)) {
					$r[] = ($addpath) ? "$prefix$x/" : "$x/";
				}
				if (!StringTools::filter($full_path, $rules_dir_walk)) {
					continue;
				}
				if ($progress && $progress instanceof Psr\Log\LoggerInterface) {
					$progress->notice("Listing {full_path}", array(
						"full_path" => $full_path
					));
				}
				$result = self::list_recursive($full_path, $options);
				if (is_array($result)) {
					$result = ArrayTools::prefix($result, "$prefix$x/");
					$r = array_merge($r, $result);
				}
			} else {
				if (!StringTools::filter($full_path, $rules_file)) {
					continue;
				}
				$r[] = ($addpath) ? "$prefix$x" : "$x";
			}
			if ($max_results > 0 && count($r) >= $max_results) {
				break;
			}
		}
		closedir($d);
		return $r;
	}

	/**
	 * Removes extraneous .
	 * ./ and ./ from a path
	 * @param string $p path to clean up
	 * @return string path with removed dots
	 */
	public static function undot($p) {
		$r = array();
		$a = explode("/", $p);
		$skip = 0;

		$n = count($a);
		while ($n-- !== 0) {
			if ($a[$n] == "..") {
				$skip = $skip + 1;
			} elseif ($a[$n] == ".") {
				// Do nothing
			} elseif ($skip > 0) {
				$skip--;
			} else {
				array_unshift($r, $a[$n]);
			}
		}
		if ($skip > 0) {
			return null;
		}
		return implode("/", $r);
	}

	/**
	 *
	 */
	public static function add_slash($p) {
		if (!$p) {
			return $p;
		}
		return substr($p, -1) === "/" ? $p : "$p/";
	}

	/**
	 *
	 */
	public static function strip_slash($p) {
		return rtrim($p, "/");
	}

	/**
	 *
	 */
	public static function iterate($source, $directory_function = null, $file_function = null) {
		$d = dir($source);
		$list = array();
		while (($f = $d->read()) !== false) {
			if ($f[0] == '.') {
				continue;
			}
			$list[] = $f;
		}
		$d->close();

		sort($list);
		foreach ($list as $f) {
			$fpath = path($source, $f);
			if (is_dir($fpath)) {
				if ($directory_function) {
					call_user_func($directory_function, $fpath, true);
				}
				self::iterate($fpath, $directory_function, $file_function);
				if ($directory_function) {
					call_user_func($directory_function, $fpath, false);
				}
			} elseif ($file_function) {
				call_user_func($file_function, $fpath);
			}
		}
	}

	/**
	 * Get a directory listing, always excluding "." and ".." entries. Adds a trailing slash ("/") on directories, and
	 * can be used to filter list as well.
	 *
	 * Try:
	 *
	 * 		$dirs = ArrayTools::unsuffix(self::ls($path), "/", true)
	 *
	 * To strip the / and return only directories, for example.
	 *
	 * @param string $path The directory to list
	 * @param string $filter A pattern to match against files in the directory. Use null for all matches.
	 * @param boolean $cat_path Whether to concatenate the path to each resulting file name
	 * @todo Move this to DirectoryIterater inherited class
	 * @return array The directory list
	 */
	public static function ls($path, $filter = null, $cat_path = false) {
		if (!is_string($filter)) {
			$filter = null;
		}
		$r = array();
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path, "{method}: {path} is not a directory", array(
				"method" => __METHOD__,
				"path" => $path
			));
		}
		$d = opendir($path);
		if (!is_resource($d)) {
			throw new Exception_Directory_NotFound($path, "{method}: {path} is not readable", array(
				"method" => __METHOD__,
				"path" => $path
			));
		}
		$path = rtrim($path, '/');
		while (($f = readdir($d)) !== false) {
			if ($f === "." || $f === "..") {
				continue;
			}
			if (is_dir("$path/$f")) {
				$f .= "/";
			}
			if ($filter === null || preg_match($filter, $f)) {
				$r[] = $cat_path ? "$path/$f" : $f;
			}
		}
		return $r;
	}

	/**
	 *
	 */
	public static function copy($source, $dest, $create = false) {
		if (!is_dir($source)) {
			throw new Exception_Directory_NotFound($source, "Copying to {dest}", array(
				"dest" => $dest
			));
		}
		if ($create) {
			self::depend($dest);
		}
		if (!is_dir($dest)) {
			throw new Exception_Directory_NotFound($dest, "Copying from {source}", array(
				"source" => $source
			));
		}
		self::delete_contents($dest);
		foreach (self::list_recursive($source) as $f) {
			$f_source = path($source, $f);
			$f_dest = path($dest, $f);
			if (is_dir($f_source)) {
				self::depend($f_dest);
			} else {
				self::depend(dirname($f_dest));
				if (!copy($f_source, $f_dest)) {
					throw new Exception_File_Permission($f_dest, "copying from $f_source");
				}
			}
		}
		return true;
	}

	/**
	 * Compute the size of the files in a path
	 *
	 * @param string $path
	 * @return number
	 */
	public static function size($path) {
		$n = 0;
		if (!is_dir($path)) {
			return is_file($path) ? filesize($path) : 0;
		}
		try {
			foreach (self::ls($path, null, true) as $k) {
				if (is_file($k)) {
					$n += filesize($k);
				} else {
					$n += self::size($k);
				}
			}
		} catch (Exception_Directory_NotFound $e) {
		}
		return $n;
	}

	/**
	 * Delete files in a directory when it exceeds a certain count, will delete first files based on sort order, so:
	 *
	 * <code>self::cull_contenst($dir)</code>
	 *
	 *   Will delete file names which sort at the top of the list
	 *
	 * @param string $directory
	 * @param integer $total
	 * @param string $order_by
	 * @return integer List of files deleted
	 */
	public static function cull_contents($directory, $total, $order_by = "name", $ascending = true) {
		$files = self::ls($directory, null, true);
		if (count($files) < $total) {
			return array();
		}
		if (empty($order_by)) {
			$order_by = "name";
		}
		if ($order_by === "name") {
			$target_files = ArrayTools::flip_copy($files);
			$sort_flags = SORT_STRING;
		} elseif ($order_by === "date") {
			foreach ($files as $i => $file) {
				$target_files[filemtime($file) . ".$i"] = $file;
			}
			$sort_flags = SORT_NUMERIC;
		} else {
			throw new Exception_Parameter("Invalid order by {order_by}, must be name or date", compact("order_by"));
		}
		ksort($target_files, $sort_flags | ($ascending ? SORT_ASC : SORT_DESC));
		$n_to_delete = count($target_files) - $total;
		$deleted = array();
		foreach ($target_files as $target_file) {
			if ($n_to_delete <= 0) {
				break;
			}
			unlink($target_file);
			$deleted[] = $target_file;
			$n_to_delete--;
		}
		return $deleted;
	}

	/**
	 * Given a list of paths and a directory name, find the first occurrance of the named directory.
	 *
	 * @param array $paths
	 *        	List of strings representing file system paths
	 * @param mixed $directory
	 *        	Directory to search for, or list of directories to search for (array)
	 * @return string Full path of found directory, or null if not found
	 * @see File::find
	 */
	public static function find_first(array $paths, $directory = null) {
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
	 * Given a list of paths and a directory name, find the first occurrance of the named directory.
	 *
	 * @param array $paths
	 *        	List of strings representing file system paths
	 * @param mixed $directory
	 *        	Directory to search for, or list of directories to search for (array)
	 * @return string Full path of found directory, or null if not found
	 * @see File::find_first
	 */
	public static function find_all(array $paths, $directory = null) {
		$result = array();
		if (is_array($directory)) {
			foreach ($paths as $path) {
				foreach ($directory as $d) {
					$the_path = path($path, $d);
					if (is_dir($the_path)) {
						$result[] = $the_path;
					}
				}
			}
		} else {
			foreach ($paths as $path) {
				$the_path = path($path, $directory);
				if (is_dir($the_path)) {
					$result[] = $the_path;
				}
			}
		}
		return $result;
	}

	/**
	 * Utility function to compare two octal values
	 *
	 * @param mixed $a
	 * @param mixed $b
	 * @return boolean
	 */
	private static function octal_equal($a, $b) {
		return intval($a) === intval($b);
	}
}
