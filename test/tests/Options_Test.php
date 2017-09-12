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
	function test_options() {
		$options = false;
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
	function test_options_path_simple() {
		$opts = new Options();
		$opts->set_option_path("a.b.c.d", "1");
		$opts->set_option_path("a.b.c.e", 1);
		$this->assert_arrays_equal($opts->option(), array(
			'a' => array(
				'b' => array(
					'c' => array(
						'd' => "1",
						'e' => 1
					)
				)
			)
		));
	}
	function test_options_path() {
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
			"d.c.a"
		);
		foreach ($paths as $path) {
			$opts->set_option_path($path, $path);
		}
		$this->assert_arrays_equal($opts->option(), array(
			'a' => array(
				'a' => array(
					'a' => 'a.a.a',
					'b' => 'a.a.b'
				),
				'b' => array(
					'c' => 'a.b.c',
					'd' => 'a.b.d',
					'e' => 'a.b.e',
					'f' => 'a.b.f'
				),
				'c' => array(
					'a' => 'a.c.a'
				)
			),
			'b' => array(
				'c' => array(
					'a' => 'b.c.a'
				)
			),
			'd' => array(
				'c' => array(
					'a' => 'd.c.a'
				)
			)
		));
		foreach ($paths as $path) {
			$this->assert_equal($opts->option_path($path), $path);
		}
		
		$this->assert_null($opts->option_path("a.a.c", null));
	}
	function test_options_inherit() {
		$options = new Options();
		
		$conf = $this->application->configuration;
		
		$conf->path_set("zesk\\Options::test1", "test1");
		$conf->path_set("zesk\\Options::test2", "test2");
		$conf->path_set("zesk\\Options::test3array", array(
			0,
			false,
			null
		));
		
		// No longer honored/merged as of 2016-01-01
		$conf->path_set("zesk\\Options::options", $optoptions = array(
			"test1" => "test2",
			"more" => "dude"
		));
		
		$options->inherit_global_options($this->application);
		
		$options = $options->option();
		$this->assert_array_key_exists($options, "test1");
		$this->assert_array_key_not_exists($options, "more");
		
		$this->assert_equal($options, array(
			"test1" => "test1",
			"test2" => "test2",
			"test3array" => array(
				0,
				false,
				null
			),
			"options" => $optoptions
		));
	}
}

