<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/dir.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */

/**
 * Directory traversal and tools
 *
 * @author kent
 */
class dir {

	/**
	 * Set this to true to log debug messages
	 *
	 * @var boolean
	 */
	public static $debug = false;

	/**
	 * Implement hooks
	 */
	public static function hooks() {
		zesk::add_hook('Application::configured', 'dir::configured');
	}

	/**
	 * configured hook
	 */
	public static function configured() {
		self::$debug = zesk::get('dir::debug');
	}

	private static function octal_equal($a, $b) {
		return intval($a) === intval($b);
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
		$perms = file::stat($path, 'perms');
		if (!self::octal_equal($perms['octal'], file::mode_to_octal($mode))) {
			if (!chmod($path, $mode)) {
				throw new Exception_Directory_Permission($path, __("Setting {filename} to mode {0}", sprintf("%04o", $mode)));
			}
			if (self::$debug) {
				log::debug(__("Changed mode of {0} from {1} to {2}", $path, $perms['octal'], sprintf("%04o", $mode)));
			}
		}
		return $path;
	}

	/**
	 * The default directory mode for new directories
	 *
	 * @return integer
	 */
	public static function default_mode() {
		$mode = zesk::get("directory_mode", 0770);
		return $mode;
	}

	public static function temporary($name = null, $mode = null) {
		if ($name === null) {
			$name = md5(microtime());
		}
		return self::depend(zesk::temporary_path($name), $mode);
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
		if (self::$debug) {
			log::debug("Created directory $path");
		}
		return $path;
	}

	public static function duplicate($source, $destination, $recursive = true, $file_copy_function = null) {
		if (empty($source)) {
			throw new Exception_Parameter("self::duplicate: Source is empty");
		}
		if (empty($destination)) {
			throw new Exception_Parameter("self::duplicate: Destination is empty");
		}
		if (!is_dir($destination)) {
			if (!mkdir($destination, zesk::get('DIRECTORY_MODE', 0770), true)) {
				throw new Exception_Directory_NotFound("Can't create $destination");
			}
		}
		$d = new DirectoryIterator($source);
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
				if (is_string($file_copy_function)) {
					$file_copy_function($fpath, path($destination, $f));
				} else {
					copy($fpath, path($destination, $f));
				}
			}
		}
		unset($d);
	}

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

	public static function delete($path) {
		self::delete_contents($path);
		return rmdir($path);
	}

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
					throw new Exception_File_Permission($full_path, "dir::delete($full_path) failed");
				}
			} else {
				if (!unlink($full_path)) {
					throw new Exception_File_Permission($full_path, "unlink $full_path");
				}
			}
		}
		return true;
	}

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
	 * Synonym for file::is_absolute
	 *
	 * @param string $f
	 * @return boolean
	 * @see file::is_absolute
	 */
	public static function is_absolute($f) {
		return file::is_absolute($f);
	}

	/**
	 * Covert old-style options into new
	 *
	 * @param array $options
	 * @param unknown $prefix
	 */
	private static function _legacy_parse_options(array $options, $prefix) {
		$options = arr::kunprefix($options, $prefix . "_", true);
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
	 *
	 * Options take the form:
	 *  (file|directory|directory_walk)_(include_pattern|exclude_pattern|default)
	 *
	 * Exclude patterns take precedence
	 * It uses str::filter to check a path against the patterns. All patterns/defaults are true.
	 *
	 * @param string $path
	 * @param array $options
	 * @return array
	 */
	public static function list_recursive($path, array $options = array()) {
		$options = !is_array($options) ? array() : $options;
		$options = array_change_key_case($options);

		$rules_file = self::_list_recursive_rules($options, "file");
		$rules_dir = self::_list_recursive_rules($options, "directory");
		$rules_dir_walk = self::_list_recursive_rules($options, "directory_walk");

		$max_results = avalue($options, "maximum_results", -1);
		$addpath = to_bool(avalue($options, "add_path", false));

		$path = rtrim($path, "/");
		$d = @opendir($path);
		if (!$d) {
			return false;
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
				if (str::filter($full_path, $rules_dir)) {
					$r[] = ($addpath) ? "$prefix$x/" : "$x/";
				}
				if (!str::filter($full_path, $rules_dir_walk)) {
					continue;
				}
				$result = self::list_recursive($full_path, $options);
				if (is_array($result)) {
					$result = arr::prefix($result, "$prefix$x/");
					$r = array_merge($r, $result);
				}
			} else {
				if (!str::filter($full_path, $rules_file)) {
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
			} else if ($a[$n] == ".") {
				// Do nothing
			} else if ($skip > 0) {
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

	public static function add_slash($p) {
		if (!$p) {
			return $p;
		}
		return substr($p, -1) === "/" ? $p : "$p/";
	}

	public static function strip_slash($p) {
		return rtrim($p, "/");
	}

	public static function iterate($source, $directory_function = null, $file_function = null) {
		$d = dir($source);
		$list = array();
		while (($f = $d->read()) !== false) {
			if ($f[0] == '.')
				continue;
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
				dir::iterate($fpath, $directory_function, $file_function);
				if ($directory_function) {
					call_user_func($directory_function, $fpath, false);
				}
			} else if ($file_function) {
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
	 * 		$dirs = arr::unsuffix(dir::ls($path), "/", true)
	 *
	 * To strip the / and return only directories, for example.
	 *
	 * @param string $path The directory to list
	 * @param string $filter A pattern to match against files in the directory. Use null for all matches.
	 * @param boolean $cat_path Whether to concatenate the path to each resulting file name
	 * @return array The directory list
	 */
	public static function ls($path, $filter = null, $cat_path = false) {
		if (!is_string($filter)) {
			$filter = null;
		}
		$r = array();
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound("dir::ls: $path is not a directory");
		}
		$d = opendir($path);
		if (!is_resource($d)) {
			throw new Exception_Directory_NotFound("dir::ls: $path is not readable ");
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

	public static function copy($source, $dest, $create = false) {
		if (!is_dir($source)) {
			throw new Exception_Directory_NotFound($source, "Copying to {dest}", array(
				"dest" => $dest
			));
		}
		if ($create) {
			dir::depend($dest);
		}
		if (!is_dir($dest)) {
			throw new Exception_Directory_NotFound($dest, "Copying from {source}", array(
				"source" => $source
			));
		}
		dir::delete_contents($dest);
		foreach (dir::list_recursive($source) as $f) {
			$f_source = path($source, $f);
			$f_dest = path($dest, $f);
			if (is_dir($f_source)) {
				dir::depend($f_dest);
			} else {
				dir::depend(dirname($f_dest));
				if (self::$debug) {
					log::debug("Copy {f_source} to {f_dest}", compact("f_source", "f_dest"));
				}
				if (!copy($f_source, $f_dest)) {
					throw new Exception_File_Permission($f_dest, "copying from $f_source");
				}
			}
		}
		return true;
	}

	/**
	 * List a directory recursively
	 * @param string $path
	 * @param boolean $recursive
	 * @param mixed $includePattern
	 * @param mixed $excludePattern
	 * @param boolean $addpath
	 * @param integer $max_results
	 * @deprecated Patterns need to be directory and file
	 * @return array
	 */
	public static function list_legacy($path, $recursive = false, $includePattern = true, $excludePattern = false, $addpath = true, $max_results = -1) {
		$path = rtrim($path, "/");
		$d = @opendir($path);
		if (!$d) {
			return false;
		}
		$prefix = ($path == ".") ? "" : "$path/";
		$r = array();
		while (($x = readdir($d)) !== false) {
			if ($x === "." || $x === "..") {
				continue;
			}
			$next_path = ($addpath) ? "$prefix$x" : "$x";
			if (is_string($excludePattern) && preg_match($excludePattern, $next_path)) {
				continue;
			}
			if (is_dir("$path/$x")) {
				if (is_string($includePattern)) {
					if (preg_match($includePattern, "$path/$x")) {
						$r[] = ($addpath) ? "$prefix$x/" : "$x/";
						if ($recursive) {
							$result = self::list_legacy("$prefix$x", $recursive, $includePattern, $excludePattern, $addpath);
							if (!$addpath) {
								$result = arr::prefix($result, "$x/");
							}
							$r = array_merge($r, $result);
						}
					}
				} else {
					$r[] = ($addpath) ? "$prefix$x/" : "$x/";
				}
			} else {
				if (is_string($includePattern) && !preg_match($includePattern, $next_path)) {
					continue;
				}
				$r[] = $next_path;
			}
			if ($max_results > 0 && count($r) >= $max_results)
				break;
		}
		closedir($d);
		return $r;
	}

	/**
	 * If a directory does not exist, create it. If an error occurs
	 * @param unknown $path
	 * @param string $mode
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @see dir::depend
	 * @deprecated 2014-02-09
	 * @return unknown
	 */
	public static function must($path, $mode = null) {
		return dir::depend($path, $mode);
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
		foreach (dir::ls($path, null, true) as $k) {
			if (is_file($k)) {
				$n += filesize($k);
			} else {
				$n += dir::size($k);
			}
		}
		return $n;
	}

	/**
	 * Delete files in a directory when it exceeds a certain count, will delete first files based on sort order, so:
	 *
	 * <code>dir::cull_contenst($dir)</code>
	 *
	 *   Will delete file names which sort at the top of the list
	 *
	 * @param string $directory
	 * @param integer $total
	 * @param string $order_by
	 * @return integer List of files deleted
	 */
	public static function cull_contents($directory, $total, $order_by = "name", $ascending = true) {
		$files = dir::ls($directory, null, true);
		if (count($files) < $total) {
			return array();
		}
		if (empty($order_by)) {
			$order_by = "name";
		}
		if ($order_by === "name") {
			$target_files = arr::flip_copy($files);
			$sort_flags = SORT_STRING;
		} else if ($order_by === "date") {
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
}
