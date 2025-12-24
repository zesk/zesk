<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Locale;

use zesk\Exception\ClassNotFound;
use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\Locale\EN
 */
class ENTest extends TestCase
{
	/**
	 * @return void
	 * @throws ClassNotFound
	 * @covers ::formatDateTime ::formatTime
	 */
	public function testFormatDate(): void
	{
		$testObject = $this->application->localeFactory('en');

		$this->assertEquals('{MMMM} {DDD}, {YYYY}', $testObject->formatDate());
	}

	/**
	 * @covers ::formatDateTime
	 * @return void
	 * @throws ClassNotFound
	 */
	public function testFormatTime(): void
	{
		$testObject = $this->application->localeFactory('en');

		$testObject->formatDateTime();

		$this->assertEquals('{12h}:{mm} {AMPM}', $testObject->formatTime());
		$this->assertEquals('{12h}:{mm}:{ss} {AMPM}', $testObject->formatTime(true));
	}

	/**
	 * @covers ::formatDateTime
	 * @return void
	 * @throws ClassNotFound
	 */
	public function testFormatDateTime(): void
	{
		$testObject = $this->application->localeFactory('en');

		$this->assertEquals('{MMMM} {DDD}, {YYYY} {12hh}:{mm} {AMPM}', $testObject->formatDateTime());
	}

	/**
	 * @return void
	 * @covers ::plural
	 * @throws ClassNotFound
	 */
	public function testPlural(): void
	{
		$testObject = $this->application->localeFactory('en');

		$word = 'sheep';
		$count = 2;
		$this->assertEquals('sheep', $testObject->plural($word, $count));
	}

	/**
	 * @return void
	 * @covers ::indefiniteArticle
	 * @throws ClassNotFound
	 */
	public function testIndefiniteArticle(): void
	{
		$testObject = $this->application->localeFactory('en');

		$word = 'hour away';
		$caps = false;
		$this->assertEquals('an', $testObject->indefiniteArticle($word, []));
		$this->assertEquals('An', $testObject->indefiniteArticle($word, ['capitalize' => true]));
		$this->assertEquals('An', $testObject->indefiniteArticle('HOUR AWAY', ['capitalize' => true]));
	}

	/**
	 * @return void
	 * @covers ::fooBar
	 * @throws ClassNotFound
	 */
	public function testConjunction(): void
	{
		$testObject = $this->application->localeFactory('en');

		$x = [
			'lions',
			'tigers',
			'bears',
		];
		$conj = 'and';
		$this->assertEquals('lions, tigers, and bears', $testObject->conjunction($x, $conj));
	}

	/**
	 * @return void
	 * @covers ::pluralNumber
	 * @throws ClassNotFound
	 */
	public function testPluralNumber(): void
	{
		$testObject = $this->application->localeFactory('en');

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
	 * @covers ::ordinal
	 * @throws ClassNotFound
	 */
	public function test_ordinal(int $input, string $result): void
	{
		$testObject = $this->application->localeRegistry('en');
		$this->assertEquals($testObject->ordinal($input), $result);
	}
}
