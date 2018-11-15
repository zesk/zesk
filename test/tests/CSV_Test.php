<?php
/**
 *
 */
namespace zesk;

/**
 * TODO Move to modules/csv/
 *
 * @author kent
 *
 */
class Test_CSV extends Test_Unit {
    protected $load_modules = array(
        "csv",
    );

    public function test_quote() {
        $x = null;
        CSV_Reader::quote($x);
    }

    public function test_quote_row() {
        $x = array(
            '',
            '\'',
            'a long line with many spaces',
            '"Quotes"',
            '""',
        );
        $newx = CSV::quote_row($x);
        dump($newx);
        $this->assert($newx === ',\',a long line with many spaces,"""Quotes""",""""""' . "\r\n");
    }
}
