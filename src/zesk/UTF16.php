<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Wed Feb 24 14:17:02 EST 2010 14:17:02
 */
namespace zesk;

/**
 * 16-bit UTF utilities
 *
 * @author kent
 */
class UTF16 {
	/**
	 * Convert a string from utf16 to utf8
	 *
	 * Thanks:
	 * http://www.moddular.org/log/utf16-to-utf8
	 * http://www.onicos.com/staff/iz/amuse/javascript/expert/utf.txt
	 *
	 * @param string $content
	 * @param bool $be Return BOM encoding characters
	 * @return string
	 */
	public static function to_utf8(string $content, bool &$be = null): string {
		$c0 = ord($content[0]);
		$c1 = ord($content[1]);

		$found_be = false;
		if ($c0 == 0xFE && $c1 == 0xFF) {
			$be = true;
			$found_be = true;
		} elseif ($c0 == 0xFF && $c1 == 0xFE) {
			$be = false;
			$found_be = true;
		}
		if ($be === null) {
			$be = true;
		}
		$len = strlen($content);
		$dec = '';
		for ($i = $found_be ? 2 : 0; $i < $len; $i += 2) {
			$c = ($be) ? ord($content[$i]) << 8 | ord($content[$i + 1]) : ord($content[$i + 1]) << 8 | ord($content[$i]);
			if ($c >= 0x0001 && $c <= 0x007F) {
				$dec .= chr($c);
			} elseif ($c > 0x07FF) {
				$dec .= chr(0xE0 | (($c >> 12) & 0x0F));
				$dec .= chr(0x80 | (($c >> 6) & 0x3F));
				$dec .= chr(0x80 | (($c >> 0) & 0x3F));
			} else {
				$dec .= chr(0xC0 | (($c >> 6) & 0x1F));
				$dec .= chr(0x80 | (($c >> 0) & 0x3F));
			}
		}
		return $dec;
	}

	/**
	 * Decode UTF-16 encoded strings.
	 *
	 * Can handle both BOM'ed data and un-BOM'ed data.
	 * Assumes Big-Endian byte order if no BOM is available.
	 * From: http://php.net/manual/en/function.utf8-decode.php
	 *
	 * @param   string  $content  UTF-16 encoded data to decode.
	 * @param   bool  $be  Return BOM value
	 * @return  string  UTF-8 / ISO encoded data.
	 * @access  public
	 * @version 0.1 / 2005-01-19
	 * @author  Rasmus Andersson {@link http://rasmusandersson.se/}
	 * @package Groupies
	 */
	public static function decode(string $content, bool &$be = null): string {
		if (strlen($content) < 2) {
			return $content;
		}
		$c0 = ord($content[0]);
		$c1 = ord($content[1]);
		$start = 0;
		if ($c0 == 0xFE && $c1 == 0xFF) {
			$be = true;
			$start = 2;
		} elseif ($c0 == 0xFF && $c1 == 0xFE) {
			$start = 2;
			$be = false;
		}
		if ($be === null) {
			$be = true;
		}
		$len = strlen($content);
		$new_content = '';
		for ($i = $start; $i < $len; $i += 2) {
			if ($be) {
				$val = ord($content[$i]) << 4;
				$val += ord($content[$i + 1]);
			} else {
				$val = ord($content[$i + 1]) << 4;
				$val += ord($content[$i]);
			}
			$new_content .= ($val == 0x228) ? "\n" : chr($val);
		}
		return $new_content;
	}

	/**
	 * This is probably too simplistic, but should work for most standard ASCII < 0x7F
	 * Used currently in CSVReader to convert delimiters
	 *
	 * @param string $content String to encode
	 * @param boolean $be Big endian-encoding
	 * @param boolean $add_bom Add byte-order marker
	 * @return string Encoded in UTF-16
	 */
	public static function encode(string $content, bool $be = true, bool $add_bom = true): string {
		$n = strlen($content);
		$result = '';
		if ($add_bom) {
			$result .= $be ? chr(0xFE) . chr(0xFF) : chr(0xFF) . chr(0xFE);
		}
		if ($be) {
			for ($i = 0; $i < $n; $i++) {
				$c = ord($content[$i]);
				$result .= chr(0x00) . chr($c);
			}
		} else {
			for ($i = 0; $i < $n; $i++) {
				$c = ord($content[$i]);
				$result .= chr($c) . chr(0x00);
			}
		}
		return $result;
	}

	/**
	 * Syntactic sugar function?
	 *
	 * @param string $mixed
	 * @param bool|null $be
	 * @return string
	 */
	public static function to_iso8859(string $mixed, bool &$be = null): string {
		return UTF16::decode($mixed, $be);
	}
}
