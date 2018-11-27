<?php
namespace zesk;

class Base26_Test extends Test_Unit {
    public function test_from_integer() {
        $i = null;
        $nChars = null;
        Base26::from_integer($i, $nChars);

        $this->assert_equal(Base26::from_integer(0, 1), "A");
        $this->assert_equal(Base26::from_integer(0, 2), "AA");
        $this->assert_equal(Base26::from_integer(0, 5), "AAAAA");
        $this->assert_equal(Base26::from_integer(1, 5), "AAAAB");
        $this->assert_equal(Base26::from_integer(4649370, 1), "KENTY");
        $this->assert_equal(Base26::from_integer(4649370, 5), "KENTY");
    }

    public function test_to_integer() {
        $this->assert_equal(Base26::to_integer("A"), 0);
        $this->assert_equal(Base26::to_integer("AA"), 0);
        $this->assert_equal(Base26::to_integer("AAAAAAAAAAAA"), 0);
        $this->assert_equal(Base26::to_integer("AAAAB"), 1);
        $this->assert_equal(Base26::to_integer("KENTY"), 4649370);
        $this->assert_equal(Base26::to_integer("KENTY"), 4649370);
    }
}
