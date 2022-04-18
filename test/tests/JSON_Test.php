<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class JSON_Test extends Test_Unit {
	/**
	 * @expectedException zesk\Exception_Parse
	 */
	public function test_decode(): void {
		JSON::decode("{");
	}

	/**
	 * @expectedException zesk\Exception_Parse
	 */
	public function test_decode_parse(): void {
		JSON::decode("{");
	}

	public function test_malencode(): void {
		$content = file_get_contents($this->application->path('test/test-data/json/malencode.txt'));
		$content = ["Hello" => JSON::prepare($content)];
		$this->assert_string_begins(json_encode($content), '{"Hello":');
	}

	public function test_decode_null(): void {
		$this->assertNull(JSON::decode("null"));
	}

	public function data_encode(): array {
		return [
			[
				"null",
				null,
			],
			[
				'{"0":{"Hello":"Dude","1241`2":"odd","__2341":2,"a459123":{}},"1":false,"2":true,"3":12312312,"4":"A string","5":"A string","*do encode result":"document.referrer","6":null,"dog":null}',
				[
					[
						"Hello" => "Dude",
						"1241`2" => "odd",
						"__2341" => 2,
						"a459123" => new \stdClass(),
					],
					false,
					true,
					12312312,
					"A string",
					'A string',
					"*do encode result" => "document.referrer",
					null,
					"dog" => null,
				],
			],
		];
	}

	public function data_encodex(): array {
		return [
			[
				"null",
				null,
			],
			[
				'{"0":{Hello:"Dude","1241`2":"odd",__2341:2,a459123:{}},"1":false,"2":true,"3":12312312,"4":"A string","5":"A string","don\'t encode result":document.referrer,"6":null,dog:null}',
				[
					[
						"Hello" => "Dude",
						"1241`2" => "odd",
						"__2341" => 2,
						"a459123" => new \stdClass(),
					],
					false,
					true,
					12312312,
					"A string",
					'A string',
					"*don't encode result" => "document.referrer",
					null,
					"dog" => null,
				],
			],
		];
	}

	/**
	 * @param $expected
	 * @param $mixed
	 * @return void
	 * @dataProvider data_encode
	 */
	public function test_encode(string $expected, mixed $mixed): void {
		$this->assertEquals($expected, JSON::encode($mixed));
	}

	/**
	 * @param $expected
	 * @param $mixed
	 * @return void
	 * @dataProvider data_encodex
	 */
	public function test_encodex(string $expected, mixed $mixed): void {
		$this->assertEquals($expected, JSON::encodex($mixed));
	}

	public function data_object_member_name_quote(): array {
		return [
			["", "\"\""],
			["a", "a"],
			["dude_123", "dude_123"],
			[" ", "\" \""],
			["a b", "\"a b\""],
			["@#", "\"@#\""],
			["Equalit'", "\"Equalit'\""],
			["egalité", "\"egalité\""],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_object_member_name_quote
	 */
	public function test_object_member_name_quote($name, $expected): void {
		$this->assertEquals($expected, JSON::object_member_name_quote($name));
	}

	public function test_quote(): void {
		$m = null;
		$this->assert(JSON::quote("this.is.a.word") === "\"this.is.a.word\"");
		$this->assert(JSON::quote("thingy") === "\"thingy\"", "\"" . JSON::quote("thingy") . "\" === \"thingy\"");
		$this->assert(JSON::quote("2thingy") === "\"2thingy\"");
		$this->assert(JSON::quote("thingy2") === "\"thingy2\"");
		$this->assert(JSON::quote("th-ingy2") === "\"th-ingy2\"");
	}

	public function data_valid_member_name(): array {
		return [
			[false, ""],
			[true, "a"],
			[true, "dude_123"],
			[false, " "],
			[false, "a b"],
			[false, "@#"],
			[false, "Equalit'"],
			[false, "egalité"],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_valid_member_name
	 */
	public function test_valid_member_name($expected, $name): void {
		$this->assertEquals($expected, JSON::valid_member_name($name));
	}

	public function internal_values() {
		$obj = new \stdClass();
		$obj->foo = "foo";
		$obj->_thing_to_save = [
			"1",
			"2",
			"5",
		];
		$obj->another = new \stdClass();

		return [
			[
				null,
			],
			[
				true,
			],
			[
				false,
			],
			[
				[],
			],
			[
				[
					"a" => "b",
				],
			],
			[
				[
					[
						"hello" => "world",
						"how" => "zit",
					],
				],
			],
			[
				$obj,
			],
		];
	}

	/**
	 * @data_provider internal_values
	 * @param mixed $mixed
	 */
	public function test_internal($mixed): void {
		$encode = JSON::encode($mixed);
		$decode = JSON::zesk_decode($encode, false);
		$encode2 = JSON::encode($decode);
		$this->assert_equal($encode, $encode2);
	}

	public function internal_expected_values() {
		return [
			[
				'{ "fructose.marketacumen.com": "fructose" }',
				[
					'fructose.marketacumen.com' => 'fructose',
				],
			],
			[
				'{
    "glossary": {
        "title": "example glossary",
		"GlossDiv": {
            "title": "S",
			"GlossList": {
                "GlossEntry": {
                    "ID": "SGML",
					"SortAs": "SGML",
					"GlossTerm": "Standard Generalized Markup Language",
					"Acronym": "SGML",
					"Abbrev": "ISO 8879:1986",
					"GlossDef": {
                        "para": "A meta-markup language, used to create markup languages such as DocBook.",
						"GlossSeeAlso": ["GML", "XML"]
                    },
					"GlossSee": "markup"
                }
            }
        }
    }
}',
				[
					"glossary" => [
						"title" => "example glossary",
						"GlossDiv" => [
							"title" => "S",
							"GlossList" => [
								"GlossEntry" => [
									"ID" => "SGML",
									"SortAs" => "SGML",
									"GlossTerm" => "Standard Generalized Markup Language",
									"Acronym" => "SGML",
									"Abbrev" => "ISO 8879:1986",
									"GlossDef" => [
										"para" => "A meta-markup language, used to create markup languages such as DocBook.",
										"GlossSeeAlso" => [
											"GML",
											"XML",
										],
									],
									"GlossSee" => "markup",
								],
							],
						],
					],
				],
			],
			[
				'{"truly":true,"falsely":false,"nullish":null,"floaty":  -5.812342e+24,"intlike":51231412,"listy":[0,1,2,3,5]}',
				[
					"truly" => true,
					"falsely" => false,
					"nullish" => null,
					"floaty" => -58123.42e+20,
					"intlike" => 51231412,
					"listy" => [
						0,
						1,
						2,
						3,
						5,
					],
				],
			],
		];
	}

	/**
	 * @data_provider internal_expected_values
	 * @param unknown $mixed
	 * @param unknown $expected
	 */
	public function test_parser($mixed, $expected): void {
		$actual = JSON::zesk_decode($mixed);
		$this->assert_equal($actual, $expected);
	}
}
