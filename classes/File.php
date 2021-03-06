<?php
/**
 *
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
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
	const MASK_FILE = 0100000;

	/**
	 *
	 * @var integer
	 */
	const MASK_SOCKET = 0140000;

	/**
	 *
	 * @var integer
	 */
	const MASK_LINK = 0120000;

	/**
	 *
	 * @var integer
	 */
	const MASK_BLOCK = 0060000;

	/**
	 *
	 * @var integer
	 */
	const MASK_DIR = 0040000;

	/**
	 *
	 * @var integer
	 */
	const MASK_CHAR = 0020000;

	/**
	 *
	 * @var integer
	 */
	const MASK_FIFO = 0010000;

	/**
	 *
	 * @var integer
	 */
	const MASK_FTYPE = 0170000;

	/**
	 *
	 * @var string
	 */
	const TYPE_SOCKET = "socket";

	/**
	 *
	 * @var string
	 */
	const TYPE_LINK = "link";

	/**
	 *
	 * @var string
	 */
	const TYPE_FILE = "file";

	/**
	 *
	 * @var string
	 */
	const TYPE_BLOCK = "block";

	/**
	 *
	 * @var string
	 */
	const TYPE_DIR = "dir";

	/**
	 *
	 * @var string
	 */
	const TYPE_CHAR = "char";

	/**
	 *
	 * @var string
	 */
	const TYPE_FIFO = "fifo";

	/**
	 *
	 * @var string
	 */
	const TYPE_UNKNOWN = "unknown";

	/**
	 *
	 * @var string
	 */
	const CHAR_SOCKET = "s";

	/**
	 *
	 * @var string
	 */
	const CHAR_LINK = "l";

	/**
	 *
	 * @var string
	 */
	const CHAR_FILE = "-";

	/**
	 *
	 * @var string
	 */
	const CHAR_BLOCK = "b";

	/**
	 *
	 * @var string
	 */
	const CHAR_DIR = "d";

	/**
	 *
	 * @var string
	 */
	const CHAR_CHAR = "c";

	/**
	 *
	 * @var string
	 */
	const CHAR_FIFO = "p";

	/**
	 *
	 * @var string
	 */
	const CHAR_UNKNOWN = "u";

	/**
	 * Return an absolute path given a filename and a working directory
	 *
	 * @param string $filename
	 * @param string $cwd
	 * @return string null
	 */
	public static function absolute_path($filename, $cwd = null) {
		if (empty($filename)) {
			return null;
		}
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
	 * @param string $mixed
	 *        	List of files to require
	 * @throws Exception_File_NotFound
	 * @return boolean
	 */
	public static function depends($mixed) {
		$mixed = to_list($mixed);
		foreach ($mixed as $f) {
			if (!file_exists($f) || !is_file($f)) {
				throw new Exception_File_NotFound($f);
			}
		}
		return true;
	}

	/**
	 * Clean a filename of invalid characters, restrictively
	 *
	 * @param string $mixed
	 *        	Filename to clean
	 * @param string $sep_char
	 *        	Character to replace unwanted characters with
	 * @return string Cleaned filename
	 */
	public static function name_clean($mixed, $sep_char = "-") {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$mixed[$k] = self::name_clean($v, $sep_char);
			}
			return $mixed;
		}
		$mixed = preg_replace("/[^-A-Za-z0-9_.]/", $sep_char, $mixed);
		$mixed = preg_replace("/$sep_char$sep_char+/", $sep_char, $mixed);
		return $mixed;
	}

	/**
	 * Convert a string into a valid path suitable for all platforms.
	 * Useful for cleaning user input for conversion to a
	 * path or file name.
	 *
	 * @todo deprecate this, where used?
	 *
	 * @param string $path String to clean
	 * @return string
	 */
	public static function clean_path($path) {
		return preg_replace("%[^-_./a-zA-Z0-9]%", '_', str_replace("_", "/", $path));
	}

	/**
	 * Check a path name of attempted hacking attempts
	 *
	 * @param string $x
	 *        	Path name to clean
	 * @return string Cleaned filename
	 */
	public static function path_check($x) {
		if (preg_match('|[^-~/A-Za-z0-9_. ()@&]|', $x)) {
			return false;
		}
		if (ArrayTools::strstr($x, array(
			"..",
			"/./",
		)) !== false) {
			return false;
		}
		return true;
	}

	/**
	 * Generate an MD5 checksum for a file
	 *
	 * @param string $path
	 *        	File to generate a checksum for
	 * @return string An md5 checksum of the file
	 */
	public static function checksum($path) {
		if (empty($path)) {
			return null;
		}
		$size = filesize($path);
		if ($size < 1024 * 1024) {
			return md5_file($path);
		}
		$data = "$size:";
		$f = @fopen($path, "rb");
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
	 *        	Filename to map
	 * @param string $pattern
	 *        	A destination pattern containing {dirname}, {basename}, {extension}, {filename},
	 *        	and {/} for directory separator
	 * @return string
	 */
	public static function map_pathinfo($filename, $pattern) {
		return map($pattern, pathinfo($filename) + array(
			'/' => DIRECTORY_SEPARATOR,
		));
	}

	/**
	 * Strip extension off of filename
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function strip_extension($filename) {
		return self::map_pathinfo($filename, "{dirname}{/}{filename}");
	}

	/**
	 * Extract a file extension from a file path
	 *
	 * @param string $filename File path to extract the extension from
	 * @param string $default The default extension to use if none found
	 * @param boolean $lower Convert the result to lowercase
	 * @return string The file extension found, or $default (false) if none found
	 */
	public static function extension($filename, $default = null, $lower = true) {
		$name = basename($filename);
		$dot = strrpos($name, ".");
		if ($dot === false) {
			return $default;
		}
		$name = substr($name, $dot + 1);
		if (empty($name)) {
			return $default;
		}
		$name = trim($name);
		if (!$lower) {
			return $name;
		}
		return strtolower($name);
	}

	/**
	 * Use a file as a semaphore counter
	 *
	 * @param string $path
	 *        	Path to file to use as a counter
	 * @return integer The number in the file, plus one
	 * @throws Exception_File_NotFound
	 */
	public static function atomic_increment($path) {
		$fp = @fopen($path, "r+b");
		if (!$fp) {
			throw new Exception_File_NotFound($path, "not found");
		}
		$until = time() + 10;
		while (!flock($fp, LOCK_EX | LOCK_NB)) {
			usleep(100);
			if (time() >= $until) {
				fclose($fp);
				return null;
			}
		}
		$id = intval(fread($fp, 20));
		$id = $id + 1;
		fseek($fp, 0);
		fwrite($fp, $id);
		flock($fp, LOCK_UN);
		fclose($fp);
		return $id;
	}

	/**
	 * Put a file atomically
	 *
	 * @param string $path
	 *        	file path
	 * @param string $data
	 *        	file data
	 * @return boolean true if successful, false if 100ms passes and can't
	 * @throws Exception_File_NotFound
	 */
	public static function atomic_put($path, $data) {
		$fp = fopen($path, "w+b");
		if (!is_resource($fp)) {
			throw new Exception_File_NotFound($path, "File::atomic_put not found");
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
	 *        	file path
	 * @param string $data
	 *        	file data
	 * @return boolean true if successful, false if 100ms passes and can't
	 * @throws Exception_File_NotFound
	 */
	public static function atomic($path, $data = null) {
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
	 * @param integer $mode Directory creation mode (e.g. 0700)
	 * @return string
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 */
	public static function temporary($path, $ext = "tmp", $mode = null) {
		return path(Directory::depend($path, $mode), md5(microtime()) . "." . ltrim($ext, "."));
	}

	/**
	 * Extract a file name excluding extension from a file path
	 *
	 * @param string $filename
	 *        	File path to extract the extension from
	 * @param boolean $lower
	 *        	Convert the result to lowercase
	 * @return string The file name without the extension
	 */
	public static function base($filename, $lower = false) {
		$filename = basename($filename);
		$dot = strrpos($filename, ".");
		if ($dot === false) {
			return $filename;
		} else {
			$filename = substr($filename, 0, $dot);
		}
		$filename = trim($filename);
		if (!$lower) {
			return $filename;
		}
		return strtolower($filename);
	}

	/**
	 * Change file mode (if file exists)
	 *
	 * @param string $file_name
	 * @param integer $mode
	 * @return boolean
	 */
	public static function chmod($file_name, $mode = 0770) {
		if (file_exists($file_name)) {
			return chmod($file_name, $mode);
		}
		return false;
	}

	/**
	 * Like file_get_contents but allows the return of a default string when file doesn't exist
	 *
	 * @param string $filename
	 *        	The file to retrieve
	 * @param mixed $default
	 *        	The return value when the file does not exist
	 * @return mixed The file contents, or $default
	 */
	public static function contents($filename, $default = null) {
		if (file_exists($filename)) {
			return file_get_contents($filename);
		}
		return $default;
	}

	/**
	 * Create or append a file with content provided
	 *
	 * @param string $filename
	 * @param string $content
	 * @throws Exception_File_Permission
	 * @return boolean
	 */
	public static function append($filename, $content) {
		$mode = file_exists($filename) ? "a" : "w";
		if (!is_resource($f = fopen($filename, $mode))) {
			throw new Exception_File_Permission("Can not open {filename} with mode {mode} to append {n} bytes of content", array(
				"filename" => $filename,
				"mode" => $mode,
				"n" => strlen($content),
			));
		}
		fwrite($f, $content);
		fclose($f);
		return true;
	}

	/**
	 * Like file_put_contents, but does some sanity checks and throws errors
	 *
	 * @param string $path File to write
	 * @param mixed $contents Contents of file
	 * @see file_put_contents
	 * @return boolean
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_Parameter
	 */
	public static function put($path, $contents) {
		if (!is_scalar($contents)) {
			throw new Exception_Parameter("{method}: Contents should be a scalar value {type} passed", array(
				"method" => __METHOD__,
				"type" => type($contents),
			));
		}
		if (!is_dir($dir = dirname($path))) {
			throw new Exception_Directory_NotFound($dir, "Unable to write {n} bytes to file {file}", array(
				"file" => $path,
				"n" => strlen($contents),
			));
		}
		if (@file_put_contents($path, $contents) === false) {
			throw new Exception_File_Permission($path, "Unable to write {n} bytes to file {file}", array(
				"file" => $path,
				"n" => strlen($contents),
			));
		}
		return true;
	}

	/**
	 * Like unlink, but does some sanity test and throws errors
	 *
	 * @param string $path
	 * @throws Exception_File_Permission
	 * @return boolean
	 */
	public static function unlink($path) {
		if (!is_dir($dir = dirname($path))) {
			return true;
		}
		if (!is_file($path)) {
			return true;
		}
		if (!unlink($path)) {
			throw new Exception_File_Permission($path, "unable to unlink");
		}
		return true;
	}

	/**
	 * Like filesize but allows the return of a default string when file doesn't exist
	 *
	 * @param string $filename
	 *        	The file to retrieve
	 * @param mixed $default
	 *        	The return value when the file does not exist
	 * @return mixed The file size, or $default
	 */
	public static function size($filename, $default = null) {
		if (file_exists($filename)) {
			return filesize($filename);
		}
		return $default;
	}

	/**
	 * Wrapper around file() to throw a file not found exception
	 *
	 * @param string $filename
	 * @throws Exception_File_NotFound
	 * @return array Lines in the file
	 */
	public static function lines($filename) {
		if (!file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		return file($filename);
	}

	/**
	 *
	 * @var array
	 */
	private static $fchars = array(
		self::MASK_FILE => self::CHAR_FILE,
		self::MASK_SOCKET => self::CHAR_SOCKET,
		self::MASK_LINK => self::CHAR_LINK,
		self::MASK_BLOCK => self::CHAR_BLOCK,
		self::MASK_DIR => self::CHAR_DIR,
		self::MASK_CHAR => self::CHAR_CHAR,
		self::MASK_FIFO => self::CHAR_FIFO,
		0 => self::CHAR_UNKNOWN,
	);

	/**
	 *
	 * @var array
	 */
	private static $mtypes = array(
		self::CHAR_FILE => self::MASK_FILE,
		self::CHAR_SOCKET => self::MASK_SOCKET,
		self::CHAR_LINK => self::MASK_LINK,
		self::CHAR_BLOCK => self::MASK_BLOCK,
		self::CHAR_DIR => self::MASK_DIR,
		self::CHAR_CHAR => self::MASK_CHAR,
		self::CHAR_FIFO => self::MASK_FIFO,
	);

	/**
	 *
	 * @return number[][]
	 */
	private static function _mode_map() {
		return array(
			self::$mtypes,
			array(
				'r' => 0x0100,
				'-' => 0,
			),
			array(
				'w' => 0x0080,
				'-' => 0,
			),
			array(
				's' => 0x0840,
				'x' => 0x0040,
				'S' => 0x0800,
				'-' => 0,
			),
			array(
				'r' => 0x0020,
				'-' => 0,
			),
			array(
				'w' => 0x0010,
				'-' => 0,
			),
			array(
				's' => 0x0408,
				'x' => 0x0008,
				'S' => 0x0400,
				'-' => 0,
			),
			array(
				'r' => 0x0004,
				'-' => 0,
			),
			array(
				'w' => 0x0002,
				'-' => 0,
			),
			array(
				's' => 0x0201,
				'x' => 0x0001,
				'S' => 0x0200,
				'-' => 0,
			),
		);
	}

	/**
	 * Given a character type in the Unix "ls" command, convert it to our
	 * internal string type names (e.g self::type_foo)
	 *
	 * @param string $char
	 * @return string
	 */
	public static function ls_type($char) {
		$char = substr($char, 0, 1);
		return avalue(self::$fchars, avalue(self::$mtypes, $char, 0), self::TYPE_UNKNOWN);
	}

	/**
	 * Convert an octal or decimal file mode to a string
	 *
	 * @param integer $mode
	 * @return string
	 */
	public static function mode_to_string($mode) {
		$map = self::_mode_map();
		$result = "";
		foreach ($map as $i => $items) {
			if ($i === 0) {
				$result .= avalue(self::$fchars, $mode & self::MASK_FTYPE, self::CHAR_UNKNOWN);
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
	 * @throws Exception_Unimplemented
	 * @return integer
	 */
	public static function string_to_mode($mode_string) {
		$keys = implode("", array_keys(self::$mtypes));
		if (!preg_match('/^[' . $keys . '][-r][-w][-xSs][-r][-w][-xSs][-r][-w][-xSs]$/', $mode_string)) {
			return null;
		}
		$map = array_values(self::_mode_map());
		$mode = 0;
		for ($i = 0; $i < strlen($mode_string); $i++) {
			$v = avalue($map[$i], $mode_string[$i], null);
			if ($v === null) {
				throw new Exception_Unimplemented("Unknown mode character $mode_string ($i)... \"" . $mode_string[$i] . "\"");
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
	public static function extension_change($file, $new_extension) {
		list($prefix, $file) = pairr($file, "/", "", $file);
		if ($prefix) {
			$prefix .= "/";
		}
		list($base) = pairr($file, ".", $file, null);
		if ($new_extension) {
			$base .= '.' . ltrim($new_extension, '.');
		}
		return $prefix . $base;
	}

	/**
	 * Octal with a zero prefix
	 *
	 * @param integer $mode
	 * @return string
	 */
	public static function mode_to_octal($mode) {
		return sprintf("0%o", 0777 & $mode);
	}

	/**
	 *
	 * @param string $id
	 * @param string $method Callable function to convert id to name
	 * @return NULL|string
	 */
	private static function _name_from_id($id, $method) {
		if (!function_exists($method)) {
			return null;
		}
		$result = @$method($id);
		if (!is_array($result)) {
			return null;
		}
		return avalue($result, 'name');
	}

	/**
	 *
	 * @param integer $uid
	 * @return string
	 */
	private static function name_from_uid($uid) {
		return self::_name_from_id($uid, 'posix_getpwuid');
	}

	/**
	 *
	 * @param integer $gid
	 * @return string
	 */
	private static function name_from_gid($gid) {
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
	 *        	Path or resource to check
	 * @param string $section
	 *        	Section to retrieve, or null for all sections
	 * @throws Exception_File_NotFound
	 * @return array
	 */
	public static function stat($path, $section = null) {
		$is_res = is_resource($path);
		if (!$is_res) {
			clearstatcache(null, $path);
		}
		$ss = $is_res ? @fstat($path) : @stat($path);
		if (!$ss) {
			throw new Exception_File_NotFound($is_res ? _dump($path) : $path);
		}

		$p = $ss['mode'];
		$mode_string = self::mode_to_string($p);
		$type = self::$fchars[$p & self::MASK_FTYPE];
		$s = array(
			'perms' => array(/* Permissions */
				'umask' => sprintf("%04o", @umask()),  /* umask */
				'string' => $mode_string,  /* drwxrwxrwx */
				'octal' => sprintf("%o", ($p & 0777)),  /* Octal without a zero prefix */
				'octal0' => self::mode_to_octal($p),  /* Octal with a zero prefix */
				'decimal' => intval($p) & 0777,  /* Decimal value, truncated */
				'fileperms' => $is_res ? null : @fileperms($path),  /* Permissions */
				'mode' => $p, /* Raw permissions value returned by fstat */
			),
			'owner' => array(
				'uid' => $ss['uid'],
				'gid' => $ss['gid'],
				'fileowner' => $ss['uid'],
				'filegroup' => $ss['gid'],
				'owner' => self::name_from_uid($ss['uid']),
				'group' => self::name_from_gid($ss['gid']),
			),
			'file' => array(
				'filename' => $is_res ? null : $path,
				'realpath' => $is_res ? null : realpath($path),
				'dirname' => $is_res ? null : dirname($path),
				'basename' => $is_res ? null : basename($path),
			),
			'filetype' => array(
				'type' => $type,
				'is_file' => is_file($path),
				'is_dir' => is_dir($path),
				'is_link' => is_link($path),
				'is_readable' => is_readable($path),
				'is_writable' => is_writable($path),
			),
			'device' => array(
				'device' => $ss['dev'], // Device
				'device_number' => $ss['rdev'], // Device number, if device.
				'inode' => $ss['ino'], // File serial number
				'link_count' => $ss['nlink'], // link count
				'link_to' => ($type == 'link') ? @readlink($path) : '',
			),
			'size' => array(
				'size' => $ss['size'], // Size of file, in bytes.
				'blocks' => $ss['blocks'], // Number 512-byte blocks allocated
				'block_size' => $ss['blksize'],
			),
			'time' => array(
				'mtime' => $ss['mtime'], // Time of last modification
				'atime' => $ss['atime'], // Time of last access.
				'ctime' => $ss['ctime'], // Time of last status change
				'accessed' => @date('Y M D H:i:s', $ss['atime']),
				'modified' => @date('Y M D H:i:s', $ss['mtime']),
				'created' => @date('Y M D H:i:s', $ss['ctime']),
			),
		);

		if (!$is_res) {
			clearstatcache(null, $path);
		}
		if ($section !== null) {
			return avalue($s, $section, $s);
		}
		return $s;
	}

	/**
	 * Max file size to trim files in memory
	 *
	 * Performance-related setting
	 *
	 * @global integer File::trim::maximum_file_size Size of file to use alternate method for
	 * @return integer
	 * @throws Exception_Lock
	 * @throws Exception_Semantics
	 */
	public static function trim_maximum_file_size() {
		$app = Kernel::singleton()->application();
		$result = to_integer($app->configuration->path_get(array(
			"zesk\file",
			"trim",
			"maximum_file_size",
		)));
		if ($result) {
			return $result;
		}
		$memory_limit = to_bytes(ini_get("memory_limit"));
		return intval($memory_limit / 2);
	}

	/**
	 * Max memory size to read while trimming files
	 *
	 * Performance-related setting
	 *
	 * @global integer File::trim::read_buffer_size Size of file to use alternate method for
	 * @return integer
	 * @throws Exception_Lock
	 * @throws Exception_Semantics
	 */
	public static function trim_read_buffer_size() {
		$app = Kernel::singleton()->application();
		$result = to_integer($app->configuration->path_get(array(
			"zesk\file",
			"trim",
			"read_buffer_size",
		)));
		if ($result) {
			return $result;
		}
		$memory_limit = to_bytes(ini_get("memory_limit"));
		$default_trim_read_buffer_size = clamp(10240, $memory_limit / 4, 1048576);
		return intval($default_trim_read_buffer_size);
	}

	/**
	 * Trim a file similarly to how you would trim a string.
	 *
	 * @param string $path
	 *        	Path to the file to trim
	 * @param integer $offset
	 *        	Offset within the file to start
	 * @param integer $length
	 *        	Length within the file to remove
	 * @throws Exception_FileSystem
	 * @throws Exception_File_Create
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_Lock
	 * @throws Exception_Semantics
	 * @global integer File::trim::maximum_file_size Size of file to use alternate method for
	 * @return boolean
	 */
	public static function trim($path, $offset = 0, $length = null) {
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
		$temp_mv = $temp . "-rename";
		$w = fopen($temp, "wb");
		if (!$w) {
			throw new Exception_File_Create($temp);
		}
		$r = fopen($path, "r+b");
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
		return $result;
	}

	/**
	 * Retrieve the first part of a file
	 *
	 * @param string $filename
	 * @param integer $length
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @return string
	 */
	public static function head($filename, $length = 1024) {
		if (!is_file($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		$f = fopen($filename, "rb");
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
	public static function rotate($path, $size_limit = 10485760, $keep_count = 7, $suffix = "") {
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
	 * @param string $f
	 *        	Path to check
	 * @return boolean
	 * @throws Exception_Parameter
	 */
	public static function is_absolute($f) {
		if (!is_string($f)) {
			throw new Exception_Parameter("{method} First parameter should be string {type} passed", array(
				"method" => __METHOD__,
				"type" => type($f),
			));
		}
		$f = strval($f);
		if ($f === "") {
			return false;
		}
		if (is_windows()) {
			if (strlen($f) < 1) {
				return false;
			}
			return $f[1] === ":" || $f[0] === "\\";
		} else {
			return $f[0] === '/';
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
	public static function move_atomic($source, $target, $new_target = null) {
		if (!is_file($target)) {
			return @rename($source, $target);
		}
		if (!is_file($source)) {
			throw new Exception_File_NotFound($source);
		}
		$pid = getmypid();
		$target_lock = $target . ".atomic-lock";
		$lock = fopen($target_lock, "w+b");
		if (!$lock) {
			throw new Exception_File_Permission("Can not create lock file {target_lock}", array(
				"target_lock" => $target_lock,
			));
		}
		if (!flock($lock, LOCK_EX)) {
			@unlink($target_lock);

			throw new Exception_File_Locked($target_lock);
		}
		$target_temp = $target . ".atomic.$pid";
		$exception = null;
		if (!@rename($target, $target_temp)) {
			$exception = new Exception_File_Permission($target_temp, "Can not rename target {target} to temp {target_temp}", compact("target", "target_temp"));
		} elseif (!@rename($source, $target)) {
			if (!@rename($target_temp, $target)) {
				$exception = new Exception_File_Permission($target, "RECOVERY: Can not rename target temp {target_temp} BACK to target {target}", compact("target", "target_temp"));
			} else {
				$exception = new Exception_File_Permission($target, "Can not rename source {source} to target {target}", compact("source", "target"));
			}
		}
		flock($lock, LOCK_UN);
		fclose($lock);
		@unlink($target_lock);
		if ($exception) {
			throw $exception;
		}
		if (!$new_target) {
			unlink($target_temp);
		} else {
			self::unlink($new_target);
			@rename($target_temp, $new_target);
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
	public static function copy_uid_gid($source, $target) {
		return self::copy_gid($source, self::copy_uid($source, $target));
	}

	/**
	 * Copy uid
	 *
	 * @param string $source
	 *        	Source file or folder to copy uid from
	 * @param string $target
	 *        	Target file or fiolder to copy uid to
	 * @return string $target returned upon success
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function copy_uid($source, $target) {
		$target_owner = File::stat($target, 'owner');
		$source_owner = File::stat($source, 'owner');
		if ($target_owner['uid'] !== $source_owner['uid']) {
			if (!@chown($target, $source_owner['uid'])) {
				throw new Exception_File_Permission($target, "{method}({source}, {target}) chown({target}, {gid})", array(
					"method" => __METHOD__,
					"source" => $source,
					"target" => $target,
				));
			}
		}
		return $target;
	}

	/**
	 * Copy uid and gid
	 *
	 * @param string $source
	 *        	Source file or folder to copy gid from
	 * @param string $target
	 *        	Target file or fiolder to copy gid to
	 * @return string $target returned upon success
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function copy_gid($source, $target) {
		$target_owner = File::stat($target, 'owner');
		$source_owner = File::stat($source, 'owner');
		if ($target_owner['gid'] !== $source_owner['gid']) {
			if (!@chgrp($target, $source_owner['gid'])) {
				throw new Exception_File_Permission($target, "{method}({source}, {target}) chgrp({target}, {gid})", array(
					"method" => __METHOD__,
					"source" => $source,
					"target" => $target,
				));
			}
		}
		return $target;
	}

	/**
	 * Check that file is writable
	 *
	 * @param string $file
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_Permission
	 * @return string
	 */
	public static function validate_writable($file) {
		if (!is_dir($dir = dirname($file))) {
			throw new Exception_Directory_NotFound($dir);
		}
		if (file_exists($file)) {
			if (is_writable($file)) {
				return $file;
			}

			throw new Exception_File_Permission($file, "Unable to write (!is_writable)");
		}
		$lock_name = "$file.pid=" . getmypid() . ".writable.temp";
		if (file_put_contents($lock_name, strval(microtime(true))) !== false) {
			unlink($lock_name);
			return $file;
		}

		throw new Exception_File_Permission($file, "Unable to write in {dir} {filename}", array(
			"dir" => $dir,
		));
	}

	/**
	 * Given a list of paths and a file name, find the first occurrence of the file.
	 *
	 * @param array $paths
	 *        	List of strings representing file system paths
	 * @param mixed $file
	 *        	File name to search for, or list of file names to search for (array)
	 * @return string Full path of found file, or null if not found
	 * @see self::find_directory
	 */
	public static function find_first(array $paths, $file = null) {
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
	 * Given a list of paths and a file name, find all occurrence of the named file.
	 *
	 * @param array $paths
	 *        	List of strings representing file system paths
	 * @param mixed $file
	 *        	File name to search for, or list of file names to search for (array)
	 * @return array list of files found, in order
	 * @see self::find_directory
	 */
	public static function find_all(array $paths, $file) {
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
}
