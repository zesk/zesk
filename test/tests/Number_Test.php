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
class Number_Test extends Test_Unit {
    public function test_format_bytes() {
        $locale = $this->application->locale_registry("en");
        $tests = array(
            0 => "0 bytes",
            1 => "1 byte",
            2 => "2 bytes",
            1024 => "1 KB",
            1024 * 1024 => "1 MB",
            1536133 => "1.5 MB",
        );
        
        foreach ($tests as $n => $result) {
            $this->assert_equal(Number::format_bytes($locale, $n), $result, "Number::format_bytes(\$locale, $n)");
        }
    }
}
