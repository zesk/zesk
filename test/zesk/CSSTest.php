<?php
declare(strict_types=1);

namespace zesk;

use zesk\Exception\SyntaxException;

class CSSTest extends UnitTest {
	public static function data_isColor(): array {
		return [
			[true, 'red'],
			[true, 'green'],
			[true, 'blue'],
			[true, 'orange'],
			[true, 'red'],
			[true, '#FFF'],
			[true, 'rgb(1,2,3)'],
			[false, 'cat'],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $color
	 * @return void
	 * @dataProvider data_isColor
	 */
	public function test_isColor(bool $expected, string $color): void {
		$this->assertEquals($expected, CSS::isColor($color));
	}

	/**
	 * @return void@
	 */
	public function test_bad_color(): void {
		$text = '';
		$this->expectException(SyntaxException::class);
		$this->assertEquals(null, CSS::colorParse($text));
	}

	public function test_colorParse(): void {
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
			} catch (SyntaxException $e) {
				$this->assertNull($result, "Parse should fail: $text");
				continue;
			}
			$this->assertNotNull($result);
			[$r, $g, $b] = explode(';', $result);
			//		$aresult = array('r' => intval($r),'g' => intval($g),'b'=>intval($b));
			$aresult = [
				intval($r),
				intval($g),
				intval($b),
			];
			$this->assertEquals($aresult, $check_result, "Color check: $text => $result");
		}
	}

	public function test_colorLookup(): void {
		$text = 'blue';
		$this->assertEquals('0000ff', CSS::colorLookup($text));
	}

	public function test_colorFormat(): void {
		$rgb = [250, 206, 190];
		$this->assertEquals('#FACEBE', CSS::colorFormat($rgb));
	}

	/**
	 * @return void
	 * @throws SyntaxException
	 */
	public function test_colorNormalize(): void {
		$this->assertEquals('FFFFFF', CSS::colorNormalize('#FFF'));
	}

	/**
	 * @return void
	 */
	public function test_color_normalize_blank(): void {
		$this->expectException(SyntaxException::class);
		CSS::colorNormalize('');
	}

	public static function data_colorTableColors(): array {
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
	 */
	public function test_color_table(string $key): void {
		$result = CSS::colorTable();
		$this->assertArrayHasKey($key, $result);
	}

	/**
	 * @dataProvider data_rgbToHex
	 * @param string $expected
	 * @param array $rgb
	 * @return void
	 */
	public function test_rgbToHex(string $expected, array $rgb): void {
		$this->assertEquals($expected, CSS::rgbToHex($rgb));
	}

	public static function data_rgbToHex(): array {
		return [
			['000102', [0, 1, 2]],
			['FFFFFF', [255, 255, 255]],
			['FFFFFF', [256, 255, 255]],
		];
	}
}
