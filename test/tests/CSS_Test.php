<?php declare(strict_types=1);
namespace zesk;

class CSS_Test extends Test_Unit {
	public function test_color_parse(): void {
		$text = null;
		$default = null;
		CSS::color_parse($text, $default);

		$colors = [
			'rgb(1,2,3)' => '1;2;3',
			'rgb(256,2,3)' => null,
			'010203' => '1;2;3',
			'#010203' => '1;2;3',
			'#01O203' => null,
			"mistyrose" => "255;228;225",
		];
		foreach ($colors as $text => $result) {
			$check_result = CSS::color_parse($text);
			if ($result === null) {
				$this->assert(__NAMESPACE__ . "\\" . 'CSS::color_parse(\'$text\') === null');
			} else {
				[$r, $g, $b] = explode(";", $result);
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
	}

	public function test_color_lookup(): void {
		$text = null;
		$default = null;
		CSS::color_lookup($text, $default);
	}

	public function test_color_format(): void {
		$rgb = null;
		$default = null;
		CSS::color_format($rgb, $default);
	}

	public function test_color_normalize(): void {
		$text = null;
		$default = null;
		CSS::color_normalize($text, $default);
	}

	public function test_color_table(): void {
		CSS::color_table();
	}

	public function test_rgb_to_hex(): void {
		$rgb = null;
		$default = null;
		CSS::rgb_to_hex($rgb, $default);
	}
}
