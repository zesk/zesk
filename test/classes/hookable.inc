<?php
class hookable_test_a extends Hookable {
	function hookit(array $data) {
		$data['hookit'] = microtime(true);
		$data = $this->hook('test', $data);
		return $data;
	}
}
class test_hookable extends Test_Unit {
	public static $counter = 0;

	function test_hook_series() {
		zesk()->hooks->add('hookable_test_a::test', __CLASS__ . '::hook_test1');
		zesk()->hooks->add('hookable_test_a::test', __CLASS__ . '::hook_test2');
		zesk()->hooks->add('hookable_test_a::test', __CLASS__ . '::hook_test3');

		$data = array(
			'dude' => 'as in the'
		);

		$a = new hookable_test_a();
		$result = $a->hookit($data);

		var_dump($result);
	}
	static function hook_test1(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test1'] = "1";
		$data['test1a'] = "1a";
		return $data;
	}
	static function hook_test2(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test2'] = "2";
		return $data;
	}
	static function hook_test3(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test3'] = "three";
		$data['test1a'] = "three not 1a";
		$data['dude'] = "the";
		$data['nice'] = "pony";
		return $data;
	}
}
