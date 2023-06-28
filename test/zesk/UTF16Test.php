<?php
declare(strict_types=1);

namespace zesk;

class UTF16Test extends UnitTest
{
	public static function data_sampleUTF8(): array
	{
		return [['abc'], ['The quick brown fox jumped over the lazy dog.'], ['OMG I Totally ❤️ that'], ];
	}

	/**
	 * @return void
	 * @dataProvider data_sampleUTF8
	 */
	public function test_decode_encode(string $str): void
	{
		$encoded_true = UTF16::encode($str, true, true);
		$encoded_false = UTF16::encode($str, false, true);
		$decoded_true = UTF16::decode($encoded_true, $bom_true);
		$decoded_false = UTF16::decode($encoded_false, $bom_false);
		$this->assertEquals($str, $decoded_true);
		$this->assertTrue($bom_true);
		$this->assertEquals($str, $decoded_false);
		$this->assertFalse($bom_false);
	}

	/**
	 * @param $utf8string
	 * @param $utf16string
	 * @return void
	 * @dataProvider data_test_to_utf8
	 */
	public function test_to_utf8($utf8string, $bom_expected, $utf16string): void
	{
		$this->assertEquals($utf8string, UTF16::toUTF8($utf16string, $bom));
		$this->assertEquals($bom_expected, $bom);
	}

	public static function data_test_to_utf8(): array
	{
		return [
			['ABC', true, chr(0xfe) . chr(0xff) . chr(0) . chr(65) . chr(0) . chr(66) . chr(0) . chr(67),
			],
		];
	}
}
