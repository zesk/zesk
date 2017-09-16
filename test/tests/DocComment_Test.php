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
	function test_extract() {
		$content = file_get_contents(ZESK_ROOT . 'classes/arr.php');
		$comments = DocComment::extract($content);
		$this->assert_is_array($comments);
		$this->assert(count($comments) > 8, "More than 8 doccomments in arr.php");
		$this->assert_contains($comments[0], "@package zesk");
	}
	function data_provider_clean() {
		return array(
			array(
				"	/**
	 * Default whitespace trimming characters
	 *
	 * @var string
	 */
",
				"\nDefault whitespace trimming characters\n\n@var string\n"
			),
			array(
				"	/******
	 *** Default whitespace trimming characters
	 ***
	 *** @var string
	 ***/
",
				"****\n** Default whitespace trimming characters\n**\n** @var string\n*"
			)
		);
	}
	/**
	 * @dataProvider data_provider_clean
	 */
	function test_clean($test, $expected) {
		$this->assert_equal(DocComment::clean($test), $expected);
	}
	
	/**
	 * 
	 * @return array
	 */
	function data_provider_parse() {
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
							"A doccomment string to clean"
						)
					),
					"return" => "string the cleaned doccomment"
				)
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
						"Server_Data"
					),
					"property" => array(
						"\$id" => array(
							"id",
							"\$id"
						),
						"\$name" => array(
							"string",
							"\$name"
						),
						"\$name_internal" => array(
							"string",
							"\$name_internal"
						),
						"\$name_external" => array(
							"string",
							"\$name_external"
						),
						"\$ip4_internal" => array(
							"ip4",
							"\$ip4_internal"
						),
						"\$ip4_external" => array(
							"ip4",
							"\$ip4_external"
						),
						"\$free_disk" => array(
							"integer",
							"\$free_disk"
						),
						"\$load" => array(
							"double",
							"\$load"
						),
						"\$alive" => array(
							"Timestamp",
							"\$alive"
						)
					)
				)
			)
		);
	}
	/**
	 * @dataProvider data_provider_parse
	 */
	function test_parse($test, $expected) {
		$this->assert_equal(DocComment::parse($test), $expected);
	}
	function test_unparse() {
		$items = array(
			"param" => array(
				"line1",
				"line2"
			),
			"desc" => "Description"
		);
		$this->assert_equal(DocComment::unparse($items), "/**\n * @param line1\n *        line2\n * @desc  Description\n */");
	}
}
