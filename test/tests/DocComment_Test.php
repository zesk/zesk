<?php declare(strict_types=1);
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
	public function test_extract(): void {
		$testfile = ZESK_ROOT . 'classes/ArrayTools.php';
		$content = file_get_contents($testfile);
		$comments = DocComment::extract($content);
		$this->assert_is_array($comments);
		$this->assert(count($comments) > 8, "More than 8 doccomments in $testfile");
		$this->assert_contains($comments[0], "@package zesk");
	}

	public function data_provider_clean() {
		return [
			[
				"	/**
	 * Default whitespace trimming characters
	 *
	 * @var string
	 */
",
				"\nDefault whitespace trimming characters\n\n@var string\n",
			],
			[
				"	/******
	 *** Default whitespace trimming characters
	 ***
	 *** @var string
	 ***/
",
				"****\n** Default whitespace trimming characters\n**\n** @var string\n*",
			],
		];
	}

	public function test_desc_no_tag(): void {
		$doc = DocComment::instance([
			"desc" => "Hello, world",
			"see" => "\\zesk\\Kernel",

		], [
			DocComment::OPTION_DESC_NO_TAG => true,
		]);
		$this->assert_equal($doc->content(), "/**\n * Hello, world\n * \n * @see \zesk\Kernel\n */");
		$doc->setOption(DocComment::OPTION_DESC_NO_TAG, false);
		$this->assert_equal($doc->content(), "/**
 * @desc Hello, world
 * @see \zesk\Kernel
 */");
	}

	/**
	 * @dataProvider data_provider_clean
	 */
	public function test_clean($test, $expected): void {
		$this->assert_equal(DocComment::clean($test), $expected);
	}

	/**
	 *
	 * @return array
	 */
	public function data_provider_parse() {
		return [
			[
				"	/**
	 * Removes stars from beginning and end of doccomments
	 *
	 * @param string \$string A doccomment string to clean
	 * @return string the cleaned doccomment
	 */
",
				[
					"desc" => "Removes stars from beginning and end of doccomments",
					"param" => [
						"\$string" => [
							"string",
							"\$string",
							"A doccomment string to clean",
						],
					],
					"return" => "string the cleaned doccomment",
				],
				"/**
 * @desc Removes stars from beginning and end of doccomments
 * @param string \$string A doccomment string to clean
 * @return string the cleaned doccomment
 */",
			],
			[
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
				[
					"desc" => "Server\n\nRepresents a server (virtual or physical)",
					"see" => [
						"Class_Server",
						"Server_Data",
					],
					"property" => [
						"\$id" => [
							"id",
							"\$id",
						],
						"\$name" => [
							"string",
							"\$name",
						],
						"\$name_internal" => [
							"string",
							"\$name_internal",
						],
						"\$name_external" => [
							"string",
							"\$name_external",
						],
						"\$ip4_internal" => [
							"ip4",
							"\$ip4_internal",
						],
						"\$ip4_external" => [
							"ip4",
							"\$ip4_external",
						],
						"\$free_disk" => [
							"integer",
							"\$free_disk",
						],
						"\$load" => [
							"double",
							"\$load",
						],
						"\$alive" => [
							"Timestamp",
							"\$alive",
						],
					],
				],
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
			],
		];
	}

	/**
	 * @dataProvider data_provider_parse
	 */
	public function test_parse($test, $expected, $unparse_expected): void {
		$this->assert_equal($parsed = DocComment::instance($test)->variables(), $expected, JSON::encode_pretty($test));
		$this->assert_equal(DocComment::instance($parsed)->content(), $unparse_expected, JSON::encode_pretty($parsed));
	}

	public function data_provider_unparse() {
		return [
			[
				[
					"see" => [
						"line1",
						"line2",
					],
					"desc" => "Description",
				],
				"/**\n * @see line1\n * @see line2\n * @desc Description\n */",
			],
		];
	}

	/**
	 * @dataProvider data_provider_unparse
	 * @param string $test
	 * @param string $expect
	 */
	public function test_unparse($test, $expect): void {
		$this->assert_is_array($test);
		$this->assert_is_string($expect);
		$this->assert_equal(DocComment::instance($test)->content(), $expect);
	}
}
