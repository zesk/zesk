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
class DocComment_Test extends UnitTest {
	public function test_extract(): void {
		$testfile = ZESK_ROOT . 'zesk/ArrayTools.php';
		$content = file_get_contents($testfile);
		$comments = DocComment::extract($content);
		$this->assertIsArray($comments);
		$this->assertGreaterThan(8, count($comments), "More than 8 doccomments in $testfile");
		if (count($comments) > 0) {
			$text = $comments[0];
			/* @var $text DocComment */
			$this->assertStringContainsString('@package zesk', $text->content());
		}
	}

	public function data_provider_clean() {
		return [
			[
				'	/**
	 * Default whitespace trimming characters
	 *
	 * @var string
	 */
',
				"\nDefault whitespace trimming characters\n\n@var string\n",
			],
			[
				'	/******
	 *** Default whitespace trimming characters
	 ***
	 *** @var string
	 ***/
',
				"****\n** Default whitespace trimming characters\n**\n** @var string\n*",
			],
		];
	}

	public function test_desc_no_tag(): void {
		$doc = DocComment::instance([
			'desc' => 'Hello, world',
			'see' => '\\zesk\\Kernel',

		], [
			DocComment::OPTION_DESC_NO_TAG => true,
		]);
		$expected = "/**\n * Hello, world\n * \n * @see \zesk\Kernel\n */";
		$this->assertEquals($expected, $doc->content());
		$doc->setOption(DocComment::OPTION_DESC_NO_TAG, false);
		$expected = "/**\n * @desc Hello, world\n * @see \zesk\Kernel\n */";
		$this->assertEquals($doc->content(), $expected);
	}

	/**
	 * @dataProvider data_provider_clean
	 */
	public function test_clean($test, $expected): void {
		$this->assertEquals(DocComment::clean($test), $expected);
	}

	/**
	 *
	 * @return array
	 */
	public function data_provider_parse() {
		return [
			[
				'	/**
	 * Removes stars from beginning and end of doccomments
	 *
	 * @param string $string A doccomment string to clean
	 * @return string the cleaned doccomment
	 */
',
				[
					'desc' => 'Removes stars from beginning and end of doccomments',
					'param' => [
						'$string' => [
							'string',
							'$string',
							'A doccomment string to clean',
						],
					],
					'return' => 'string the cleaned doccomment',
				],
				'/**
 * @desc Removes stars from beginning and end of doccomments
 * @param string $string A doccomment string to clean
 * @return string the cleaned doccomment
 */',
			],
			[
				'/**
 * Server
 *
 * Represents a server (virtual or physical)
 *
 * @see Class_Server
 * @see ServerMeta
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
					'desc' => "Server\n\nRepresents a server (virtual or physical)",
					'see' => [
						'Class_Server',
						'ServerMeta',
					],
					'property' => [
						'$id' => [
							'id',
							'$id',
						],
						'$name' => [
							'string',
							'$name',
						],
						'$name_internal' => [
							'string',
							'$name_internal',
						],
						'$name_external' => [
							'string',
							'$name_external',
						],
						'$ip4_internal' => [
							'ip4',
							'$ip4_internal',
						],
						'$ip4_external' => [
							'ip4',
							'$ip4_external',
						],
						'$free_disk' => [
							'integer',
							'$free_disk',
						],
						'$load' => [
							'double',
							'$load',
						],
						'$alive' => [
							'Timestamp',
							'$alive',
						],
					],
				],
				"/**\n * @desc Server\n * \n *       Represents a server (virtual or physical)
 * @see Class_Server
 * @see ServerMeta
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
		$this->assertEquals($parsed = DocComment::instance($test)->variables(), $expected, JSON::encodePretty($test));
		$this->assertEquals(DocComment::instance($parsed)->content(), $unparse_expected, JSON::encodePretty($parsed));
	}

	public function data_provider_content() {
		return [
			[
				[
					'see' => [
						'line1',
						'line2',
					],
					'desc' => 'Description',
				],
				"/**\n * @see line1\n * @see line2\n * @desc Description\n */",
			],
		];
	}

	/**
	 * @dataProvider data_provider_content
	 * @param string $test
	 * @param string $expect
	 */
	public function test_content($test, $expect): void {
		$this->assertIsArray($test);
		$this->assertIsString($expect);
		$this->assertEquals($expect, DocComment::instance($test)->content());
	}
}
