<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Locale;

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
		$testx = $this->application->localeFactory('en');

		$testx->formatDate();

		$testx->formatDateTime();

		$include_seconds = false;
		$testx->formatTime($include_seconds);

		$word = 'sheep';
		$count = 2;
		$this->assertEquals('sheep', $testx->plural($word, $count));

		$word = 'hour away';
		$caps = false;
		$this->assertEquals('an', $testx->indefiniteArticle($word, []));
		$this->assertEquals('An', $testx->indefiniteArticle($word, ['capitalize' => true]));
		$this->assertEquals('An', $testx->indefiniteArticle('HOUR AWAY', ['capitalize' => true]));

		$x = [
			'lions',
			'tigers',
			'bears',
		];
		$conj = 'and';
		$this->assertEquals('lions, tigers, and bears', $testx->conjunction($x, $conj));

		$s = 'word';
		$n = 3;
		$locale = null;
		$this->assertEquals('3 words', $testx->pluralNumber($s, $n));
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
		$testx = $this->application->localeRegistry('en');
		$this->assertEquals($testx->ordinal($input), $result);
	}
}
