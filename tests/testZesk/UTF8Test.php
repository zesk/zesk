<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception\FileParseException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParseException;
use zesk\UTF8;

/**
 * @coversDefaultClass \zesk\UTF8
 * @uses UTF8
 */
class UTF8Test extends TestCase
{
	public static function data_sampleUTF8(): array
	{
		return [
			['abc'],
			['The quick brown fox jumped over the lazy dog.'],
			['OMG I Totally ❤️ that'],
		];
	}

	public static function data_to_iso8859(): array
	{
		return [
			[
				'français',
				'fran' . chr(0xE7) . 'ais',
			],
		];
	}

	/**
	 * @dataProvider data_to_iso8859
	 * @param string $utf8
	 * @param string $iso8859
	 * @return void
	 * @covers ::toISO8859
	 */
	public function test_to_iso8859(string $utf8, string $iso8859): void
	{
		$this->assertEquals($iso8859, UTF8::toISO8859($utf8));
	}

	/**
	 * @dataProvider data_to_iso8859
	 * @param string $utf8
	 * @param string $iso8859
	 * @return void
	 * @throws FileParseException
	 * @throws KeyNotFound
	 * @throws ParseException
	 * @covers ::fromCharacterSet
	 */
	public function test_from_charset(string $utf8, string $iso8859): void
	{
		$this->assertEquals($utf8, UTF8::fromCharacterSet($iso8859, 'iso-8859-1'));
	}
}
