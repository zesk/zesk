<?php
declare(strict_types=1);
/**
 * File tools and abstractions
 *
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileCreate;
use zesk\Exception\FileLocked;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\FileSystemException;
use zesk\Exception\NotFoundException;
use zesk\Exception\SyntaxException;
use zesk\Exception\TimeoutExpired;
use zesk\Exception\UnimplementedException;

/**
 * File abstraction, lots of file tools
 *
 * @author kent
 */
class File
{
	/**
	 * Dimension returned by expandStats related to file permissions
	 */
	public const STATS_PERMS = 'perms';

	/**
	 * Dimension returned by expandStats related to file owner and group
	 */
	public const STATS_OWNER = 'owner';

	/**
	 * Dimension returned by expandStats related to file names and paths
	 */
	public const STATS_NAME = 'name';

	/**
	 * Dimension returned by expandStats related to file type
	 */
	public const STATS_TYPE = 'type';

	/**
	 * Dimension returned by expandStats related to file device
	 */
	public const STATS_DEVICE = 'device';

	/**
	 * Dimension returned by expandStats related to file size
	 */
	public const STATS_SIZE = 'size';

	/**
	 * Dimension returned by expandStats related to file creation, modification, and accessed time
	 */
	public const STATS_TIME = 'time';

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

	public static array $charToString = [
		self::CHAR_FIFO => self::TYPE_FIFO, self::CHAR_CHAR => self::TYPE_CHAR, self::CHAR_BLOCK => self::TYPE_BLOCK,
		self::CHAR_DIR => self::TYPE_DIR, self::CHAR_FILE => self::TYPE_FILE, self::CHAR_LINK => self::TYPE_LINK,
		self::CHAR_SOCKET => self::TYPE_SOCKET, self::CHAR_UNKNOWN => self::TYPE_UNKNOWN,
	];

	/**
	 * Return an absolute path given a filename and a working directory
	 *
	 * @param string $filename
	 * @param ?string $cwd
	 * @return string null
	 */
	public static function absolutePath(string $filename, string $cwd = null): string
	{
		if ($filename[0] === '/') {
			return $filename;
		}
		if ($cwd === null) {
			$cwd = getcwd();
		}
		return self::path($cwd, $filename);
	}

	/**
	 * Synonym for Directory::path for convenience
	 *
	 * @param array|string $paths
	 * @return string
	 * @see Directory::path()
	 */
	public static function path(array|string $paths): string
	{
		return Directory::path(func_get_args());
	}

	/**
	 * Require a file or files to exist
	 *
	 * @param array|string $mixed List of files to require
	 * @throws FileNotFound
	 */
	public static function depends(array|string $mixed): void
	{
		foreach (Types::toList($mixed) as $f) {
			if (!file_exists($f) || !is_file($f)) {
				throw new FileNotFound($f);
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
	public static function nameClean(string $mixed, string $sep_char = '-'): string
	{
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
	public static function cleanPath(string $path): string
	{
		return preg_replace('%[^-_./a-zA-Z0-9]%', '_', str_replace('_', '/', $path));
	}

	/**
	 * Check a path name of attempted hacking attempts
	 *
	 * @param string $x Path name to clean
	 * @return bool
	 */
	public static function pathCheck(string $x): bool
	{
		if (preg_match('|[^-~/A-Za-z0-9_. ()@&]|', $x)) {
			return false;
		}
		if (StringTools::contains($x, [
			'..', '/./',
		]) !== false) {
			return false;
		}
		return true;
	}

	/**
	 * Generate an MD5 checksum for a file
	 *
	 * @return string md5 checksum of the file
	 */
	/**
	 * @param string $path File to generate a checksum for
	 * @return string
	 * @throws FileNotFound
	 */
	public static function checksum(string $path): string
	{
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
	 *            A destination pattern containing {dirname}, {dirnamePrefix}, {basename}, {extension}, {filename},
	 *            and {/} for directory separator. {dirnamePrefix} is a derived value which matches characters before
	 *            the filename and includes a trailing DIRECTORY_SEPARATOR if non-blank.
	 * @return string
	 */
	public static function mapPathInfo(string $filename, string $pattern): string
	{
		$pathInfo = pathinfo($filename);
		$dirName = $pathInfo['dirname'] ?? '.';
		$sep = DIRECTORY_SEPARATOR;
		$pathInfo['dirnamePrefix'] = $dirName !== '.' || str_starts_with($filename, ".$sep") ? $dirName . $sep : '';
		return ArrayTools::map($pattern, $pathInfo + [
			'/' => $sep,
		]);
	}

	/**
	 * Strip extension off of filename
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function stripExtension(string $filename): string
	{
		return self::mapPathInfo($filename, '{dirnamePrefix}{filename}');
	}

	/**
	 * Extract a file extension from a file path
	 *
	 * @param string $filename File path to extract the extension from
	 * @return string The file extension found, or $default (false) if none found
	 */
	public static function extension(string $filename): string
	{
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
	 * @throws FileNotFound
	 * @throws TimeoutExpired
	 */
	public static function atomicIncrement(string $path): int
	{
		$fp = @fopen($path, 'r+b');
		if (!$fp) {
			throw new FileNotFound($path, 'not found');
		}
		$timeout = 10;
		$until = time() + $timeout;
		while (!flock($fp, LOCK_EX | LOCK_NB)) {
			usleep(100);
			if (time() >= $until) {
				fclose($fp);

				throw new TimeoutExpired('atomicIncrement({file}) after {timeout} seconds', [
					'file' => $path, 'timeout' => $timeout,
				]);
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
	 * @throws FileNotFound
	 */
	public static function atomicPut(string $path, string $data): bool
	{
		$fp = fopen($path, 'w+b');
		if (!is_resource($fp)) {
			throw new FileNotFound($path, 'File::atomicPut not found');
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
	 * Create a unique temporary file name
	 *
	 * @param string $path Directory for temporary file
	 * @param string $ext Extension to place on temporary file
	 * @param ?int $mode Directory creation mode (e.g. 0700)
	 * @return string
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 */
	public static function temporary(string $path, string $ext = 'tmp', int $mode = null): string
	{
		return self::path(Directory::depend($path, $mode), md5(microtime()) . '.' . ltrim($ext, '.'));
	}

	/**
	 * Extract a file name excluding extension from a file path
	 *
	 * @param string $filename
	 *            File path to extract the extension from
	 * @return string The file name without the extension
	 */
	public static function base(string $filename): string
	{
		$filename = basename($filename);
		$dot = strrpos($filename, '.');
		if ($dot === false) {
			return $filename;
		}
		$filename = substr($filename, 0, $dot);
		return trim($filename);
	}

	/**
	 * Change file mode (iff file exists)
	 *
	 * @param string $file_name
	 * @param int $mode
	 * @return void
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function chmod(string $file_name, int $mode = 504 /* 0o770 */): void
	{
		if (!file_exists($file_name)) {
			throw new FileNotFound($file_name, 'Can not set mode to {mode}', [
				'mode' => self::modeToOctal($mode),
			]);
		}
		if (!chmod($file_name, $mode)) {
			throw new FilePermission($file_name, 'Can not set mode to {mode}', [
				'mode' => self::modeToOctal($mode),
			]);
		}
	}

	/**
	 * file_get_contents with exceptions
	 *
	 * @param string $filename The file to retrieve
	 * @return string The file contents
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function contents(string $filename): string
	{
		if (file_exists($filename)) {
			$contents = @file_get_contents($filename);
			if (is_string($contents)) {
				return $contents;
			}

			throw new FilePermission($filename);
		}

		throw new FileNotFound($filename);
	}

	/**
	 * Create or append a file with content provided
	 *
	 * @param string $filename
	 * @param string $content
	 * @return int Bytes written
	 * @throws FilePermission
	 */
	public static function append(string $filename, string $content): int
	{
		$mode = file_exists($filename) ? 'a' : 'w';
		if (!is_resource($f = fopen($filename, $mode))) {
			throw new FilePermission($filename, 'Can not open {path} with mode {mode} to append {n} bytes of content', [
				'mode' => $mode, 'n' => strlen($content),
			]);
		}
		$result = fwrite($f, $content);
		fclose($f);
		if ($result === false) {
			throw new FilePermission($filename, 'Can not write {n} bytes', ['n' => strlen($content)]);
		}
		return $result;
	}

	/**
	 * Like file_put_contents, but does some sanity checks and throws errors
	 *
	 * @param string $path File to write
	 * @param mixed $contents Contents of file
	 * @throws FilePermission
	 * @see file_put_contents
	 */
	public static function put(string $path, string $contents): void
	{
		if (file_put_contents($path, $contents) === false) {
			throw new FilePermission($path, 'Unable to write {n} bytes to file {file}', [
				'file' => $path, 'n' => strlen($contents),
			]);
		}
	}

	/**
	 * Like unlink, but does some sanity test and throws errors
	 *
	 * @param string $path
	 * @throws FilePermission
	 */
	public static function unlink(string $path): void
	{
		if (!is_dir(dirname($path))) {
			return;
		}
		if (!is_file($path)) {
			return;
		}
		if (!unlink($path)) {
			throw new FilePermission($path, 'unable to unlink');
		}
	}

	/**
	 * Like filesize but throws an error when file not found
	 *
	 * @param string $filename
	 * @return int
	 * @throws FileNotFound
	 */
	public static function size(string $filename): int
	{
		if (!file_exists($filename)) {
			throw new FileNotFound($filename);
		}
		return filesize($filename);
	}

	/**
	 * Wrapper around file() to throw a file not found exception
	 *
	 * @param string $filename
	 * @return array Lines in the file
	 * @throws FileNotFound
	 */
	public static function lines(string $filename): array
	{
		if (!file_exists($filename)) {
			throw new FileNotFound($filename);
		}
		return file($filename);
	}

	/**
	 * @param string $filename
	 * @param string $mode
	 * @return resource
	 * @throws FilePermission
	 */
	public static function open(string $filename, string $mode): mixed
	{
		$res = fopen($filename, $mode);
		if (!$res) {
			throw new FilePermission($filename, 'File::open("{path}", "{mode}") failed', [
				'mode' => $mode,
			]);
		}
		return $res;
	}

	/**
	 *
	 * @var array
	 */
	private static array $maskToChars = [
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
	private static array $charToMask = [
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
	private static function _modeMap(): array
	{
		return [
			self::$charToMask, [
				'r' => 0x0100, '-' => 0,
			], [
				'w' => 0x0080, '-' => 0,
			], [
				's' => 0x0840, 'x' => 0x0040, 'S' => 0x0800, '-' => 0,
			], [
				'r' => 0x0020, '-' => 0,
			], [
				'w' => 0x0010, '-' => 0,
			], [
				's' => 0x0408, 'x' => 0x0008, 'S' => 0x0400, '-' => 0,
			], [
				'r' => 0x0004, '-' => 0,
			], [
				'w' => 0x0002, '-' => 0,
			], [
				's' => 0x0201, 'x' => 0x0001, 'S' => 0x0200, '-' => 0,
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
	public static function lsType(string $char): string
	{
		$char = substr($char, 0, 1);
		return self::$maskToChars[self::$charToMask[$char] ?? 0] ?? self::TYPE_UNKNOWN;
	}

	/**
	 * Convert an octal or decimal file mode to a string
	 *
	 * @param int $mode
	 * @return string
	 */
	public static function modeToString(int $mode): string
	{
		$map = self::_modeMap();
		$result = '';
		foreach ($map as $i => $items) {
			if ($i === 0) {
				$result .= self::$maskToChars[$mode & self::MASK_FTYPE] ?? self::CHAR_UNKNOWN;
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
	 * @throws UnimplementedException
	 * @throws SyntaxException
	 */
	public static function stringToMode(string $mode_string): int
	{
		$keys = implode('', array_keys(self::$charToMask));
		if (!preg_match('/^[' . $keys . '][-r][-w][-xSs][-r][-w][-xSs][-r][-w][-xSs]$/', $mode_string)) {
			throw new SyntaxException('{mode_string} does not match pattern');
		}
		$map = array_values(self::_modeMap());
		$mode = 0;
		for ($i = 0; $i < strlen($mode_string); $i++) {
			$v = $map[$i][$mode_string[$i]] ?? null;
			if ($v === null) {
				throw new UnimplementedException("Unknown mode character $mode_string ($i)... \"" . $mode_string[$i] . '"');
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
	public static function setExtension(string $file, string $new_extension): string
	{
		[$prefix, $file] = StringTools::reversePair($file, '/', '', $file);
		if ($prefix) {
			$prefix .= '/';
		}
		[$base] = StringTools::reversePair($file, '.', $file);
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
	public static function modeToOctal(int $mode): string
	{
		return sprintf('0%o', 0o777 & $mode);
	}

	/**
	 *
	 * @param string $id
	 * @param string $method Callable function to convert id to name
	 * @return NULL|string
	 */
	private static function _nameFromID(mixed $id, string $method): ?string
	{
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
	 * @return string|null
	 */
	private static function nameFromUID(int $uid): ?string
	{
		return self::_nameFromID($uid, 'posix_getpwuid');
	}

	/**
	 *
	 * @param int $gid
	 * @return string|null
	 */
	private static function nameFromGID(int $gid): ?string
	{
		return self::_nameFromID($gid, 'posix_getgrgid');
	}

	/**
	 * stat with extended results
	 *
	 * @param string $path Path to check
	 * @param ?string $section Section to retrieve, or null for all sections
	 * @return array
	 * @throws FileNotFound
	 */
	public static function stat(string $path, string $section = null): array
	{
		clearstatcache(false, $path);
		$ss = @stat($path);
		if (!$ss) {
			throw new FileNotFound($path);
		}
		$ss['path'] = $path;
		$ss['is_resource'] = false;
		$s = self::expandStats($ss);
		if ($section !== null) {
			return $s[$section] ?? [];
		}
		return $s;
	}

	/**
	 * fstat with extended results
	 *
	 * @param resource $path
	 *            Path or resource to check
	 * @param ?string $section
	 *            Section to retrieve, or null for all sections
	 * @return array
	 * @throws FileNotFound
	 */
	public static function resourceStat(mixed $path, string $section = null): array
	{
		assert(is_resource($path));
		$ss = @fstat($path);
		if (!$ss) {
			throw new FileNotFound(Debug::dump($path));
		}
		$ss['is_resource'] = true;
		$s = self::expandStats($ss);
		if ($section !== null) {
			return $s[$section] ?? [];
		}
		return $s;
	}

	/**
	 * Thanks webmaster at askapache dot com
	 * Souped up fstat.
	 * Rewritten a bunch.
	 *
	 * @param array $ss
	 * @return array[]
	 */
	public static function expandStats(array $ss): array
	{
		$o777 = 511; /* 0o777 */

		$is_resource = $ss['is_resource'] ?? false;
		$path = $ss['path'] ?? null;
		$p = $ss['mode'];
		$modeString = self::modeToString($p);
		$type = self::$maskToChars[$p & self::MASK_FTYPE];
		$s = [
			/* Permissions */
			self::STATS_PERMS => [
				'umask' => sprintf('%04o', umask()),  /* umask */
				'string' => $modeString,  /* drwxrwxrwx */
				'octal' => sprintf('%o', ($p & $o777)),  /* Octal without a zero prefix */
				'octal0' => self::modeToOctal($p),  /* Octal with a zero prefix */
				'decimal' => intval($p) & $o777,  /* Decimal value, truncated */
				'fileperms' => is_string($path) ? @fileperms($path) : null,  /* Permissions */
				'mode' => $p,
				/* Raw permissions value returned by fstat */
			],
			self::STATS_OWNER => [
				'uid' => $ss['uid'],
				'gid' => $ss['gid'],
				'owner' => self::nameFromUID($ss['uid']),
				'group' => self::nameFromGID($ss['gid']),
			],
			self::STATS_NAME => [
				'filename' => $is_resource ? null : $path,
				'realpath' => $is_resource ? null : realpath($path),
				'dirname' => $is_resource ? null : dirname($path),
				'basename' => $is_resource ? null : basename($path),
			],
			self::STATS_TYPE => [
				self::STATS_TYPE => $type,
				'string' => self::$charToString[$type] ?? '',
				'is_file' => is_file($path),
				'is_dir' => is_dir($path),
				'is_link' => is_link($path),
				'is_readable' => is_readable($path),
				'is_writable' => is_writable($path),
			],
			self::STATS_DEVICE => [
				self::STATS_DEVICE => $ss['dev'], // Device
				'deviceNumber' => $ss['rdev'], // Device number, if device.
				'inode' => $ss['ino'], // File serial number
				'linkCount' => $ss['nlink'], // link count
				'linkTo' => ($type == 'link') ? @readlink($path) : '',
			],
			self::STATS_SIZE => [
				self::STATS_SIZE => $ss['size'], // Size of file, in bytes.
				'blocks' => $ss['blocks'], // Number 512-byte blocks allocated
				'blockSize' => $ss['blksize'],
			],
			self::STATS_TIME => [
				'mtime' => $ss['mtime'], // Time of last modification
				'atime' => $ss['atime'], // Time of last access.
				'ctime' => $ss['ctime'], // Time of last status change
				'accessed' => @date('Y M D H:i:s', $ss['atime']),
				'modified' => @date('Y M D H:i:s', $ss['mtime']),
				'created' => @date('Y M D H:i:s', $ss['ctime']),
			],
		];

		if (!$is_resource) {
			clearstatcache(false, $path);
		}
		return $s;
	}

	/**
	 * Max file size to trim files in memory
	 *
	 * Performance-related setting
	 *
	 * @param Application $application
	 * @return int
	 */
	public static function trimMaximumFileSize(Application $application): int
	{
		$result = $application->configuration->path([self::class, 'trim'])->getInt('maximum_file_size');
		if ($result) {
			return $result;
		}
		$memory_limit = Types::toBytes(ini_get('memory_limit'));
		return intval($memory_limit / 2);
	}

	/**
	 * Max memory size to read while trimming files
	 *
	 * Performance-related setting
	 *
	 * @param Application $application
	 * @return int
	 */
	public static function trimReadBufferSize(Application $application): int
	{
		$result = $application->configuration->path([self::class, 'trim'])->getInt('read_buffer_size');
		if ($result) {
			return $result;
		}
		$memory_limit = Types::toBytes(ini_get('memory_limit'));
		$default_trim_read_buffer_size = Number::clamp(10240, $memory_limit / 4, 1048576);
		return intval($default_trim_read_buffer_size);
	}

	/**
	 * Trim a file similarly to how you would trim a string.
	 *
	 * @param Application $application
	 * @param string $path Path to the file to trim
	 * @param int $offset Offset within the file to start
	 * @param int|null $length Length within the file to remove
	 * @return bool
	 * @throws FileSystemException
	 * @throws FileCreate
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function trim(Application $application, string $path, int $offset = 0, int $length = null): bool
	{
		if (!is_file($path)) {
			throw new FileNotFound($path);
		}
		if ($offset === 0 && $length === null) {
			return true;
		}
		$size = filesize($path);
		if ($length === null) {
			$length = $size;
		}
		if ($size < self::trimMaximumFileSize($application)) {
			$result = file_put_contents($path, substr(file_get_contents($path), $offset, $length));
			if ($result === false) {
				return false;
			}
			if ($result === $length - $offset) {
				return true;
			}

			throw new FileSystemException("Unable to write $length bytes to $path ($result !== $length - $offset)");
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
			throw new FileCreate($temp);
		}
		$r = fopen($path, 'r+b');
		if (!$r) {
			fclose($w);

			throw new FileCreate($path);
		}
		fseek($r, $offset);
		$read_buffer_size = self::trimReadBufferSize($application);
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

			throw new FilePermission("Rename $path to $temp_mv");
		}
		$result = rename($temp, $path);
		@unlink($temp_mv);
		if (!$result) {
			throw new FilePermission("Didn't rename $temp to $path");
		}
		return true;
	}

	/**
	 * Retrieve the first part of a file
	 *
	 * @param string $filename
	 * @param int $length
	 * @return string
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	public static function head(string $filename, int $length = 1024): string
	{
		if (!is_file($filename)) {
			throw new FileNotFound($filename);
		}
		$f = fopen($filename, 'rb');
		if (!$f) {
			throw new FilePermission("$filename:Can not read");
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
	public static function rotate(string $path, int $size_limit = 10485760, int $keep_count = 7, string $suffix = ''): bool
	{
		if (!file_exists($path)) {
			return false;
		}
		if ($size_limit > 0 && filesize($path) < $size_limit) {
			return false;
		}
		if (file_exists("$path.$keep_count$suffix")) {
			@unlink("$path.$keep_count$suffix");
		}
		$n = $keep_count;
		while ($n-- !== 0) {
			if (file_exists("$path.$n$suffix")) {
				@rename("$path.$n$suffix", "$path." . ($n + 1) . $suffix);
			}
		}
		@rename($path, "$path.0$suffix");
		return true;
	}

	/**
	 * Is the path an absolute path?
	 *
	 * @param string $path
	 *            Path to check
	 * @return boolean
	 */
	public static function isAbsolute(string $path): bool
	{
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
	 * @param string|null $new_target
	 * @return void
	 * @throws FileLocked
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function moveAtomic(string $source, string $target, string $new_target = null): void
	{
		if (!is_file($target)) {
			if (!rename($source, $target)) {
				throw new FilePermission($target, 'Can not rename {source} to {target}', [
					'source' => $source, 'target' => $target,
				]);
			}
		}
		if (!is_file($source)) {
			throw new FileNotFound($source);
		}
		$pid = getmypid();
		$targetLock = $target . '.atomic-lock';
		$lock = fopen($targetLock, 'w+b');
		if (!$lock) {
			throw new FilePermission($targetLock, 'Can not create lock file');
		}
		if (!flock($lock, LOCK_EX)) {
			fclose($lock);
			unlink($targetLock);

			throw new FileLocked($targetLock);
		}
		$target_temp = $target . ".atomic.$pid";
		$exception = null;
		if (!rename($target, $target_temp)) {
			$exception = new FilePermission($target_temp, 'Can not rename target {target} to temp {target_temp}', compact('target', 'target_temp'));
		} elseif (!@rename($source, $target)) {
			if (!@rename($target_temp, $target)) {
				$exception = new FilePermission($target, 'RECOVERY: Can not rename target temp {target_temp} BACK to target {target}', compact('target', 'target_temp'));
			} else {
				$exception = new FilePermission($target, 'Can not rename source {source} to target {target}', compact('source', 'target'));
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
	}

	/**
	 * Copy uid and gid
	 *
	 * @param string $source Source file or folder to copy uid/gid from
	 * @param string $target Target file or fiolder to copy uid/gid to
	 * @return string $target returned upon success
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	public static function copyOwnerAndGroup(string $source, string $target): string
	{
		return self::copyGroup($source, self::copyOwner($source, $target));
	}

	/**
	 * Copy uid
	 *
	 * @param string $source Source file or directory to copy uid from
	 * @param string $target Target file or directory to copy uid to
	 * @return string $target returned upon success
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function copyOwner(string $source, string $target): string
	{
		$target_owner = File::stat($target, 'owner');
		$source_owner = File::stat($source, 'owner');
		if ($target_owner['uid'] !== $source_owner['uid']) {
			if (!chown($target, $source_owner['uid'])) {
				throw new FilePermission($target, '{method}({source}, {target}) chown({target}, {gid})', [
					'method' => __METHOD__, 'source' => $source, 'target' => $target,
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
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function copyGroup(string $source, string $target): string
	{
		$target_owner = File::stat($target, 'owner');
		$source_owner = File::stat($source, 'owner');
		if ($target_owner['gid'] !== $source_owner['gid']) {
			if (!chgrp($target, $source_owner['gid'])) {
				throw new FilePermission($target, '{method}({source}, {target}) chgrp({target}, {gid})', [
					'method' => __METHOD__, 'source' => $source, 'target' => $target,
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
	 * @throws FilePermission
	 * @throws DirectoryNotFound
	 */
	public static function validateWritable(string $file): string
	{
		if (!is_dir($dir = dirname($file))) {
			throw new DirectoryNotFound($dir);
		}
		if (file_exists($file)) {
			if (is_writable($file)) {
				return $file;
			}

			throw new FilePermission($file, 'Unable to write (!is_writable)');
		}
		$lock_name = "$file.pid=" . getmypid() . '.writable.temp';
		if (file_put_contents($lock_name, strval(microtime(true))) !== false) {
			unlink($lock_name);
			return $file;
		}

		throw new FilePermission($file, 'Unable to write in {dir} {filename}', [
			'dir' => $dir,
		]);
	}

	/**
	 * @param array $paths List of strings representing file system paths
	 * @param array|string|null $files File name to search for, or list of file names to search for (array)
	 * @return string Full path of found file, or null if not found
	 * @throws NotFoundException
	 */
	public static function findFirst(array $paths, array|string $files = null): string
	{
		if (is_string($files)) {
			$files = [$files];
		} elseif ($files === null) {
			$files = [null];
		}
		$all_files = [];
		foreach ($paths as $path) {
			foreach ($files as $file) {
				$the_path = self::path($path, $file);
				if (is_file($the_path)) {
					return $the_path;
				}
				$all_files[] = $the_path;
			}
		}

		throw new NotFoundException('No files exist {paths} {files}', ['paths' => $paths, 'files' => $all_files]);
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
	public static function findAll(array $paths, array|string $file): array
	{
		$result = [];
		if (!is_array($file)) {
			$file = [$file];
		}
		foreach ($paths as $path) {
			foreach ($file as $f) {
				$the_path = self::path($path, $f);
				if (is_file($the_path)) {
					$result[] = $the_path;
				}
			}
		}
		return $result;
	}

	/**
	 * @param string|array $files
	 * @param int $modifiedBefore
	 * @return array
	 */
	public static function deleteModifiedBefore(string|array $files, int $modifiedBefore): array
	{
		$result = [];
		$now = time();
		foreach (Types::toList($files) as $file) {
			if (!is_file($file)) {
				$result[$file] = new FileNotFound($file);
				continue;
			}
			$fileModificationTime = filemtime($file);
			if ($fileModificationTime < $modifiedBefore) {
				$result[$file] = [
					'file' => $file, 'when' => date('Y-m-d H:i:s'), 'delta' => $now - $fileModificationTime,
					'deleted' => unlink($file),
				];
			} else {
				$result[$file] = [
					'file' => $file, 'when' => date('Y-m-d H:i:s'), 'delta' => $now - $fileModificationTime,
				];
			}
		}
		return $result;
	}
}
