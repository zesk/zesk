<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @author kent
 */
class Hexadecimal {
	/**
	 * Hex character codes
	 *
	 * @var string
	 */
	public const codes = '0123456789ABCDEF';

	/**
	 * Decode a hexadecimal string
	 * @todo Doesn't PHP offer this natively?
	 * @param string $hexadecimal
	 * @return string
	 */
	public static function decode(string $hexadecimal) {
		$r = '';
		$x = preg_replace('/[^' . self::codes . ']/', '', strtoupper($hexadecimal));
		$n = strlen($x);
		for ($i = 0; $i < $n; $i = $i + 2) {
			$r .= chr(strpos(self::codes, $x[$i]) << 4 | strpos(self::codes, $x[$i + 1]));
		}
		return $r;
	}

	/**
	 * Encode a hexadecimal string
	 *
	 * @todo Doesn't PHP offer this natively?
	 * @param string $plaintext
	 * @return string Hexadecimal-encoded string
	 */
	public static function encode(string $plaintext): string {
		$h = self::codes;
		$r = '';
		$n = strlen($plaintext);
		for ($i = 0; $i < $n; $i++) {
			$r .= $h[(ord($plaintext[$i]) >> 4) & 0x0F] . $h[ord($plaintext[$i]) & 0x0F];
		}
		return $r;
	}
}
