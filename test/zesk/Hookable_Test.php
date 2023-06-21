<?php
declare(strict_types=1);

namespace zesk;

class hookable_test_a extends Hookable {
	public const HOOK_TEST = __CLASS__ . '::test';

	public static Application $app;

	public function hookit(array $data) {
		$data['hookit'] = microtime(true);
		$data = $this->invokeHooks(self::HOOK_TEST, $data);
		return $data;
	}

	public static function appendTracking(string $whatever): void {
		$calls = Types::toArray(self::$app->configuration->get('hookable'));
		$calls[] = $whatever;
		self::$app->configuration->set('hookable', $calls);
	}

	public static function doit(): void {
		self::appendTracking(__METHOD__);
	}
}

class hookable_test_b extends hookable_test_a {
	public static function doit(): void {
		self::appendTracking(__METHOD__);
	}
}

class Hookable_Test extends UnitTest {
	public static $counter = 0;

	public function test_hook_series(): void {
		$this->application->hooks->add('zesk\\hookable_test_a::test', __CLASS__ . '::hook_test1');
		$this->application->hooks->add('zesk\\hookable_test_a::test', __CLASS__ . '::hook_test2');
		$this->application->hooks->add('zesk\\hookable_test_a::test', __CLASS__ . '::hook_test3');

		$data = [
			'dude' => 'as in the',
		];

		$a = new hookable_test_a($this->application);
		$result = $a->hookit($data);

		$this->assertArrayHasKeys(['dude', 'hookit', 'test1', 'test2', 'test3', 'nice'], $result);
		$this->assertEquals('the', $result['dude']);
		$this->assertEquals('1', $result['test1']);
		$this->assertEquals('2', $result['test2']);
		$this->assertEquals('three', $result['test3']);
		$this->assertEquals('pony', $result['nice']);
		$this->assertIsFloat($result['hookit']);


		/*array(10) {
  ["dude"]=>
  string(3) "the"
  ["hookit"]=>
  float(1665288601.921211)
  ["zesk\Hookable_Test::hook_test3"]=>
  float(1665288601.921533)
  ["test3"]=>
  string(5) "three"
  ["test1a"]=>
  string(12) "three not 1a"
  ["nice"]=>
  string(4) "pony"
  ["zesk\Hookable_Test::hook_test2"]=>
  float(1665288601.9215)
  ["test2"]=>
  string(1) "2"
  ["zesk\Hookable_Test::hook_test1"]=>
  float(1665288601.921457)
  ["test1"]=>
  string(1) "1"
} */
	}

	public function test_allCall(): void {
		$this->application->classes->register(hookable_test_b::class);
		hookable_test_a::$app = $this->application;
		$this->application->hooks->allCall(hookable_test_a::class . '::doit');
		$results = toArray($this->application->configuration->get('hookable'));
		$this->assertEquals([hookable_test_a::class . '::doit', hookable_test_b::class . '::doit'], $results);
	}

	public static function hook_test1(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test1'] = '1';
		$data['test1a'] = '1a';
		return $data;
	}

	public static function hook_test2(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test2'] = '2';
		return $data;
	}

	public static function hook_test3(hookable_test_a $object, array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test3'] = 'three';
		$data['test1a'] = 'three not 1a';
		$data['dude'] = 'the';
		$data['nice'] = 'pony';
		return $data;
	}

	public function test_options_inherit(): void {
		$options = new hookable_test_a($this->application);

		$conf = $this->application->configuration;

		$conf->setPath(hookable_test_a::class . '::test1', 'test1');
		$conf->setPath(hookable_test_a::class . '::test2', 'test2');
		$conf->setPath(hookable_test_a::class . '::test3array', [
			0, false, null,
		]);

		// No longer honored/merged as of 2016-01-01
		$conf->setPath(hookable_test_a::class . '::options', $optoptions = [
			'test1' => 'test2', 'more' => 'dude',
		]);

		$options->inheritConfiguration();

		$options = $options->options();
		$this->assertArrayHasKey('test1', $options);
		$this->assertArrayNotHasKey('more', $options);

		$this->assertEquals($options, [
			'test1' => 'test1', 'test2' => 'test2', 'test3array' => [
				0, false, null,
			], 'options' => $optoptions,
		]);
	}
}
