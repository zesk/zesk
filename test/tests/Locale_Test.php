<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Locale_Test extends Test_Unit {
	function test_plural() {
		$tests = array(
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'JaZzes',
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
			'box' => 'boxes'
		);

		$n = 2;
		$locale = $this->en_locale();
		foreach ($tests as $test => $result) {
			$this->assert_equal($locale->plural($test, $n), $result);
		}
	}
	function test_conjunction() {
		$locale = $this->en_locale();
		$x = array(
			'one thing'
		);
		$c = null;
		$locale = null;
		$result = $locale->conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one thing');

		$x = array(
			'one',
			'two'
		);
		$result = $locale->conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one or two');

		$x = array(
			'one',
			'two',
			'three'
		);
		$result = $locale->conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one, two or three');

		$x = array(
			"lions",
			"tigers",
			"bears"
		);
		$c = "or";
		$this->assert($locale->conjunction($x, $c) === "lions, tigers or bears");

		$x = array(
			"lions",
			"tigers",
			"bears"
		);
		$c = "and";
		$this->assert($locale->conjunction($x, $c) === "lions, tigers and bears");

		$x = array(
			"lions",
			"tigers"
		);
		$c = "and";
		$this->assert($locale->conjunction($x, $c) === "lions and tigers");

		$x = array(
			"lions"
		);
		$c = "and";
		$this->assert($locale->conjunction($x, $c) === "lions");
	}
	function test_date_format() {
		$locale = null;
		$locale->date_format($locale);
	}
	function test_datetime_format() {
		$locale = null;
		$locale->datetime_format($locale);
	}
	function test_dialect() {
		$locale = null;
		$locale->dialect($locale);

		$this->assert_equal($locale->dialect("en_us"), "US");
		$this->assert_equal($locale->dialect("EN_US"), "US");
		$this->assert_equal($locale->dialect("EN_US_Southern"), "US");
		$this->assert_equal($locale->dialect("En"), "");
		$this->assert_equal($locale->dialect(""), "");
		$this->assert_equal($locale->dialect(null), "US");
	}
	function test_duration_string() {
		$number = null;
		$locale = "en_US";
		$this->assert_equal($locale->duration_string(1, "second", $number, $locale), "1 second");
		$this->assert_equal($locale->duration_string(2, "second", $number, $locale), "2 seconds");
		$this->assert_equal($locale->duration_string(60, "second", $number, $locale), "60 seconds");
		$this->assert_equal($locale->duration_string(120, "second", $number, $locale), "2 minutes");
		$this->assert_equal($locale->duration_string(120, "second", $number, $locale), "2 minutes");
	}
	function test_indefinite_article() {
		$word = null;
		$caps = false;
		$locale = null;
		$this->assert_equal($locale->indefinite_article("euro", false), "a");
		$this->assert_equal($locale->indefinite_article("honor", false), "an");
		echo basename(__FILE__) . ": success\n";
	}
	function test_language() {
		$this->assert_equal(Locale::parse_language("EN_US"), "en");
		$this->assert_equal(Locale::parse_language("EN_US_Southern"), "en");
		$this->assert_equal(Locale::parse_language("En"), "en");
		$this->assert_equal(Locale::parse_language(""), "");
	}
	function test_negate_word() {
		$locale = $this->application->locale;
		$word = null;
		$language = "en";
		$locale->negate_word($word, $language);
	}
	function test_normalize() {
		$this->assert_equal(Locale::normalize("EN_us"));
		$this->assert_equal(Locale::normalize("Fr_Fr"), "fr_FR");
	}
	function test_now_string() {
		$ts = null;
		$min_unit = false;
		$zero_string = false;
		$locale = null;
		$locale->now_string($ts, $min_unit, $zero_string, $locale);
		echo basename(__FILE__) . ": success\n";
	}

	/**
	 *
	 */
	function test_ordinal() {
		$locale = $this->en_locale();
		$this->assert_equal($locale->ordinal(1), "1st");
		$this->assert_equal($locale->ordinal(2), "2nd");
		$this->assert_equal($locale->ordinal(3), "3rd");
		$this->assert_equal($locale->ordinal(11), "11th");
		$this->assert_equal($locale->ordinal(12), "12th");
		$this->assert_equal($locale->ordinal(13), "13th");
		$this->assert_equal($locale->ordinal(21), "21st");
		$this->assert_equal($locale->ordinal(22), "22nd");
		$this->assert_equal($locale->ordinal(23), "23rd");
		$this->assert_equal($locale->ordinal(101), "101st");
		$this->assert_equal($locale->ordinal(102), "102nd");
		$this->assert_equal($locale->ordinal(103), "103rd");
		$this->assert_equal($locale->ordinal(110), "110th");
		$this->assert_equal($locale->ordinal(111), "111th");
		$this->assert_equal($locale->ordinal(112), "112th");
		$this->assert_equal($locale->ordinal(113), "113th");

		$locale = $this->fr_locale();
		$this->assert_equal($locale->ordinal(1, "fr"), "1r");
		$this->assert_equal($locale->ordinal(2, "fr"), "2e");
		$this->assert_equal($locale->ordinal(21, "fr"), "21e");
	}
	function test_plural2() {
		$tests = array(
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'JaZzes',
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
			'box' => 'boxes'
		);

		$n = 2;
		$locale = $this->en_locale();
		foreach ($tests as $test => $result) {
			$this->assert_equal($locale->plural($test, $n), $result);
		}

		echo basename(__FILE__) . ": success\n";
	}
	function test_plural_number() {
		$tests = array(
			'woman' => 'women',
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'JaZzes',
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
			'box' => 'boxes'
		);

		$locales = array(
			"en",
			"en_US",
			"en_GB"
		);
		foreach ($locales as $locale) {
			$n = null;
			foreach ($tests as $test => $plural) {
				$n = mt_rand(2, 1000);
				$this->assert($locale->plural_number($test, $n, $locale) === $n . " " . $plural, $locale->plural_number($test, $n, $locale) . " === $n $plural");
				$this->assert($locale->plural_number($test, 0, $locale) === "0 " . $plural, $locale->plural_number($test, 0, $locale) . " === 0 $plural");
				$this->assert($locale->plural_number($test, 1, $locale) === "1 " . $test, $locale->plural_number($test, 1, $locale) . " === 1 $test");
				$this->assert($locale->plural_number($test, 2, $locale) === "2 " . $plural, $locale->plural_number($test, 2, $locale) . " === 2 $plural");
			}
		}
	}
	function en_locale() {
		if ($this->application->locale->language() === "en") {
			return $this->application->locale;
		}
		return $this->application->locale_factory("en");
	}
	function test_plural_word() {
		$locale = $this->en_locale();
		$tests = array(
			'Jazz' => 'Jazzes',
			'JAZZ' => 'JAZZES',
			'JaZz' => 'JaZzes',
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
			'box' => 'boxes'
		);

		$n = null;
		foreach ($tests as $test => $plural) {
			$n = mt_rand(2, 1000);
			$this->assert($locale->plural_word($test, $n, "en") === $n . " " . $plural, $locale->plural_word($test, $n, "en") . " === $n $plural");
			$this->assert($locale->plural_word($test, 0, "en") === "no " . $plural, $locale->plural_word($test, 0, "en") . " === no $plural");
			$this->assert($locale->plural_word($test, 1, "en") === "one " . $test, $locale->plural_word($test, 1, "en") . " === 1 $test");
		}
	}
	function test___() {
		$tt = array(
			'cuddle' => 'boink'
		);
		$locale = 'xy';
		$x = new Locale($this->application, $locale);
		$x->translations($tt);

		$this->assertEquals($x->__('cuddle', 'xy'), 'boink');
	}

	/**
	 *
	 */
	function test_time_format() {
		$locale = null;
		$include_seconds = false;
		$locale->time_format($locale, $include_seconds);
	}
	function testLocales() {
		return array(
			array(
				$this->application->locale
			)
		);
	}
	/**
	 * @dataProvider testLocales
	 * @param Locale $locale
	 */
	function test_translate(Locale $locale) {
		foreach (array(
			"Hello",
			"world"
		) as $phrase) {
			$this->assert_equal($locale->__($phrase), $locale($phrase));
		}
	}

	/**
	 * Apparently, this was a problem at some point
	 */
	function test_now_string_translation() {
		$locale = $this->application->locale;
		$now = time();
		$in_10 = $locale->now_string($now + 10);
		$ago_10 = $locale->now_string($now - 10);
		$this->assert(strpos($in_10, "Locale::") === false);
		$this->assert(strpos($in_10, "now_string") === false);
		$this->assert(strpos($ago_10, "Locale::") === false);
		$this->assert(strpos($ago_10, "now_string") === false);
	}
}
