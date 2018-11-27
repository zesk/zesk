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
class DocComment_Test extends Test_Unit {
    public function test_extract() {
        $testfile = ZESK_ROOT . 'classes/ArrayTools.php';
        $content = file_get_contents($testfile);
        $comments = DocComment::extract($content);
        $this->assert_is_array($comments);
        $this->assert(count($comments) > 8, "More than 8 doccomments in $testfile");
        $this->assert_contains($comments[0], "@package zesk");
    }

    public function data_provider_clean() {
        return array(
            array(
                "	/**
	 * Default whitespace trimming characters
	 *
	 * @var string
	 */
",
                "\nDefault whitespace trimming characters\n\n@var string\n",
            ),
            array(
                "	/******
	 *** Default whitespace trimming characters
	 ***
	 *** @var string
	 ***/
",
                "****\n** Default whitespace trimming characters\n**\n** @var string\n*",
            ),
        );
    }

    public function test_desc_no_tag() {
        $doc = DocComment::instance([
            "desc" => "Hello, world",
            "see" => "\\zesk\\Kernel",

        ], [
            DocComment::OPTION_DESC_NO_TAG => true,
        ]);
        $this->assert_equal($doc->content(), "/**\n * Hello, world\n * \n * @see \zesk\Kernel\n */");
        $doc->set_option(DocComment::OPTION_DESC_NO_TAG, false);
        $this->assert_equal($doc->content(), "/**
 * @desc Hello, world
 * @see \zesk\Kernel
 */");
    }

    /**
     * @dataProvider data_provider_clean
     */
    public function test_clean($test, $expected) {
        $this->assert_equal(DocComment::clean($test), $expected);
    }

    /**
     *
     * @return array
     */
    public function data_provider_parse() {
        return array(
            array(
                "	/**
	 * Removes stars from beginning and end of doccomments
	 *
	 * @param string \$string A doccomment string to clean
	 * @return string the cleaned doccomment
	 */
",
                array(
                    "desc" => "Removes stars from beginning and end of doccomments",
                    "param" => array(
                        "\$string" => array(
                            "string",
                            "\$string",
                            "A doccomment string to clean",
                        ),
                    ),
                    "return" => "string the cleaned doccomment",
                ),
                "/**
 * @desc Removes stars from beginning and end of doccomments
 * @param string \$string A doccomment string to clean
 * @return string the cleaned doccomment
 */",
            ),
            array(
                '/**
 * Server
 *
 * Represents a server (virtual or physical)
 *
 * @see Class_Server
 * @see Server_Data
 * @property id $id
 * @property string $name
 * @property string $name_internal
 * @property string $name_external
 * @property ip4 $ip4_internal
 * @property ip4 $ip4_external
 * @property integer $free_disk
 * @property integer $free_disk
 * @property double $load
 * @property Timestamp $alive
 */
',
                array(
                    "desc" => "Server\n\nRepresents a server (virtual or physical)",
                    "see" => array(
                        "Class_Server",
                        "Server_Data",
                    ),
                    "property" => array(
                        "\$id" => array(
                            "id",
                            "\$id",
                        ),
                        "\$name" => array(
                            "string",
                            "\$name",
                        ),
                        "\$name_internal" => array(
                            "string",
                            "\$name_internal",
                        ),
                        "\$name_external" => array(
                            "string",
                            "\$name_external",
                        ),
                        "\$ip4_internal" => array(
                            "ip4",
                            "\$ip4_internal",
                        ),
                        "\$ip4_external" => array(
                            "ip4",
                            "\$ip4_external",
                        ),
                        "\$free_disk" => array(
                            "integer",
                            "\$free_disk",
                        ),
                        "\$load" => array(
                            "double",
                            "\$load",
                        ),
                        "\$alive" => array(
                            "Timestamp",
                            "\$alive",
                        ),
                    ),
                ),
                "/**\n * @desc Server\n * \n *       Represents a server (virtual or physical)
 * @see Class_Server
 * @see Server_Data
 * @property id \$id
 * @property string \$name
 * @property string \$name_internal
 * @property string \$name_external
 * @property ip4 \$ip4_internal
 * @property ip4 \$ip4_external
 * @property integer \$free_disk
 * @property double \$load
 * @property Timestamp \$alive
 */",
            ),
        );
    }

    /**
     * @dataProvider data_provider_parse
     */
    public function test_parse($test, $expected, $unparse_expected) {
        $this->assert_equal($parsed = DocComment::instance($test)->variables(), $expected, JSON::encode_pretty($test));
        $this->assert_equal(DocComment::instance($parsed)->content(), $unparse_expected, JSON::encode_pretty($parsed));
    }

    public function data_provider_unparse() {
        return array(
            array(
                array(
                    "see" => array(
                        "line1",
                        "line2",
                    ),
                    "desc" => "Description",
                ),
                "/**\n * @see line1\n * @see line2\n * @desc Description\n */",
            ),
        );
    }

    /**
     * @dataProvider data_provider_unparse
     * @param string $test
     * @param string $expect
     */
    public function test_unparse($test, $expect) {
        $this->assert_is_array($test);
        $this->assert_is_string($expect);
        $this->assert_equal(DocComment::instance($test)->content(), $expect);
    }
}
