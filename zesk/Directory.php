<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 */

use DirectoryIterator;
use UnexpectedValueException;
use Psr;

/**
 *
 */
class Directory extends Hookable {
	/**
	 *
	 * @var integer
	 */
	private static int $defaultMode = 504; /* 0o0770 */

	/**
	 * Souped up fstat.
	 *
	 * @thanks
	 *
	 * @param mixed $path
	 *            Path or resource to check
	 * @param ?string $section
	 *            Section to retrieve, or null for all sections
	 * @return array
	 * @throws Exception_Directory_NotFound
	 * @see File::stat
	 */
	public static function stat(string $path, string $section = null): array {
		clearstatcache(false, $path);
		$ss = @stat($path);
		if (!$ss) {
			throw new Exception_Directory_NotFound($path);
		}
		$ss['path'] = $path;
		$s = File::expandStats($ss);
		if ($section !== null) {
			return $s[$section] ?? [];
		}
		return $s;
	}

	/**
	 * If a directory does not exist, create it. If an error occurs
	 * @param string $path
	 * @param int|null $mode
	 * @return string
	 * @throws Exception_Directory_Permission
	 * @throws Exception_Directory_Create
	 */
	public static function depend(string $path, int $mode = null): string {
		if ($mode === null) {
			$mode = self::defaultMode();
		}
		if (!self::create($path, $mode)) {
			throw new Exception_Directory_Create($path);
		}

		try {
			$perms = Directory::stat($path, 'perms');
		} catch (Exception_Directory_NotFound) {
			throw new Exception_Directory_Permission($path, 'Can not stat {path}');
		}
		if (strval($perms['octal']) === File::mode_to_octal($mode)) {
			if (!chmod($path, $mode)) {
				throw new Exception_Directory_Permission($path, 'Setting {path} to mode {mode}', [
					'mode' => sprintf('%04o', $mode),
				]);
			}
		}
		return $path;
	}

	/**
	 * Change directory mode (iff directory exists)
	 *
	 * @param string $directory
	 * @param int $mode
	 * @return void
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 */
	public static function chmod(string $directory, int $mode = 504 /* 0o770 */): void {
		if (!is_dir($directory)) {
			throw new Exception_Directory_NotFound($directory, 'Can not set mode to {mode}', [
				'mode' => File::mode_to_octal($mode),
			]);
		}
		if (!chmod($directory, $mode)) {
			throw new Exception_Directory_Permission($directory, 'Can not set mode to {mode}', [
				'mode' => File::mode_to_octal($mode),
			]);
		}
	}

	/**
	 * Require a directory to exist at $path and throw an Exception_Directory_NotFound if it does not.
	 *
	 * @param string $path
	 * @return string
	 * @throws Exception_Directory_NotFound
	 */
	public static function must(string $path): string {
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		return $path;
	}

	/**
	 * The default directory mode for new directories
	 *
	 * @return int
	 */
	public static function defaultMode(): int {
		return self::$defaultMode;
	}

	/**
	 * The default directory mode for new directories
	 *
	 * @param int $mode
	 * @return void
	 */
	public static function setDefaultMode(int $mode): void {
		self::$defaultMode = $mode;
	}

	/**
	 * Create a directory if it does not exist
	 * @param string $path Path to create
	 * @param int $mode Default mode of directory (UNIX permissions)
	 * @return string $path of directory if created, null if not
	 * @throws Exception_Directory_Create
	 */
	public static function create(string $path, int $mode = -1): string {
		if ($mode < 0) {
			$mode = self::defaultMode();
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
	 * @param ?callable $file_copy_function
	 * @return string
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Parameter
	 */
	public static function duplicate(string $source, string $destination, bool $recursive = true, callable $file_copy_function = null): string {
		if (empty($source)) {
			throw new Exception_Parameter('self::duplicate: Source is empty');
		}
		if (empty($destination)) {
			throw new Exception_Parameter('self::duplicate: Destination is empty');
		}
		if (!is_dir($destination)) {
			if (!mkdir($destination, self::defaultMode(), true)) {
				throw new Exception_Directory_NotFound("Can't create $destination");
			}
		}
		$directoryIterator = new DirectoryIterator($source);
		foreach ($directoryIterator as $fileInfo) {
			/* @var $fileInfo DirectoryIterator */
			if ($fileInfo->isDot()) {
				continue;
			}
			$source_path = path($source, $fileInfo->getFilename());
			$target_path = path($destination, strval($fileInfo));
			if (is_dir($source_path)) {
				if ($recursive) {
					self::duplicate($source_path, $target_path, $recursive, $file_copy_function);
				}
			} else {
				if ($file_copy_function) {
					$file_copy_function($source_path, $target_path);
				} else {
					copy($source_path, $target_path);
				}
			}
		}
		unset($directoryIterator);
		return $destination;
	}

	/**
	 *
	 * @param string $path
	 * @return boolean
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 */
	public static function isEmpty(string $path): bool {
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		$d = opendir($path);
		if (!is_resource($d)) {
			throw new Exception_Directory_Permission($path, 'Can\'t list directory {path}', [
				'path' => $path,
			]);
		}
		while (($f = readdir($d)) !== false) {
			if ($f === '.' || $f === '..') {
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
	/**
	 * @param string $path
	 * @return void
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_Permission
	 * @throws Exception_Directory_NotFound
	 */
	public static function delete(string $path): void {
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		self::deleteContents($path);
		if (!rmdir($path)) {
			throw new Exception_Directory_Permission($path, __METHOD__ . ' rmdir returned false');
		}
	}

	/**
	 * @param string $path
	 * @return void
	 * @throws Exception_Directory_Permission
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function deleteContents(string $path): void {
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}

		try {
			$x = new DirectoryIterator($path);
		} catch (UnexpectedValueException) {
			return;
		}
		foreach ($x as $f) {
			if ($f->isDot()) {
				continue;
			}
			$full_path = path($path, $f->getFilename());
			if ($f->isDir()) {
				try {
					self::delete($full_path);
				} catch (Exception_Directory_NotFound) {
					// Not sure why but who cares
				}
			} else {
				if (!unlink($full_path)) {
					throw new Exception_File_Permission($full_path, __METHOD__ . " unlink($full_path) failed");
				}
			}
		}
	}

	/**
	 * @param string $absolute_root
	 * @param string $mixed
	 * @return string
	 * @throws Exception_Deprecated
	 * @throws Exception_Directory_NotFound
	 */
	public static function make_absolute(string $absolute_root, string $mixed): string {
		zesk()->deprecated(__METHOD__);
		return self::makeAbsolute($absolute_root, $mixed);
	}

	/**
	 * @param string $absolute_root A known valid path in the file system
	 * @param string $mixed An elements to append to convert to an absolute path at the given root
	 * @return string
	 * @throws Exception_Directory_NotFound
	 */
	public static function makeAbsolute(string $absolute_root, string $mixed): string {
		if (!is_dir($absolute_root)) {
			throw new Exception_Directory_NotFound($absolute_root);
		}
		if (self::isAbsolute($mixed)) {
			return $mixed;
		}
		return path($absolute_root, $mixed);
	}

	/**
	 * Synonym for File::is_absolute
	 *
	 * @param string $path
	 * @return boolean
	 * @see File::is_absolute
	 */
	public static function isAbsolute(string $path): bool {
		return File::isAbsolute($path);
	}

	/**
	 * Covert old-style options into new
	 *
	 * @param array $options
	 * @param string $prefix
	 * @param bool $default
	 * @return array
	 */
	private static function _legacyParseOptions(array $options, string $prefix, bool $default): array {
		$options = ArrayTools::keysRemovePrefix($options, $prefix . '_', true);
		$include_pattern = $options['include_pattern'] ?? null;
		$exclude_pattern = $options['exclude_pattern'] ?? null;
		$default = $options['default'] ?? $default;
		if ($include_pattern === false) {
			return [
				false,
			];
		}
		if ($exclude_pattern === true) {
			return [
				false,
			];
		}
		if ($exclude_pattern !== null) {
			$result[$exclude_pattern] = false;
		}
		if ($include_pattern !== null) {
			$result[$include_pattern] = true;
		}
		$result[] = toBool($default);
		return $result;
	}

	/**
	 * @throws Exception_Parameter
	 */
	private static function _listRecursiveRules(array $options, string $name, bool $default): array {
		$k = self::RULE_PREFIX . $name;
		if (array_key_exists($k, $options)) {
			if (is_bool($options[$k])) {
				return [
					$options[$k],
				];
			}
			if (!is_array($options[$k])) {
				throw new Exception_Parameter('Recursive rules {key} must be boolean or an array, {type} passed', [
					'key' => $k, 'type' => type($options[$k]),
				]);
			}
			return $options[$k];
		}
		return self::_legacyParseOptions($options, $name, $default);
	}

	public const RULE_PREFIX = 'rules_';

	public const RULE_SUFFIX_FILE = 'file';

	public const RULE_SUFFIX_DIRECTORY = 'directory';

	public const RULE_SUFFIX_DIRECTORY_WALK = 'directory_walk';

	/**
	 * Option rule for listRecursive
	 */
	public const LIST_RULE_DIRECTORY = self::RULE_PREFIX . self::RULE_SUFFIX_DIRECTORY;

	/**
	 * Option rule for listRecursive
	 */
	public const LIST_RULE_FILE = self::RULE_PREFIX . self::RULE_SUFFIX_FILE;

	/**
	 * Option rule for listRecursive. Rules specify whether a directory should be traversed.
	 */
	public const LIST_RULE_DIRECTORY_WALK = self::RULE_PREFIX . self::RULE_SUFFIX_DIRECTORY_WALK;

	public const LIST_ADD_PATH = 'add_path';

	/**
	 * @param string $path
	 * @param array $options
	 * @return array
	 * @throws Exception_Parameter
	 */
	public static function list_recursive(string $path, array $options = []): array {
		return self::listRecursive($path, $options);
	}

	/**
	 * List a directory recursively
	 *
	 * Options work as follows:
	 *        rules_file = list of rules => true/false - Whether to include a file in the results (matched against FULL PATH)
	 *        rules_directory = Whether to include a directory in the result (matched against FULL PATH)
	 *        rules_directory_walk = Whether to walk a directory (matched against FULL PATH)
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
	 * @throws Exception_Parameter
	 */
	public static function listRecursive(string $path, array $options = []): array {
		$options = !is_array($options) ? [] : $options;
		$options = array_change_key_case($options);
		$progress = $options['progress'] ?? null;
		/* @var $progress Psr\Log\LoggerInterface */
		$rules_file = self::_listRecursiveRules($options, self::RULE_SUFFIX_FILE, false);
		$rules_dir = self::_listRecursiveRules($options, self::RULE_SUFFIX_DIRECTORY, false);
		$rules_dir_walk = self::_listRecursiveRules($options, self::RULE_SUFFIX_DIRECTORY_WALK, true);

		$max_results = $options['maximum_results'] ?? -1;
		$addPath = toBool($options[self::LIST_ADD_PATH] ?? false);

		$path = rtrim($path, '/');
		$d = @opendir($path);
		if (!$d) {
			return [];
		}
		$r = [];
		$options['add_path'] = false;
		$prefix = $addPath ? (str_ends_with($path, '/') ? $path : "$path/") : '';
		while (($x = readdir($d)) !== false) {
			if ($x === '.' || $x === '..') {
				continue;
			}
			$full_path = path($path, $x);
			if (is_dir($full_path)) {
				$full_path .= '/';
				if (StringTools::filter($full_path, $rules_dir)) {
					$r[] = ($addPath) ? "$prefix$x/" : "$x/";
				}
				if (!StringTools::filter($full_path, $rules_dir_walk)) {
					continue;
				}
				if ($progress instanceof Psr\Log\LoggerInterface) {
					$progress->notice('Listing {full_path}', [
						'full_path' => $full_path,
					]);
				}
				$result = self::list_recursive($full_path, $options);
				$result = ArrayTools::prefixValues($result, "$prefix$x/");
				$r = array_merge($r, $result);
			} else {
				if (!StringTools::filter($full_path, $rules_file)) {
					continue;
				}
				$r[] = ($addPath) ? "$prefix$x" : "$x";
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
	 * ./ and ../ from a path
	 * @param string $p path to clean up
	 * @return string path with removed dots
	 * @throws Exception_Syntax
	 */
	public static function removeDots(string $p): string {
		$r = [];
		$a = explode('/', $p);
		$skip = 0;

		$n = count($a);
		while ($n-- !== 0) {
			if ($a[$n] === '..') {
				$skip = $skip + 1;
			} elseif ($a[$n] === '.') {
				continue;
			} elseif ($skip > 0) {
				$skip--;
			} else {
				array_unshift($r, $a[$n]);
			}
		}
		if ($skip > 0) {
			throw new Exception_Syntax('Invalid path dots "{path}"', ['path' => $p]);
		}
		return implode('/', $r);
	}

	/**
	 *
	 */
	public static function addSlash(string $p): string {
		if ($p === '') {
			return $p;
		}
		return str_ends_with($p, '/') ? $p : "$p/";
	}

	/**
	 * Trim slash from right side of string
	 *
	 * @param string $path
	 * @return string
	 */
	public static function stripSlash(string $path): string {
		return rtrim($path, '/');
	}

	/**
	 *
	 */
	public static function iterate($source, $directory_function = null, $file_function = null): void {
		$d = dir($source);
		$list = [];
		while (($f = $d->read()) !== false) {
			if ($f[0] == '.') {
				continue;
			}
			$list[] = $f;
		}
		$d->close();

		sort($list);
		foreach ($list as $f) {
			$filePath = path($source, $f);
			if (is_dir($filePath)) {
				if ($directory_function) {
					call_user_func($directory_function, $filePath, true);
				}
				self::iterate($filePath, $directory_function, $file_function);
				if ($directory_function) {
					call_user_func($directory_function, $filePath, false);
				}
			} elseif ($file_function) {
				call_user_func($file_function, $filePath);
			}
		}
	}

	/**
	 * Get a directory listing, always excluding "." and ".." entries. Adds a trailing slash ("/") on directories, and
	 * can be used to filter list as well.
	 *
	 * Try:
	 *
	 *        $dirs = ArrayTools::valuesRemoveSuffix(self::ls($path), "/", true)
	 *
	 * To strip the / and return only directories, for example.
	 *
	 * @param string $path The directory to list
	 * @param null|string $filter A pattern to match against files in the directory. Use null for all matches.
	 * @param boolean $cat_path Whether to concatenate the path to each resulting file name
	 * @return array The directory list
	 * @throws Exception_Directory_NotFound
	 * @todo Move this to DirectoryIterator inherited class
	 */
	public static function ls(string $path, string $filter = null, bool $cat_path = false): array {
		$r = [];
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path, '{method}: {path} is not a directory', [
				'method' => __METHOD__, 'path' => $path,
			]);
		}
		$d = opendir($path);
		if (!is_resource($d)) {
			throw new Exception_Directory_NotFound($path, '{method}: {path} is not readable', [
				'method' => __METHOD__, 'path' => $path,
			]);
		}
		$path = rtrim($path, '/');
		while (($f = readdir($d)) !== false) {
			if ($f === '.' || $f === '..') {
				continue;
			}
			if (is_dir("$path/$f")) {
				$f .= '/';
			}
			if ($filter === null || preg_match($filter, $f)) {
				$r[] = $cat_path ? "$path/$f" : $f;
			}
		}
		return $r;
	}

	/**
	 * @param string $source
	 * @param string $dest
	 * @param bool $create
	 * @return void
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_Permission
	 * @throws Exception_Parameter
	 */
	public static function copy(string $source, string $dest, bool $create = false): void {
		if (!is_dir($source)) {
			throw new Exception_Directory_NotFound($source, 'Copying to {dest}', [
				'dest' => $dest,
			]);
		}
		if ($create) {
			self::depend($dest);
		}
		if (!is_dir($dest)) {
			throw new Exception_Directory_NotFound($dest, 'Copying from {source}', [
				'source' => $source,
			]);
		}
		self::deleteContents($dest);
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
	}

	/**
	 * Compute the size of the files in a path. Does not handle integer overflow?
	 *
	 * @param string $path
	 * @return number
	 */
	public static function size(string $path): float {
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
		} catch (Exception_Directory_NotFound) {
		}
		return $n;
	}

	/**
	 * Delete files in a directory when it exceeds a certain count, will delete first files based on sort order, so:
	 *
	 * <code>Directory::cullContents($dir)</code>
	 *
	 *   Will delete file names which sort at the top of the list
	 *
	 * @param string $directory The directory
	 * @param int $total The number of files to allow in this directory
	 * @param string $order_by "name" or "date" are allowed
	 * @return array List of files deleted
	 * @throws Exception_Parameter|Exception_Directory_NotFound
	 */
	public static function cullContents(string $directory, int $total, string $order_by = 'name', bool $ascending = true): array {
		$files = self::ls($directory, null, true);
		if (count($files) < $total) {
			return [];
		}
		if (empty($order_by)) {
			$order_by = 'name';
		}
		if ($order_by === 'name') {
			$target_files = ArrayTools::valuesFlipCopy($files);
			$sort_flags = SORT_STRING;
		} elseif ($order_by === 'date') {
			foreach ($files as $i => $file) {
				$target_files[filemtime($file) . ".$i"] = $file;
			}
			$sort_flags = SORT_NUMERIC;
		} else {
			throw new Exception_Parameter('Invalid order by {order_by}, must be name or date', compact('order_by'));
		}
		ksort($target_files, $sort_flags | ($ascending ? SORT_ASC : SORT_DESC));
		$n_to_delete = count($target_files) - $total;
		$deleted = [];
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
	 * Given a list of paths and a directory name, find the first occurrence of the named directory.
	 *
	 * @param array $paths List of strings representing file system paths
	 * @param array|string|null $directory Directory to search for, or list of directories suffixes to search for
	 * @return string
	 * @throws Exception_NotFound
	 * @see File::find
	 */
	public static function findFirst(array $paths, array|string $directory = null): string {
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

		throw new Exception_NotFound('No directory exists {paths}, {directory}', [
			'paths' => $paths, 'directory' => $directory,
		]);
	}

	/**
	 * Given a list of paths and a directory name, find the first occurrence of the named directory.
	 *
	 * @param array $paths
	 *            List of strings representing file system paths
	 * @param mixed $directory
	 *            Directory to search for, or list of directories to search for (array)
	 * @return array All possible paths which actually exist
	 * @see File::findFirst
	 */
	public static function findAll(array $paths, array|string $directory = ''): array {
		$result = [];
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
}
