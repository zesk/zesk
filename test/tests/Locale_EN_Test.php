<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Lang_en_test extends Test_Unit {
	public function test_everything(): void {
		$testx = $this->application->localeFactory('en');

		$testx->date_format();

		$testx->datetime_format();

		$include_seconds = false;
		$testx->time_format($include_seconds);

		$word = 'sheep';
		$count = 2;
		$this->assert($testx->plural($word, $count) === 'sheep');

		$word = 'hour away';
		$caps = false;
		$this->assert_equal($testx->indefinite_article($word, []), 'an');
		$this->assert_equal($testx->indefinite_article($word, ['capitalize' => true]), 'An');
		$this->assert_equal($testx->indefinite_article('HOUR AWAY', ['capitalize' => true]), 'An');

		$x = [
			'lions',
			'tigers',
			'bears',
		];
		$conj = 'and';
		$this->assert_equal($testx->conjunction($x, $conj), 'lions, tigers, and bears');

		$s = 'word';
		$n = 3;
		$locale = null;
		$this->assert_equal($testx->plural_number($s, $n), '3 words');
	}

	public function ordinal_tests() {
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
	 * @dataProvider ordinal_tests
	 */
	public function test_ordinal(int $input, string $result): void {
		$testx = $this->application->localeRegistry('en');
		$this->assert_equal($testx->ordinal($input), $result);
	}
}
