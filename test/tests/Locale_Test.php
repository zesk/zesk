<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use zesk\Lists;

/**
 *
 * @author kent
 *
 */
class Locale_Test extends UnitTest {
	public function data_plural(): array {
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

	public function conjunction_data(): array {
		return [
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
	 * @dataProvider conjunction_data
	 */
	public function test_conjunction(array $words, string $conjunction, string $expected): void {
		$locale = $this->application->localeRegistry('en');
		$this->assertEquals($expected, $locale->conjunction($words, $conjunction));
	}

	public function to_locale_list($id_list) {
		$this->setUp();
		$args = [];
		foreach (Lists::unique(toList($id_list)) as $id) {
			$args[] = [
				$this->application->localeFactory($id),
			];
		}
		return $args;
	}

	/**
	 * Data provider for generic tests across English locales
	 *
	 * @return \zesk\Locale[][]
	 */
	public function en_locales() {
		return $this->to_locale_list('en;en_US;en_GB;en_CA');
	}

	/**
	 * Data provider for generic tests across locales
	 *
	 * @return \zesk\Locale[][]
	 */
	public function locales() {
		return $this->to_locale_list('en_US;en_GB;fr_FR;de_DE;es_ES');
	}

	/**
	 * @dataProvider locales
	 * @param Locale $locale
	 */
	public function test_date_format(Locale $locale): void {
		$this->assert_is_string($locale->date_format($locale));
	}

	/**
	 * @dataProvider locales
	 * @param Locale $locale
	 */
	public function test_datetime_format(Locale $locale): void {
		$this->assert_is_string($locale->datetime_format());
	}

	/**
	 * @dataProvider locales
	 * @param Locale $locale
	 */
	public function test_parse_dialect(Locale $locale): void {
		$this->assert_equal(Locale::parse_dialect('en_us'), 'US');
		$this->assert_equal(Locale::parse_dialect('EN_US'), 'US');
		$this->assert_equal(Locale::parse_dialect('EN_US_Southern'), 'US');
		$this->assert_equal(Locale::parse_dialect('En'), '');
		$this->assert_equal(Locale::parse_dialect(''), '');
	}

	/**
	 * @dataProvider en_locales
	 * @param Locale $locale
	 */
	public function test_duration_string(Locale $locale): void {
		$number = null;
		$this->assert_equal($locale->duration_string(1, 'second', $number), '1 second');
		$this->assert_equal($locale->duration_string(2, 'second', $number), '2 seconds');
		$this->assert_equal($locale->duration_string(60, 'second', $number), '60 seconds');
		$this->assert_equal($locale->duration_string(120, 'second', $number), '2 minutes');
		$this->assert_equal($locale->duration_string(120, 'second', $number), '2 minutes');
	}

	/**
	 * @dataProvider en_locales
	 * @param Locale $locale
	 */
	public function test_indefinite_article(Locale $locale): void {
		$this->assert_equal($locale->indefinite_article('euro', []), 'a');
		$this->assert_equal($locale->indefinite_article('honor', []), 'an');
	}

	public function test_language(): void {
		$this->assert_equal(Locale::parse_language('EN_US'), 'en');
		$this->assert_equal(Locale::parse_language('EN_US_Southern'), 'en');
		$this->assert_equal(Locale::parse_language('En'), 'en');
		$this->assert_equal(Locale::parse_language(''), '');
	}

	public function test_negate_word(): void {
		$locale = $this->application->locale;
		$this->assertEquals('updog', $locale->negate_word('dog', 'up'));
	}

	public function test_normalize(): void {
		$this->assert_equal(Locale::normalize('EN_us'), 'en_US');
		$this->assert_equal(Locale::normalize('ABCD_EFGH_IJ'), 'ab_EF');
		$this->assert_equal(Locale::normalize('Fr_Fr'), 'fr_FR');
	}

	public function now_string_data(): array {
		return [
			['now', Timestamp::UNIT_MINUTE, 'zero', 'zero'],
		];
	}

	/**
	 * @dataProvider en_locales
	 * @param Locale $locale
	 */
	public function test_now_string(Locale $locale): void {
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
		$this->assert_equal($locale->ordinal(1), '1st');
		$this->assert_equal($locale->ordinal(2), '2nd');
		$this->assert_equal($locale->ordinal(3), '3rd');
		$this->assert_equal($locale->ordinal(11), '11th');
		$this->assert_equal($locale->ordinal(12), '12th');
		$this->assert_equal($locale->ordinal(13), '13th');
		$this->assert_equal($locale->ordinal(21), '21st');
		$this->assert_equal($locale->ordinal(22), '22nd');
		$this->assert_equal($locale->ordinal(23), '23rd');
		$this->assert_equal($locale->ordinal(101), '101st');
		$this->assert_equal($locale->ordinal(102), '102nd');
		$this->assert_equal($locale->ordinal(103), '103rd');
		$this->assert_equal($locale->ordinal(110), '110th');
		$this->assert_equal($locale->ordinal(111), '111th');
		$this->assert_equal($locale->ordinal(112), '112th');
		$this->assert_equal($locale->ordinal(113), '113th');

		$locale = $this->fr_locale();
		$this->assert_equal($locale->ordinal(1, 'fr'), '1r');
		$this->assert_equal($locale->ordinal(2, 'fr'), '2e');
		$this->assert_equal($locale->ordinal(21, 'fr'), '21e');
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
			$this->assert_equal($locale->plural($test, $n), $result);
		}
	}

	/**
	 * @dataProvider en_locales
	 * @param Locale $locale
	 */
	public function test_plural_number(Locale $locale): void {
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

	public function translation_tests() {
		$this->setUp();

		$id = 'xy';
		$xy = Locale::factory($this->application, $id);
		$xy->translations([
			'cuddle' => 'boink',
		]);
		$this->assertInstanceOf(Locale_Default::class, $xy);
		return [
			[
				$xy,
				'cuddle',
				'boink',
			],
			[
				$xy,
				'Cuddle',
				'Boink',
			],
			[
				$xy,
				'CUddle',
				'BOINK',
			],
			[
				$xy,
				'CUDDLE',
				'BOINK',
			],
		];
	}

	/**
	 * @dataProvider translation_tests
	 * @param Locale $locale
	 * @param string $expected
	 * @param string $test
	 */
	public function test_locale_translation(Locale $locale, $test, $expected): void {
		$this->assertEquals($expected, $locale->__($test));
	}

	/**
	 * @dataProvider locales
	 */
	public function test_time_format(Locale $locale): void {
		$include_seconds = false;
		$this->assert_is_string($locale->time_format($include_seconds));
	}

	/**
	 * @dataProvider locales
	 * @param Locale $locale
	 */
	public function test_translate(Locale $locale): void {
		foreach ([
			'Hello',
			'world',
		] as $phrase) {
			$this->assert_equal($locale->__($phrase), $locale($phrase));
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
		$this->assert(!str_contains($in_10, 'Locale::'));
		$this->assert(!str_contains($in_10, 'now_string'));
		$this->assert(!str_contains($ago_10, 'Locale::'));
		$this->assert(!str_contains($ago_10, 'now_string'));
	}
}
