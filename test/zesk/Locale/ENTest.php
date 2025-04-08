<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace test\zesk\Locale;

use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class ENTest extends TestCase
{
	public function test_everything(): void
	{
		$testObject = $this->application->localeFactory('en');

		$testObject->formatDate();

		$testObject->formatDateTime();

		$include_seconds = false;
		$testObject->formatTime($include_seconds);

		$word = 'sheep';
		$count = 2;
		$this->assertEquals('sheep', $testObject->plural($word, $count));

		$word = 'hour away';
		$caps = false;
		$this->assertEquals('an', $testObject->indefiniteArticle($word, []));
		$this->assertEquals('An', $testObject->indefiniteArticle($word, ['capitalize' => true]));
		$this->assertEquals('An', $testObject->indefiniteArticle('HOUR AWAY', ['capitalize' => true]));

		$x = [
			'lions',
			'tigers',
			'bears',
		];
		$conj = 'and';
		$this->assertEquals('lions, tigers, and bears', $testObject->conjunction($x, $conj));

		$s = 'word';
		$n = 3;
		$locale = null;
		$this->assertEquals('3 words', $testObject->pluralNumber($s, $n));
	}

	public static function data_ordinal_tests(): array
	{
		return [
			[
				1,
				'1st',
			],
			[
				1,
				'1st',
			],
			[
				0,
				'0th',
			],
			[
				0,
				'0th',
			],
			[
				11,
				'11th',
			],
			[
				101,
				'101st',
			],
			[
				2,
				'2nd',
			],
			[
				12,
				'12th',
			],
			[
				21,
				'21st',
			],
			[
				22,
				'22nd',
			],
			[
				99,
				'99th',
			],
			[
				100000001,
				'100000001st',
			],
		];
	}

	/**
	 * @dataProvider data_ordinal_tests
	 */
	public function test_ordinal(int $input, string $result): void
	{
		$testObject = $this->application->localeRegistry('en');
		$this->assertEquals($testObject->ordinal($input), $result);
	}
}
