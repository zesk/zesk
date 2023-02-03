<?php
declare(strict_types=1);

namespace zesk;

class CSS_Test extends UnitTest {
	/**
	 * @return void@
	 */
	public function test_bad_color(): void {
		$text = '';
		$this->expectException(Exception_Syntax::class);
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
			} catch (Exception_Syntax $e) {
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

	public function test_colorNormalize(): void {
		$this->assertEquals('FFFFFF', CSS::colorNormalize('#FFF', 'nope'));
	}

	/**
	 * @return void
	 */
	public function test_color_normalize_blank(): void {
		$this->expectException(Exception_Syntax::class);
		CSS::colorNormalize('', 'nope');
	}

	public function test_color_table(): void {
		CSS::color_table();
	}

	public function test_rgbToHex(): void {
		$rgb = null;
		$default = null;
		$this->assertEquals('000102', CSS::rgbToHex([0, 1, 2]));
	}
}
