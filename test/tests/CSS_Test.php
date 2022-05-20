<?php
declare(strict_types=1);

namespace zesk;

class CSS_Test extends Test_Unit {
	/**
	 * @return void@
	 * @expectedException zesk\Exception_Syntax
	 */
	public function test_bad_color(): void {
		$text = '';
		$default = null;
		$this->assertEquals(null, CSS::color_parse($text, $default));
	}

	public function test_color_parse(): void {
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
				$check_result = CSS::color_parse($text);
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
			dump($aresult);
			dump($check_result);
			$this->assert_arrays_equal($aresult, $check_result, "Color check: $text => $result");
		}
	}

	public function test_color_lookup(): void {
		$text = 'blue';
		$this->assertEquals('0000ff', CSS::color_lookup($text));
	}

	public function test_color_format(): void {
		$rgb = [250, 206, 190];
		$this->assertEquals('#FACEBE', CSS::color_format($rgb));
	}

	public function test_color_normalize(): void {
		$this->assertEquals('FFFFFF', CSS::color_normalize('#FFF', 'nope'));
	}

	/**
	 * @return void
	 * @expectedException zesk\Exception_Syntax
	 */
	public function test_color_normalize_blank(): void {
		CSS::color_normalize('', 'nope');
	}

	public function test_color_table(): void {
		CSS::color_table();
	}

	public function test_rgb_to_hex(): void {
		$rgb = null;
		$default = null;
		$this->assertEquals('000102', CSS::rgb_to_hex([0, 1, 2]));
	}
}
