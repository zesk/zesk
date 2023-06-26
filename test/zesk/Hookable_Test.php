<?php
declare(strict_types=1);

namespace zesk;

class hookable_test_a extends Hookable {
	public const HOOK_TEST = __CLASS__ . '::test';

	public static Application $app;

	public function hookit(array $data) {
		$data['hookit'] = microtime(true);
		$data = $this->invokeFilters(self::HOOK_TEST, $data);
		return $data;
	}

	public static function appendTracking(string $whatever): void {
		$calls = Types::toArray(self::$app->configuration->get('hookable'));
		$calls[] = $whatever;
		self::$app->configuration->set('hookable', $calls);
	}

	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public static function doit(array $data): array {
		$data['test_a'] = 99;
		return $data;
	}
}

class hookable_test_b extends hookable_test_a {
	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public function requiresObjectHookB(array $data): array {
		$data['test_b'] = 99;
		return $data;
	}

	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public static function hook_test2(array $data) {
		$data['test2'] = '2';
		return $data;
	}

	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public function hook_test3(array $data) {
		$data['test3'] = 'three';
		$data['test1a'] = 'three not 1a';
		$data['dude'] = 'the';
		$data['nice'] = 'pony';
		return $data;
	}
}

class Hookable_Test extends UnitTest {
	public static $counter = 0;

	public function test_hook_series(): void {
		$this->application->configure();
		$this->application->hooks->registerFilter(hookable_test_a::HOOK_TEST, $this->hook_test1(...));

		$data = [
			'dude' => 'as in the',
		];
		$this->application->classes->register([hookable_test_a::class, hookable_test_b::class]);

		$a = new hookable_test_a($this->application);
		$result = $a->hookit($data);

		$this->assertArrayHasKeys([
			'dude', 'hookit', 'test1', 'test2', 'test_a',
		], $result, JSON::encodePretty($result));
		$this->assertEquals('as in the', $result['dude']);
		$this->assertEquals('1', $result['test1']);
		$this->assertEquals('2', $result['test2']);
		$this->assertIsFloat($result['hookit']);

		$data = $a->invokeObjectFilters(hookable_test_a::HOOK_TEST, $data);
		$this->assertArrayHasKeys([
			'dude', 'hookit', 'test1', 'test2', 'test_a', 'test2',
		], $result, JSON::encodePretty($result));

		$b = new hookable_test_b($this->application);
		$data = [
			'dude' => 'test_b',
		];
		$data = $b->invokeObjectFilters(hookable_test_a::HOOK_TEST, $data);
		$this->assertEquals([
			'dude' => 'the', 'test_b' => 99, 'test2' => '2', 'test3' => 'three', 'test1a' => 'three not 1a',
			'nice' => 'pony', 'test_a' => 99,
		], $data);

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

	public function hook_test1(array $data) {
		$data[__METHOD__] = microtime(true);
		$data['test1'] = '1';
		$data['test1a'] = '1a';
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
