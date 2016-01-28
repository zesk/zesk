<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/gzip.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Mon Jun 16 14:33:27 EDT 2008
 */

class gzip {
	static $started = false;
	static $ok = null;

	public static function enabled($set = null) {
		if (is_bool($set)) {
			zesk::set('gz_output', $set);
		}
		return zesk::get1('gz_output;gzip::enabled');
	}

	public static function ok() {
		if (is_bool(self::$ok)) {
			return self::$ok;
		}
		$accept_encoding = explode(",", str_replace(" ", "", strtolower(avalue($_SERVER, 'HTTP_ACCEPT_ENCODING', ''))));
		self::$ok = in_array("gzip", $accept_encoding) || in_array("deflate", $accept_encoding);
		return self::$ok;
	}

	public static function start() {
		if (self::enabled() && self::ok()) {
			ob_start();
			self::$started = true;
		}
	}

	private static function handler($content) {
		if (!self::ok() || headers_sent() || !self::enabled()) {
			return $content;
		}
		if (!function_exists('gzencode')) {
			return $content;
		}
		header("Content-Encoding: gzip");
		return gzencode($content, _G('gz_compress_level', 9));
	}

	public static function end() {
		if (self::$started) {
			echo gzip::handler(ob_get_clean());
		}
	}
}

/**
 * @deprecated
 */
function gzip_handler_start() {
	return gzip::start();
}

/**
 * @deprecated
 */
function gzip_handler_end() {
	return gzip::end();
}

