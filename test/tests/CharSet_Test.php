<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

class CharSet_Test extends Test_Unit {
	public function test_supported(): void {
		$charset = null;
		$result = charset::supported($charset);
		$this->application->development(true);
		$this->assert(is_array($result));
		foreach ($result as $charset) {
			$this->assert(charset::supported($charset) === true);
			$this->assert(charset::supported($charset . '-No') === false);
			$result = charset::to_utf8("Hello, world", $charset);
		}
	}

	public function to_utf8(): void {
		$every_char = "";
		for ($i = 32; $i <= 127; $i++) {
			$every_char .= chr($i);
		}

		$all_charsets = charset::supported();

		//echo Text::lalign("SAMPLE", 20) . $every_char . "\n";
		foreach ($all_charsets as $charset) {
			$result = charset::to_utf8($every_char, $charset);
			//echo Text::lalign($charset, 20) . $result . "\n";
			if (in_array($charset, [
				"CP037",
				"CP1026",
				"CP424",
				"CP500",
				"CP864",
				"CP875",
				"GSM0338",
				"STDENC",
				"SYMBOL",
				"US-ASCII-QUOTES",
				"ZDINGBAT",
			])) {
				$this->assert($result !== $every_char, "Failed for charset $charset");
			} else {
				$this->assert($result === $every_char, "Failed for charset $charset");
			}
		}

		$tests = [
			[
				chr(0xC1) . chr(0xC2) . chr(0xC3) . chr(0xC4),
				"CP424",
				"ABCD",
			],
			[
				chr(0xE7),
				"ISO-8859-10",
				UTF16::to_utf8(chr(0x01) . chr(0x2F)),
			],
		];
		foreach ($tests as $test) {
			[$data, $charset, $expect] = $test;
			$result = charset::to_utf8($data, $charset);
			echo "$data\n";
			echo "$charset\n";
			echo "$result\n";
			echo "-\n";
			$this->assert($result === $expect, "$result === $expect");
		}
	}
}
