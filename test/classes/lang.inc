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
			$this->assert(Locale::plural($test, $n, $language) === $result, Locale::plural($test, $n, $language) . " !== " . $result);
		}
	}

	function test_conjunction() {
		$x = array(
			'one thing'
		);
		$c = null;
		$locale = null;
		$result = Locale::conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one thing');

		$x = array(
			'one',
			'two'
		);
		$result = Locale::conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one or two');

		$x = array(
			'one',
			'two',
			'three'
		);
		$result = Locale::conjunction($x, $c, $locale);
		echo "$result\n";
		$this->assert($result === 'one, two or three');

		$x = array(
			"lions",
			"tigers",
			"bears"
		);
		$c = "or";
		$this->assert(Locale::conjunction($x, $c) === "lions, tigers or bears");

		$x = array(
			"lions",
			"tigers",
			"bears"
		);
		$c = "and";
		$this->assert(Locale::conjunction($x, $c) === "lions, tigers and bears");

		$x = array(
			"lions",
			"tigers"
		);
		$c = "and";
		$this->assert(Locale::conjunction($x, $c) === "lions and tigers");

		$x = array(
			"lions"
		);
		$c = "and";
		$this->assert(Locale::conjunction($x, $c) === "lions");

		echo basename(__FILE__) . ": success\n";
	}

	function test_date_format() {
		$locale = null;
		Locale::date_format($locale);
		echo basename(__FILE__) . ": success\n";
	}

	function test_datetime_format() {
		$locale = null;
		Locale::datetime_format($locale);
		echo basename(__FILE__) . ": success\n";
	}

	function test_dialect() {
		$locale = null;
		Locale::dialect($locale);

		$this->assert(Locale::dialect("en_us") === "US");
		$this->assert(Locale::dialect("EN_US") === "US");
		$this->assert(Locale::dialect("EN_US_Southern") === "US");
		$this->assert(Locale::dialect("En") === "");
		$this->assert(Locale::dialect("") === "");
		$this->assert(Locale::dialect(null) === "");
		echo basename(__FILE__) . ": success\n";
	}

	function test_duration_string() {
		$number = null;
		$locale = "en_US";
		$this->assert_equal(Locale::duration_string(1, "second", $number, $locale), "1 second");
		$this->assert_equal(Locale::duration_string(2, "second", $number, $locale), "2 seconds");
		$this->assert_equal(Locale::duration_string(60, "second", $number, $locale), "60 seconds");
		$this->assert_equal(Locale::duration_string(120, "second", $number, $locale), "2 minutes");
		$this->assert_equal(Locale::duration_string(120, "second", $number, $locale), "2 minutes");
	}

	function test_indefinite_article() {
		$word = null;
		$caps = false;
		$locale = null;
		$this->assert_equal(Locale::indefinite_article("euro", false, "en_US"), "a");
		$this->assert_equal(Locale::indefinite_article("honor", false, "en_US"), "an");
		echo basename(__FILE__) . ": success\n";
	}

	function test_language() {
		$locale = null;
		Locale::language($locale);

		$this->assert(Locale::language("EN_US") === "en");
		$this->assert(Locale::language("EN_US_Southern") === "en");
		$this->assert(Locale::language("En") === "en");
		$this->assert(Locale::language("") === "");
	}

	function test_load() {
		$locale = null;
		Locale::load($locale);
	}

	function test_loaded() {
		$locale = null;
		Locale::loaded($locale);
	}

	function test_locale_path() {
		$add = null;
		Locale::locale_path($add);
	}

	function test_negate_word() {
		$word = null;
		$language = "en";
		Locale::negate_word($word, $language);
	}

	function test_normalize() {
		$this->assert_equal(Locale::normalize("EN_us"), "en_US");
		$this->assert_equal(Locale::normalize("Fr_Fr"), "fr_FR");
	}

	function test_now_string() {
		$ts = null;
		$min_unit = false;
		$zero_string = false;
		$locale = null;
		Locale::now_string($ts, $min_unit, $zero_string, $locale);
		echo basename(__FILE__) . ": success\n";
	}

	function test_ordinal() {
		$this->assert_equal(Locale::ordinal(1, "en_US"), "1st");
		$this->assert_equal(Locale::ordinal(2, "en_US"), "2nd");
		$this->assert_equal(Locale::ordinal(3, "en_US"), "3rd");
		$this->assert_equal(Locale::ordinal(11, "en_US"), "11th");
		$this->assert_equal(Locale::ordinal(12, "en_US"), "12th");
		$this->assert_equal(Locale::ordinal(13, "en_US"), "13th");
		$this->assert_equal(Locale::ordinal(21, "en_US"), "21st");
		$this->assert_equal(Locale::ordinal(22, "en_US"), "22nd");
		$this->assert_equal(Locale::ordinal(23, "en_US"), "23rd");
		$this->assert_equal(Locale::ordinal(101, "en_US"), "101st");
		$this->assert_equal(Locale::ordinal(102, "en_US"), "102nd");
		$this->assert_equal(Locale::ordinal(103, "en_US"), "103rd");
		$this->assert_equal(Locale::ordinal(110, "en_US"), "110th");
		$this->assert_equal(Locale::ordinal(111, "en_US"), "111th");
		$this->assert_equal(Locale::ordinal(112, "en_US"), "112th");
		$this->assert_equal(Locale::ordinal(113, "en_US"), "113th");

		$this->assert_equal(Locale::ordinal(1, "fr"), "1r");
		$this->assert_equal(Locale::ordinal(2, "fr"), "2e");
		$this->assert_equal(Locale::ordinal(21, "fr"), "21e");
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
			$this->assert(Locale::plural($test, $n) === $result, Locale::plural($test, $n, $language) . " !== " . $result);
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
				$this->assert(Locale::plural_number($test, $n, $locale) === $n . " " . $plural, Locale::plural_number($test, $n, $locale) . " === $n $plural");
				$this->assert(Locale::plural_number($test, 0, $locale) === "0 " . $plural, Locale::plural_number($test, 0, $locale) . " === 0 $plural");
				$this->assert(Locale::plural_number($test, 1, $locale) === "1 " . $test, Locale::plural_number($test, 1, $locale) . " === 1 $test");
				$this->assert(Locale::plural_number($test, 2, $locale) === "2 " . $plural, Locale::plural_number($test, 2, $locale) . " === 2 $plural");
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
			$this->assert(Locale::plural_word($test, $n, "en") === $n . " " . $plural, Locale::plural_word($test, $n, "en") . " === $n $plural");
			$this->assert(Locale::plural_word($test, 0, "en") === "no " . $plural, Locale::plural_word($test, 0, "en") . " === no $plural");
			$this->assert(Locale::plural_word($test, 1, "en") === "one " . $test, Locale::plural_word($test, 1, "en") . " === 1 $test");
		}
	}

	function test___() {
		$locale = 'xy';
		$tt = array(
			'cuddle' => 'boink'
		);
		Locale::register($locale, $tt);
		$this->assert(Locale::translate('cuddle', 'xy') === 'boink');
	}

	function test_shutdown() {
		Locale::shutdown();
	}

	function test_time_format() {
		$locale = null;
		$include_seconds = false;
		Locale::time_format($locale, $include_seconds);
	}

	function test_translate() {
		$phrase = null;
		$locale = null;
		Locale::translate($phrase, $locale);
	}

	function test_now_string_translation() {
		$now = time();
		$in_10 = Locale::now_string($now + 10);
		$ago_10 = Locale::now_string($now - 10);
		$this->assert(strpos($in_10, "Locale::") === false);
		$this->assert(strpos($in_10, "now_string") === false);
		$this->assert(strpos($ago_10, "Locale::") === false);
		$this->assert(strpos($ago_10, "now_string") === false);
	}
}
