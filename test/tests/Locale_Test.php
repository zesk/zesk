<?php
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
class Locale_Test extends Test_Unit {
    public function test_plural() {
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
            'box' => 'boxes',
            'z' => 'zs',
            'w' => 'ws',
        );

        $n = 2;
        $locale = $this->en_locale();
        foreach ($tests as $test => $result) {
            $this->assert_equal($locale->plural($test, $n), $result);
        }
    }

    public function test_conjunction() {
        $locale = $this->en_locale();
        $x = array(
            'one thing',
        );
        $c = null;
        $locale = $this->application->locale_registry("en");
        $result = $locale->conjunction($x, $c);
        echo "$result\n";
        $this->assert($result === 'one thing');

        $x = array(
            'one',
            'two',
        );
        $result = $locale->conjunction($x, $c);
        $this->assertEquals("one or two", $result);

        $x = array(
            'one',
            'two',
            'three',
        );
        $result = $locale->conjunction($x, $c);
        echo "$result\n";
        $this->assertEquals('one, two, or three', $result);

        $x = array(
            "lions",
            "tigers",
            "bears",
        );
        $c = "or";
        $this->assert($locale->conjunction($x, $c) === "lions, tigers, or bears");

        $x = array(
            "lions",
            "tigers",
            "bears",
            "campers",
            "zebras",
        );
        $c = "or";
        $this->assert($locale->conjunction($x, $c) === "lions, tigers, bears, campers, or zebras");

        $x = array(
            "lions",
            "tigers",
            "bears",
        );
        $c = "and";
        $this->assert($locale->conjunction($x, $c) === "lions, tigers, and bears");

        $x = array(
            "lions",
            "tigers",
        );
        $c = "and";
        $this->assert($locale->conjunction($x, $c) === "lions and tigers");

        $x = array(
            "lions",
        );
        $c = "and";
        $this->assert($locale->conjunction($x, $c) === "lions");
    }

    public function to_locale_list($id_list) {
        $args = array();
        foreach (Lists::unique(to_list($id_list)) as $id) {
            $args[] = array(
                $this->application->locale_factory($id),
            );
        }
        return $args;
    }

    /**
     * Data provider for generic tests across English locales
     *
     * @return \zesk\Locale[][]
     */
    public function en_locales() {
        return $this->to_locale_list("en;en_US;en_GB;en_CA");
    }

    /**
     * Data provider for generic tests across locales
     *
     * @return \zesk\Locale[][]
     */
    public function locales() {
        return $this->to_locale_list("en_US;en_GB;fr_FR;de_DE;es_ES");
    }

    /**
     * @dataProvider locales
     * @param Locale $locale
     */
    public function test_date_format(Locale $locale) {
        $this->assert_is_string($locale->date_format($locale));
    }

    /**
     * @dataProvider locales
     * @param Locale $locale
     */
    public function test_datetime_format(Locale $locale) {
        $this->assert_is_string($locale->datetime_format());
    }

    /**
     * @dataProvider locales
     * @param Locale $locale
     */
    public function test_parse_dialect(Locale $locale) {
        $this->assert_equal(Locale::parse_dialect("en_us"), "US");
        $this->assert_equal(Locale::parse_dialect("EN_US"), "US");
        $this->assert_equal(Locale::parse_dialect("EN_US_Southern"), "US");
        $this->assert_equal(Locale::parse_dialect("En"), null);
        $this->assert_equal(Locale::parse_dialect(""), null);
        $this->assert_equal(Locale::parse_dialect(false), null);
        $this->assert_equal(Locale::parse_dialect(0), null);
        $this->assert_equal(Locale::parse_dialect(0.0), null);
        $this->assert_equal(Locale::parse_dialect(null), null);
    }

    /**
     * @dataProvider en_locales
     * @param Locale $locale
     */
    public function test_duration_string(Locale $locale) {
        $number = null;
        $this->assert_equal($locale->duration_string(1, "second", $number), "1 second");
        $this->assert_equal($locale->duration_string(2, "second", $number), "2 seconds");
        $this->assert_equal($locale->duration_string(60, "second", $number), "60 seconds");
        $this->assert_equal($locale->duration_string(120, "second", $number), "2 minutes");
        $this->assert_equal($locale->duration_string(120, "second", $number), "2 minutes");
    }

    /**
     * @dataProvider en_locales
     * @param Locale $locale
     */
    public function test_indefinite_article(Locale $locale) {
        $word = null;
        $caps = false;
        $this->assert_equal($locale->indefinite_article("euro", false), "a");
        $this->assert_equal($locale->indefinite_article("honor", false), "an");
    }

    public function test_language() {
        $this->assert_equal(Locale::parse_language("EN_US"), "en");
        $this->assert_equal(Locale::parse_language("EN_US_Southern"), "en");
        $this->assert_equal(Locale::parse_language("En"), "en");
        $this->assert_equal(Locale::parse_language(""), null);
        $this->assert_equal(Locale::parse_language(0), null);
        $this->assert_equal(Locale::parse_language(0.0), null);
        $this->assert_equal(Locale::parse_language(false), null);
        $this->assert_equal(Locale::parse_language(null), null);
    }

    public function test_negate_word() {
        $locale = $this->application->locale;
        $word = null;
        $language = "en";
        $locale->negate_word($word, $language);
    }

    public function test_normalize() {
        $this->assert_equal(Locale::normalize("EN_us"), 'en_US');
        $this->assert_equal(Locale::normalize("ABCD_EFGH_IJ"), 'ab_EF');
        $this->assert_equal(Locale::normalize("Fr_Fr"), "fr_FR");
    }

    /**
     * @dataProvider en_locales
     * @param Locale $locale
     */
    public function test_now_string(Locale $locale) {
        $ts = null;
        $min_unit = false;
        $zero_string = false;
        $result = $locale->now_string($ts, $min_unit, $zero_string);
        $this->assert_is_string($result);
    }

    /**
     *
     */
    public function test_ordinal() {
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

    public function test_plural2() {
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
            'box' => 'boxes',
        );

        $n = 2;
        $locale = $this->en_locale();
        foreach ($tests as $test => $result) {
            $this->assert_equal($locale->plural($test, $n), $result);
        }

        echo basename(__FILE__) . ": success\n";
    }

    /**
     * @dataProvider en_locales
     * @param Locale $locale
     */
    public function test_plural_number(Locale $locale) {
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
            'box' => 'boxes',
        );

        $n = null;
        foreach ($tests as $test => $plural) {
            for ($i = 0; $i < 100; $i++) {
                $n = mt_rand(2, 1000);
                $this->assertEquals("$n $plural", $locale->plural_number($test, $n));
            }
            $this->assertEquals("0 $plural", $locale->plural_number($test, 0));
            $this->assertEquals("1 $test", $locale->plural_number($test, 1));
            $this->assertEquals("2 $plural", $locale->plural_number($test, 2));
        }
    }

    public function en_locale() {
        return $this->application->locale_registry("en");
    }

    public function fr_locale() {
        return $this->application->locale_registry("fr");
    }

    public function test_plural_word() {
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
            'box' => 'boxes',
        );

        $n = null;
        foreach ($tests as $test => $plural) {
            for ($i = 0; $i < 100; $i++) {
                $n = mt_rand(2, 1000);
                $this->assertEquals($locale->plural_word($test, $n, "en"), $n . " " . $plural);
            }
            $this->assertEquals($locale->plural_word($test, 0, "en"), "no " . $plural);
            $this->assertEquals($locale->plural_word($test, 1, "en"), "one " . $test);
        }
    }

    public function translation_tests() {
        $id = 'xy';
        $xy = Locale::factory($this->application, $id);
        $xy->translations(array(
            'cuddle' => 'boink',
        ));
        $this->assertInstanceOf(Locale_Default::class, $xy);
        return array(
            array(
                $xy,
                'cuddle',
                'boink',
            ),
            array(
                $xy,
                'Cuddle',
                'Boink',
            ),
            array(
                $xy,
                'CUddle',
                'BOINK',
            ),
            array(
                $xy,
                'CUDDLE',
                'BOINK',
            ),
        );
    }

    /**
     * @dataProvider translation_tests
     * @param Locale $locale
     * @param string $expected
     * @param string $test
     */
    public function test_locale_translation(Locale $locale, $test, $expected) {
        $this->assertEquals($expected, $locale->__($test));
    }

    /**
     * @dataProvider locales
     */
    public function test_time_format(Locale $locale) {
        $include_seconds = false;
        $this->assert_is_string($locale->time_format($include_seconds));
    }

    /**
     * @dataProvider locales
     * @param Locale $locale
     */
    public function test_translate(Locale $locale) {
        foreach (array(
            "Hello",
            "world",
        ) as $phrase) {
            $this->assert_equal($locale->__($phrase), $locale($phrase));
        }
    }

    /**
     * Apparently, this was a problem at some point
     */
    public function test_now_string_translation() {
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
