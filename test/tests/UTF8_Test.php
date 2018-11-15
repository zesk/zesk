<?php
namespace zesk;

class UTF8_Test extends Test_Unit {
    public function test_to_iso8859() {
        $mixed = null;
        UTF8::to_iso8859($mixed);
    }

    public function test_from_charset() {
        $mixed = null;
        $charset = null;
        UTF8::from_charset($mixed, "iso-8859-1");
    }
}
