<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use stdClass;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;

/**
 *
 * @author kent
 *
 */
class JSONTest extends UnitTest {
	public function test_decode(): void {
		$this->expectException(ParseException::class);
		JSON::decode('{');
	}

	public function test_decode_parse(): void {
		$this->expectException(ParseException::class);
		JSON::decode('{');
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 */
	public function test_malencode(): void {
		$content = file_get_contents($this->application->path('test/test-data/json/malencode.txt'));
		$content = ['Hello' => JSON::prepare($content)];
		$this->assertStringContainsString('{"Hello":', json_encode($content));
	}

	public static function data_prepare(): array {
		$thing1 = new class {
			public function doit(): array {
				return ['yes' => 'works'];
			}

			public function doit_again($param): array {
				return ['param' => $param];
			}

			public function __toString(): string {
				return 'thing one';
			}
		};
		$thing2 = new class {
			public string $kind = 'thing';

			public string $type = 'class';

			public int $id = 1232;
		};
		$random_string = self::randomHex(16);
		return [
			[null, null, [], []], [[], [], [], []], [['a'], ['a'], [], []], [['a' => 'b'], ['a' => 'b'], [], []],
			[['yes' => 'works'], $thing1, ['doit'], []], [['param' => 'a'], $thing1, ['doit_again'], ['a']],
			[['param' => $random_string], $thing1, ['doit_again'], [$random_string]],
			['thing one', $thing1, ['not_found'], []],
			[['kind' => 'thing', 'type' => 'class', 'id' => 1232], $thing2, ['not_found'], []],
		];
	}

	/**
	 * @param $expected
	 * @param $mixed
	 * @param $methods
	 * @param $arguments
	 * @return void
	 * @throws SemanticsException
	 * @dataProvider data_prepare
	 */
	public function test_prepare($expected, $mixed, $methods, $arguments): void {
		$this->assertEquals($expected, JSON::prepare($mixed, $methods, $arguments));
	}

	public function test_decode_null(): void {
		$this->assertNull(JSON::decode('null'));
	}

	public static function data_encode(): array {
		return [
			[null, fopen('php://stdin', 'rb')],
			['null', null, ],
			[
				'{"0":{"Hello":"Dude","1241`2":"odd","__2341":2,"a459123":{}},"1":false,"2":true,"3":12312312,"4":"A string","5":"A string","*do encode result":"document.referrer","6":null,"dog":null}',
				[
					[
						'Hello' => 'Dude', '1241`2' => 'odd', '__2341' => 2, 'a459123' => new stdClass(),
					], false, true, 12312312, 'A string', 'A string', '*do encode result' => 'document.referrer', null,
					'dog' => null,
				],
			],
		];
	}

	/**
	 * @param string|null $expected
	 * @param mixed $mixed
	 * @return void
	 * @throws SemanticsException
	 * @dataProvider data_encode
	 */
	public function test_encode(?string $expected, mixed $mixed): void {
		if ($expected === null) {
			$this->expectException(SemanticsException::class);
		}
		$this->assertEquals($expected, JSON::encode($mixed));
	}

	/**
	 * @param string|null $expected
	 * @param mixed $mixed
	 * @return void
	 * @dataProvider data_encodeSpecial
	 */
	public function test_encodeSpecial(?string $expected, mixed $mixed): void {
		if ($expected === null) {
			$this->expectException(SemanticsException::class);
		}
		$this->assertEquals($expected, JSON::encodeSpecial($mixed));
	}

	/**
	 * @return array[]
	 */
	public static function data_encodeSpecial(): array {
		return [
			[
				'null', null,
			], [
				'[0,1,2,3]', [0, 1, 2, 3],
			],
			[
				'{"0":{"Hello":"Dude","1241`2":"odd","__2341":2,"a459123":{}},"1":false,"2":true,"3":12312312,"4":"A string","5":"A string","don\'t encode result":document.referrer,"6":null,"dog":null}',
				[
					[
						'Hello' => 'Dude', '1241`2' => 'odd', '__2341' => 2, 'a459123' => new stdClass(),
					], false, true, 12312312, 'A string', 'A string', '*don\'t encode result' => 'document.referrer',
					null, 'dog' => null,
				],
			],
		];
	}

	/**
	 * @dataProvider data_encodeJavaScript
	 * @param string $expected
	 * @param mixed $mixed
	 * @return void
	 */
	public function test_encodeJavaScript(string $expected, mixed $mixed): void {
		$this->assertEquals($expected, JSON::encodeJavaScript($mixed));
	}

	public static function data_encodeJavaScript(): array {
		return [
			[
				'null', null,
			], [
				'[0,1,2,3]', [0, 1, 2, 3],
			], [
				'{"0":{Hello:"Dude","1241`2":"odd",__2341:2,a459123:{}},"1":false,"2":true,"3":12312312,"4":"A string","5":"A string","don\'t encode result":document.referrer,"6":null,dog:null}',
				[
					[
						'Hello' => 'Dude', '1241`2' => 'odd', '__2341' => 2, 'a459123' => new stdClass(),
					], false, true, 12312312, 'A string', 'A string', '*don\'t encode result' => 'document.referrer',
					null, 'dog' => null,
				],
			],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_object_member_name_quote
	 * @param string $name
	 * @param string $expected
	 */
	public function test_object_member_name_quote(string $name, string $expected): void {
		$this->assertEquals($expected, JSON::object_member_name_quote($name));
	}

	public static function data_object_member_name_quote(): array {
		return [
			['', '""'], ['a', 'a'], ['dude_123', 'dude_123'], [' ', '" "'], ['a b', '"a b"'], ['@#', '"@#"'],
			['Equalit\'', '"Equalit\'"'], ['egalité', '"egalité"'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $mixed
	 * @return void
	 * @dataProvider data_quote
	 */
	public function test_quote(string $expected, string $mixed): void {
		$this->assertEquals($expected, JSON::quote($mixed));
	}

	public static function data_quote(): array {
		return [
			['"\\\\t\\\\n\\\\r\\\\"', '\t\n\r\\'],
			['"this.is.a.word"', 'this.is.a.word'],
			['"thingy"', 'thingy'],
			['"2thingy"', '2thingy'],
			['"thingy2"', 'thingy2'],
			['"th-ingy2"', 'th-ingy2'],
		];
	}

	public static function data_valid_member_name(): array {
		return [
			[false, ''], [true, 'a'], [true, 'dude_123'], [false, ' '], [false, 'a b'], [false, '@#'],
			[false, 'Equalit\''], [false, 'egalité'],
		];
	}

	/**
	 * @dataProvider data_valid_member_name
	 * @param bool $expected
	 * @param string $name
	 * @return void
	 */
	public function test_valid_member_name(bool $expected, string $name): void {
		$this->assertEquals($expected, JSON::valid_member_name($name));
	}

	public static function data_internal_values(): array {
		$obj = new stdClass();
		$obj->foo = 'foo';
		$obj->_thing_to_save = [
			'1', '2', '5',
		];
		$obj->another = new stdClass();

		return [
			[
				null,
			], [
				true,
			], [
				false,
			], [
				[],
			], [
				[
					'a' => 'b',
				],
			], [
				[
					[
						'hello' => 'world', 'how' => 'zit',
					],
				],
			], [
				$obj,
			],
		];
	}

	/**
	 * @dataProvider data_internal_values
	 * @param mixed $mixed
	 * @return void
	 * @throws SemanticsException
	 */
	public function test_internal(mixed $mixed): void {
		$encode = JSON::encode($mixed);
		$decode = JSON::zesk_decode($encode);
		$encode2 = JSON::encode($decode);
		$this->assertEquals($encode, $encode2);
	}

	public static function data_zesk_decode(): array {
		return [
			[
				'{ "fructose.marketacumen.com": "fructose" }', [
					'fructose.marketacumen.com' => 'fructose',
				],
			], [
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
}', [
					'glossary' => [
						'title' => 'example glossary', 'GlossDiv' => [
							'title' => 'S', 'GlossList' => [
								'GlossEntry' => [
									'ID' => 'SGML', 'SortAs' => 'SGML',
									'GlossTerm' => 'Standard Generalized Markup Language', 'Acronym' => 'SGML',
									'Abbrev' => 'ISO 8879:1986', 'GlossDef' => [
										'para' => 'A meta-markup language, used to create markup languages such as DocBook.',
										'GlossSeeAlso' => [
											'GML', 'XML',
										],
									], 'GlossSee' => 'markup',
								],
							],
						],
					],
				],
			], [
				'{"truly":true,"falsely":false,"nullish":null,"floaty":  -5.812342e+24,"intlike":51231412,"listy":[0,1,2,3,5]}',
				[
					'truly' => true, 'falsely' => false, 'nullish' => null, 'floaty' => -58123.42e+20,
					'intlike' => 51231412, 'listy' => [
						0, 1, 2, 3, 5,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider data_zesk_decode
	 * @param string $mixed
	 * @param mixed $expected
	 */
	public function test_zesk_decode(string $mixed, mixed $expected): void {
		$actual = JSON::zesk_decode($mixed);
		$this->assertEquals($expected, $actual);
	}
}
