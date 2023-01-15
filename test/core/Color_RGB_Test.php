<?php
declare(strict_types=1);

namespace zesk;

class Color_RGB_Test extends UnitTest {
	public function test_basics(): void {
		$r = 1;
		$g = 2;
		$b = 255;
		$x = new Color_RGB($r, $g, $b);

		$this->assertEquals($x->__toString(), '0102FF');
	}

	public function data_factory(): array {
		return [
			['010203', '010203', 257, 255], ['000000', 0, 0, 0], ['010203', 1, 2, 3], ['000000', -1, -2, -3],
			['FFFFFF', 257, 257, 255], ['FFFFFF', 'FFFFFF', 257, 255], ['000000', '000000', 0, 0],
			['000000', '000000', 1, 2], ['DEADBE', 'DEADBE', 1, 2], ['000102', 0, 1, 2], ['AAAAAA', 'AAA', 257, 255],
			['000102', [0, 1, 2], 257, 255], ['FFFF02', [256, 257, 2], 257, 255],
			['FFFF02', ['r' => 256, 'g' => 257, 'b' => 2], 257, 255],
			['DEADBE', ['r' => 222, 'g' => 173, 'b' => 190, 'a' => 99], 0, 0],
		];
	}

	/**
	 * @param $expected
	 * @param int|string|array $r
	 * @param int $g
	 * @param int $b
	 * @return void
	 * @throws Exception_Syntax
	 * @dataProvider data_factory
	 */
	public function test_factory($expected, int|string|array $r, int $g, int $b): void {
		$this->assertEquals($expected, Color_RGB::factory($r, $g, $b)->__toString());
	}
}
