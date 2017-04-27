<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Compatibility.php $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 12:19:42 EDT 2008
 */
namespace zesk;

if (!function_exists("date_default_timezone_get")) {
	function date_default_timezone_get() {
		return avalue($_ENV, "TZ", "UTC");
	}
	function date_default_timezone_set($x) {
		putenv("TZ=$x");
		$_ENV["TZ"] = $x;
	}
}

if (!function_exists("htmlspecialchars_decode")) {
	function htmlspecialchars_decode($uSTR) {
		return strtr($uSTR, array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)));
	}
}

if (!function_exists('str_getcsv')) {
	function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$memory = $zesk->configuration->path_get(array(
			'str_getcsv',
			'line_size'
		), 1024 * 1024);
		
		$fp = fopen("php://temp/maxmemory:$memory", 'r+');
		fputs($fp, $input);
		rewind($fp);
		$data = fgetcsv($fp, 1000, $delimiter, $enclosure, $escape);
		fclose($fp);
		return $data;
	}
}

if (!function_exists("ob_end_clean_all")) {
	/**
	 * Delete all output buffers
	 *
	 * @return void
	 */
	function ob_end_clean_all() {
		$level = ob_get_level();
		while ($level > 0) {
			@ob_end_clean();
			$old_level = $level;
			$level = ob_get_level();
			if ($old_level === $level)
				break;
		}
	}
}

if (!function_exists('sgn')) {
	function sgn($value) {
		if ($value > 0) {
			return 1;
		}
		if ($value < 0) {
			return -1;
		}
		if (is_numeric($value)) {
			return 0;
		}
		return null;
	}
}

if (!function_exists('quoted_printable_encode')) {
	function quoted_printable_encode($str) {
		$lp = 0;
		$ret = '';
		$hex = "0123456789ABCDEF";
		$length = strlen($str);
		$str_index = 0;
		
		while ($length--) {
			if ((($c = $str[$str_index++]) == "\015") && ($str[$str_index] == "\012") && $length > 0) {
				$ret .= "\015";
				$ret .= $str[$str_index++];
				$length--;
				$lp = 0;
			} else {
				if (ctype_cntrl($c) || (ord($c) == 0x7f) || (ord($c) & 0x80) || ($c == '=') || (($c == ' ') && ($str[$str_index] == "\015"))) {
					if (($lp += 3) > PHP_QPRINT_MAXL) {
						$ret .= '=';
						$ret .= "\015";
						$ret .= "\012";
						$lp = 3;
					}
					$ret .= '=';
					$ret .= $hex[ord($c) >> 4];
					$ret .= $hex[ord($c) & 0xf];
				} else {
					if ((++$lp) > PHP_QPRINT_MAXL) {
						$ret .= '=';
						$ret .= "\015";
						$ret .= "\012";
						$lp = 1;
					}
					$ret .= $c;
				}
			}
		}
		return $ret;
	}
}
class Compatibility {
	public static function install() {
		if (!defined('PHP_VERSION_ID')) {
			$php_version = PHP_VERSION;
			define('PHP_VERSION_ID', ($php_version[0] * 10000 + $php_version[2] * 100 + $php_version[4]));
		}
		if (function_exists('date_default_timezone_set')) {
			$tz = ini_get('date.timezone');
			if (!$tz) {
				// All politics is local.
				date_default_timezone_set('America/New_York');
			}
		}
	}
}
