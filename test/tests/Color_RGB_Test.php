<?php
namespace zesk;

class Color_RGB_Test extends Test_Unit {
    public function test_basics() {
        $r = 1;
        $g = 2;
        $b = 255;
        $x = new Color_RGB($r, $g, $b);
        
        $this->assert_equal($x->__toString(), "0102FF");
    }
}
