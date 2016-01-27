<?php
class test_css extends Test_Unit {

	function test_color_parse() {
		$text = null;
		$default = null;
		css::color_parse($text, $default);

		$colors = array(
			'rgb(1,2,3)' => '1;2;3',
			'rgb(256,2,3)' => null,
			'010203' => '1;2;3',
			'#010203' => '1;2;3',
			'#01O203' => null,
			"mistyrose" => "255;228;225"
		);
		foreach ($colors as $text => $result) {
			$check_result = css::color_parse($text);
			if ($result === null) {
				$this->assert('css::color_parse(\'$text\') === null');
			} else {
				list($r, $g, $b) = explode(";", $result);
				//		$aresult = array('r' => intval($r),'g' => intval($g),'b'=>intval($b));
				$aresult = array(
					intval($r),
					intval($g),
					intval($b)
				);
				dump($aresult);
				dump($check_result);
				$this->assert_arrays_equal($aresult, $check_result, "Color check: $text => $result");
			}
		}
	}

	function test_color_lookup() {
		$text = null;
		$default = null;
		css::color_lookup($text, $default);
	}

	function test_color_format() {
		$rgb = null;
		$default = null;
		css::color_format($rgb, $default);
	}

	function test_color_normalize() {
		$text = null;
		$default = null;
		css::color_normalize($text, $default);
	}

	function test_color_table() {
		css::color_table();
	}

	function test_rgb_to_hex() {
		$rgb = null;
		$default = null;
		css::rgb_to_hex($rgb, $default);
	}
}
