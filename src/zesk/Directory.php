<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 */

use Closure;
use DirectoryIterator;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\SyntaxException;

/**
 *
 */
class Directory extends Hookable {
	/**
	 * Build our rule strings
	 */
	protected const RULE_PREFIX = 'rules_';

	/**
	 * Build our rule strings
	 */
	protected const RULE_SUFFIX_FILE = 'file';

	/**
	 * Build our rule strings
	 */
	protected const RULE_SUFFIX_DIRECTORY = 'directory';

	/**
	 * TODO camelCase
	 * Build our rule strings
	 */
	protected const RULE_SUFFIX_DIRECTORY_WALK = 'directory_walk';

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

	/**
	 * TODO camelCase
	 * Boolean value whether to prefix resulting strings with the path
	 */
	public const LIST_ADD_PATH = 'add_path';

	/**
	 * Pass in LoggerInterface to get progress
	 */
	public const LIST_PROGRESS = 'progress';

	/**
	 * TODO camelCase
	 * Stop after maximum results
	 */
	public const LIST_MAXIMUM_RESULTS = 'maximum_results';

	/**
	 *
	 * @var integer
	 */
	private static int $defaultMode = 504; /* 0o0770 */

	/**
	 * Create a file path and ensure only one slash appears between path entries. Do not use this
	 * with URLs, use glue instead.
	 *
	 * @param array|string $path Variable list of path items, or array of path items to concatenate
	 * @return string with a properly formatted path
	 */
	public static function path(array|string $path /* dir, dir, ... */): string {
		$args = func_get_args();
		$r = StringTools::joinArray('/', $args);
		return preg_replace('|(/\.)+/|', '/', $r);
	}

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
	 * @throws DirectoryNotFound
	 * @see File::stat
	 */
	public static function stat(string $path, string $section = null): array {
		clearstatcache(false, $path);
		$ss = @stat($path);
		if (!$ss) {
			throw new DirectoryNotFound($path);
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
	 * @throws DirectoryPermission
	 * @throws DirectoryCreate
	 */
	public static function depend(string $path, int $mode = null): string {
		if ($mode === null) {
			$mode = self::defaultMode();
		}
		if (!self::create($path, $mode)) {
			throw new DirectoryCreate($path);
		}

		try {
			$perms = Directory::stat($path, 'perms');
		} catch (DirectoryNotFound) {
			throw new DirectoryPermission($path, 'Can not stat {path}');
		}
		if (strval($perms['octal']) === File::modeToOctal($mode)) {
			if (!chmod($path, $mode)) {
				throw new DirectoryPermission($path, 'Setting {path} to mode {mode}', [
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
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 */
	public static function chmod(string $directory, int $mode = 504 /* 0o770 */): void {
		if (!is_dir($directory)) {
			throw new DirectoryNotFound($directory, 'Can not set mode to {mode}', [
				'mode' => File::modeToOctal($mode),
			]);
		}
		if (!chmod($directory, $mode)) {
			throw new DirectoryPermission($directory, 'Can not set mode to {mode}', [
				'mode' => File::modeToOctal($mode),
			]);
		}
	}

	/**
	 * Require a directory to exist at $path and throw an DirectoryNotFound if it does not.
	 *
	 * @param string $path
	 * @return string
	 * @throws DirectoryNotFound
	 */
	public static function must(string $path): string {
		if (!is_dir($path)) {
			throw new DirectoryNotFound($path);
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
	 * @throws DirectoryCreate
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

			throw new DirectoryCreate($path);
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
	 * @throws DirectoryNotFound
	 * @throws ParameterException
	 */
	public static function duplicate(string $source, string $destination, bool $recursive = true, callable $file_copy_function = null): string {
		if (empty($source)) {
			throw new ParameterException('self::duplicate: Source is empty');
		}
		if (empty($destination)) {
			throw new ParameterException('self::duplicate: Destination is empty');
		}
		if (!is_dir($destination)) {
			if (!mkdir($destination, self::defaultMode(), true)) {
				throw new DirectoryNotFound("Can't create $destination");
			}
		}
		$directoryIterator = new DirectoryIterator($source);
		foreach ($directoryIterator as $fileInfo) {
			/* @var $fileInfo DirectoryIterator */
			if ($fileInfo->isDot()) {
				continue;
			}
			$source_path = self::path($source, $fileInfo->getFilename());
			$target_path = self::path($destination, strval($fileInfo));
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
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 */
	public static function isEmpty(string $path): bool {
		if (!is_dir($path)) {
			throw new DirectoryNotFound($path);
		}
		$d = opendir($path);
		if (!is_resource($d)) {
			throw new DirectoryPermission($path, 'Can\'t list directory {path}', [
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
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @throws DirectoryNotFound
	 */
	public static function delete(string $path): void {
		if (!is_dir($path)) {
			throw new DirectoryNotFound($path);
		}
		self::deleteContents($path);
		if (!rmdir($path)) {
			throw new DirectoryPermission($path, __METHOD__ . ' rmdir returned false');
		}
	}

	/**
	 * @param string $path
	 * @return void
	 * @throws DirectoryPermission
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 */
	public static function deleteContents(string $path): void {
		if (!is_dir($path)) {
			throw new DirectoryNotFound($path);
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
			$full_path = self::path($path, $f->getFilename());
			if ($f->isDir()) {
				try {
					self::delete($full_path);
				} catch (DirectoryNotFound) {
					// Not sure why but who cares
				}
			} else {
				if (!unlink($full_path)) {
					throw new FilePermission($full_path, __METHOD__ . " unlink($full_path) failed");
				}
			}
		}
	}

	/**
	 * @param string $absolute_root A known valid path in the file system
	 * @param string $mixed An elements to append to convert to an absolute path at the given root
	 * @return string
	 * @throws DirectoryNotFound
	 */
	public static function makeAbsolute(string $absolute_root, string $mixed): string {
		if (!is_dir($absolute_root)) {
			throw new DirectoryNotFound($absolute_root);
		}
		if (self::isAbsolute($mixed)) {
			return $mixed;
		}
		return self::path($absolute_root, $mixed);
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
		$result[] = Types::toBool($default);
		return $result;
	}

	/**
	 * @throws ParameterException
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
				throw new ParameterException('Recursive rules {key} must be boolean or an array, {type} passed', [
					'key' => $k, 'type' => Types::type($options[$k]),
				]);
			}
			return $options[$k];
		}
		return self::_legacyParseOptions($options, $name, $default);
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
	 * @throws ParameterException
	 */
	public static function listRecursive(string $path, array $options = []): array {
		$options = !is_array($options) ? [] : $options;
		$options = array_change_key_case($options);
		$progress = $options[self::LIST_PROGRESS] ?? null;
		/* @var $progress LoggerInterface */
		$rules_file = self::_listRecursiveRules($options, self::RULE_SUFFIX_FILE, false);
		$rules_dir = self::_listRecursiveRules($options, self::RULE_SUFFIX_DIRECTORY, false);
		$rules_dir_walk = self::_listRecursiveRules($options, self::RULE_SUFFIX_DIRECTORY_WALK, true);

		$max_results = $options[self::LIST_MAXIMUM_RESULTS] ?? -1;
		$addPath = Types::toBool($options[self::LIST_ADD_PATH] ?? false);

		$path = rtrim($path, '/');
		$d = @opendir($path);
		if (!$d) {
			return [];
		}
		$r = [];
		$options[self::LIST_ADD_PATH] = false;
		$prefix = $addPath ? (str_ends_with($path, '/') ? $path : "$path/") : '';
		while (($x = readdir($d)) !== false) {
			if ($x === '.' || $x === '..') {
				continue;
			}
			$full_path = self::path($path, $x);
			if (is_dir($full_path)) {
				$full_path .= '/';
				if (StringTools::filter($full_path, $rules_dir)) {
					$r[] = ($addPath) ? "$prefix$x/" : "$x/";
				}
				if (!StringTools::filter($full_path, $rules_dir_walk)) {
					continue;
				}
				if ($progress instanceof LoggerInterface) {
					$progress->notice('Listing {full_path}', [
						'full_path' => $full_path,
					]);
				}
				$result = self::listRecursive($full_path, $options);
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
	 * @throws SyntaxException
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
			throw new SyntaxException('Invalid path dots "{path}"', ['path' => $p]);
		}
		return implode('/', $r);
	}

	/**
	 * Simple function to ensure there is a slash on the end of a file, unless the file is empty
	 *
	 * @param string $path
	 * @return string
	 */
	public static function addSlash(string $path): string {
		return ($path === '') ? $path : (str_ends_with($path, '/') ? $path : "$path/");
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
	/**
	 * @param string $source
	 * @param callable|Closure|null $directory_function
	 * @param callable|Closure|null $file_function
	 * @return void
	 * @throws DirectoryNotFound
	 */
	public static function iterate(string $source, null|callable|Closure $directory_function = null, null|callable|Closure $file_function = null): void {
		if (!is_dir($source)) {
			throw new DirectoryNotFound($source);
		}
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
			$filePath = self::path($source, $f);
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
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @todo Move this to DirectoryIterator inherited class
	 */
	public static function ls(string $path, string $filter = null, bool $cat_path = false): array {
		$r = [];
		if (!is_dir($path)) {
			throw new DirectoryNotFound($path, '{method}: {path} is not a directory', [
				'method' => __METHOD__, 'path' => $path,
			]);
		}
		$d = opendir($path);
		if (!is_resource($d)) {
			throw new DirectoryPermission($path, '{method}: {path} is not readable', [
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
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @throws ParameterException
	 */
	public static function copy(string $source, string $dest, bool $create = false): void {
		if (!is_dir($source)) {
			throw new DirectoryNotFound($source, 'Copying to {dest}', [
				'dest' => $dest,
			]);
		}
		if ($create) {
			self::depend($dest);
		}
		self::deleteContents($dest);
		foreach (self::listRecursive($source) as $f) {
			$f_source = self::path($source, $f);
			$f_dest = self::path($dest, $f);
			if (is_dir($f_source)) {
				self::depend($f_dest);
			} else {
				self::depend(dirname($f_dest));
				if (!copy($f_source, $f_dest)) {
					throw new FilePermission($f_dest, "copying from $f_source");
				}
			}
		}
	}

	/**
	 * Compute the size of the files in a path. Does not handle integer overflow?
	 *
	 * @param string $path
	 * @return number
	 * @throws DirectoryPermission
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
		} catch (DirectoryNotFound) {
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
	 * @param bool $ascending
	 * @return array List of files deleted
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws ParameterException
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
			throw new ParameterException('Invalid order by {order_by}, must be name or date', compact('order_by'));
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
	 * @throws NotFoundException
	 * @see File::find
	 */
	public static function findFirst(array $paths, array|string $directory = null): string {
		if (is_array($directory)) {
			foreach ($paths as $path) {
				foreach ($directory as $d) {
					$the_path = self::path($path, $d);
					if (is_dir($the_path)) {
						return $the_path;
					}
				}
			}
		} else {
			foreach ($paths as $path) {
				$the_path = self::path($path, $directory);
				if (is_dir($the_path)) {
					return $the_path;
				}
			}
		}

		throw new NotFoundException('No directory exists {paths}, {directory}', [
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
					$the_path = self::path($path, $d);
					if (is_dir($the_path)) {
						$result[] = $the_path;
					}
				}
			}
		} else {
			foreach ($paths as $path) {
				$the_path = self::path($path, $directory);
				if (is_dir($the_path)) {
					$result[] = $the_path;
				}
			}
		}
		return $result;
	}
}
