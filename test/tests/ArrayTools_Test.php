<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 */

namespace zesk;

use \stdClass;

class ArrayTools_Test extends Test_Unit {
	public function test_change_value_case(): void {
		$a = [
			"a" => "ABC",
			"b" => "BCD",
			"c" => "def",
		];
		$this->assert_arrays_equal(ArrayTools::change_value_case($a), [
			"a" => "abc",
			"b" => "bcd",
			"c" => "def",
		]);

		$a = [
			"a" => "A",
			"b" => "lowercasething",
			"C" => "LoWeRCaSeThInG",
		];
		$this->assert_arrays_equal(ArrayTools::change_value_case($a), [
			"a" => "a",
			"b" => "lowercasething",
			"C" => "lowercasething",
		]);
	}

	public function test_flip_copy(): void {
		$x = [
			"A",
			"B",
			"C",
		];
		$this->assert_arrays_equal(ArrayTools::flip_copy($x, true), [
			"a" => "A",
			"b" => "B",
			"c" => "C",
		]);
		$this->assert_arrays_equal(ArrayTools::flip_copy($x, false), [
			"A" => "A",
			"B" => "B",
			"C" => "C",
		]);

		$x = [
			"one",
			"two",
			"three",
		];
		$lower = true;
		$result = ArrayTools::flip_copy($x, $lower);
		$this->assert_arrays_equal($result, [
			"one" => "one",
			"two" => "two",
			"three" => "three",
		]);

		$result = ArrayTools::flip_copy([
			"1",
			"2",
			"3",
			"fish",
			"4",
			"5",
			"1",
			"2",
		], $lower);
		$this->assert_arrays_equal($result, [
			"1" => "1",
			"2" => "2",
			"3" => "3",
			"fish" => "fish",
			"4" => "4",
			"5" => "5",
		]);
		$x = [
			"A",
			"B",
		];
		$c = ArrayTools::flip_copy($x, true);
		$this->assert_arrays_equal($c, [
			"a" => "A",
			"b" => "B",
		]);
		$c = ArrayTools::flip_copy($x, false);
		$this->assert_arrays_equal($c, [
			"A" => "A",
			"B" => "B",
		]);
	}

	public function test_wrap(): void {
		$a = [];
		$prefix = "";
		$suffix = "";
		$this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), [], "", true, true);
		$a = [
			"a",
		];
		$this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $a);

		$prefix = "a";
		$suffix = "";
		$a = [
			"a" => "b",
		];
		$b = [
			"a" => "ab",
		];
		$this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $b, "", true, true);

		$prefix = "a";
		$suffix = "bb";
		$a = ["a" => "b", ];
		$b = [
			"a" => "abbb",
		];
		$this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $b, "", true, true);

		$prefix = "a";
		$suffix = "cc";
		$a = [
			"a" => "b",
			2 => "b",
			412312 => 54,
		];
		$b = [
			"a" => "abcc",
			2 => "abcc",
			412312 => "a54cc",
		];
		$this->assert_arrays_equal(ArrayTools::wrap($a, $prefix, $suffix), $b, "", true, true);

		$arr = [
			"a",
			"b",
			"c",
		];
		$prefix = '{';
		$suffix = '}';
		$result = ArrayTools::wrap($arr, $prefix, $suffix);
		$result_correct = [
			"{a}",
			"{b}",
			"{c}",
		];

		$this->assert_arrays_equal($result, $result_correct);
	}

	public function test_kprefix(): void {
		$source = [
			"a" => "a",
			"b" => "b",
			"c" => "c",
		];
		$dest = [
			"Dudea" => "a",
			"Dudeb" => "b",
			"Dudec" => "c",
		];

		$this->assert_arrays_equal(ArrayTools::kprefix($source, "Dude"), $dest, "ArrayTools::kprefix");
	}

	public function test_suffix(): void {
		$a = [
			"Boy",
			"Girl",
			"Baby",
		];
		$p = "Big";
		$this->assert_arrays_equal(ArrayTools::suffix($a, $p), [
			"BoyBig",
			"GirlBig",
			"BabyBig",
		]);
		$arr = [
			0,
			1,
			2,
			3,
			4,
			5,
			6,
		];
		$str = "-Things";
		$result = ArrayTools::suffix($arr, $str);
		$result_correct = [
			"0-Things",
			"1-Things",
			"2-Things",
			"3-Things",
			"4-Things",
			"5-Things",
			"6-Things",
		];
		$this->assert_arrays_equal($result, $result_correct);
	}

	public function test_remove(): void {
		$arr = [
			0,
			1,
			2,
			3,
			4,
			5,
			6,
		];
		$keys = "0;2;4;6";
		$result = ArrayTools::remove($arr, to_list($keys));
		$result_correct = [
			1 => 1,
			3 => 3,
			5 => 5,
		];
		$this->assert_arrays_equal($result, $result_correct);
	}

	public function test_include_exclude(): void {
		$a = [
			'a',
			'b',
			'c',
			'd',
		];
		$include = null;
		$exclude = null;
		$lower = true;
		$result = ArrayTools::include_exclude($a, 'a;b;e', $exclude, $lower);
		Debug::output($result);
		$this->assert_arrays_equal($result, [
			'a',
			'b',
		]);
		$result = ArrayTools::include_exclude($a, null, 'a;b;e', $lower);
		Debug::output($result);
		$this->assert_arrays_equal($result, [
			2 => 'c',
			3 => 'd',
		]);

		$a = [
			'a',
			'B',
			'c',
			'd',
		];
		// Default is to retain case
		$result = ArrayTools::include_exclude($a, null, 'a;b;e');
		$this->assert_arrays_equal($result, [
			1 => 'B',
			2 => 'c',
			3 => 'd',
		]);
	}

	public function test_increment(): void {
		$arr = [];
		$k = "item";
		$result = ArrayTools::increment($arr, $k);
		$this->assert("$result === 1");
		$this->assert_arrays_equal($arr, [
			"item" => 1,
		]);
		$result = ArrayTools::increment($arr, $k);
		$this->assert("$result === 2");
		$this->assert_arrays_equal($arr, [
			"item" => 2,
		]);
		$k = 'decimal';
		$result = ArrayTools::increment($arr, $k, 2.1);
		$this->assert_equal($result, 2.1);
		$this->assert_arrays_equal($arr, [
			"item" => 2,
			"decimal" => 2.1,
		]);
		$result = ArrayTools::increment($arr, $k, 3.2);
		$this->assert_equal($result, 5.3);
		// 		$this->assert_arrays_equal($arr, array(
		// 			"item" => 2,
		// 			"decimal" => 5.3
		// 		));
	}

	public function test_insert(): void {
		$arr1 = [
			"x" => "x",
			"a" => "a",
			"y" => "y",
		];
		$key = "a";
		$arr2 = [
			"p" => "p",
		];
		$this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, false), [
			"x" => "x",
			"a" => "a",
			"p" => "p",
			"y" => "y",
		], "basic after", true, true);
		$this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, true), [
			"x" => "x",
			"p" => "p",
			"a" => "a",
			"y" => "y",
		], "basic before", true, true);

		$arr2 = [
			"p" => "p",
			"q" => "q",
		];

		$this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, false), [
			"x" => "x",
			"a" => "a",
			"p" => "p",
			"q" => "q",
			"y" => "y",
		], "multi after", true, true);
		$this->assert_arrays_equal(ArrayTools::insert($arr1, $key, $arr2, true), [
			"x" => "x",
			"p" => "p",
			"q" => "q",
			"a" => "a",
			"y" => "y",
		], "multi before", true, true);
	}

	public function test_kwrap(): void {
		$arr = [
			"UserID" => "UserUser",
			"UserName" => "UserUser",
			"UserDig" => "UserUser",
		];
		$arr_result = [
			"{UserID}" => "UserUser",
			"{UserName}" => "UserUser",
			"{UserDig}" => "UserUser",
		];
		$prefix = '{';
		$suffix = '}';
		$result = ArrayTools::kwrap($arr, $prefix, $suffix);
		$this->assert_arrays_equal($result, $arr_result);
	}

	public function test_kunprefix(): void {
		$arr = [
			"UserID" => "UserUser",
			"UserName" => "UserUser",
			"UserDig" => "UserUser",
		];
		$str = "User";
		$result = ArrayTools::kunprefix($arr, $str);
		$this->assert_arrays_equal($result, [
			"ID" => "UserUser",
			"Name" => "UserUser",
			"Dig" => "UserUser",
		]);
	}

	public function test_kunsuffix(): void {
		$arr = [
			"UserID" => "UserUser",
			"UserName" => "UserUser",
			"UserDig" => "UserUser",
		];
		$result_correct = [
			"UserID" => "UserUser",
			"UserName" => "UserUser",
			"UserDig" => "UserUser",
		];
		$str = "User";
		$result = ArrayTools::kunsuffix($arr, $str);
		$this->assert_arrays_equal($result, $result_correct);

		$arr = [
			"UserIDUser" => "UserUser",
			"UserNameUser" => "UserUser",
			"UserDigUser" => "UserUser",
			"NoSuffix" => "UserUser",
		];
		$result_correct = [
			"UserID" => "UserUser",
			"UserName" => "UserUser",
			"UserDig" => "UserUser",
			"NoSuffix" => "UserUser",
		];
		$str = "User";
		$result = ArrayTools::kunsuffix($arr, $str);
		$this->assert_arrays_equal($result, $result_correct);
	}

	public function test_map_keys(): void {
		$array = [
			"one" => 1,
			"two" => 2,
			"three" => 3,
			"four" => 4,
		];

		$key_map = [
			"one" => "un",
			"two" => "deux",
			"three" => "trois",
		];

		$result_correct = [
			"un" => 1,
			"deux" => 2,
			"trois" => 3,
			"four" => 4,
		];

		$result = ArrayTools::map_keys($array, $key_map);
		$this->assert_arrays_equal($result, $result_correct);

		$a = [
			"a" => "a",
			"b" => "b",
			"Aardvark" => "animal",
			123 => "one-two-three",
			"Zebra" => "stripes",
		];
		// Overwrite "b"
		$map = [
			"a" => "b",
			123 => "Zamboni",
		];
		$result = ArrayTools::map_keys($a, $map);
		$compare_result = [
			"b" => "a",
			"Aardvark" => "animal",
			"Zamboni" => "one-two-three",
			"Zebra" => "stripes",
		];
		$this->assert_arrays_equal($result, $compare_result);
		// No overwrite
		$map = [
			"a" => "c",
			123 => "Zamboni",
		];
		$this->assert_arrays_equal(ArrayTools::map_keys($a, $map), [
			"b" => "b",
			"c" => "a",
			"Aardvark" => "animal",
			"Zamboni" => "one-two-three",
			"Zebra" => "stripes",
		]);
	}

	public function test_map_values(): void {
		$array = [
			"one" => 1,
			"two" => 2,
			"three" => 3,
			"four" => 4,
		];
		$array = array_flip($array);

		$result_correct = [
			"un" => 1,
			"deux" => 2,
			"trois" => 3,
			"four" => 4,
		];
		$result_correct = array_flip($result_correct);

		$value_map = [
			"one" => "un",
			"two" => "deux",
			"three" => "trois",
		];

		$result = ArrayTools::map_values($array, $value_map);
		$this->assert_arrays_equal($result, $result_correct);
	}

	public function test_merge(): void {
		$a1 = [
			"hello" => [
				"now" => "yes",
				"i" => "do",
				"nooooow!" => [
					"where" => "are",
					"you" => "going",
					"what" => "do you mean",
				],
			],
		];
		$a2 = [
			"hold-on" => "no",
			"hello" => [
				"now1" => "yes",
				"i2" => "do",
				"i" => "don't",
				"nooooow!" => [
					"where1" => "are",
					"you2" => "going",
					"what3" => "do you mean",
				],
			],
		];
		$result = ArrayTools::merge($a1, $a2);

		$correct_result = [
			'hello' => [
				'now' => 'yes',
				'i' => 'don\'t',
				'nooooow!' => [
					'where' => 'are',
					'you' => 'going',
					'what' => 'do you mean',
					'where1' => 'are',
					'you2' => 'going',
					'what3' => 'do you mean',
				],
				'now1' => 'yes',
				'i2' => 'do',
			],
			'hold-on' => 'no',
		];
		$this->assert_arrays_equal($result, $correct_result);
	}

	public function test_prefix(): void {
		$arr = [
			0,
			1,
			2,
			3,
			4,
			5,
		];
		$str = "Homing-Pigeon-";
		$result = ArrayTools::prefix($arr, $str);
		$result_correct = [
			"Homing-Pigeon-0",
			"Homing-Pigeon-1",
			"Homing-Pigeon-2",
			"Homing-Pigeon-3",
			"Homing-Pigeon-4",
			"Homing-Pigeon-5",
		];
		$this->assert_arrays_equal($result, $result_correct);

		$a = [
			"Boy",
			"Girl",
			"Baby",
		];
		$p = "Big";
		$this->assert_arrays_equal(ArrayTools::prefix($a, $p), [
			"BigBoy",
			"BigGirl",
			"BigBaby",
		]);
	}

	public function test_unprefix(): void {
		$arr = [
			"GooBar",
			"GooBird",
			"GooPlan",
			"gooCmon",
		];
		$str = "Goo";
		$result = ArrayTools::unprefix($arr, $str);
		$result_correct = [
			"Bar",
			"Bird",
			"Plan",
			"gooCmon",
		];

		$this->assert_arrays_equal($result, $result_correct);
	}

	public function test_unsuffix(): void {
		$arr = [
			"0-Thing",
			"1-Thing",
			"2-Thing",
			"3-Thingy",
			"0-Thing",
		];
		$str = "-Thing";
		$result = ArrayTools::unsuffix($arr, $str);
		$result_correct = [
			"0",
			"1",
			"2",
			"3-Thingy",
			"0",
		];
		$this->assert_arrays_equal($result, $result_correct);
	}

	public function test_trim(): void {
		$a = [
			" foo ",
			" \n\t\0bar\n\t ",
		];
		$result = ArrayTools::trim($a);
		$this->assert_arrays_equal($result, [
			"foo",
			"bar",
		]);
	}

	public function test_transpose(): void {
		$arr = [
			[
				"1",
				"2",
				"3",
			],
			[
				"4",
				"5",
				"6",
			],
			[
				"7",
				"8",
				"9",
			],
		];
		$result = ArrayTools::transpose($arr);
		$this->assert_arrays_equal($result, [
			[
				"1",
				"4",
				"7",
			],
			[
				"2",
				"5",
				"8",
			],
			[
				"3",
				"6",
				"9",
			],
		]);

		$arr = [
			[
				"1",
				"2",
				"3",
				"4",
			],
			[
				"5",
				"6",
				"7",
				"8",
			],
		];
		$result = ArrayTools::transpose($arr);
		$this->assert_arrays_equal($result, [
			[
				"1",
				"5",
			],
			[
				"2",
				"6",
			],
			[
				"3",
				"7",
			],
			[
				"4",
				"8",
			],
		]);

		$result2 = ArrayTools::transpose($result);
		$this->assert_arrays_equal($result2, $arr);
	}

	public function test_filter_prefix(): void {
		$a = [
			"AHello" => 94,
			"Dog" => 34,
			"Hello" => 1,
			"hello_there" => 2,
			"HELLO.THERE" => new stdClass(),
			"Hello.Kitty" => "Kitty",
		];
		$ks = "hello";
		$lower = false;
		$this->assert_arrays_equal(ArrayTools::filter_prefix($a, $ks, true), [
			"Hello" => 1,
			"hello_there" => 2,
			"HELLO.THERE" => new stdClass(),
			"Hello.Kitty" => "Kitty",
		]);
		$this->assert_arrays_equal(ArrayTools::filter_prefix($a, $ks, false), [
			"hello_there" => 2,
		]);
		$this->assert_arrays_equal(ArrayTools::filter_prefix($a, [
			"Hello",
			"AHello",
		], false), [
			"AHello" => 94,
			"Hello" => 1,
			"Hello.Kitty" => "Kitty",
		]);
		$this->assert_arrays_equal(ArrayTools::filter_prefix($a, [
			"Hello.",
			"Dog ",
		], false), [
			"Hello.Kitty" => "Kitty",
		]);
		$this->assert_arrays_equal(ArrayTools::filter_prefix($a, [
			"Hello.",
			"Dog ",
		], true), [
			"HELLO.THERE" => new stdClass(),
			"Hello.Kitty" => "Kitty",
		]);
	}

	public function test_filter(): void {
		$arr = [
			"1",
			'2,3',
			"4",
		];
		$include = "0;2";
		$result = ArrayTools::filter($arr, $include);
		$this->assert_arrays_equal($result, [
			"1",
			2 => "4",
		]);

		$include = [
			0,
			2,
		];
		$result = ArrayTools::filter($arr, $include);
		$this->assert_arrays_equal($result, [
			"1",
			2 => "4",
		]);

		$include = [
			"0",
			"2",
		];
		$result = ArrayTools::filter($arr, $include);
		$this->assert_arrays_equal($result, [
			"1",
			2 => "4",
		]);

		// $x = null;
		// $keys = null;
		// $got_exception = false;
		// try {
		// ArrayTools::filter($x, $keys);
		// } catch (Exception $e) {
		// $got_exception = true;
		// }
		// $this->assert($got_exception === true, "Exception should be thrown");

		$x = [
			"A" => "Kent",
			"b" => "Ruler",
			"C" => "another",
			3 => "dogs",
		];

		$a = $x;
		$b = "A;b;C;3";
		$c = $x;
		$r = ArrayTools::filter($a, $b);
		$this->assert_arrays_equal($r, $c, __FILE__ . ":" . __LINE__);

		$a = $x;
		$b = "a;B;c;3";
		$c = [
			3 => "dogs",
		];
		$r = ArrayTools::filter($a, $b);
		$this->assert_arrays_equal($r, $c, __FILE__ . ":" . __LINE__);

		$a = $x;
		$b = "A;3";
		$c = $x;
		unset($c['C']);
		unset($c['b']);
		$r = ArrayTools::filter($a, $b);
		$this->assert_arrays_equal($r, $c, __FILE__ . ":" . __LINE__);

		$a = [
			"A" => "B",
			"B",
			"A",
			"C" => "D",
		];
		$ks = "A;1";
		$this->assert_arrays_equal(ArrayTools::filter($a, $ks), [
			"A" => "B",
			1 => "A",
		]);
	}

	public function test_find(): void {
		$haystack = null;
		$needles = null;
		ArrayTools::find($haystack, $needles);

		$exclude_files = [
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
		];
		$needles = "/setup/landings-generate.php";
		$this->assert(ArrayTools::find($exclude_files, $needles) !== false, "Can't find $needles in " . implode(", ", $exclude_files));
	}

	public function test_has(): void {
		$array = [
			0 => "hello",
			3 => "dude",
			"kitty" => "cat",
		];
		$this->assert(ArrayTools::has($array, "0;3") === true);
		$this->assert(ArrayTools::has($array, 3) === true);
		$this->assert(ArrayTools::has($array, 0) === true);
		$this->assert(ArrayTools::has($array, 'kitty') === true);
		$this->assert(ArrayTools::has($array, 1) === false);
		$this->assert(ArrayTools::has($array, 'Kitty') === false);
	}

	public function test_is_assoc(): void {
		$array = [
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
		];
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
		$this->assert(ArrayTools::is_list([]) === true);
		$this->assert(ArrayTools::is_list([
				"1",
				"3",
			]) === true);
		$this->assert(ArrayTools::is_list([
				"1",
				2 => "3",
			]) === false);
		$this->assert(ArrayTools::is_list([
				"1",
				2 => "3",
				4,
				5 => "f",
			]) === false);
		$this->assert(ArrayTools::is_list([
				1,
				2,
				3,
				4,
				5,
				9,
			]) === true);
		$this->assert(ArrayTools::is_list(array_merge([
				1,
				2,
				3,
				4,
				5,
				9,
			], [
				"a",
				1,
				2,
				3,
				4,
				5,
				9,
			])) === true);
		$faker = new faker();
		$faker->__set('0', 'zero');
		$faker->__set('1', 'one');
		$this->assert(ArrayTools::is_list($faker) === false);
	}

	public function test_is_list(): void {
		$mixed = null;
		$this->assert(ArrayTools::is_list($mixed) === false);
		$this->assert(ArrayTools::is_list(false) === false);
		$this->assert(ArrayTools::is_list(true) === false);
		$this->assert(ArrayTools::is_list(0) === false);
		$this->assert(ArrayTools::is_list(1) === false);
		$this->assert(ArrayTools::is_list("mixed") === false);
		$this->assert(ArrayTools::is_list(new stdClass()) === false);
		$this->assert(ArrayTools::is_list([]) === true);
		$this->assert(ArrayTools::is_list([
				"1",
				"3",
			]) === true);
		$this->assert(ArrayTools::is_list([
				"1",
				2 => "3",
			]) === false);
		$this->assert(ArrayTools::is_list([
				"1",
				2 => "3",
				4,
				5 => "f",
			]) === false);
		$this->assert(ArrayTools::is_list([
				1,
				2,
				3,
				4,
				5,
				9,
			]) === true);
		$this->assert(ArrayTools::is_list(array_merge([
				1,
				2,
				3,
				4,
				5,
				9,
			], [
				"a",
				1,
				2,
				3,
				4,
				5,
				9,
			])) === true);

		$faker = new faker();
		$faker->__set('0', 'zero');
		$faker->__set('1', 'one');
		$this->assert(ArrayTools::is_list($faker) === false);
	}

	public function test_kfind(): void {
		$source = [
			"A" => "A",
			"B" => "B",
		];
		$sourcekeys = [
			"B",
			"C",
		];
		$default = "Dude";
		$this->assert(ArrayTools::kfind($source, $sourcekeys, $default) === "B");
	}

	public function test_max(): void {
		$a = [
			1,
			2,
			3,
			4,
			6,
			"513234",
			123,
			-1,
			52145,
		];
		$default = null;
		$result = ArrayTools::max($a, $default);
		$this->assert($result === "513234");
		$this->assert("$result === 513234");

		$a = [
			"1",
			2,
			3,
			99,
			10000,
			12,
			94123123,
			"94123124",
		];
		$this->assert(ArrayTools::max($a) == 94123124, ArrayTools::max($a) . " == 94123124");
	}

	public function test_min(): void {
		$a = [
			"-412312",
			4,
			61234,
			6123,
			3,
			-51235412,
			3,
			123,
			5,
		];
		$default = null;
		$this->assert(ArrayTools::min($a, $default) === -51235412);

		$a = [
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
		];
		$this->assert(ArrayTools::min($a) == -23412312, ArrayTools::min($a) . " == -23412312");
	}

	public function test_path(): void {
		$array = [
			"path" => [
				"to" => [
					"the" => [
						"deep" => [
							"portion" => 1231242,
						],
						"funzone" => "pigeon",
					],
				],
			],
		];
		$path = "path.to.the.deep.portion";
		$default = null;
		$this->assert(apath($array, $path, $default) === 1231242);
		$path = "path.to.the.funzone";
		$this->assert(apath($array, $path, $default) === "pigeon");
		$path = "path.to.the.funzone.thing";
		$this->assert(apath($array, $path, "uh-uh") === "uh-uh");
	}

	public function test_stristr(): void {
		$haystack = "A rather long sentence";
		$needles = [
			"Aa",
			"rathI",
			"lonGs",
			"sentance",
		];

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

	public function test_strstr(): void {
		$needles = [
			"Aa",
			"rathI",
			"lonGs",
			"sentance",
		];

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

	public function test_append(): void {
		$arr = null;
		$k = null;
		$v = null;
		ArrayTools::append($arr, $k, $v);
		echo basename(__FILE__) . ": success\n";
	}

	public function test_rtrim(): void {
		$a = [
			"efgabcdabcdddabad",
			"ABCabcdddd",
			"abcdeddddddddddddd",
		];
		$charlist = "abcd";
		$this->assert_equal(ArrayTools::rtrim($a, $charlist), [
			"efg",
			"ABC",
			"abcde",
		]);
	}

	public function test_clean(): void {
		$a = null;
		$value = '';
		$this->assertEquals([2 => false, 3 => null], ArrayTools::clean(["", "", false, null], ['']));
	}

	public function test_kfilter(): void {
		$a = null;
		$include = false;
		$exclude = false;
		$lower = true;
		ArrayTools::kfilter($a, $include, $exclude, $lower);
	}

	public function test_kltrim(): void {
		$a = null;
		$charlist = "a";
		$this->assert_equal(ArrayTools::kltrim([
			"aaaab" => "aaaab",
			"AAAAb" => "AAAAb",
			"baaaa" => "baaaa",
		], $charlist), [
			"b" => "aaaab",
			"AAAAb" => "AAAAb",
			"baaaa" => "baaaa",
		]);
	}

	public function test_kpair(): void {
		$array = [];
		$delim = ' ';
		ArrayTools::kpair($array, $delim);
	}

	public function test_ksuffix(): void {
		$arr = [1 => "one", 2 => "two", "three" => 3];
		$str = "duck";
		$this->assertEquals(['1duck' => 'one', '2duck' => 'two', 'threeduck' => 3], ArrayTools::ksuffix($arr, $str));
	}

	public function test_ltrim(): void {
		$charlist = "aA";
		$this->assertEquals([
			"aaaab" => "b",
			"AAAAb" => "b",
			"baaaa" => "baaaa",
		], ArrayTools::ltrim([
			"aaaab" => "aaaab",
			"AAAAb" => "AAAAb",
			"baaaa" => "baaaa",
		], $charlist));
	}

	public function test_preg_quote(): void {
		$string = null;
		$delimiter = null;
		ArrayTools::preg_quote($string, $delimiter);
	}

	public function test_prepend(): void {
		$arr = null;
		$k = null;
		$v = null;
		ArrayTools::prepend($arr, $k, $v);
	}

	public function test_trim_clean(): void {
		$arr = [
			"    \n\r\n\n\r\t\t\t\thello\t\n\r",
			"\n\n\n\r\t\n",
			" world",
			" ",
			"",
			null,
			false,
		];
		$value = '';
		$result = ArrayTools::trim_clean($arr);
		$this->assert_arrays_equal($result, [
			0 => "hello",
			2 => "world",
		]);
	}
}

class faker {
	public function __set($n, $v): void {
		$this->$n = $v;
	}
}
