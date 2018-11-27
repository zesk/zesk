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
class JavaScript_Test extends Test_Unit {
    public function test_clean_function_name() {
        $x = null;
        JavaScript::clean_function_name($x);
    }

    public function test_null() {
        $x = null;
        JavaScript::null($x);
    }

    public function test_obfuscate_begin() {
        JavaScript::obfuscate_begin();
        JavaScript::obfuscate_end(array());
    }

    /**
     * @expectedException zesk\Exception_Semantics
     */
    public function test_obfuscate_begin2() {
        JavaScript::obfuscate_begin();
        JavaScript::obfuscate_begin();
    }

    /**
     * @depends test_obfuscate_begin2
     * @expectedException zesk\Exception_Semantics
     */
    public function test_obfuscate_end() {
        $function_map = array();
        JavaScript::obfuscate_end($function_map);
        JavaScript::obfuscate_end($function_map);
    }

    public function test_string() {
        $x = null;
        JavaScript::string($x);
    }
}
