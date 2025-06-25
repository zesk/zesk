<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\CSS;
use zesk\Exception\SyntaxException;

/**
 * @uses CSS
 * @coversDefaultClass \zesk\CSS
 */
class CSSTest extends TestCase
{
	public static function data_isColor(): array
	{
		return [
			[
				true,
				'red',
			],
			[
				true,
				'green',
			],
			[
				true,
				'blue',
			],
			[
				true,
				'orange',
			],
			[
				true,
				'red',
			],
			[
				true,
				'#FFF',
			],
			[
				true,
				'rgb(1,2,3)',
			],
			[
				false,
				'cat',
			],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $color
	 * @return void
	 * @dataProvider data_isColor
	 * @covers ::isColor
	 */
	public function test_isColor(bool $expected, string $color): void
	{
		$this->assertEquals($expected, CSS::isColor($color));
	}

	public static function data_colorParseThrow(): array
	{
		return [
			[''],
			['rgb('],
		];
	}

	/**
	 * @param string $text
	 * @return void
	 * @dataProvider data_colorParseThrow
	 * @return void
	 * @throws SyntaxException
	 * @covers ::colorParse
	 */
	public function test_bad_color(string $text): void
	{
		$this->expectException(SyntaxException::class);
		$this->assertEquals(null, CSS::colorParse($text));
	}

	/**
	 * @return void
	 * @covers ::colorParse
	 */
	public function test_colorParse(): void
	{
		$colors = [
			'rgb(1,2,3)' => '1;2;3',
			'rgb(256,2,3)' => '255;2;3',
			'010203' => '1;2;3',
			'#010203' => '1;2;3',
			'#01O203' => null,
			'mistyrose' => '255;228;225',
		];
		foreach ($colors as $text => $result) {
			try {
				$check_result = CSS::colorParse($text);
			} catch (SyntaxException) {
				$this->assertNull($result, "Parse should fail: $text");
				continue;
			}
			$this->assertNotNull($result);
			[
				$r,
				$g,
				$b,
			] = explode(';', $result);
			$rgb = [
				intval($r),
				intval($g),
				intval($b),
			];
			$this->assertEquals($rgb, $check_result, "Color check: $text => $result");
		}
	}

	public static function data_colorLookup(): array
	{
		return [
			['0000ff', 'blue'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $lookup
	 * @return void
	 * @covers ::colorLookup
	 * @dataProvider data_colorLookup
	 */
	public function test_colorLookup(string $expected, string $lookup): void
	{
		$this->assertEquals($expected, CSS::colorLookup($lookup));
	}

	/**
	 * @return array[]
	 */
	public static function data_colorFormat(): array
	{
		return [
			['#FACEBE', [250, 206, 190, ]],
			['#FACE00', [250, 206, 0, ]],
			['#FA00BE', [250, 0, 190, ]],
		];
	}

	/**
	 * @param string $expected
	 * @param array $color
	 * @return void
	 * @dataProvider data_colorFormat
	 * @covers ::colorFormat
	 */
	public function test_colorFormat(string $expected, array $color): void
	{
		$this->assertEquals($expected, CSS::colorFormat($color));
	}

	public static function data_colorNormalize(): array
	{
		return [
			['FFFFFF', '#FFF'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $test
	 * @return void
	 * @throws SyntaxException
	 * @covers ::colorNormalize
	 * @dataProvider data_colorNormalize
	 */
	public function test_colorNormalize(string $expected, string $test): void
	{
		$this->assertEquals($expected, CSS::colorNormalize($test));
	}

	public static function data_colorNormalizeThrow(): array
	{
		return [
			[''],
		];
	}

	/**
	 * @param string $text
	 * @return void
	 * @throws SyntaxException
	 * @covers ::colorNormalize
	 * @dataProvider data_colorNormalizeThrow
	 */
	public function test_color_normalize_blank(string $text): void
	{
		$this->expectException(SyntaxException::class);
		CSS::colorNormalize($text);
	}

	/**
	 * @return array[]
	 */
	public static function data_colorTableColors(): array
	{
		return [
			['lightgray'],
			['lightgrey'],
			['orange'],
			['black'],
			['salmon'],
		];
	}

	/**
	 * @param string $key
	 * @return void
	 * @dataProvider data_colorTableColors
	 * @covers ::colorTable
	 */
	public function test_color_table(string $key): void
	{
		$result = CSS::colorTable();
		$this->assertArrayHasKey($key, $result);
	}

	/**
	 * @dataProvider data_rgbToHex
	 * @param string $expected
	 * @param array $rgb
	 * @return void
	 * @covers ::rgbToHex
	 */
	public function test_rgbToHex(string $expected, array $rgb): void
	{
		$this->assertEquals($expected, CSS::rgbToHex($rgb));
	}

	public static function data_rgbToHex(): array
	{
		return [
			[
				'000102',
				[
					0,
					1,
					2,
				],
			],
			[
				'FFFFFF',
				[
					255,
					255,
					255,
				],
			],
			[
				'FFFFFF',
				[
					256,
					255,
					255,
				],
			],
		];
	}
}
