<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Options_Test extends Test_Unit {
	public function test_options() {
		$options = array();
		$testx = new Options($options);

		$testx->__sleep();

		$testx->option();

		$remove = false;
		$testx->options_exclude($remove);

		$selected = false;
		$testx->options_include($selected);

		$testx->option_keys();

		$name = null;
		$checkEmpty = false;
		$testx->has_option($name, $checkEmpty);

		$mixed = null;
		$value = false;
		$overwrite = true;
		$testx->set_option($mixed, $value, $overwrite);

		$name = null;
		$default = false;
		$testx->option($name, $default);

		$name = null;
		$default = false;
		$testx->option_bool($name, $default);

		$name = null;
		$default = false;
		$testx->option_integer($name, $default);

		$name = null;
		$default = false;
		$testx->option_double($name, $default);

		$name = null;
		$default = false;
		$testx->option_array($name, $default);

		$name = null;
		$default = false;
		$delimiter = ';';
		$testx->option_list($name, $default, $delimiter);
	}

	public function test_options_path_simple() {
		$opts = new Options();
		$opts->set_option_path("a.b.c.d", "1");
		$opts->set_option_path("a.b.c.e", 1);
		$this->assert_arrays_equal($opts->option(), array(
			'a' => array(
				'b' => array(
					'c' => array(
						'd' => "1",
						'e' => 1,
					),
				),
			),
		));
	}

	public function test_options_path() {
		$opts = new Options();

		$paths = array(
			"a.a.a",
			"a.a.b",
			"a.b.c",
			"a.b.d",
			"a.b.e",
			"a.b.f",
			"a.c.a",
			"b.c.a",
			"d.c.a",
		);
		foreach ($paths as $path) {
			$opts->set_option_path($path, $path);
		}
		$this->assert_arrays_equal($opts->option(), array(
			'a' => array(
				'a' => array(
					'a' => 'a.a.a',
					'b' => 'a.a.b',
				),
				'b' => array(
					'c' => 'a.b.c',
					'd' => 'a.b.d',
					'e' => 'a.b.e',
					'f' => 'a.b.f',
				),
				'c' => array(
					'a' => 'a.c.a',
				),
			),
			'b' => array(
				'c' => array(
					'a' => 'b.c.a',
				),
			),
			'd' => array(
				'c' => array(
					'a' => 'd.c.a',
				),
			),
		));
		foreach ($paths as $path) {
			$this->assert_equal($opts->option_path($path), $path);
		}

		$this->assert_null($opts->option_path("a.a.c", null));
	}
}
