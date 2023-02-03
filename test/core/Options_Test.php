<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use function md5;
use function microtime;

/**
 *
 * @author kent
 *
 */
class Options_Test extends UnitTest {
	public function test_options(): void {
		$init = ['item' => 1, 'thing' => 'sure', 'space underscore_dash-' => 'ok'];
		$mapped = ['item' => 1, 'thing' => 'sure', 'space_underscore_dash_' => 'ok'];
		$options = new Options($init);

		$this->assertEquals($mapped, $options->options());

		$selected = ['item' => 2, 'thing' => 'not', 'another' => 'yes'];
		$this->assertEquals(['item' => 1, 'thing' => 'sure', 'another' => 'yes'], $options->options($selected));

		$this->assertEquals(['item', 'thing', 'space_underscore_dash_'], $options->optionKeys());


		$key = md5(microtime());
		$options->setOption($key, 0);
		$this->assertEquals(0, $options->option($key));

		$this->assertTrue($options->hasOption($key, false));
		$this->assertFalse($options->hasOption($key, true));
		$this->assertFalse($options->hasOption('notfound', true));
		$this->assertFalse($options->hasOption('notfound', false));

		$options->setOption($key, 'true');
		$this->assertTrue($options->optionBool($key));
		$options->setOption($key, '1');
		$this->assertTrue($options->optionBool($key));

		$this->assertEquals(1, $options->optionInt($key, 123));
		$this->assertEquals(123, $options->optionInt('unknown', 123));
		$this->assertEquals(0, $options->optionInt('unknown'));

		$options->setOption($key, '674.23e12');
		$this->assertEquals(6.7423E+14, $options->optionFloat($key));
		$options->setOption($key, '0.0001');
		$this->assertEquals(0.0001, $options->optionFloat($key));

		$options->setOption($key, []);
		$this->assertEquals([], $options->optionArray($key));
		$options->setOption($key, 'thing1');
		$this->assertEquals(23.3, $options->optionFloat($key, 23.3));
		$options->optionAppend($key, 'thing2');
		$options->optionAppend($key, '99');
		$this->assertEquals(['thing1', 'thing2', '99'], $options->optionArray($key));
		$this->assertEquals(['thing1', 'thing2', '99'], $options->optionIterable($key));

		$options->setOption($key, ['thing1', 'thing2', '99']);
		$this->assertEquals(['thing1', 'thing2', '99'], $options->optionIterable($key, []));

		$this->assertEquals('abcd', $options->optionPath([], 'abcd'));
	}

	public function data_hasAny(): array {
		$testOptions = new Options(['one' => 1, 'two' => 2, 'three space' => 3, 'four-dash' => 4, 'five-😀' => 5]);
		return [
			[true, ['one', 'two'], $testOptions], [true, 'one', $testOptions], [true, 'two', $testOptions],
			[true, 'three space', $testOptions], [true, 'three_space', $testOptions],
			[true, 'three-space', $testOptions], [true, 'four dash', $testOptions], [true, 'four-dash', $testOptions],
			[true, 'four_dash', $testOptions], [true, 'five-😀', $testOptions], [false, 'five-😀-', $testOptions],
			[true, ['no', 'no', 'no', 'two'], $testOptions],
		];
	}

	/**
	 * @param bool $expected
	 * @param $hasAny
	 * @param Options $testOptions
	 * @return void
	 * @dataProvider data_hasAny
	 */
	public function test_hasAny(bool $expected, $hasAny, Options $testOptions): void {
		$this->assertEquals($expected, $testOptions->hasAnyOption($hasAny));
	}

	public function test_options_path_simple(): void {
		$opts = new Options();
		$opts->setOptionPath(['a', 'b', 'c', 'd'], '1');
		$opts->setOptionPath(['a', 'b', 'c', 'e'], 1);
		$this->assertEquals(['a' => ['b' => ['c' => ['d' => '1', 'e' => 1, ], ], ], ], $opts->options());
	}

	public function test_options_path(): void {
		$opts = new Options();

		$paths = ['a.a.a', 'a.a.b', 'a.b.c', 'a.b.d', 'a.b.e', 'a.b.f', 'a.c.a', 'b.c.a', 'd.c.a', ];
		foreach ($paths as $path) {
			$opts->setOptionPath(explode('.', $path), $path);
		}
		$expectedOptions = [
			'a' => [
				'a' => [
					'a' => 'a.a.a', 'b' => 'a.a.b',
				], 'b' => [
					'c' => 'a.b.c', 'd' => 'a.b.d', 'e' => 'a.b.e', 'f' => 'a.b.f',
				], 'c' => ['a' => 'a.c.a', ],
			], 'b' => ['c' => ['a' => 'b.c.a', ], ], 'd' => ['c' => ['a' => 'd.c.a', ], ],
		];
		$this->assertEquals($expectedOptions, $opts->options());
		foreach ($paths as $path) {
			$this->assertEquals($path, $opts->optionPath(explode('.', $path)));
		}

		$this->assertNull($opts->optionPath(['a', 'a', 'c'], null));
	}
}
