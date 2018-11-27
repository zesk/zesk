<?php
namespace zesk;

class hookable_test_a extends Hookable {
	public function hookit(array $data) {
		$data['hookit'] = microtime(true);
		$data = $this->call_hook('test', $data);
		return $data;
	}
}
class Hookable_Test extends Test_Unit {
	public static $counter = 0;

	public function test_hook_series() {
		$this->application->hooks->add('zesk\\hookable_test_a::test', __CLASS__ . '::hook_test1');
		$this->application->hooks->add('zesk\\hookable_test_a::test', __CLASS__ . '::hook_test2');
		$this->application->hooks->add('zesk\\hookable_test_a::test', __CLASS__ . '::hook_test3');

		$data = array(
			'dude' => 'as in the',
		);

		$a = new hookable_test_a($this->application);
		$result = $a->hookit($data);

		var_dump($result);
	}

	public static function hook_test1(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test1'] = "1";
		$data['test1a'] = "1a";
		return $data;
	}

	public static function hook_test2(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test2'] = "2";
		return $data;
	}

	public static function hook_test3(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test3'] = "three";
		$data['test1a'] = "three not 1a";
		$data['dude'] = "the";
		$data['nice'] = "pony";
		return $data;
	}

	public function test_options_inherit() {
		$options = new hookable_test_a($this->application);

		$conf = $this->application->configuration;

		$conf->path_set(hookable_test_a::class . "::test1", "test1");
		$conf->path_set(hookable_test_a::class . "::test2", "test2");
		$conf->path_set(hookable_test_a::class . "::test3array", array(
			0,
			false,
			null,
		));

		// No longer honored/merged as of 2016-01-01
		$conf->path_set(hookable_test_a::class . "::options", $optoptions = array(
			"test1" => "test2",
			"more" => "dude",
		));

		$options->inherit_global_options();

		$options = $options->option();
		$this->assert_array_key_exists($options, "test1");
		$this->assert_array_key_not_exists($options, "more");

		$this->assert_equal($options, array(
			"test1" => "test1",
			"test2" => "test2",
			"test3array" => array(
				0,
				false,
				null,
			),
			"options" => $optoptions,
		));
	}
}
