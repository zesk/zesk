<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Lang_en_test extends Test_Unit {
    public function test_everything() {
        $testx = $this->application->locale_factory("en");

        $testx->date_format();

        $testx->datetime_format();

        $include_seconds = false;
        $testx->time_format($include_seconds);

        $word = 'sheep';
        $count = 2;
        $this->assert($testx->plural($word, $count) === 'sheep');

        $word = 'hour away';
        $caps = false;
        $this->assert_equal($testx->indefinite_article($word, false), 'an');
        $this->assert_equal($testx->indefinite_article($word, true), 'An');
        $this->assert_equal($testx->indefinite_article("HOUR AWAY", true), 'An');

        $x = array(
            "lions",
            "tigers",
            "bears",
        );
        $conj = "and";
        $this->assert_equal($testx->conjunction($x, $conj), "lions, tigers, and bears");

        $s = "word";
        $n = 3;
        $locale = null;
        $this->assert_equal($testx->plural_number($s, $n), "3 words");
    }

    public function ordinal_tests() {
        return array(
            array(
                '1',
                '1st',
            ),
            array(
                1,
                '1st',
            ),
            array(
                '0',
                '0th',
            ),
            array(
                0,
                '0th',
            ),
            array(
                '11',
                '11th',
            ),
            array(
                '101',
                '101st',
            ),
            array(
                '2',
                '2nd',
            ),
            array(
                '12',
                '12th',
            ),
            array(
                '21',
                '21st',
            ),
            array(
                '22',
                '22nd',
            ),
            array(
                '99',
                '99th',
            ),
            array(
                '100000001',
                '100000001st',
            ),
        );
    }

    /**
     * @data_provider ordinal_tests
     */
    public function test_ordinal($input, $result) {
        $testx = $this->application->locale_registry("en");
        $this->assert_equal($testx->ordinal($input), $result);
    }
}
