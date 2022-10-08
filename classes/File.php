<?php
declare(strict_types=1);
/**
 *
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * File abstraction, lots of file tools
 *
 * @author kent
 */
class File {
	/**
	 *
	 * @var integer
	 */
	public const MASK_FILE = 0o100000;

	/**
	 *
	 * @var integer
	 */
	public const MASK_SOCKET = 0o140000;

	/**
	 *
	 * @var integer
	 */
	public const MASK_LINK = 0o120000;

	/**
	 *
	 * @var integer
	 */
	public const MASK_BLOCK = 0o060000;

	/**
	 *
	 * @var integer
	 */
	public const MASK_DIR = 0o040000;

	/**
	 *
	 * @var integer
	 */
	public const MASK_CHAR = 0o020000;

	/**
	 *
	 * @var integer
	 */
	public const MASK_FIFO = 0o010000;

	/**
	 *
	 * @var integer
	 */
	public const MASK_FTYPE = 0o170000;

	/**
	 *
	 * @var string
	 */
	public const TYPE_SOCKET = 'socket';

	/**
	 *
	 * @var string
	 */
	public const TYPE_LINK = 'link';

	/**
	 *
	 * @var string
	 */
	public const TYPE_FILE = 'file';

	/**
	 *
	 * @var string
	 */
	public const TYPE_BLOCK = 'block';

	/**
	 *
	 * @var string
	 */
	public const TYPE_DIR = 'dir';

	/**
	 *
	 * @var string
	 */
	public const TYPE_CHAR = 'char';

	/**
	 *
	 * @var string
	 */
	public const TYPE_FIFO = 'fifo';

	/**
	 *
	 * @var string
	 */
	public const TYPE_UNKNOWN = 'unknown';

	/**
	 *
	 * @var string
	 */
	public const CHAR_SOCKET = 's';

	/**
	 *
	 * @var string
	 */
	public const CHAR_LINK = 'l';

	/**
	 *
	 * @var string
	 */
	public const CHAR_FILE = '-';

	/**
	 *
	 * @var string
	 */
	public const CHAR_BLOCK = 'b';

	/**
	 *
	 * @var string
	 */
	public const CHAR_DIR = 'd';

	/**
	 *
	 * @var string
	 */
	public const CHAR_CHAR = 'c';

	/**
	 *
	 * @var string
	 */
	public const CHAR_FIFO = 'p';

	/**
	 *
	 * @var string
	 */
	public const CHAR_UNKNOWN = 'u';

	public static array $char_to_string = [
		self::CHAR_FIFO => self::TYPE_FIFO,
		self::CHAR_CHAR => self::TYPE_CHAR,
		self::CHAR_BLOCK => self::TYPE_BLOCK,
		self::CHAR_DIR => self::TYPE_DIR,
		self::CHAR_FILE => self::TYPE_FILE,
		self::CHAR_LINK => self::TYPE_LINK,
		self::CHAR_SOCKET => self::TYPE_SOCKET,
		self::CHAR_UNKNOWN => self::TYPE_UNKNOWN,
	];

	/**
	 * Return an absolute path given a filename and a working directory
	 *
	 * @param string $filename
	 * @param ?string $cwd
	 * @return string null
	 */
	public static function absolute_path(string $filename, string $cwd = null): string {
		if ($filename[0] === '/') {
			return $filename;
		}
		if ($cwd === null) {
			$cwd = getcwd();
		}
		return path($cwd, $filename);
	}

	/**
	 * Require a file or files to exist
	 *
	 * @param array $mixed List of files to require
	 * @throws Exception_File_NotFound
	 */
	public static function depends(array $mixed): void {
		foreach ($mixed as $f) {
			if (!file_exists($f) || !is_file($f)) {
				throw new Exception_File_NotFound($f);
			}
		}
	}

	/**
	 * Clean a filename of invalid characters, restrictively
	 *
	 * @param string $mixed
	 *            Filename to clean
	 * @param string $sep_char
	 *            Character to replace unwanted characters with
	 * @return string Cleaned filename
	 */
	public static function name_clean(string $mixed, string $sep_char = '-'): string {
		$mixed = preg_replace('/[^-A-Za-z0-9_.]/', $sep_char, $mixed);
		return preg_replace("/$sep_char$sep_char+/", $sep_char, $mixed);
	}

	/**
	 * Convert a string into a valid path suitable for all platforms.
	 * Useful for cleaning user input for conversion to a
	 * path or file name.
	 *
	 * @param string $path String to clean
	 * @return string
	 * @todo deprecate this, where used?
	 *
	 */
	public static function clean_path(string $path): string {
		return preg_replace('%[^-_./a-zA-Z0-9]%', '_', str_replace('_', '/', $path));
	}

	/**
	 * Check a path name of attempted hacking attempts
	 *
	 * @param string $x Path name to clean
	 * @return bool
	 */
	public static function path_check(string $x): bool {
		if (preg_match('|[^-~/A-Za-z0-9_. ()@&]|', $x)) {
			return false;
		}
		if (StringTools::contains($x, [
				'..',
				'/./',
			]) !== false) {
			return false;
		}
		return true;
	}

	/**
	 * Generate an MD5 checksum for a file
	 *
	 * @return string An md5 checksum of the file
	 */
	/**
	 * @param string $path File to generate a checksum for
	 * @return string
	 * @throws Exception_File_NotFound
	 */
	public static function checksum(string $path): string {
		$size = self::size($path);
		if ($size < 1024 * 1024) {
			return strval(md5_file($path));
		}
		$data = "$size:";
		$f = fopen($path, 'rb');
		$data .= fread($f, 1024 * 512);
		$data .= "-$size-";
		if (fseek($f, $size - 1024) === 0) {
			$data .= fread($f, 1024 * 512);
		}
		fclose($f);
		return md5($data);
	}

	/**
	 * Convert a file path to another string using the map call
	 *
	 * @param string $filename
	 *            Filename to map
	 * @param string $pattern
	 *            A destination pattern containing {dirname}, {basename}, {extension}, {filename},
	 *            and {/} for directory separator
	 * @return string
	 */
	public static function map_pathinfo(string $filename, string $pattern): string {
		return map($pattern, pathinfo($filename) + [
				'/' => DIRECTORY_SEPARATOR,
			]);
	}

	/**
	 * Strip extension off of filename
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function strip_extension(string $filename): string {
		return self::map_pathinfo($filename, '{dirname}{/}{filename}');
	}

	/**
	 * Extract a file extension from a file path
	 *
	 * @param string $filename File path to extract the extension from
	 * @return string The file extension found, or $default (false) if none found
	 */
	public static function extension(string $filename): string {
		$name = basename($filename);
		$dot = strrpos($name, '.');
		if ($dot === false) {
			return '';
		}
		$name = substr($name, $dot + 1);
		if (empty($name)) {
			return '';
		}
		return trim($name);
	}

	/**
	 * Use a file as a semaphore counter
	 *
	 * @param string $path
	 *            Path to file to use as a counter
	 * @return integer The number in the file, plus one
	 * @throws Exception_File_NotFound
	 */
	public static function atomic_increment(string $path): int {
		$fp = @fopen($path, 'r+b');
		if (!$fp) {
			throw new Exception_File_NotFound($path, 'not found');
		}
		$until = time() + 10;
		while (!flock($fp, LOCK_EX | LOCK_NB)) {
			usleep(100);
			if (time() >= $until) {
				fclose($fp);
				return -1;
			}
		}
		$id = intval(fread($fp, 20));
		$id = $id + 1;
		fseek($fp, 0);
		fwrite($fp, strval($id));
		flock($fp, LOCK_UN);
		fclose($fp);
		return $id;
	}

	/**
	 * Put a file atomically
	 *
	 * @param string $path
	 *            file path
	 * @param string $data
	 *            file data
	 * @return boolean true if successful, false if 100ms passes and can't
	 * @throws Exception_File_NotFound
	 */
	public static function atomic_put(string $path, string $data): bool {
		$fp = fopen($path, 'w+b');
		if (!is_resource($fp)) {
			throw new Exception_File_NotFound($path, 'File::atomic_put not found');
		}
		$until = time() + 10;
		while (!flock($fp, LOCK_EX | LOCK_NB)) {
			usleep(100);
			if (time() >= $until) {
				fclose($fp);
				return false;
			}
		}
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
		return true;
	}

	/**
	 * Put a file atomically
	 *
	 * @param string $path
	 *            file path
	 * @param string $data
	 *            file data
	 * @return boolean true if successful, false if 100ms passes and can't
	 * @throws Exception_File_NotFound
	 */
	public static function atomic(string $path, mixed $data = null): mixed {
		if ($data !== null) {
			return self::atomic_put($path, serialize($data));
		}
		if (($result = File::contents($path, null)) !== null) {
			$result = unserialize($result);
			if ($result === false) {
				return null;
			}
			return $result;
		}
		return null;
	}

	/**
	 * Create a unique temporary file name
	 *
	 * @param string $path Directory for temporary file
	 * @param string $ext Extension to place on temporary file
	 * @param int $mode Directory creation mode (e.g. 0700)
	 * @return string
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 */
	public static function temporary(string $path, string $ext = 'tmp', int $mode = null) {
		return path(Directory::depend($path, $mode), md5(microtime()) . '.' . ltrim($ext, '.'));
	}

	/**
	 * Extract a file name excluding extension from a file path
	 *
	 * @param string $filename
	 *            File path to extract the extension from
	 * @return string The file name without the extension
	 */
	public static function base(string $filename, $lower = false): string {
		if ($lower !== false) {
			zesk()->deprecated('lower parameter');
		}
		$filename = basename($filename);
		$dot = strrpos($filename, '.');
		if ($dot === false) {
			return $filename;
		}
		$filename = substr($filename, 0, $dot);
		return trim($filename);
	}

	/**
	 * Change file mode (if file exists)
	 *
	 * @param string $file_name
	 * @param int $mode
	 * @return boolean
	 */
	public static function chmod(string $file_name, int $mode = 0o770): bool {
		if (file_exists($file_name)) {
			return chmod($file_name, $mode);
		}
		return false;
	}

	/**
	 * Like file_get_contents but allows the return of a default string when file doesn't exist
	 *
	 * @param string $filename
	 *            The file to retrieve
	 * @return ?string The file contents, or null
	 */
	public static function contents(string $filename): string|null {
		if (file_exists($filename)) {
			$contents = file_get_contents($filename);
			return is_string($contents) ? $contents : null;
		}
		return null;
	}

	/**
	 * Create or append a file with content provided
	 *
	 * @param string $filename
	 * @param string $content
	 * @throws Exception_File_Permission
	 */
	public static function append(string $filename, string $content): void {
		$mode = file_exists($filename) ? 'a' : 'w';
		if (!is_resource($f = fopen($filename, $mode))) {
			throw new Exception_File_Permission('Can not open {filename} with mode {mode} to append {n} bytes of content', [
				'filename' => $filename,
				'mode' => $mode,
				'n' => strlen($content),
			]);
		}
		fwrite($f, $content);
		fclose($f);
	}

	/**
	 * Like file_put_contents, but does some sanity checks and throws errors
	 *
	 * @param string $path File to write
	 * @param mixed $contents Contents of file
	 * @throws Exception_File_Permission
	 * @see file_put_contents
	 */
	public static function put(string $path, string $contents): void {
		if (file_put_contents($path, $contents) === false) {
			throw new Exception_File_Permission($path, 'Unable to write {n} bytes to file {file}', [
				'file' => $path,
				'n' => strlen($contents),
			]);
		}
	}

	/**
	 * Like unlink, but does some sanity test and throws errors
	 *
	 * @param string $path
	 * @throws Exception_File_Permission
	 */
	public static function unlink(string $path): void {
		if (!is_dir($dir = dirname($path))) {
			return;
		}
		if (!is_file($path)) {
			return;
		}
		if (!unlink($path)) {
			throw new Exception_File_Permission($path, 'unable to unlink');
		}
	}

	/**
	 * Like filesize but throws an error when file not found
	 *
	 * @param $filename
	 * @throws Exception_File_NotFound
	 */
	public static function size(string $filename): int {
		if (!file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		return filesize($filename);
	}

	/**
	 * Wrapper around file() to throw a file not found exception
	 *
	 * @param string $filename
	 * @return array Lines in the file
	 * @throws Exception_File_NotFound
	 */
	public static function lines(string $filename): array {
		if (!file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		return file($filename);
	}

	/**
	 *
	 * @var array
	 */
	private static array $mask_to_chars = [
		self::MASK_FILE => self::CHAR_FILE,
		self::MASK_SOCKET => self::CHAR_SOCKET,
		self::MASK_LINK => self::CHAR_LINK,
		self::MASK_BLOCK => self::CHAR_BLOCK,
		self::MASK_DIR => self::CHAR_DIR,
		self::MASK_CHAR => self::CHAR_CHAR,
		self::MASK_FIFO => self::CHAR_FIFO,
		0 => self::CHAR_UNKNOWN,
	];

	/**
	 *
	 * @var array
	 */
	private static array $char_to_mask = [
		self::CHAR_FILE => self::MASK_FILE,
		self::CHAR_SOCKET => self::MASK_SOCKET,
		self::CHAR_LINK => self::MASK_LINK,
		self::CHAR_BLOCK => self::MASK_BLOCK,
		self::CHAR_DIR => self::MASK_DIR,
		self::CHAR_CHAR => self::MASK_CHAR,
		self::CHAR_FIFO => self::MASK_FIFO,
	];

	/**
	 * This array corresponds, one-to-one of the pattern for ls directories
	 *
	 * So drwsrwxrwx or whatever shows up in LS listings.
	 *
	 * @return number[][]
	 */
	private static function _mode_map(): array {
		return [
			self::$char_to_mask,
			[
				'r' => 0x0100,
				'-' => 0,
			],
			[
				'w' => 0x0080,
				'-' => 0,
			],
			[
				's' => 0x0840,
				'x' => 0x0040,
				'S' => 0x0800,
				'-' => 0,
			],
			[
				'r' => 0x0020,
				'-' => 0,
			],
			[
				'w' => 0x0010,
				'-' => 0,
			],
			[
				's' => 0x0408,
				'x' => 0x0008,
				'S' => 0x0400,
				'-' => 0,
			],
			[
				'r' => 0x0004,
				'-' => 0,
			],
			[
				'w' => 0x0002,
				'-' => 0,
			],
			[
				's' => 0x0201,
				'x' => 0x0001,
				'S' => 0x0200,
				'-' => 0,
			],
		];
	}

	/**
	 * Given a character type in the Unix "ls" command, convert it to our
	 * internal string type names (e.g self::type_foo)
	 *
	 * @param string $char
	 * @return string
	 */
	public static function ls_type(string $char): string {
		$char = substr($char, 0, 1);
		return self::$mask_to_chars[self::$char_to_mask[$char] ?? 0] ?? self::TYPE_UNKNOWN;
	}

	/**
	 * Convert an octal or decimal file mode to a string
	 *
	 * @param int $mode
	 * @return string
	 */
	public static function mode_to_string(int $mode): string {
		$map = self::_mode_map();
		$result = '';
		foreach ($map as $i => $items) {
			if ($i === 0) {
				$result .= self::$mask_to_chars[$mode & self::MASK_FTYPE] ?? self::CHAR_UNKNOWN;
			} else {
				foreach ($items as $char => $bits) {
					if (($mode & $bits) === $bits) {
						$result .= $char;

						break;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Convert a ls-style mode string (e.g.
	 * -rw-rw-rw) to a bitwise file mode
	 *
	 * @param string $mode_string
	 * @return int
	 * @throws Exception_Unimplemented
	 * @throws Exception_Syntax
	 */
	public static function string_to_mode(string $mode_string): int {
		$keys = implode('', array_keys(self::$char_to_mask));
		if (!preg_match('/^[' . $keys . '][-r][-w][-xSs][-r][-w][-xSs][-r][-w][-xSs]$/', $mode_string)) {
			throw new Exception_Syntax('{mode_string} does not match pattern');
		}
		$map = array_values(self::_mode_map());
		$mode = 0;
		for ($i = 0; $i < strlen($mode_string); $i++) {
			$v = $map[$i][$mode_string[$i]] ?? null;
			if ($v === null) {
				throw new Exception_Unimplemented("Unknown mode character $mode_string ($i)... \"" . $mode_string[$i] . '"');
			}
			$mode |= $v;
		}
		return $mode;
	}

	/**
	 * Change a file extension from one extension to another (string manipulation)
	 *
	 * @param string $file
	 * @param string $new_extension Extension with or without a "." in it (it's removed). If null, then extension is removed completely (no dot, either.)
	 * @return string
	 */
	public static function setExtension(string $file, string $new_extension): string {
		[$prefix, $file] = pairr($file, '/', '', $file);
		if ($prefix) {
			$prefix .= '/';
		}
		[$base] = pairr($file, '.', $file);
		if ($new_extension) {
			$base .= '.' . ltrim($new_extension, '.');
		}
		return $prefix . $base;
	}

	/**
	 * Octal with a zero prefix
	 *
	 * @param int $mode
	 * @return string
	 */
	public static function mode_to_octal(int $mode): string {
		return sprintf('0%o', 0o777 & $mode);
	}

	/**
	 *
	 * @param string $id
	 * @param string $method Callable function to convert id to name
	 * @return NULL|string
	 */
	private static function _name_from_id(mixed $id, callable $method): ?string {
		if (!function_exists($method)) {
			return null;
		}
		$result = @$method($id);
		if (!is_array($result)) {
			return null;
		}
		return $result['name'] ?? null;
	}

	/**
	 *
	 * @param int $uid
	 * @return string
	 */
	private static function name_from_uid(int $uid): ?string {
		return self::_name_from_id($uid, 'posix_getpwuid');
	}

	/**
	 *
	 * @param int $gid
	 * @return string
	 */
	private static function name_from_gid(int $gid): ?string {
		return self::_name_from_id($gid, 'posix_getgrgid');
	}

	/**
	 * Thanks webmaster at askapache dot com
	 * Souped up fstat.
	 * Rewritten slightly.
	 *
	 * @thanks
	 *
	 * @param mixed $path
	 *            Path or resource to check
	 * @param string $section
	 *            Section to retrieve, or null for all sections
	 * @return array
	 * @throws Exception_File_NotFound
	 */
	public static function stat(string $path, string $section = null): array {
		$is_res = is_resource($path);
		if (!$is_res) {
			clearstatcache(false, $path);
		}
		$ss = $is_res ? @fstat($path) : @stat($path);
		if (!$ss) {
			throw new Exception_File_NotFound($is_res ? _dump($path) : $path);
		}

		$p = $ss['mode'];
		$mode_string = self::mode_to_string($p);
		$type = self::$mask_to_chars[$p & self::MASK_FTYPE];
		$s = [
			/* Permissions */
			'perms' => [
				'umask' => sprintf('%04o', @umask()),  /* umask */
				'string' => $mode_string,  /* drwxrwxrwx */
				'octal' => sprintf('%o', ($p & 0o777)),  /* Octal without a zero prefix */
				'octal0' => self::mode_to_octal($p),  /* Octal with a zero prefix */
				'decimal' => intval($p) & 0o777,  /* Decimal value, truncated */
				'fileperms' => $is_res ? null : @fileperms($path),  /* Permissions */
				'mode' => $p, /* Raw permissions value returned by fstat */
			],
			'owner' => [
				'uid' => $ss['uid'],
				'gid' => $ss['gid'],
				'fileowner' => $ss['uid'],
				'filegroup' => $ss['gid'],
				'owner' => self::name_from_uid($ss['uid']),
				'group' => self::name_from_gid($ss['gid']),
			],
			'file' => [
				'filename' => $is_res ? null : $path,
				'realpath' => $is_res ? null : realpath($path),
				'dirname' => $is_res ? null : dirname($path),
				'basename' => $is_res ? null : basename($path),
			],
			'filetype' => [
				'type' => $type,
				'string_type' => self::$char_to_string[$type] ?? null,
				'is_file' => is_file($path),
				'is_dir' => is_dir($path),
				'is_link' => is_link($path),
				'is_readable' => is_readable($path),
				'is_writable' => is_writable($path),
			],
			'device' => [
				'device' => $ss['dev'], // Device
				'device_number' => $ss['rdev'], // Device number, if device.
				'inode' => $ss['ino'], // File serial number
				'link_count' => $ss['nlink'], // link count
				'link_to' => ($type == 'link') ? @readlink($path) : '',
			],
			'size' => [
				'size' => $ss['size'], // Size of file, in bytes.
				'blocks' => $ss['blocks'], // Number 512-byte blocks allocated
				'block_size' => $ss['blksize'],
			],
			'time' => [
				'mtime' => $ss['mtime'], // Time of last modification
				'atime' => $ss['atime'], // Time of last access.
				'ctime' => $ss['ctime'], // Time of last status change
				'accessed' => @date('Y M D H:i:s', $ss['atime']),
				'modified' => @date('Y M D H:i:s', $ss['mtime']),
				'created' => @date('Y M D H:i:s', $ss['ctime']),
			],
		];

		if (!$is_res) {
			clearstatcache(false, $path);
		}
		if ($section !== null) {
			return $s[$section] ?? [];
		}
		return $s;
	}

	/**
	 * Max file size to trim files in memory
	 *
	 * Performance-related setting
	 *
	 * @return integer
	 * @throws Exception_Semantics
	 * @global integer File::trim::maximum_file_size Size of file to use alternate method for
	 */
	public static function trim_maximum_file_size() {
		$app = Kernel::singleton()->application();
		$result = to_integer($app->configuration->path_get([
			"zesk\file",
			'trim',
			'maximum_file_size',
		]));
		if ($result) {
			return $result;
		}
		$memory_limit = to_bytes(ini_get('memory_limit'));
		return intval($memory_limit / 2);
	}

	/**
	 * Max memory size to read while trimming files
	 *
	 * Performance-related setting
	 *
	 * @return integer
	 * @throws Exception_Lock
	 * @throws Exception_Semantics
	 * @global integer File::trim::read_buffer_size Size of file to use alternate method for
	 */
	public static function trim_read_buffer_size(): int {
		$app = Kernel::singleton()->application();
		$result = to_integer($app->configuration->path_get([
			"zesk\file",
			'trim',
			'read_buffer_size',
		]));
		if ($result) {
			return $result;
		}
		$memory_limit = to_bytes(ini_get('memory_limit'));
		$default_trim_read_buffer_size = clamp(10240, $memory_limit / 4, 1048576);
		return intval($default_trim_read_buffer_size);
	}

	/**
	 * Trim a file similarly to how you would trim a string.
	 *
	 * @param string $path
	 *            Path to the file to trim
	 * @param int $offset
	 *            Offset within the file to start
	 * @param int $length
	 *            Length within the file to remove
	 * @return boolean
	 * @throws Exception_FileSystem
	 * @throws Exception_File_Create
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_Lock
	 * @throws Exception_Semantics
	 * @global integer File::trim::maximum_file_size Size of file to use alternate method for
	 */
	public static function trim(string $path, int $offset = 0, int $length = null): bool {
		if (!is_file($path)) {
			throw new Exception_File_NotFound($path);
		}
		if ($offset === 0 && $length === null) {
			return true;
		}
		$size = filesize($path);
		if ($length === null) {
			$length = $size;
		}
		if ($size < self::trim_maximum_file_size()) {
			$result = file_put_contents($path, substr(file_get_contents($path), $offset, $length));
			if ($result === false) {
				return false;
			}
			if ($result === $length - $offset) {
				return true;
			}

			throw new Exception_FileSystem("Unable to write $length bytes to $path ($result !== $length - $offset)");
		}
		if ($offset < 0) {
			$offset = $size - $offset;
			if ($offset < 0) {
				$offset = 0;
			}
		}
		if ($length < 0) {
			$length = $size + $length - $offset;
		}
		$temp = $path . '-temp-trim-' . getmypid();
		$temp_mv = $temp . '-rename';
		$w = fopen($temp, 'wb');
		if (!$w) {
			throw new Exception_File_Create($temp);
		}
		$r = fopen($path, 'r+b');
		if (!$r) {
			fclose($w);

			throw new Exception_File_Create($path);
		}
		fseek($r, $offset);
		$read_buffer_size = self::trim_read_buffer_size();
		$remain = $length;
		while (!feof($r)) {
			$read_size = min($remain, $read_buffer_size);
			$data = fread($r, $read_size);
			fwrite($w, $data);
			$remain -= strlen($data);
		}
		fclose($w);
		fclose($r);
		if (!rename($path, $temp_mv)) {
			@unlink($temp);
			@unlink($temp_mv);

			throw new Exception_File_Permission("Rename $path to $temp_mv");
		}
		$result = rename($temp, $path);
		@unlink($temp_mv);
		if (!$result) {
			throw new Exception_File_Permission("Didn't rename $temp to $path");
		}
		return true;
	}

	/**
	 * Retrieve the first part of a file
	 *
	 * @param string $filename
	 * @param int $length
	 * @return string
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	public static function head($filename, $length = 1024) {
		if (!is_file($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		$f = fopen($filename, 'rb');
		if (!$f) {
			throw new Exception_File_Permission("$filename:Can not read");
		}
		$result = fread($f, $length);
		fclose($f);
		return $result;
	}

	/**
	 * Handle log rotation
	 *
	 * @param string $path
	 * @param int $size_limit
	 * @param int $keep_count
	 * @param string $suffix
	 * @return boolean
	 */
	public static function rotate($path, $size_limit = 10485760, $keep_count = 7, $suffix = '') {
		if (file_exists($path) && ($size_limit === null || filesize($path) > $size_limit)) {
			if (file_exists("$path.$keep_count$suffix")) {
				@unlink("$path.$keep_count$suffix");
			}
			$n = intval($keep_count);
			while ($n-- !== 0) {
				if (file_exists("$path.$n$suffix")) {
					@rename("$path.$n$suffix", "$path." . ($n + 1) . $suffix);
				}
			}
			@rename($path, "$path.0$suffix");
			return true;
		}
		return false;
	}

	/**
	 * Is the path an absolute path?
	 *
	 * @param string $path
	 *            Path to check
	 * @return boolean
	 */
	public static function isAbsolute(string $path): bool {
		if ($path === '') {
			return false;
		}
		if (is_windows()) {
			if (strlen($path) < 1) {
				return false;
			}
			return $path[1] === ':' || $path[0] === '\\';
		} else {
			return $path[0] === '/';
		}
	}

	/**
	 * Rename a file to another file atomically - and delete file.
	 * Handles rollback, locking, etc.
	 *
	 * @param string $source
	 * @param string $target
	 * @return bool
	 * @throws Exception_File_Locked
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function move_atomic(string $source, string $target, string $new_target = null) {
		if (!is_file($target)) {
			if (!rename($source, $target)) {
				throw new Exception_File_Permission($target, 'Can not rename {source} to {target}', [
					'source' => $source,
					'target' => $target,
				]);
			}
		}
		if (!is_file($source)) {
			throw new Exception_File_NotFound($source);
		}
		$pid = getmypid();
		$targetLock = $target . '.atomic-lock';
		$lock = fopen($targetLock, 'w+b');
		if (!$lock) {
			throw new Exception_File_Permission('Can not create lock file {targetLock}', [
				'targetLock' => $targetLock,
			]);
		}
		if (!flock($lock, LOCK_EX)) {
			unlink($targetLock);

			throw new Exception_File_Locked($targetLock);
		}
		$target_temp = $target . ".atomic.$pid";
		$exception = null;
		if (!rename($target, $target_temp)) {
			$exception = new Exception_File_Permission($target_temp, 'Can not rename target {target} to temp {target_temp}', compact('target', 'target_temp'));
		} elseif (!@rename($source, $target)) {
			if (!@rename($target_temp, $target)) {
				$exception = new Exception_File_Permission($target, 'RECOVERY: Can not rename target temp {target_temp} BACK to target {target}', compact('target', 'target_temp'));
			} else {
				$exception = new Exception_File_Permission($target, 'Can not rename source {source} to target {target}', compact('source', 'target'));
			}
		}
		flock($lock, LOCK_UN);
		fclose($lock);
		unlink($targetLock);
		if ($exception) {
			throw $exception;
		}
		if (!$new_target) {
			unlink($target_temp);
		} else {
			self::unlink($new_target);
			rename($target_temp, $new_target);
		}
		return true;
	}

	/**
	 * Copy uid and gid
	 *
	 * @param string $source Source file or folder to copy uid/gid from
	 * @param string $target Target file or fiolder to copy uid/gid to
	 * @return string $target returned upon success
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	public static function copy_uid_gid(string $source, string $target): string {
		return self::copy_gid($source, self::copy_uid($source, $target));
	}

	/**
	 * Copy uid
	 *
	 * @param string $source Source file or directory to copy uid from
	 * @param string $target Target file or directory to copy uid to
	 * @return string $target returned upon success
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function copy_uid(string $source, string $target): string {
		$target_owner = File::stat($target, 'owner');
		$source_owner = File::stat($source, 'owner');
		if ($target_owner['uid'] !== $source_owner['uid']) {
			if (!chown($target, $source_owner['uid'])) {
				throw new Exception_File_Permission($target, '{method}({source}, {target}) chown({target}, {gid})', [
					'method' => __METHOD__,
					'source' => $source,
					'target' => $target,
				]);
			}
		}
		return $target;
	}

	/**
	 * Copy uid and gid
	 *
	 * @param string $source Source file or directory to copy gid from
	 * @param string $target Target file or directory to copy gid to
	 * @return string $target returned upon success
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function copy_gid(string $source, string $target): string {
		$target_owner = File::stat($target, 'owner');
		$source_owner = File::stat($source, 'owner');
		if ($target_owner['gid'] !== $source_owner['gid']) {
			if (!chgrp($target, $source_owner['gid'])) {
				throw new Exception_File_Permission($target, '{method}({source}, {target}) chgrp({target}, {gid})', [
					'method' => __METHOD__,
					'source' => $source,
					'target' => $target,
				]);
			}
		}
		return $target;
	}

	/**
	 * Check that file is writable
	 *
	 * @param string $file
	 * @return string
	 * @throws Exception_File_Permission
	 * @throws Exception_Directory_NotFound
	 */
	public static function validate_writable(string $file): string {
		if (!is_dir($dir = dirname($file))) {
			throw new Exception_Directory_NotFound($dir);
		}
		if (file_exists($file)) {
			if (is_writable($file)) {
				return $file;
			}

			throw new Exception_File_Permission($file, 'Unable to write (!is_writable)');
		}
		$lock_name = "$file.pid=" . getmypid() . '.writable.temp';
		if (file_put_contents($lock_name, strval(microtime(true))) !== false) {
			unlink($lock_name);
			return $file;
		}

		throw new Exception_File_Permission($file, 'Unable to write in {dir} {filename}', [
			'dir' => $dir,
		]);
	}

	/**
	 * @param array $paths List of strings representing file system paths
	 * @param array|string $files File name to search for, or list of file names to search for (array)
	 * @return string Full path of found file, or null if not found
	 * @throws Exception_NotFound
	 */
	public static function find_first(array $paths, array|string $files = null): string {
		if (is_string($files)) {
			$files = [$files];
		} elseif ($files === null) {
			$files = [''];
		}
		$all_files = [];
		foreach ($paths as $path) {
			foreach ($files as $file) {
				$the_path = path($path, $file);
				if (is_file($the_path)) {
					return $the_path;
				}
				$all_files[] = $the_path;
			}
		}

		throw new Exception_NotFound('No files exist {files}', ['files' => $all_files]);
	}

	/**
	 * Given a list of paths and a file name, find all occurrence of the named file.
	 *
	 * @param array $paths
	 *            List of strings representing file system paths
	 * @param mixed $file
	 *            File name to search for, or list of file names to search for (array)
	 * @return array list of files found, in order
	 * @see self::find_directory
	 */
	public static function find_all(array $paths, array|string $file): array {
		$result = [];
		if (!is_array($file)) {
			$file = [$file];
		}
		foreach ($paths as $path) {
			foreach ($file as $f) {
				$the_path = path($path, $f);
				if (is_file($the_path)) {
					$result[] = $the_path;
				}
			}
		}
		return $result;
	}

	/**
	 * Change a file extension from one extension to another (string manipulation)
	 *
	 * @param string $file
	 * @param string $new_extension Extension with or without a "." in it (it's removed). If null, then extension is removed completely (no dot, either.)
	 * @return string
	 * @deprecated 2022-05
	 */
	public static function extension_change(string $file, string $new_extension): string {
		return self::setExtension($file, $new_extension);
	}
}
