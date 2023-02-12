<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Locale_Test extends UnitTest {
	public static function data_plural(): array {
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
	public function test_plural($expected, $test): void {
		$n = 2;
		$locale = $this->en_locale();
		$this->assertEquals($expected, $locale->plural($test, $n));
	}

	public static function data_conjunction(): array {
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
	public function test_conjunction(array $words, string $conjunction, string $expected): void {
		$locale = $this->application->localeRegistry('en');
		$this->assertEquals($expected, $locale->conjunction($words, $conjunction));
	}

	/**
	 * @param $id_list
	 * @return array
	 */
	public static function to_locale_list($id_list): array {
		$args = [];
		foreach (Lists::unique(toList($id_list)) as $id) {
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
	public static function data_en_locales(): array {
		return self::to_locale_list('en;en_US;en_GB;en_CA');
	}

	/**
	 * Data provider for generic tests across locales
	 *
	 * @return Locale[]
	 */
	public static function data_locales(): array {
		return self::to_locale_list('en_US;en_GB;fr_FR;de_DE;es_ES');
	}

	/**
	 * @dataProvider data_locales
	 * @param Locale $locale
	 */
	public function test_date_format($mixed): void {
		$locale = $this->applyClosures($mixed);
		$this->assertIsString($locale->date_format($locale));
	}

	/**
	 * @dataProvider data_locales
	 * @param Locale $locale
	 */
	public function test_datetime_format($mixed): void {
		$locale = $this->applyClosures($mixed);
		$this->assertIsString($locale->datetime_format());
	}

	/**
	 * @dataProvider data_locales
	 * @param Locale $locale
	 */
	public function test_parse_dialect($mixed): void {
		$locale = $this->applyClosures($mixed);
		$this->assertEquals(Locale::parse_dialect('en_us'), 'US');
		$this->assertEquals(Locale::parse_dialect('EN_US'), 'US');
		$this->assertEquals(Locale::parse_dialect('EN_US_Southern'), 'US');
		$this->assertEquals(Locale::parse_dialect('En'), '');
		$this->assertEquals(Locale::parse_dialect(''), '');
	}

	/**
	 * @dataProvider data_en_locales
	 * @param Locale $locale
	 */
	public function test_duration_string($mixed): void {
		$locale = $this->applyClosures($mixed);
		$number = null;
		$this->assertEquals($locale->duration_string(1, 'second', $number), '1 second');
		$this->assertEquals($locale->duration_string(2, 'second', $number), '2 seconds');
		$this->assertEquals($locale->duration_string(60, 'second', $number), '60 seconds');
		$this->assertEquals($locale->duration_string(120, 'second', $number), '2 minutes');
		$this->assertEquals($locale->duration_string(120, 'second', $number), '2 minutes');
	}

	/**
	 * @dataProvider data_en_locales
	 * @param Locale $locale
	 */
	public function test_indefinite_article($mixed): void {
		$locale = $this->applyClosures($mixed);
		$this->assertEquals($locale->indefiniteArticle('euro', []), 'a');
		$this->assertEquals($locale->indefiniteArticle('honor', []), 'an');
	}

	public function test_language(): void {
		$this->assertEquals(Locale::parse_language('EN_US'), 'en');
		$this->assertEquals(Locale::parse_language('EN_US_Southern'), 'en');
		$this->assertEquals(Locale::parse_language('En'), 'en');
		$this->assertEquals(Locale::parse_language(''), '');
	}

	public function test_negate_word(): void {
		$locale = $this->application->locale;
		$this->assertEquals('updog', $locale->negate_word('dog', 'up'));
	}

	public function test_normalize(): void {
		$this->assertEquals(Locale::normalize('EN_us'), 'en_US');
		$this->assertEquals(Locale::normalize('ABCD_EFGH_IJ'), 'ab_EF');
		$this->assertEquals(Locale::normalize('Fr_Fr'), 'fr_FR');
	}

	public function now_string_data(): array {
		return [
			['now', Timestamp::UNIT_MINUTE, 'zero', 'zero'],
		];
	}

	/**
	 * @dataProvider data_en_locales
	 * @param Locale $locale
	 */
	public function test_now_string($mixed): void {
		$locale = $this->applyClosures($mixed);
		foreach ($this->now_string_data() as $items) {
			[$timestamp, $min_unit, $zero_string, $expeected] = $items;
			$this->assertEquals($expeected, $locale->now_string($timestamp, $min_unit, $zero_string));
		}
	}

	/**
	 *
	 */
	public function test_ordinal(): void {
		$locale = $this->en_locale();
		$this->assertEquals($locale->ordinal(1), '1st');
		$this->assertEquals($locale->ordinal(2), '2nd');
		$this->assertEquals($locale->ordinal(3), '3rd');
		$this->assertEquals($locale->ordinal(11), '11th');
		$this->assertEquals($locale->ordinal(12), '12th');
		$this->assertEquals($locale->ordinal(13), '13th');
		$this->assertEquals($locale->ordinal(21), '21st');
		$this->assertEquals($locale->ordinal(22), '22nd');
		$this->assertEquals($locale->ordinal(23), '23rd');
		$this->assertEquals($locale->ordinal(101), '101st');
		$this->assertEquals($locale->ordinal(102), '102nd');
		$this->assertEquals($locale->ordinal(103), '103rd');
		$this->assertEquals($locale->ordinal(110), '110th');
		$this->assertEquals($locale->ordinal(111), '111th');
		$this->assertEquals($locale->ordinal(112), '112th');
		$this->assertEquals($locale->ordinal(113), '113th');

		$locale = $this->fr_locale();
		$this->assertEquals($locale->ordinal(1, 'fr'), '1r');
		$this->assertEquals($locale->ordinal(2, 'fr'), '2e');
		$this->assertEquals($locale->ordinal(21, 'fr'), '21e');
	}

	public function test_plural2(): void {
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
	 * @param Locale $locale
	 */
	public function test_plural_number($mixed): void {
		$locale = $this->applyClosures($mixed);
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
				$this->assertEquals("$n $plural", $locale->plural_number($test, $n));
			}
			$this->assertEquals("0 $plural", $locale->plural_number($test, 0));
			$this->assertEquals("1 $test", $locale->plural_number($test, 1));
			$this->assertEquals("2 $plural", $locale->plural_number($test, 2));
		}
	}

	public function en_locale() {
		return $this->application->localeRegistry('en');
	}

	public function fr_locale() {
		return $this->application->localeRegistry('fr');
	}

	public function test_plural_word(): void {
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
				$n = random_int(2, 1000);
				$this->assertEquals($locale->plural_word($test, $n, 'en'), $n . ' ' . $plural);
			}
			$this->assertEquals($locale->plural_word($test, 0, 'en'), 'no ' . $plural);
			$this->assertEquals($locale->plural_word($test, 1, 'en'), 'one ' . $test);
		}
	}

	/**
	 * @return array[]
	 * @throws Exception_Class_NotFound
	 */
	public static function data_locale_translation(): array {
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
	 * @param Locale $locale
	 * @param string $expected
	 * @param string $test
	 */
	public function test_locale_translation($mixed, string $test, string $expected): void {
		$locale = $this->applyClosures($mixed);
		$this->assertInstanceOf(Locale_Default::class, $locale);
		$this->assertEquals($expected, $locale->__($test));
	}

	/**
	 * @dataProvider data_locales
	 */
	public function test_time_format($mixed): void {
		$locale = $this->applyClosures($mixed);
		$include_seconds = false;
		$this->assertIsString($locale->time_format($include_seconds));
	}

	/**
	 * @dataProvider data_locales
	 * @param Locale $locale
	 */
	public function test_translate($mixed): void {
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
	public function test_now_string_translation(): void {
		$locale = $this->application->locale;
		$now = time();
		$in_10 = $locale->now_string($now + 10);
		$ago_10 = $locale->now_string($now - 10);
		$this->assertFalse(str_contains($in_10, 'Locale::'));
		$this->assertFalse(str_contains($in_10, 'now_string'));
		$this->assertFalse(str_contains($ago_10, 'Locale::'));
		$this->assertFalse(str_contains($ago_10, 'now_string'));
	}
}
