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
class Debug_Test extends Test_Unit {
    public function test_calling_file() {
        Debug::calling_file();
    }

    public function test_calling_function() {
        $depth = 1;
        calling_function($depth);
    }

    public function test_dump() {
        Debug::dump();
    }

    public function test_output() {
        Debug::output();
    }
}
