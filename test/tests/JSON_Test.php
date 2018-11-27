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
class JSON_Test extends Test_Unit {
    /**
     * @expectedException zesk\Exception_Parse
     */
    public function test_decode() {
        JSON::decode(null);
    }

    public function test_decode_null() {
        $this->assert_equal(JSON::decode("null"), null);
    }

    public function test_encode() {
        $mixed = null;
        JSON::encode($mixed);

        $mixed = array(
            array(
                "Hello" => "Dude",
                "1241`2" => "odd",
                "__2341" => 2,
                "a459123" => new \stdClass(),
            ),
            false,
            true,
            12312312,
            "A string",
            'A string',
            "*don't encode result" => "document.referrer",
            null,
            "dog" => null,
        );
        $this->assert_equal(JSON::encode($mixed), '{"0":{"Hello":"Dude","1241`2":"odd","__2341":2,"a459123":{}},"1":false,"2":true,"3":12312312,"4":"A string","5":"A string","don\'t encode result":document.referrer,"6":null,"dog":null}');
    }

    public function test_encodex() {
        $mixed = null;
        $this->assert_equal(JSON::encodex($mixed), "null");
    }

    public function test_object_member_name_quote() {
        $name = null;
        JSON::object_member_name_quote($name);
    }

    public function test_quote() {
        $m = null;
        $this->assert(JSON::quote("this.is.a.word") === "\"this.is.a.word\"");
        $this->assert(JSON::quote("thingy") === "\"thingy\"", "\"" . JSON::quote("thingy") . "\" === \"thingy\"");
        $this->assert(JSON::quote("2thingy") === "\"2thingy\"");
        $this->assert(JSON::quote("thingy2") === "\"thingy2\"");
        $this->assert(JSON::quote("th-ingy2") === "\"th-ingy2\"");
    }

    public function test_valid_member_name() {
        $name = null;
        $this->assert_equal(JSON::valid_member_name($name), false);
    }

    public function internal_values() {
        $obj = new \stdClass();
        $obj->foo = "foo";
        $obj->_thing_to_save = array(
            "1",
            "2",
            "5",
        );
        $obj->another = new \stdClass();

        return array(
            array(
                null,
            ),
            array(
                true,
            ),
            array(
                false,
            ),
            array(
                array(),
            ),
            array(
                array(
                    "a" => "b",
                ),
            ),
            array(
                array(
                    array(
                        "hello" => "world",
                        "how" => "zit",
                    ),
                ),
            ),
            array(
                $obj,
            ),
        );
    }

    /**
     * @data_provider internal_values
     * @param mixed $mixed
     */
    public function test_internal($mixed) {
        $encode = JSON::encode($mixed);
        $decode = JSON::zesk_decode($encode, false);
        $encode2 = JSON::encode($decode);
        $this->assert_equal($encode, $encode2);
    }

    public function internal_expected_values() {
        return array(
            array(
                '{ "fructose.marketacumen.com": "fructose" }',
                array(
                    'fructose.marketacumen.com' => 'fructose',
                ),
            ),
            array(
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
                array(
                    "glossary" => array(
                        "title" => "example glossary",
                        "GlossDiv" => array(
                            "title" => "S",
                            "GlossList" => array(
                                "GlossEntry" => array(
                                    "ID" => "SGML",
                                    "SortAs" => "SGML",
                                    "GlossTerm" => "Standard Generalized Markup Language",
                                    "Acronym" => "SGML",
                                    "Abbrev" => "ISO 8879:1986",
                                    "GlossDef" => array(
                                        "para" => "A meta-markup language, used to create markup languages such as DocBook.",
                                        "GlossSeeAlso" => array(
                                            "GML",
                                            "XML",
                                        ),
                                    ),
                                    "GlossSee" => "markup",
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                '{"truly":true,"falsely":false,"nullish":null,"floaty":  -5.812342e+24,"intlike":51231412,"listy":[0,1,2,3,5]}',
                array(
                    "truly" => true,
                    "falsely" => false,
                    "nullish" => null,
                    "floaty" => -58123.42e+20,
                    "intlike" => 51231412,
                    "listy" => array(
                        0,
                        1,
                        2,
                        3,
                        5,
                    ),
                ),
            ),
        );
    }

    /**
     * @data_provider internal_expected_values
     * @param unknown $mixed
     * @param unknown $expected
     */
    public function test_parser($mixed, $expected) {
        $actual = JSON::zesk_decode($mixed);
        $this->assert_equal($actual, $expected);
    }
}
