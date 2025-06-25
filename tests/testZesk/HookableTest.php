<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception\ConfigurationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SystemException;
use zesk\JSON;

/**
 * @covers \zesk\Hookable
 * @coversDefaultClass  \zesk\Hookable
 */
class HookableTest extends TestCase
{
	public static int $counter = 0;

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @covers \zesk\Hookable::invokeFilters ::invokeObjectFilters
	 */
	public function test_hook_series(): void
	{
		$this->application->configure();
		$this->application->hooks->registerFilter(HookableTestA::HOOK_TEST, $this->hook_test1(...));

		$data = [
			'dude' => 'as in the',
		];
		$this->application->classes->register([
			HookableTestA::class,
			HookableTestB::class,
		]);

		$a = new HookableTestA($this->application);
		$result = $a->runOurTestHook($data);

		$this->assertArrayHasKeys([
			'dude',
			'ourTestHook',
			'test1',
			'test2',
			'test_a',
		], $result, JSON::encodePretty($result));
		$this->assertEquals('as in the', $result['dude']);
		$this->assertEquals('1', $result['test1']);
		$this->assertEquals('2', $result['test2']);
		$this->assertIsFloat($result['ourTestHook']);

		$data = $a->invokeObjectFilters(HookableTestA::HOOK_TEST, $data);
		$this->assertArrayHasKeys([
			'dude',
			'ourTestHook',
			'test1',
			'test2',
			'test_a',
			'test2',
		], $result, JSON::encodePretty($result));

		$b = new HookableTestB($this->application);
		$data = [
			'dude' => 'test_b',
		];
		$data = $b->invokeObjectFilters(HookableTestA::HOOK_TEST, $data);
		$this->assertEquals([
			'dude' => 'the',
			'test_b' => 99,
			'test2' => '2',
			'test3' => 'three',
			'test1a' => 'three not 1a',
			'nice' => 'pony',
			'test_a' => 99,
		], $data);
	}

	public function hook_test1(array $data): array
	{
		$data[__METHOD__] = microtime(true);
		$data['test1'] = '1';
		$data['test1a'] = '1a';
		return $data;
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 * @covers ::inheritConfiguration
	 */
	public function test_options_inherit(): void
	{
		$options = new HookableTestA($this->application);

		$conf = $this->application->configuration;

		$conf->setPath(HookableTestA::class . '::test1', 'test1');
		$conf->setPath(HookableTestA::class . '::test2', 'test2');
		$conf->setPath(HookableTestA::class . '::test3array', [
			0,
			false,
			null,
		]);

		$setOptions = [
			'test1' => 'test2',
			'more' => 'dude',
		];
		// No longer honored/merged as of 2016-01-01
		$conf->setPath(HookableTestA::class . '::options', $setOptions);

		$options->inheritConfiguration();

		$options = $options->options();
		$this->assertArrayHasKey('test1', $options);
		$this->assertArrayNotHasKey('more', $options);

		$this->assertEquals($options, [
			'test1' => 'test1',
			'test2' => 'test2',
			'test3array' => [
				0,
				false,
				null,
			],
			'options' => $setOptions,
		]);
	}
}
