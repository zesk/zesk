<?php
/**
 * @package zesk
 * @subpackage test
 */
namespace zesk;

use \stdClass;

class ArrayTools_Test extends Test_Unit {
    public function test_change_value_case() {
        $a = array(
            "a" => "ABC",
            "b" => "BCD",
            "c" => "def",
        );
        $this->assert_arrays_equal(ArrayTools::change_value_case($a), array(
            "a" => "abc",
            "b" => "bcd",
            "c" => "def",
        ));
        
        $a = array(
            "a" => "A",
            "b" => "lowercasething",
            "C" => "LoWeRCaSeThInG",
        );
        $this->assert_arrays_equal(ArrayTools::change_value_case($a), array(
            "a" => "a",
            "b" => "lowercasething",
            "C" => "lowercasething",
        ));
    }

    public function test_flip_copy() {
        $x = array(
            "A",
            "B",
            "C",
        );
        $this->assert_arrays_equal(ArrayTools::flip_copy($x, true), array(
            "a" => "A",
            "b" => "B",
            "c" => "C",
        ));
        $this->assert_arrays_equal(ArrayTools::flip_copy($x, false), array(
            "A" => "A",
            "B" => "B",
            "C" => "C",
        ));
        
        $x = array(
            "one",
            "two",
            "three",
        );
        $lower = true;
        $result = ArrayTools::flip_copy($x, $lower);
        $this->assert_arrays_equal($result, array(
            "one" => "one",
            "two" => "two",
            "three" => "three",
        ));
        
        $result = ArrayTools::flip_copy(array(
            "1",
            "2",
            "3",
            "fish",
            "4",
            "5",
            "1",
            "2",
        ), $lower);
        $this->assert_arrays_equal($result, array(
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "fish" => "fish",
            "4" => "4",
            "5" => "5",
        ));
        $x = array(
            "A",
            "B",
        );
        $c = ArrayTools::flip_copy($x, true);
        $this->assert_arrays_equal($c, array(
            "a" => "A",
            "b" => "B",
        ));
        $c = ArrayTools::flip_copy($x, false);
        $this->assert_arrays_equal($c, array(
            "A" => "A",
            "B" => "B",
        ));
    }

    public function test_wrap() {
        $a = array();
        $prefix = null;
        $suffix = null;
        $this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), array(), "", true, true);
        $a = array(
            "a",
        );
        $this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $a);
        
        $prefix = "a";
        $suffix = null;
        $a = array(
            "a" => "b",
        );
        $b = array(
            "a" => "ab",
        );
        $this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $b, "", true, true);
        
        $prefix = "a";
        $suffix = "bb";
        $a = array(
            "a" => "b",
        );
        
        ;
        $b = array(
            "a" => "abbb",
        );
        $this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $b, "", true, true);
        
        $prefix = "a";
        $suffix = "cc";
        $a = array(
            "a" => "b",
            2 => "b",
            412312 => 54,
        );
        $b = array(
            "a" => "abcc",
            2 => "abcc",
            412312 => "a54cc",
        );
        $this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $b, "", true, true);
        
        $arr = array(
            "a",
            "b",
            "c",
        );
        $prefix = '{';
        $suffix = '}';
        $result = ArrayTools::wrap($arr, $prefix, $suffix);
        $result_correct = array(
            "{a}",
            "{b}",
            "{c}",
        );
        
        $this->assert_arrays_equal($result, $result_correct);
    }

    public function test_kprefix() {
        $source = array(
            "a" => "a",
            "b" => "b",
            "c" => "c",
        );
        $dest = array(
            "Dudea" => "a",
            "Dudeb" => "b",
            "Dudec" => "c",
        );
        
        $this->assert_arrays_equal(ArrayTools::kprefix($source, "Dude"), $dest, "ArrayTools::kprefix");
    }

    public function test_suffix() {
        $a = array(
            "Boy",
            "Girl",
            "Baby",
        );
        $p = "Big";
        $this->assert_arrays_equal(ArrayTools::suffix($a, $p), array(
            "BoyBig",
            "GirlBig",
            "BabyBig",
        ));
        $arr = array(
            0,
            1,
            2,
            3,
            4,
            5,
            6,
        );
        $str = "-Things";
        $result = ArrayTools::suffix($arr, $str);
        $result_correct = array(
            "0-Things",
            "1-Things",
            "2-Things",
            "3-Things",
            "4-Things",
            "5-Things",
            "6-Things",
        );
        $this->assert_arrays_equal($result, $result_correct);
    }

    public function test_remove() {
        $arr = array(
            0,
            1,
            2,
            3,
            4,
            5,
            6,
        );
        $keys = "0;2;4;6";
        $result = ArrayTools::remove($arr, $keys);
        $result_correct = array(
            1 => 1,
            3 => 3,
            5 => 5,
        );
        $this->assert_arrays_equal($result, $result_correct);
    }

    public function test_include_exclude() {
        $a = array(
            'a',
            'b',
            'c',
            'd',
        );
        $include = null;
        $exclude = null;
        $lower = true;
        $result = ArrayTools::include_exclude($a, 'a;b;e', $exclude, $lower);
        Debug::output($result);
        $this->assert_arrays_equal($result, array(
            'a',
            'b',
        ));
        $result = ArrayTools::include_exclude($a, null, 'a;b;e', $lower);
        Debug::output($result);
        $this->assert_arrays_equal($result, array(
            2 => 'c',
            3 => 'd',
        ));
        
        $a = array(
            'a',
            'B',
            'c',
            'd',
        );
        // Default is to retain case
        $result = ArrayTools::include_exclude($a, null, 'a;b;e');
        $this->assert_arrays_equal($result, array(
            1 => 'B',
            2 => 'c',
            3 => 'd',
        ));
    }

    public function test_increment() {
        $arr = array();
        $k = "item";
        $result = ArrayTools::increment($arr, $k);
        $this->assert("$result === 1");
        $this->assert_arrays_equal($arr, array(
            "item" => 1,
        ));
        $result = ArrayTools::increment($arr, $k);
        $this->assert("$result === 2");
        $this->assert_arrays_equal($arr, array(
            "item" => 2,
        ));
        $k = 'decimal';
        $result = ArrayTools::increment($arr, $k, 2.1);
        $this->assert_equal($result, 2.1);
        $this->assert_arrays_equal($arr, array(
            "item" => 2,
            "decimal" => 2.1,
        ));
        $result = ArrayTools::increment($arr, $k, 3.2);
        $this->assert_equal($result, 5.3);
        // 		$this->assert_arrays_equal($arr, array(
        // 			"item" => 2,
        // 			"decimal" => 5.3
        // 		));
    }

    public function test_insert() {
        $arr1 = array(
            "x" => "x",
            "a" => "a",
            "y" => "y",
        );
        $key = "a";
        $arr2 = array(
            "p" => "p",
        );
        $this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, false), array(
            "x" => "x",
            "a" => "a",
            "p" => "p",
            "y" => "y",
        ), "basic after", true, true);
        $this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, true), array(
            "x" => "x",
            "p" => "p",
            "a" => "a",
            "y" => "y",
        ), "basic before", true, true);
        
        $arr2 = array(
            "p" => "p",
            "q" => "q",
        );
        
        $this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, false), array(
            "x" => "x",
            "a" => "a",
            "p" => "p",
            "q" => "q",
            "y" => "y",
        ), "multi after", true, true);
        $this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, true), array(
            "x" => "x",
            "p" => "p",
            "q" => "q",
            "a" => "a",
            "y" => "y",
        ), "multi before", true, true);
    }

    public function test_kwrap() {
        $arr = array(
            "UserID" => "UserUser",
            "UserName" => "UserUser",
            "UserDig" => "UserUser",
        );
        $arr_result = array(
            "{UserID}" => "UserUser",
            "{UserName}" => "UserUser",
            "{UserDig}" => "UserUser",
        );
        $prefix = '{';
        $suffix = '}';
        $result = ArrayTools::kwrap($arr, $prefix, $suffix);
        $this->assert_arrays_equal($result, $arr_result);
    }

    public function test_kunprefix() {
        $arr = array(
            "UserID" => "UserUser",
            "UserName" => "UserUser",
            "UserDig" => "UserUser",
        );
        $str = "User";
        $result = ArrayTools::kunprefix($arr, $str);
        $this->assert_arrays_equal($result, array(
            "ID" => "UserUser",
            "Name" => "UserUser",
            "Dig" => "UserUser",
        ));
    }

    public function test_kunsuffix() {
        $arr = array(
            "UserID" => "UserUser",
            "UserName" => "UserUser",
            "UserDig" => "UserUser",
        );
        $result_correct = array(
            "UserID" => "UserUser",
            "UserName" => "UserUser",
            "UserDig" => "UserUser",
        );
        $str = "User";
        $result = ArrayTools::kunsuffix($arr, $str);
        $this->assert_arrays_equal($result, $result_correct);
        
        $arr = array(
            "UserIDUser" => "UserUser",
            "UserNameUser" => "UserUser",
            "UserDigUser" => "UserUser",
            "NoSuffix" => "UserUser",
        );
        $result_correct = array(
            "UserID" => "UserUser",
            "UserName" => "UserUser",
            "UserDig" => "UserUser",
            "NoSuffix" => "UserUser",
        );
        $str = "User";
        $result = ArrayTools::kunsuffix($arr, $str);
        $this->assert_arrays_equal($result, $result_correct);
    }

    public function test_map_keys() {
        $array = array(
            "one" => 1,
            "two" => 2,
            "three" => 3,
            "four" => 4,
        );
        
        $key_map = array(
            "one" => "un",
            "two" => "deux",
            "three" => "trois",
        );
        
        $result_correct = array(
            "un" => 1,
            "deux" => 2,
            "trois" => 3,
            "four" => 4,
        );
        
        $result = ArrayTools::map_keys($array, $key_map);
        $this->assert_arrays_equal($result, $result_correct);
        
        $a = array(
            "a" => "a",
            "b" => "b",
            "Aardvark" => "animal",
            123 => "one-two-three",
            "Zebra" => "stripes",
        );
        // Overwrite "b"
        $map = array(
            "a" => "b",
            123 => "Zamboni",
        );
        $result = ArrayTools::map_keys($a, $map);
        $compare_result = array(
            "b" => "a",
            "Aardvark" => "animal",
            "Zamboni" => "one-two-three",
            "Zebra" => "stripes",
        );
        $this->assert_arrays_equal($result, $compare_result);
        // No overwrite
        $map = array(
            "a" => "c",
            123 => "Zamboni",
        );
        $this->assert_arrays_equal(ArrayTools::map_keys($a, $map), array(
            "b" => "b",
            "c" => "a",
            "Aardvark" => "animal",
            "Zamboni" => "one-two-three",
            "Zebra" => "stripes",
        ));
    }

    public function test_map_values() {
        $array = array(
            "one" => 1,
            "two" => 2,
            "three" => 3,
            "four" => 4,
        );
        $array = array_flip($array);
        
        $result_correct = array(
            "un" => 1,
            "deux" => 2,
            "trois" => 3,
            "four" => 4,
        );
        $result_correct = array_flip($result_correct);
        
        $value_map = array(
            "one" => "un",
            "two" => "deux",
            "three" => "trois",
        );
        
        $result = ArrayTools::map_values($array, $value_map);
        $this->assert_arrays_equal($result, $result_correct);
    }

    public function test_merge() {
        $a1 = array(
            "hello" => array(
                "now" => "yes",
                "i" => "do",
                "nooooow!" => array(
                    "where" => "are",
                    "you" => "going",
                    "what" => "do you mean",
                ),
            ),
        );
        $a2 = array(
            "hold-on" => "no",
            "hello" => array(
                "now1" => "yes",
                "i2" => "do",
                "i" => "don't",
                "nooooow!" => array(
                    "where1" => "are",
                    "you2" => "going",
                    "what3" => "do you mean",
                ),
            ),
        );
        $result = ArrayTools::merge($a1, $a2);
        
        $correct_result = array(
            'hello' => array(
                'now' => 'yes',
                'i' => 'don\'t',
                'nooooow!' => array(
                    'where' => 'are',
                    'you' => 'going',
                    'what' => 'do you mean',
                    'where1' => 'are',
                    'you2' => 'going',
                    'what3' => 'do you mean',
                ),
                'now1' => 'yes',
                'i2' => 'do',
            ),
            'hold-on' => 'no',
        );
        $this->assert_arrays_equal($result, $correct_result);
    }

    public function test_prefix() {
        $arr = array(
            0,
            1,
            2,
            3,
            4,
            5,
        );
        $str = "Homing-Pigeon-";
        $result = ArrayTools::prefix($arr, $str);
        $result_correct = array(
            "Homing-Pigeon-0",
            "Homing-Pigeon-1",
            "Homing-Pigeon-2",
            "Homing-Pigeon-3",
            "Homing-Pigeon-4",
            "Homing-Pigeon-5",
        );
        $this->assert_arrays_equal($result, $result_correct);
        
        $a = array(
            "Boy",
            "Girl",
            "Baby",
        );
        $p = "Big";
        $this->assert_arrays_equal(ArrayTools::prefix($a, $p), array(
            "BigBoy",
            "BigGirl",
            "BigBaby",
        ));
    }

    public function test_unprefix() {
        $arr = array(
            "GooBar",
            "GooBird",
            "GooPlan",
            "gooCmon",
        );
        $str = "Goo";
        $result = ArrayTools::unprefix($arr, $str);
        $result_correct = array(
            "Bar",
            "Bird",
            "Plan",
            "gooCmon",
        );
        
        $this->assert_arrays_equal($result, $result_correct);
    }

    public function test_unsuffix() {
        $arr = array(
            "0-Thing",
            "1-Thing",
            "2-Thing",
            "3-Thingy",
            "0-Thing",
        );
        $str = "-Thing";
        $result = ArrayTools::unsuffix($arr, $str);
        $result_correct = array(
            "0",
            "1",
            "2",
            "3-Thingy",
            "0",
        );
        $this->assert_arrays_equal($result, $result_correct);
    }

    public function test_trim() {
        $a = array(
            " foo ",
            " \n\t\0bar\n\t ",
        );
        $result = ArrayTools::trim($a);
        $this->assert_arrays_equal($result, array(
            "foo",
            "bar",
        ));
    }

    public function test_transpose() {
        $arr = array(
            array(
                "1",
                "2",
                "3",
            ),
            array(
                "4",
                "5",
                "6",
            ),
            array(
                "7",
                "8",
                "9",
            ),
        );
        $result = ArrayTools::transpose($arr);
        $this->assert_arrays_equal($result, array(
            array(
                "1",
                "4",
                "7",
            ),
            array(
                "2",
                "5",
                "8",
            ),
            array(
                "3",
                "6",
                "9",
            ),
        ));
        
        $arr = array(
            array(
                "1",
                "2",
                "3",
                "4",
            ),
            array(
                "5",
                "6",
                "7",
                "8",
            ),
        );
        $result = ArrayTools::transpose($arr);
        $this->assert_arrays_equal($result, array(
            array(
                "1",
                "5",
            ),
            array(
                "2",
                "6",
            ),
            array(
                "3",
                "7",
            ),
            array(
                "4",
                "8",
            ),
        ));
        
        $result2 = ArrayTools::transpose($result);
        $this->assert_arrays_equal($result2, $arr);
    }

    public function test_filter_prefix() {
        $a = array(
            "AHello" => 94,
            "Dog" => 34,
            "Hello" => 1,
            "hello_there" => 2,
            "HELLO.THERE" => new stdClass(),
            "Hello.Kitty" => "Kitty",
        );
        $ks = "hello";
        $lower = false;
        $this->assert_arrays_equal(ArrayTools::filter_prefix($a, $ks, true), array(
            "Hello" => 1,
            "hello_there" => 2,
            "HELLO.THERE" => new stdClass(),
            "Hello.Kitty" => "Kitty",
        ));
        $this->assert_arrays_equal(ArrayTools::filter_prefix($a, $ks, false), array(
            "hello_there" => 2,
        ));
        $this->assert_arrays_equal(ArrayTools::filter_prefix($a, array(
            "Hello",
            "AHello",
        ), false), array(
            "AHello" => 94,
            "Hello" => 1,
            "Hello.Kitty" => "Kitty",
        ));
        $this->assert_arrays_equal(ArrayTools::filter_prefix($a, array(
            "Hello.",
            "Dog ",
        ), false), array(
            "Hello.Kitty" => "Kitty",
        ));
        $this->assert_arrays_equal(ArrayTools::filter_prefix($a, array(
            "Hello.",
            "Dog ",
        ), true), array(
            "HELLO.THERE" => new stdClass(),
            "Hello.Kitty" => "Kitty",
        ));
    }

    public function test_filter() {
        $arr = array(
            "1",
            '2,3',
            "4",
        );
        $include = "0;2";
        $result = ArrayTools::filter($arr, $include);
        $this->assert_arrays_equal($result, array(
            "1",
            2 => "4",
        ));
        
        $include = array(
            0,
            2,
        );
        $result = ArrayTools::filter($arr, $include);
        $this->assert_arrays_equal($result, array(
            "1",
            2 => "4",
        ));
        
        $include = array(
            "0",
            "2",
        );
        $result = ArrayTools::filter($arr, $include);
        $this->assert_arrays_equal($result, array(
            "1",
            2 => "4",
        ));
        
        // $x = null;
        // $keys = null;
        // $got_exception = false;
        // try {
        // ArrayTools::filter($x, $keys);
        // } catch (Exception $e) {
        // $got_exception = true;
        // }
        // $this->assert($got_exception === true, "Exception should be thrown");
        
        $x = array(
            "A" => "Kent",
            "b" => "Ruler",
            "C" => "another",
            3 => "dogs",
        );
        
        $a = $x;
        $b = "A;b;C;3";
        $c = $x;
        $r = ArrayTools::filter($a, $b);
        $this->assert_arrays_equal($r, $c, __FILE__ . ":" . __LINE__);
        
        $a = $x;
        $b = "a;B;c;3";
        $c = array(
            3 => "dogs",
        );
        $r = ArrayTools::filter($a, $b);
        $this->assert_arrays_equal($r, $c, __FILE__ . ":" . __LINE__);
        
        $a = $x;
        $b = "A;3";
        $c = $x;
        unset($c['C']);
        unset($c['b']);
        $r = ArrayTools::filter($a, $b);
        $this->assert_arrays_equal($r, $c, __FILE__ . ":" . __LINE__);
        
        $a = array(
            "A" => "B",
            "B",
            "A",
            "C" => "D",
        );
        $ks = "A;1";
        $this->assert_arrays_equal(ArrayTools::filter($a, $ks), array(
            "A" => "B",
            1 => "A",
        ));
    }

    public function test_find() {
        $haystack = null;
        $needles = null;
        ArrayTools::find($haystack, $needles);
        
        $exclude_files = array(
            'cc_form.php',
            'want-to-be-complete',
            'setup/email.php',
            'import-log.php',
            '/ab_try.php',
            '/ab.php',
            '/keyword/update.php',
            '/report/reporter.php',
            '/report/geo.php',
            '/setup/landings-generate.php',
        );
        $needles = "/setup/landings-generate.php";
        $this->assert(ArrayTools::find($exclude_files, $needles) !== false, "Can't find $needles in " . implode(", ", $exclude_files));
    }

    public function test_has() {
        $array = array(
            0 => "hello",
            3 => "dude",
            "kitty" => "cat",
        );
        $this->assert(ArrayTools::has($array, "0;3") === true);
        $this->assert(ArrayTools::has($array, 3) === true);
        $this->assert(ArrayTools::has($array, 0) === true);
        $this->assert(ArrayTools::has($array, 'kitty') === true);
        $this->assert(ArrayTools::has($array, 1) === false);
        $this->assert(ArrayTools::has($array, 'Kitty') === false);
    }

    public function test_is_assoc() {
        $array = array(
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
        );
        $this->assert(ArrayTools::is_assoc($array) === false);
        $array[-1] = "";
        $this->assert(ArrayTools::is_assoc($array) === true);
        
        $mixed = null;
        $this->assert(ArrayTools::is_list($mixed) === false);
        $this->assert(ArrayTools::is_list(false) === false);
        $this->assert(ArrayTools::is_list(true) === false);
        $this->assert(ArrayTools::is_list(0) === false);
        $this->assert(ArrayTools::is_list(1) === false);
        $this->assert(ArrayTools::is_list("mixed") === false);
        $this->assert(ArrayTools::is_list(new stdClass()) === false);
        $this->assert(ArrayTools::is_list(array()) === true);
        $this->assert(ArrayTools::is_list(array(
            "1",
            "3",
        )) === true);
        $this->assert(ArrayTools::is_list(array(
            "1",
            2 => "3",
        )) === false);
        $this->assert(ArrayTools::is_list(array(
            "1",
            2 => "3",
            4,
            5 => "f",
        )) === false);
        $this->assert(ArrayTools::is_list(array(
            1,
            2,
            3,
            4,
            5,
            9,
        )) === true);
        $this->assert(ArrayTools::is_list(array_merge(array(
            1,
            2,
            3,
            4,
            5,
            9,
        ), array(
            "a",
            1,
            2,
            3,
            4,
            5,
            9,
        ))) === true);
        $faker = new faker();
        $faker->__set('0', 'zero');
        $faker->__set('1', 'one');
        $this->assert(ArrayTools::is_list($faker) === false);
    }

    public function test_is_list() {
        $mixed = null;
        $this->assert(ArrayTools::is_list($mixed) === false);
        $this->assert(ArrayTools::is_list(false) === false);
        $this->assert(ArrayTools::is_list(true) === false);
        $this->assert(ArrayTools::is_list(0) === false);
        $this->assert(ArrayTools::is_list(1) === false);
        $this->assert(ArrayTools::is_list("mixed") === false);
        $this->assert(ArrayTools::is_list(new stdClass()) === false);
        $this->assert(ArrayTools::is_list(array()) === true);
        $this->assert(ArrayTools::is_list(array(
            "1",
            "3",
        )) === true);
        $this->assert(ArrayTools::is_list(array(
            "1",
            2 => "3",
        )) === false);
        $this->assert(ArrayTools::is_list(array(
            "1",
            2 => "3",
            4,
            5 => "f",
        )) === false);
        $this->assert(ArrayTools::is_list(array(
            1,
            2,
            3,
            4,
            5,
            9,
        )) === true);
        $this->assert(ArrayTools::is_list(array_merge(array(
            1,
            2,
            3,
            4,
            5,
            9,
        ), array(
            "a",
            1,
            2,
            3,
            4,
            5,
            9,
        ))) === true);
        
        $faker = new faker();
        $faker->__set('0', 'zero');
        $faker->__set('1', 'one');
        $this->assert(ArrayTools::is_list($faker) === false);
    }

    public function test_kfind() {
        $source = array(
            "A" => "A",
            "B" => "B",
        );
        $sourcekeys = array(
            "B",
            "C",
        );
        $default = "Dude";
        $this->assert(ArrayTools::kfind($source, $sourcekeys, $default) === "B");
    }

    public function test_max() {
        $a = array(
            1,
            2,
            3,
            4,
            6,
            "513234",
            123,
            -1,
            52145,
        );
        $default = null;
        $result = ArrayTools::max($a, $default);
        $this->assert($result === "513234");
        $this->assert("$result === 513234");
        
        $a = array(
            "1",
            2,
            3,
            99,
            10000,
            12,
            94123123,
            "94123124",
        );
        $this->assert(ArrayTools::max($a) == 94123124, ArrayTools::max($a) . " == 94123124");
    }

    public function test_min() {
        $a = array(
            "-412312",
            4,
            61234,
            6123,
            3,
            -51235412,
            3,
            123,
            5,
        );
        $default = null;
        $this->assert(ArrayTools::min($a, $default) === -51235412);
        
        $a = array(
            "1",
            2,
            3,
            99,
            10000,
            12,
            94123123,
            "94123124",
            "-23412312",
            10000,
            12,
            94123123,
        );
        $this->assert(ArrayTools::min($a) == -23412312, ArrayTools::min($a) . " == -23412312");
    }

    public function test_path() {
        $array = array(
            "path" => array(
                "to" => array(
                    "the" => array(
                        "deep" => array(
                            "portion" => 1231242,
                        ),
                        "funzone" => "pigeon",
                    ),
                ),
            ),
        );
        $path = "path.to.the.deep.portion";
        $default = null;
        $this->assert(apath($array, $path, $default) === 1231242);
        $path = "path.to.the.funzone";
        $this->assert(apath($array, $path, $default) === "pigeon");
        $path = "path.to.the.funzone.thing";
        $this->assert(apath($array, $path, "uh-uh") === "uh-uh");
    }

    public function test_stristr() {
        $haystack = "A rather long sentence";
        $needles = array(
            "Aa",
            "rathI",
            "lonGs",
            "sentance",
        );
        
        $this->assert(ArrayTools::stristr($haystack, $needles) === false);
        $haystack = "A rather long senaatence";
        $this->assert(ArrayTools::stristr($haystack, $needles) === 0);
        
        $haystack = "A rather long sentence rathI";
        $this->assert(ArrayTools::stristr($haystack, $needles) === 1);
        
        $haystack = "lonGSA rather long sentence";
        $this->assert(ArrayTools::stristr($haystack, $needles) === 2);
        
        $haystack = "A rather long sentance";
        $this->assert(ArrayTools::stristr($haystack, $needles) === 3);
    }

    public function test_strstr() {
        $needles = array(
            "Aa",
            "rathI",
            "lonGs",
            "sentance",
        );
        
        $haystack = "A rather long sentence";
        $this->assert(ArrayTools::strstr($haystack, $needles) === false);
        
        $haystack = "A rather long senaatence";
        $this->assert(ArrayTools::strstr($haystack, $needles) === false);
        
        $haystack = "A rather long sentence rathI";
        $this->assert(ArrayTools::strstr($haystack, $needles) === 1);
        
        $haystack = "lonGSA rather long sentence";
        $this->assert(ArrayTools::strstr($haystack, $needles) === false);
        
        $haystack = "A rather long sentance";
        $this->assert(ArrayTools::strstr($haystack, $needles) === 3);
    }

    public function test_append() {
        $arr = null;
        $k = null;
        $v = null;
        ArrayTools::append($arr, $k, $v);
        echo basename(__FILE__) . ": success\n";
    }

    public function test_rtrim() {
        $a = array(
            "efgabcdabcdddabad",
            "ABCabcdddd",
            "abcdeddddddddddddd",
        );
        $charlist = "abcd";
        $this->assert_equal(ArrayTools::rtrim($a, $charlist), array(
            "efg",
            "ABC",
            "abcde",
        ));
    }

    public function test_clean() {
        $a = null;
        $value = '';
        ArrayTools::clean($a, $value);
    }

    public function test_kfilter() {
        $a = null;
        $include = false;
        $exclude = false;
        $lower = true;
        ArrayTools::kfilter($a, $include, $exclude, $lower);
    }

    public function test_kltrim() {
        $a = null;
        $charlist = "a";
        $this->assert_equal(ArrayTools::kltrim(array(
            "aaaab" => "aaaab",
            "AAAAb" => "AAAAb",
            "baaaa" => "baaaa",
        ), $charlist), array(
            "b" => "aaaab",
            "AAAAb" => "AAAAb",
            "baaaa" => "baaaa",
        ));
    }

    public function test_kpair() {
        $array = array();
        $delim = ' ';
        ArrayTools::kpair($array, $delim);
    }

    public function test_ksuffix() {
        $arr = array();
        $str = null;
        ArrayTools::ksuffix($arr, $str);
    }

    public function test_ltrim() {
        $a = null;
        $charlist = "aA";
        $this->assert_equal(ArrayTools::ltrim(array(
            "aaaab" => "aaaab",
            "AAAAb" => "AAAAb",
            "baaaa" => "baaaa",
        ), $charlist), array(
            "aaaab" => "b",
            "AAAAb" => "b",
            "baaaa" => "baaaa",
        ));
    }

    public function test_preg_quote() {
        $string = null;
        $delimiter = null;
        ArrayTools::preg_quote($string, $delimiter);
    }

    public function test_prepend() {
        $arr = null;
        $k = null;
        $v = null;
        ArrayTools::prepend($arr, $k, $v);
    }

    public function test_trim_clean() {
        $arr = array(
            "    \n\r\n\n\r\t\t\t\thello\t\n\r",
            "\n\n\n\r\t\n",
            " world",
            " ",
            "",
            null,
            false,
        );
        $value = '';
        $result = ArrayTools::trim_clean($arr);
        $this->assert_arrays_equal($result, array(
            0 => "hello",
            2 => "world",
        ));
    }
}
class faker {
    public function __set($n, $v) {
        $this->$n = $v;
    }
}
