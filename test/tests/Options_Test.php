<?php declare(strict_types=1);
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
	public function test_options(): void {
		$options = [];
		$testx = new Options($options);

		$testx->__sleep();

		$testx->options();

		$selected = false;
		$testx->options_include($selected);

		$testx->option_keys();

		$name = null;
		$checkEmpty = false;
		$testx->hasOption($name, $checkEmpty);

		$mixed = null;
		$value = false;
		$overwrite = true;
		$testx->setOption($mixed, $value, $overwrite);

		$name = null;
		$default = false;
		$testx->option($name, $default);

		$name = null;
		$default = false;
		$testx->optionBool($name, $default);

		$name = null;
		$default = false;
		$testx->optionInt($name, $default);

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

	public function test_options_path_simple(): void {
		$opts = new Options();
		$opts->setOption_path("a.b.c.d", "1");
		$opts->setOption_path("a.b.c.e", 1);
		$this->assert_arrays_equal($opts->options(), [
			'a' => [
				'b' => [
					'c' => [
						'd' => "1",
						'e' => 1,
					],
				],
			],
		]);
	}

	public function test_options_path(): void {
		$opts = new Options();

		$paths = [
			"a.a.a",
			"a.a.b",
			"a.b.c",
			"a.b.d",
			"a.b.e",
			"a.b.f",
			"a.c.a",
			"b.c.a",
			"d.c.a",
		];
		foreach ($paths as $path) {
			$opts->setOption_path($path, $path);
		}
		$this->assert_arrays_equal($opts->options(), [
			'a' => [
				'a' => [
					'a' => 'a.a.a',
					'b' => 'a.a.b',
				],
				'b' => [
					'c' => 'a.b.c',
					'd' => 'a.b.d',
					'e' => 'a.b.e',
					'f' => 'a.b.f',
				],
				'c' => [
					'a' => 'a.c.a',
				],
			],
			'b' => [
				'c' => [
					'a' => 'b.c.a',
				],
			],
			'd' => [
				'c' => [
					'a' => 'd.c.a',
				],
			],
		]);
		foreach ($paths as $path) {
			$this->assert_equal($opts->option_path($path), $path);
		}

		$this->assert_null($opts->option_path("a.a.c", null));
	}
}
