<?php
class test_lang extends Test_Unit {

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
		$language = "en";
		foreach ($tests as $test => $result) {
			$this->assert(lang::plural($test, $n, $language) === $result, lang::plural($test, $n, $language) . " !== " . $result);
		}
	}

	function test_conjunction() {
		$x = array(
			'one thing'
		);
		$c = null;
		$locale = null;
		$result = lang::conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one thing');

		$x = array(
			'one',
			'two'
		);
		$result = lang::conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one or two');

		$x = array(
			'one',
			'two',
			'three'
		);
		$result = lang::conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one, two or three');

		$x = array(
			"lions",
			"tigers",
			"bears"
		);
		$c = "or";
		$this->assert(lang::conjunction($x, $c) === "lions, tigers or bears");

		$x = array(
			"lions",
			"tigers",
			"bears"
		);
		$c = "and";
		$this->assert(lang::conjunction($x, $c) === "lions, tigers and bears");

		$x = array(
			"lions",
			"tigers"
		);
		$c = "and";
		$this->assert(lang::conjunction($x, $c) === "lions and tigers");

		$x = array(
			"lions"
		);
		$c = "and";
		$this->assert(lang::conjunction($x, $c) === "lions");

		echo basename(__FILE__) . ": success\n";
	}

	function test_date_format() {
		$locale = null;
		lang::date_format($locale);
		echo basename(__FILE__) . ": success\n";
	}

	function test_datetime_format() {
		$locale = null;
		lang::datetime_format($locale);
		echo basename(__FILE__) . ": success\n";
	}

	function test_dialect() {
		$locale = null;
		lang::dialect($locale);

		$this->assert(lang::dialect("en_us") === "US");
		$this->assert(lang::dialect("EN_US") === "US");
		$this->assert(lang::dialect("EN_US_Southern") === "US");
		$this->assert(lang::dialect("En") === "");
		$this->assert(lang::dialect("") === "");
		$this->assert(lang::dialect(null) === "");
		echo basename(__FILE__) . ": success\n";
	}

	function test_duration_string() {
		$number = null;
		$locale = "en_US";
		$this->assert_equal(lang::duration_string(1, "second", $number, $locale), "1 second");
		$this->assert_equal(lang::duration_string(2, "second", $number, $locale), "2 seconds");
		$this->assert_equal(lang::duration_string(60, "second", $number, $locale), "60 seconds");
		$this->assert_equal(lang::duration_string(120, "second", $number, $locale), "2 minutes");
		$this->assert_equal(lang::duration_string(120, "second", $number, $locale), "2 minutes");
	}

	function test_indefinite_article() {
		$word = null;
		$caps = false;
		$locale = null;
		$this->assert_equal(lang::indefinite_article("euro", false, "en_US"), "a");
		$this->assert_equal(lang::indefinite_article("honor", false, "en_US"), "an");
		echo basename(__FILE__) . ": success\n";
	}

	function test_language() {
		$locale = null;
		lang::language($locale);

		$this->assert(lang::language("EN_US") === "en");
		$this->assert(lang::language("EN_US_Southern") === "en");
		$this->assert(lang::language("En") === "en");
		$this->assert(lang::language("") === "");
	}

	function test_load() {
		$locale = null;
		lang::load($locale);
	}

	function test_loaded() {
		$locale = null;
		lang::loaded($locale);
	}

	function test_locale_path() {
		$add = null;
		lang::locale_path($add);
	}

	function test_negate_word() {
		$word = null;
		$language = "en";
		lang::negate_word($word, $language);
	}

	function test_normalize() {
		$this->assert_equal(lang::normalize("EN_us"), "en_US");
		$this->assert_equal(lang::normalize("Fr_Fr"), "fr_FR");
	}

	function test_now_string() {
		$ts = null;
		$min_unit = false;
		$zero_string = false;
		$locale = null;
		lang::now_string($ts, $min_unit, $zero_string, $locale);
		echo basename(__FILE__) . ": success\n";
	}

	function test_ordinal() {
		$this->assert_equal(lang::ordinal(1, "en_US"), "1st");
		$this->assert_equal(lang::ordinal(2, "en_US"), "2nd");
		$this->assert_equal(lang::ordinal(3, "en_US"), "3rd");
		$this->assert_equal(lang::ordinal(11, "en_US"), "11th");
		$this->assert_equal(lang::ordinal(12, "en_US"), "12th");
		$this->assert_equal(lang::ordinal(13, "en_US"), "13th");
		$this->assert_equal(lang::ordinal(21, "en_US"), "21st");
		$this->assert_equal(lang::ordinal(22, "en_US"), "22nd");
		$this->assert_equal(lang::ordinal(23, "en_US"), "23rd");
		$this->assert_equal(lang::ordinal(101, "en_US"), "101st");
		$this->assert_equal(lang::ordinal(102, "en_US"), "102nd");
		$this->assert_equal(lang::ordinal(103, "en_US"), "103rd");
		$this->assert_equal(lang::ordinal(110, "en_US"), "110th");
		$this->assert_equal(lang::ordinal(111, "en_US"), "111th");
		$this->assert_equal(lang::ordinal(112, "en_US"), "112th");
		$this->assert_equal(lang::ordinal(113, "en_US"), "113th");

		$this->assert_equal(lang::ordinal(1, "fr"), "1r");
		$this->assert_equal(lang::ordinal(2, "fr"), "2e");
		$this->assert_equal(lang::ordinal(21, "fr"), "21e");
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
		$language = "en";
		foreach ($tests as $test => $result) {
			$this->assert(lang::plural($test, $n) === $result, lang::plural($test, $n, $language) . " !== " . $result);
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
				$this->assert(lang::plural_number($test, $n, $locale) === $n . " " . $plural, lang::plural_number($test, $n, $locale) . " === $n $plural");
				$this->assert(lang::plural_number($test, 0, $locale) === "0 " . $plural, lang::plural_number($test, 0, $locale) . " === 0 $plural");
				$this->assert(lang::plural_number($test, 1, $locale) === "1 " . $test, lang::plural_number($test, 1, $locale) . " === 1 $test");
				$this->assert(lang::plural_number($test, 2, $locale) === "2 " . $plural, lang::plural_number($test, 2, $locale) . " === 2 $plural");
			}
		}
	}

	function test_plural_word() {
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
			$this->assert(lang::plural_word($test, $n, "en") === $n . " " . $plural, lang::plural_word($test, $n, "en") . " === $n $plural");
			$this->assert(lang::plural_word($test, 0, "en") === "no " . $plural, lang::plural_word($test, 0, "en") . " === no $plural");
			$this->assert(lang::plural_word($test, 1, "en") === "one " . $test, lang::plural_word($test, 1, "en") . " === 1 $test");
		}
	}

	function test___() {
		$locale = 'xy';
		$tt = array(
			'cuddle' => 'boink'
		);
		lang::register($locale, $tt);
		$this->assert(lang::translate('cuddle', 'xy') === 'boink');
	}

	function test_shutdown() {
		lang::shutdown();
	}

	function test_time_format() {
		$locale = null;
		$include_seconds = false;
		lang::time_format($locale, $include_seconds);
	}

	function test_translate() {
		$phrase = null;
		$locale = null;
		lang::translate($phrase, $locale);
	}

	function test_now_string_translation() {
		$now = time();
		$in_10 = lang::now_string($now + 10);
		$ago_10 = lang::now_string($now - 10);
		$this->assert(strpos($in_10, "lang::") === false);
		$this->assert(strpos($in_10, "now_string") === false);
		$this->assert(strpos($ago_10, "lang::") === false);
		$this->assert(strpos($ago_10, "now_string") === false);
	}
}
