<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use zesk\Exception\ClassNotFound;
use zesk\Locale\Locale;

/**
 *
 * @author kent
 *
 */
class LocaleTest extends UnitTest
{
	public function test_locale___(): void
	{
		$locale = $this->application->locale;
		$this->assertEquals([], $locale->__([], ['ignored' => true]));
	}

	public static function data_plural(): array
	{
		$tests = [
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'Jazzes',
			'funny' => 'funnies',
			'funnY' => 'funnies',
			'FUNNY' => 'FUNNIES',
			'clock' => 'clocks',
			'sheep' => 'sheep',
			'Sheep' => 'Sheep',
			'SHEEP' => 'SHEEP',
			'dog' => 'dogs',
			'person' => 'people',
			'cat' => 'cats',
			'topaz' => 'topazes',
			'kiss' => 'kisses',
			'octopus' => 'octopi',
			'Octopus' => 'Octopi',
			'OCTOPUS' => 'OCTOPI',
			'OcToPuS' => 'Octopi',
			'FOX' => 'FOXES',
			'box' => 'boxes',
			'z' => 'zs',
			'w' => 'ws',
		];
		$result = [];
		foreach ($tests as $test => $expected) {
			$result[] = [$expected, $test];
		}
		return $result;
	}

	/**
	 * @dataProvider data_plural
	 * @param $expected
	 * @param $test
	 * @return void
	 */
	public function test_plural($expected, $test): void
	{
		$n = 2;
		$locale = $this->en_locale();
		$this->assertEquals($expected, $locale->plural($test, $n));
	}

	public static function data_conjunction(): array
	{
		return [
			[
				['Apples', 'Pears', 'Frogs'],
				'and',
				'Apples, Pears, and Frogs',
			],
			[
				['Apples', 'Pears', 'Frogs'],
				'',
				'Apples, Pears, or Frogs',
			],
			[
				['Apples', 'Pears', 'Frogs'],
				'no',
				'Apples, Pears, no Frogs',
			],
			[
				['one thing', ],
				'',
				'one thing',
			],
			[
				[
					'one',
					'two',
				],
				'or',
				'one or two',
			],
			[
				[
					'one',
					'two',
					'three',
				],
				'',
				'one, two, or three',
			],
			[
				[
					'lions',
					'tigers',
					'bears',
				],
				'or',
				'lions, tigers, or bears',
			],
			[
				[
					'lions',
					'tigers',
					'bears',
					'campers',
					'zebras',
				],
				'or',
				'lions, tigers, bears, campers, or zebras',
			],
			[
				[
					'lions',
					'tigers',
					'bears',
				],
				'and',
				'lions, tigers, and bears',
			],
			[
				[
					'lions',
					'tigers',
				],
				'and',
				'lions and tigers',
			],
			[
				[
					'lions',
				],
				'and',
				'lions',
			],
		];
	}

	/**
	 * @param array $words
	 * @param string $conjunction
	 * @param string $expected
	 * @return void
	 * @dataProvider data_conjunction
	 */
	public function test_conjunction(array $words, string $conjunction, string $expected): void
	{
		$locale = $this->application->localeRegistry('en');
		$this->assertEquals($expected, $locale->conjunction($words, $conjunction));
	}

	/**
	 * @param array|string $id_list
	 * @return array
	 */
	public static function to_locale_list(array|string $id_list): array
	{
		$args = [];
		foreach (Lists::unique(Types::toList($id_list)) as $id) {
			$args[] = [
				fn () => self::app()->localeFactory($id),
			];
		}
		return $args;
	}

	/**
	 * Data provider for generic tests across English locales
	 *
	 * @return Locale[]
	 */
	public static function data_en_locales(): array
	{
		return self::to_locale_list('en;en_US;en_GB;en_CA');
	}

	/**
	 * Data provider for generic tests across locales
	 *
	 * @return Locale[]
	 */
	public static function data_locales(): array
	{
		return self::to_locale_list('en_US;en_GB;fr_FR;de_DE;es_ES');
	}

	/**
	 * @dataProvider data_locales
	 * @param $mixed
	 */
	public function test_date_format($mixed): void
	{
		$locale = $this->applyClosures($mixed);
		/* @var $locale Locale */
		$this->assertIsString($locale->formatDate());
	}

	/**
	 * @dataProvider data_locales
	 * @param mixed $mixed
	 */
	public function test_datetime_format(mixed $mixed): void
	{
		$locale = $this->applyClosures($mixed);
		/* @var $locale Locale */
		$this->assertIsString($locale->formatDateTime());
	}

	public static function data_parseDialect(): array
	{
		return [
			['US', 'en_us'],
			['US', 'EN_US'],
			['US', 'EN_US_Southern'],
			['', 'En'],
			['', ''],
		];
	}

	/**
	 * @dataProvider data_parseDialect
	 */
	public function test_parse_dialect(string $expected, string $languageString): void
	{
		$this->assertEquals($expected, Locale::parseDialect($languageString));
	}

	/**
	 * @return array[]
	 */
	public static function data_en_durationStrings(): array
	{
		$tests = [];
		foreach (self::data_en_locales() as $localeTest) {
			$locale = $localeTest[0];
			$newTests = [
				['1 second', 1, 'second', $locale],
				['2 seconds', 2, 'second', $locale],
				['60 seconds', 60, 'second', $locale],
				['2 minutes', 120, 'second', $locale],
				['2 minutes', 120, 'second', $locale],
			];
			$tests = array_merge($tests, $newTests);
		}
		return $tests;
	}

	/**
	 * @dataProvider data_en_durationStrings
	 */
	public function test_duration_string($expected, $delta, $unit, $mixed): void
	{
		$locale = $this->applyClosures($mixed);
		/* @var $locale Locale */
		$number = null;
		$this->assertEquals($expected, $locale->durationString($delta, $unit, $number));
	}

	/**
	 * @dataProvider data_en_locales
	 * @param $mixed
	 */
	public function test_indefinite_article($mixed): void
	{
		$locale = $this->applyClosures($mixed);
		$this->assertEquals('a', $locale->indefiniteArticle('euro', []));
		$this->assertEquals('an', $locale->indefiniteArticle('honor', []));
	}

	public function test_language(): void
	{
		$this->assertEquals('en', Locale::parseLanguage('EN_US'));
		$this->assertEquals('en', Locale::parseLanguage('EN_US_Southern'));
		$this->assertEquals('en', Locale::parseLanguage('En'));
		$this->assertEquals('', Locale::parseLanguage(''));
	}

	public function test_negate_word(): void
	{
		$locale = $this->application->locale;
		$this->assertEquals('updog', $locale->negateWord('dog', 'up'));
	}

	public function test_normalize(): void
	{
		$this->assertEquals('en_US', Locale::normalize('EN_us'));
		$this->assertEquals('ab_EF', Locale::normalize('ABCD_EFGH_IJ'));
		$this->assertEquals('fr_FR', Locale::normalize('Fr_Fr'));
	}

	public static function now_string_data(): array
	{
		$tests = [];
		foreach (self::data_en_locales() as $localeTest) {
			$locale = $localeTest[0];
			$newTests = [
				['zero', 'now', Temporal::UNIT_MINUTE, 'zero', $locale],
				['1 minute ago', '-1 minute', Temporal::UNIT_MINUTE, 'zero', $locale],
				['in 1 minute', '90 seconds', Temporal::UNIT_MINUTE, 'zero', $locale],
			];
			$tests = array_merge($tests, $newTests);
		}
		return $tests;
	}

	/**
	 * @dataProvider now_string_data
	 */
	public function test_now_string($expeected, $timestamp, $min_unit, $zero_string, $mixed): void
	{
		$locale = $this->applyClosures($mixed);
		/* @var $locale Locale */
		$this->assertEquals($expeected, $locale->nowString($timestamp, $min_unit, $zero_string));
	}

	public static function data_ordinal_en(): array
	{
		return [
			['1st', 1],
			['2nd', 2],
			['3rd', 3],
			['11th', 11],
			['12th', 12],
			['13th', 13],
			['21st', 21],
			['22nd', 22],
			['23rd', 23],
			['101st', 101],
			['102nd', 102],
			['103rd', 103],
			['110th', 110],
			['111th', 111],
			['112th', 112],
			['113th', 113],
		];
	}

	/**
	 * @dataProvider data_ordinal_en
	 */
	public function test_ordinal_en(string $expected, int $number): void
	{
		$locale = $this->en_locale();
		$this->assertEquals($expected, $locale->ordinal($number));

		$locale = $this->fr_locale();
		$this->assertEquals('1r', $locale->ordinal(1, 'fr'));
		$this->assertEquals('2e', $locale->ordinal(2, 'fr'));
		$this->assertEquals('21e', $locale->ordinal(21, 'fr'));
	}

	/**
	 * @dataProvider data_ordinal_fr
	 */
	public function test_ordinal_fr(string $expected, int $number): void
	{
		$locale = $this->fr_locale();
		$this->assertEquals($expected, $locale->ordinal($number));
	}

	public static function data_ordinal_fr(): array
	{
		return [
			['1r', 1],
			['2e', 2],
			['21e', 21],
		];
	}

	public function test_plural2(): void
	{
		$tests = [
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'Jazzes',
			'funny' => 'funnies',
			'funnY' => 'funnies',
			'FUNNY' => 'FUNNIES',
			'clock' => 'clocks',
			'sheep' => 'sheep',
			'Sheep' => 'Sheep',
			'SHEEP' => 'SHEEP',
			'dog' => 'dogs',
			'person' => 'people',
			'cat' => 'cats',
			'topaz' => 'topazes',
			'kiss' => 'kisses',
			'octopus' => 'octopi',
			'Octopus' => 'Octopi',
			'OCTOPUS' => 'OCTOPI',
			'OcToPuS' => 'Octopi',
			'FOX' => 'FOXES',
			'box' => 'boxes',
		];

		$n = 2;
		$locale = $this->en_locale();
		foreach ($tests as $test => $result) {
			$this->assertEquals($locale->plural($test, $n), $result);
		}
	}

	/**
	 * @dataProvider data_en_locales
	 * @param $mixed
	 * @throws \Exception
	 */
	public function test_plural_number($mixed): void
	{
		$locale = $this->applyClosures($mixed);
		/* @var $locale Locale */
		$tests = [
			'woman' => 'women',
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'Jazzes',
			'funny' => 'funnies',
			'funnY' => 'funnies',
			'FUNNY' => 'FUNNIES',
			'clock' => 'clocks',
			'sheep' => 'sheep',
			'Sheep' => 'Sheep',
			'SHEEP' => 'SHEEP',
			'dog' => 'dogs',
			'person' => 'people',
			'cat' => 'cats',
			'topaz' => 'topazes',
			'kiss' => 'kisses',
			'octopus' => 'octopi',
			'Octopus' => 'Octopi',
			'OCTOPUS' => 'OCTOPI',
			'OcToPuS' => 'Octopi',
			'FOX' => 'FOXES',
			'box' => 'boxes',
		];

		$n = null;
		foreach ($tests as $test => $plural) {
			for ($i = 0; $i < 100; $i++) {
				$n = random_int(2, 1000);
				$this->assertEquals("$n $plural", $locale->pluralNumber($test, $n));
			}
			$this->assertEquals("0 $plural", $locale->pluralNumber($test, 0));
			$this->assertEquals("1 $test", $locale->pluralNumber($test, 1));
			$this->assertEquals("2 $plural", $locale->pluralNumber($test, 2));
		}
	}

	public function en_locale()
	{
		return $this->application->localeRegistry('en');
	}

	public function fr_locale()
	{
		return $this->application->localeRegistry('fr');
	}

	public function test_plural_word(): void
	{
		$locale = $this->en_locale();
		$tests = [
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'Jazzes',
			'funny' => 'funnies',
			'funnY' => 'funnies',
			'FUNNY' => 'FUNNIES',
			'clock' => 'clocks',
			'sheep' => 'sheep',
			'Sheep' => 'Sheep',
			'SHEEP' => 'SHEEP',
			'dog' => 'dogs',
			'person' => 'people',
			'cat' => 'cats',
			'topaz' => 'topazes',
			'kiss' => 'kisses',
			'octopus' => 'octopi',
			'Octopus' => 'Octopi',
			'OCTOPUS' => 'OCTOPI',
			'OcToPuS' => 'Octopi',
			'FOX' => 'FOXES',
			'box' => 'boxes',
		];

		$n = null;
		foreach ($tests as $test => $plural) {
			for ($i = 0; $i < 100; $i++) {
				$n = $this->randomInteger(2, 1000);
				$this->assertEquals($locale->pluralWord($test, $n, 'en'), $n . ' ' . $plural);
			}
			$this->assertEquals($locale->pluralWord($test, 0, 'en'), 'no ' . $plural);
			$this->assertEquals($locale->pluralWord($test, 1, 'en'), 'one ' . $test);
		}
	}

	/**
	 * @return array[]
	 * @throws ClassNotFound
	 */
	public static function data_locale_translation(): array
	{
		$localeFactory = function (): Locale {
			$id = 'xy';
			$xy = Locale::factory(self::app(), $id);
			$xy->setTranslations([
				'cuddle' => 'boink',
			]);
			return $xy;
		};
		return [
			[
				$localeFactory,
				'cuddle',
				'boink',
			],
			[
				$localeFactory,
				'Cuddle',
				'Boink',
			],
			[
				$localeFactory,
				'CUddle',
				'BOINK',
			],
			[
				$localeFactory,
				'CUDDLE',
				'BOINK',
			],
		];
	}

	/**
	 * @dataProvider data_locale_translation
	 * @param $mixed
	 * @param string $test
	 * @param string $expected
	 */
	public function test_locale_translation($mixed, string $test, string $expected): void
	{
		$locale = $this->applyClosures($mixed);
		/* @var $locale Locale */
		$this->assertInstanceOf(Locale::class, $locale);
		$this->assertEquals($expected, $locale->__($test));
	}

	/**
	 * @dataProvider data_locales
	 */
	public function test_time_format($mixed): void
	{
		$locale = $this->applyClosures($mixed);
		/* @var $locale Locale */
		$this->assertIsString($locale->formatTime(true));
		$this->assertIsString($locale->formatTime(false));
	}

	/**
	 * @dataProvider data_locales
	 * @param $mixed
	 */
	public function test_translate($mixed): void
	{
		$locale = $this->applyClosures($mixed);
		foreach ([
			'Hello',
			'world',
		] as $phrase) {
			$this->assertEquals($locale->__($phrase), $locale($phrase));
		}
	}

	/**
	 * Apparently, this was a problem at some point
	 */
	public function test_now_string_translation(): void
	{
		$locale = $this->application->locale;
		$now = time();
		$in_10 = $locale->nowString($now + 10);
		$ago_10 = $locale->nowString($now - 10);
		$this->assertFalse(str_contains($in_10, 'Locale::'));
		$this->assertFalse(str_contains($in_10, 'now_string'));
		$this->assertFalse(str_contains($ago_10, 'Locale::'));
		$this->assertFalse(str_contains($ago_10, 'now_string'));
	}
}
