<?php
namespace zesk;

class UTF16_Test extends Test_Unit {
    public function test_decode() {
        $str = null;
        $be = null;
        UTF16::decode($str, $be);
    }

    public function test_encode() {
        $str = null;
        $be = true;
        $add_bom = true;
        UTF16::encode($str, $be, $add_bom);
    }

    public function test_to_iso8859() {
        $mixed = null;
        $be = null;
        UTF16::to_iso8859($mixed, $be);
    }

    public function test_to_utf8() {
        $str = null;
        $be = null;
        UTF16::to_utf8($str, $be);
    }
}
