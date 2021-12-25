<?php declare(strict_types=1);
/**
 * Convert from one charset to another
 *
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
namespace zesk;

use \DirectoryIterator;

class charset {
	/**
	 *
	 */
	private static $tables = [];

	/**
	 * Convert an array of strings or a string from the given charset to UTF-8.
	 * @param array|string $data
	 * @param string $charset
	 * @param array $missing
	 * @return array|string
	 * @throws Exception_Convert
	 * @throws Exception_File_Format
	 */
	public static function to_utf8($data, $charset, &$missing = []) {
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				$data[$k] = self::to_utf8($v, $charset, $missing);
			}
			return $data;
		}
		$charset = strtoupper($charset);
		if ($charset === "ISO-8859-1") {
			return utf8_encode($data);
		}
		if ($charset === "UTF-8") {
			return $data;
		}
		$table = self::load_table($charset);
		$length = strlen($data);
		$result = "";
		for ($i = 0; $i < $length; $i++) {
			$c = ord($data[$i]);
			if (!array_key_exists($c, $table)) {
				$missing[$c] = isset($missing[$c]) ? $missing[$c] + 1 : 1;
				continue;
			}
			$u = $table[$c];
			if ($u < 0x80) {
				$result .= chr($u);
			} elseif ($u < 0x800) {
				$result .= /*                                                      */ chr(($u >> 6) + 0xC0) /*    */ . chr(($u & 0x3F) + 0x80);
			} elseif ($u < 0x10000) {
				$result .= /*                    */ chr(($u >> 12) + 0xE0) /*    */ . chr((($u >> 6) & 0x3F) + 0x80) . chr(($u & 0x3F) + 0x80);
			} elseif ($u < 0x200000) {
				$result .= chr(($u >> 18) + 0xF0) . chr((($u >> 12) & 0x3F) + 0x80) . chr((($u >> 6) & 0x3F) + 0x80) . chr(($u & 0x3F) + 0x80);
			}
		}
		return $result;
	}

	/**
	 * Load a character set table
	 *
	 * @throws Exception_File_Format if file has bad data in it
	 * @throws Exception_Convert if unknown charset
	 * @param string $charset
	 * @return array
	 */
	private static function load_table($charset) {
		if (!array_key_exists($charset, self::$tables)) {
			$path = self::charset_path($charset);
			if (!file_exists($path)) {
				throw new Exception_Convert("Unknown charset $charset");
			}
			$lines = file($path, FILE_SKIP_EMPTY_LINES);
			$table = [];
			foreach ($lines as $line_number => $original_line) {
				if (str_contains($original_line, '#UNDEFINED')) {
					continue;
				}
				$line = preg_replace('/\s+/', ' ', trim(StringTools::left($original_line, "#")));
				if (empty($line)) {
					continue;
				}
				$matches = null;
				if (!preg_match('/0x([A-Z0-9]{2,4}) 0x([A-Z0-9]{4})/i', $line, $matches)) {
					throw new Exception_File_Format("Bad line " . ($line_number + 1) . " in $path");
				}
				$table[hexdec($matches[1])] = hexdec($matches[2]);
			}
			self::$tables[$charset] = $table;
		}
		return self::$tables[$charset];
	}

	/**
	 * Files are stored in ZESK_ROOT/etc/charset-data/
	 *
	 * @param string $charset Charset path to return (optional)
	 * @return string Path to charset files or individual charset file
	 */
	private static function charset_path($charset = null) {
		return ZESK_ROOT . 'etc/charset-data/' . ($charset === null ? '' : strtolower($charset) . '.txt');
	}

	/**
	 * Do we support the given charset conversion? If null passed in, returns the list
	 * of all of the supported charsets.
	 *
	 * @param string $charset
	 * @return string[]|boolean
	 */
	public static function supported($charset = null) {
		if ($charset === null) {
			return self::list_all();
		}
		return file_exists(self::charset_path($charset));
	}

	/**
	 * Return a list of the available charsets.
	 *
	 * @return string[]
	 */
	private static function list_all() {
		$iter = new DirectoryIterator(self::charset_path());
		$result = [];
		foreach ($iter as $file) {
			if ($file->isDir()) {
				continue;
			}
			$name = $file->getBasename();
			if ($name[0] === '.') {
				continue;
			}
			if (substr($name, -4) === '.txt') {
				$result[] = strtoupper(substr($name, 0, -4));
			}
		}
		return $result;
	}
}
