<?php
declare(strict_types=1);
namespace zesk;

class UTF8_Test extends UnitTest
{
	public static function data_sampleUTF8(): array
	{
		return [['abc'], ['The quick brown fox jumped over the lazy dog.'], ['OMG I Totally ❤️ that'], ];
	}

	public static function data_to_iso8859(): array
	{
		return [
			['français', 'fran' . chr(0xE7) . 'ais'],
		];
	}

	/**
	 * @dataProvider data_to_iso8859
	 * @param string $utf8
	 * @param string $iso8859
	 * @return void
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
	 */
	public function test_from_charset($utf8, $iso8859): void
	{
		$this->assertEquals($utf8, UTF8::fromCharacterSet($iso8859, 'iso-8859-1'));
	}
}
