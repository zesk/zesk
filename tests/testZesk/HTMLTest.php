<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use stdClass;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\HTML;
use zesk\HTMLTag;
use zesk\PHP;
use zesk\Types;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\HTML
 * @uses HTML
 */
class HTMLTest extends TestCase
{
	public static function data_styleClean(): array
	{
		return [
			[[
				'type' => 'text/javascript',
				'src' => 'style.css',
			], [
				'type' => 'text/javascript',
				'src' => 'style.css',
				'crap' => 'nope',
			], null, ['crap']],
		];
	}

	public static function dataWrap(): array
	{
		return [
			[
				'This is a <strong>simple</strong> example',
				[
					'This is a [simple] example',
					'<strong>[]</strong>',
				],
			],
			[
				'This is a simple example',
				[
					'This is a [1:simple] example',
					'<strong>[]</strong>',
				],
			],
			[
				'This is an example with <strong>two</strong> <em>items</em> example',
				[
					'This is an example with [two] [items] example',
					'<strong>[]</strong>',
					'<em>[]</em>',
				],
			],
			[
				'This is an example with <strong>two</strong> <strong>items</strong> example',
				[
					'This is an example with [two] [0:items] example',
					'<strong>[]</strong>',
					'<em>[]</em>',
				],
			],
			[
				'This is an example with <em>two</em> <em>items</em> example',
				[
					'This is an example with [1:two] [items] example',
					'<strong>[]</strong>',
					'<em>[]</em>',
				],
			],

			[
				'This is an example with <em>two</em> <em>items</em> example',
				[
					'This is an example with [1:two] [1:items] example',
					'<strong>[]</strong>',
					'<em>[]</em>',
				],
			],

			[
				'Nested example with <2>outermost <0>nest0</0> <1>nest1</1></2> example',
				[
					'Nested example with [outermost [nest0] [nest1]] example',
					'<0>[]</0>',
					'<1>[]</1>',
					'<2>[]</2>',
				],
			],
		];
	}

	/**
	 * @param array $arguments
	 * @param string $expected
	 * @return void
	 * @dataProvider dataWrap
	 * @covers ::wrap
	 */
	public function testWrap(string $expected, array $arguments): void
	{
		$this->assertEquals($expected, call_user_func_array([
			HTML::class,
			'wrap',
		], $arguments), 'HTML::wrap ' . PHP::dump($arguments) . " => $expected failed");
	}

	public static function data_cleanStyleAttributes(): array
	{
		$base = '<a style="background-image: inherit; background-color: red;" href="/">Hello world</a>';
		return [
			['<a style="background-color: red;" href="/">Hello world</a>', $base, ['background-color'], []],
			['<a style="background-image: inherit;" href="/">Hello world</a>', $base, ['background-image'], []],
			[$base, $base, ['background-image', 'background-color'], []],
			[$base, $base, ['background-color', 'background-image'], []],
			['<a href="/">Hello world</a>', $base, [], ['background-color', 'background-image']],
			['<a href="/">Hello world</a>', $base, ['background-image'], ['background-image']],
		];
	}

	/**
	 * @param array $expected
	 * @param array $test
	 * @param string|array|null $allowed
	 * @param string|array|null $disallowed
	 * @return void
	 * @covers ::styleClean
	 * @dataProvider data_styleClean
	 */
	public function test_styleClean(array $expected, array $test, null|string|array $allowed, null|string|array $disallowed): void
	{
		$this->assertEquals($expected, HTML::styleClean($test, $allowed, $disallowed));
	}

	public static function data_parseAttributes(): array
	{
		return [
			[[], []],
			[[1, 2, 3], [1, 2, 3]],
			[['1' => true], '1'],
			[[
				'article' => '12',
				'template' => 'volunteer-help',
			], 'template="volunteer-help" article="12"'],
		];
	}

	/**
	 * @covers ::parseAttributes
	 * @dataProvider data_parseAttributes
	 * @param array $expected
	 * @param string|array $attributes
	 * @return void
	 */
	public function test_parseAttributes(array $expected, string|array $attributes): void
	{
		$this->assertEquals($expected, HTML::parseAttributes($attributes));
	}

	/**
	 * @param HTMLTag[] $tags
	 * @return array
	 */
	public function cleanTagWhitespace(array $tags): array
	{
		foreach ($tags as $index => $tag) {
			$html = $tag->innerHTML();
			$html = preg_replace("/\s+/", ' ', $html);
			$tag->setInnerHTML($html);

			$html = $tag->outerHTML();
			$html = preg_replace("/\s+/", ' ', $html);
			$tag->setOuterHTML($html);

			$tags[$index] = $tag;
		}
		return $tags;
	}

	public static function data_extractTags(): array
	{
		return [
			[
				[
					new HTMLTag('a', [
						'href' => '/',
					], '<img src="https://zesk.com/test/_img/iana-logo-pageheader.png" alt="Homepage" />', '<a href="/"><img src="https://zesk.com/test/_img/iana-logo-pageheader.png" alt="Homepage" /></a>', 1107),
					new HTMLTag('a', [
						'href' => '/domains/',
					], 'Domains', '<a href="/domains/">Domains</a>', 1240),
					new HTMLTag('a', [
						'href' => '/numbers/',
					], 'Numbers', '<a href="/numbers/">Numbers</a>', 1279),
					new HTMLTag('a', [
						'href' => '/protocols/',
					], 'Protocols', '<a href="/protocols/">Protocols</a>', 1318),
					new HTMLTag('a', [
						'href' => '/about/',
					], 'About IANA', '<a href="/about/">About IANA</a>', 0),
					new HTMLTag('a', [
						'href' => '/go/rfc2606',
					], 'RFC 2606', '<a href="/go/rfc2606">RFC 2606</a>', 0),
					new HTMLTag('a', [
						'href' => '/about/',
					], 'About', '<a href="/about/">About</a>', 0),
					new HTMLTag('a', [
						'href' => '/about/presentations/',
					], 'Presentations', '<a href="/about/presentations/">Presentations</a>', 1867),
					new HTMLTag('a', [
						'href' => '/about/performance/',
					], 'Performance', '<a href="/about/performance/">Performance</a>', 1936),
					new HTMLTag('a', [
						'href' => '/reports/',
					], 'Reports', '<a href="/reports/">Reports</a>', 1986),
					new HTMLTag('a', [
						'href' => '/domains/',
					], 'Domains', '<a href="/domains/">Domains</a>', 2052),
					new HTMLTag('a', [
						'href' => '/domains/root/',
					], 'Root Zone', '<a href="/domains/root/">Root Zone</a>', 2092),
					new HTMLTag('a', [
						'href' => '/domains/int/',
					], '.INT', '<a href="/domains/int/">.INT</a>', 2135),
					new HTMLTag('a', [
						'href' => '/domains/arpa/',
					], '.ARPA', '<a href="/domains/arpa/">.ARPA</a>', 2173),
					new HTMLTag('a', [
						'href' => '/domains/idn-tables/',
					], 'IDN Repository', '<a href="/domains/idn-tables/">IDN Repository</a>', 2212),
					new HTMLTag('a', [
						'href' => '/protocols/',
					], 'Protocols', '<a href="/protocols/">Protocols</a>', 2274),
					new HTMLTag('a', [
						'href' => '/numbers/',
					], 'Number Resources', '<a href="/numbers/">Number Resources</a>', 2329),
					new HTMLTag('a', [
						'href' => '/abuse/',
					], 'Abuse Information', '<a href="/abuse/">Abuse Information</a>', 2378),
					new HTMLTag('a', [
						'href' => 'http://www.icann.org/',
					], 'Internet Corporation for Assigned Names and Numbers', '<a href="http://www.icann.org/">Internet Corporation for Assigned Names and Numbers</a>', 2523),
					new HTMLTag('a', [
						'href' => 'mailto:iana@iana.org?subject=General%20website%20feedback',
					], 'iana@iana.org', '<a href="mailto:iana@iana.org?subject=General%20website%20feedback">iana@iana.org</a>', 2723),
				],
				'a',
				file_get_contents(dirname(__DIR__) . '/test-data/html-extract_tags.html'),
				true,
			],
		];
	}

	/**
	 * @param array $expected_tags
	 * @param string $tagName
	 * @param string $contents
	 * @param bool $recursive
	 * @covers ::extractTags
	 * @dataProvider data_extractTags
	 * @return void
	 */
	public function test_extractTags(array $expected_tags, string $tagName, string $contents, bool $recursive): void
	{
		$tags = HTML::extractTags($tagName, $contents, $recursive);
		$tags = $this->cleanTagWhitespace($tags);
		$expected_tags = $this->cleanTagWhitespace($expected_tags);

		foreach ($expected_tags as $index => $result_tag) {
			$tags[$index]->offset = $result_tag->offset;
		}

		$this->assertEquals($expected_tags, $tags);
	}

	public static function data_a(): array
	{
		return [
			['<a id="ninety" href="/" />', '/', ['id' => 'ninety']],
		];
	}

	/**
	 * @param string $expected
	 * @param string $href
	 * @param array $attributes
	 * @return void
	 * @covers ::a
	 * @dataProvider data_a
	 */
	public function test_a(string $expected, string $href, array $attributes): void
	{
		$this->assertEquals($expected, HTML::a($href, $attributes));
	}

	public static function data_attributes(): array
	{
		return [
			[' hello="world"', ['hello' => 'world']],
			[' amp="wor&amp;ld"', ['amp' => 'wor&ld']],
			[' quote="&quot;quoted&quot;"', ['quote' => '"quoted"']],
		];
	}

	/**
	 * @param string $expected
	 * @param array $attributes
	 * @return void
	 * @covers ::attributes
	 * @dataProvider data_attributes
	 */
	public function test_attributes(string $expected, array $attributes): void
	{
		$this->assertEquals($expected, HTML::attributes($attributes));
	}

	public static function data_img(): array
	{
		return [
			['<img alt="" title="" src="antidis/" />', '', '', [], ],
			['<img alt="" title="" src="https://www.example.com/image.gif" />', 'https://www.example.com/image.gif', '', ['alt' => 'dude'], ],
		];
	}

	/**
	 * @param string $expected
	 * @param string $src
	 * @param string $text
	 * @param array $attributes
	 * @dataProvider data_img
	 * @return void
	 * @covers ::img
	 */
	public function test_img(string $expected, string $src, string $text, array $attributes): void
	{
		$this->assertEquals($expected, HTML::img($this->application, $src, $text, $attributes));
	}

	/**
	 * @param string $expected
	 * @param string $string
	 * @param array $include
	 * @param array $exclude
	 * @return void
	 * @dataProvider data_cleanStyleAttributes
	 * @covers ::cleanStyleAttributes
	 */
	public function test_clean_style_attributes(string $expected, string $string, array $include, array $exclude): void
	{
		$this->assertEquals($expected, HTML::cleanStyleAttributes($string, $include, $exclude));
	}

	public static function dataCleanTags(): array
	{
		$simple_html = '<a href="/"><strong>Bold</strong><em>Italic</em><p>Paragraphs</p></a><h1>Heading</h1><h2>Another Heading</h2>';
		return [
			[
				$simple_html,
				$simple_html,
				null,
				[],
			],
			[
				'<a href="/"><strong>Bold</strong><em>Italic</em><p>Paragraphs</p></a><h1>Heading</h1>Another Heading',
				$simple_html,
				null,
				['h2'],
			],
			[
				'<a href="/"><strong>Bold</strong><em>Italic</em><p>Paragraphs</p></a><h1>Heading</h1>Another Heading',
				$simple_html,
				[
					'a',
					'strong',
					'p',
					'h1',
					'em',
				],
				[],
			],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @param $allowed_tags
	 * @param $remove_tags
	 * @return void
	 * @dataProvider dataCleanTags
	 * @covers ::cleanTags
	 */
	public function test_cleanTags($expected, $string, $allowed_tags, $remove_tags): void
	{
		$this->assertEquals($expected, HTML::cleanTags($string, $allowed_tags, $remove_tags), 'HTML::clean_tags');
	}

	public static function dataCleanTagAttributes(): array
	{
		return [
			[
				'<a class="dude">Link</a>',
				[],
				['class'],
				'<a>Link</a>',
			],
		];
	}

	/**
	 * @param string $string
	 * @param        $include
	 * @param        $exclude
	 * @param        $expected
	 * @return void
	 * @dataProvider dataCleanTagAttributes
	 * @covers ::cleanTagsAttributes
	 */
	public function test_cleanTagsAttributes(string $string, $include, $exclude, $expected): void
	{
		$this->assertEquals($expected, HTML::cleanTagsAttributes($string, $include, $exclude), 'HTML::clean_tags_attributes');
	}

	public static function dataCleanTagsWithoutAttributes(): array
	{
		return [
			[
				'<a href=\'/\'>Link</a>',
				['a'],
				'<a href=\'/\'>Link</a>',
			],
			[
				'<a href=\'/\'>Link</a>Nolink',
				['a'],
				'<a href=\'/\'>Link</a><a>Nolink</a>',
			],
		];
	}

	/**
	 * @param string $expected
	 * @param array $tags
	 * @param string $html
	 * @return void
	 * @dataProvider dataCleanTagsWithoutAttributes
	 * @covers ::cleanTagsWithoutAttributes
	 */
	public function testCleanTagsWithoutAttributes(string $expected, array $tags, string $html): void
	{
		$this->assertEquals($expected, HTML::cleanTagsWithoutAttributes($tags, $html));
	}

	public static function dataCountEndTags(): array
	{
		return [
			[
				0,
				'a',
				'',
			],
			[
				1,
				'a',
				'</a>',
			],
		];
	}

	/**
	 * @param $tag
	 * @param $mixed
	 * @param $expected
	 * @dataProvider dataCountEndTags
	 * @covers ::countEndTags
	 */
	public function test_countEndTags($expected, $tag, $mixed): void
	{
		$this->assertEquals($expected, HTML::countEndTags($tag, $mixed));
	}

	/**
	 * @return void
	 * @covers ::countUntilTag
	 */
	public function test_countUntilTag(): void
	{
		$string = 'All good <strong>things</strong> must come to an <em>end</em>.<h1>Hello!</h1>';
		//        01234567890123456789012345678901234567890123456789
		$result = HTML::countUntilTag($string);
		$this->assertEquals(17, $result['next']);
		$this->assertEquals(9, $result['offset']);
		$this->assertEquals(8, $result['tagMatchLength']);
		$this->assertEquals(2, $result['words']);
		$this->assertEquals('<strong>', $result['tagMatch']);
		$this->assertEquals('strong', $result['tagName']);
	}

	public static function data_div(): array
	{
		return [
			['<div class="hello world">Dude</div>', '.hello world', 'Dude', ],
		];
	}

	/**
	 * @param string $expected
	 * @param string|array $attributes
	 * @param string $content
	 * @return void
	 * @covers ::div
	 * @dataProvider data_div
	 */
	public function test_div(string $expected, string|array $attributes, string $content): void
	{
		$this->assertEquals($expected, HTML::div($attributes, $content));
	}

	public static function dataEllipsis(): array
	{
		return [
			[
				'<a href="https://www.example.com/">this is a word with </a> ...',
				'<a href="https://www.example.com/">this is a word with a lot of other words in it</a>',
				20,
				' ...',
			],
			[
				'<a href="https://www.example.com/">this</a>&mdash;',
				'<a href="https://www.example.com/">this is a word with a lot of other words in it</a>',
				4,
				'&mdash;',
			],
		];
	}

	/**
	 * @dataProvider dataEllipsis
	 * @covers ::ellipsis
	 * @param $expected
	 * @param $string
	 * @param $length
	 * @param $dot_dot_dot
	 * @return void
	 */
	public function test_ellipsis($expected, $string, $length, $dot_dot_dot): void
	{
		$this->assertEquals($expected, HTML::ellipsis($string, $length, $dot_dot_dot));
	}

	public static function data_eTag(): array
	{
		return [
			[
				'',
				'div',
				'.class',
				'',
			],
			[
				'<div class="class">a</div>',
				'div',
				'.class',
				'a',
			],
		];
	}

	/**
	 * @dataProvider data_eTag
	 * @param $expected
	 * @param $name
	 * @param $mixed
	 * @param $content
	 * @return void
	 * @covers ::etag
	 */
	public function test_etag($expected, $name, $mixed, $content): void
	{
		$this->assertEquals($expected, HTML::etag($name, $mixed, $content));
	}

	public static function data_extractEmails(): array
	{
		return [
			[
				[
					'info@example.com',
					'nowhere@noone.com',
				],
				'<a href="mailto:info@example.com">nowhere@noone.com</a>',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $content
	 * @return void
	 * @dataProvider data_extractEmails
	 * @covers ::extractEmails
	 */
	public function test_extractEmails($expected, $content): void
	{
		$this->assertEquals($expected, HTML::extractEmails($content));
	}

	public static function data_extractLinks(): array
	{
		return [
			[[], 'Goo <a href="/">Dude</a>'],
			[['https://www.example.com/'], 'Goo <a href="https://www.example.com/">Dude</a>'],
		];
	}

	/**
	 * @param array $expected
	 * @param string $html
	 * @return void
	 * @covers ::extractLinks
	 * @dataProvider data_extractLinks
	 * @return void
	 */
	public function test_extract_links(array $expected, string $html): void
	{
		$this->assertEquals($expected, HTML::extractLinks($html));
	}

	public static function data_extractTagContents(): array
	{
		return [
			[
				'Link',
				'a',
				'<a href="">Link</a>',
			],
			[
				'Link',
				'a',
				'<a href="">Link</a><a>Loop</a>',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $tag
	 * @param $mixed
	 * @return void
	 * @throws NotFoundException
	 * @dataProvider data_extractTagContents
	 * @covers ::extractTagContents
	 */
	public function test_extractTagContents($expected, $tag, $mixed): void
	{
		$this->assertEquals($expected, HTML::extractTagContents($tag, $mixed));
	}

	public static function data_extractTagObject(): array
	{
		$outer_html = '<a href="/">Link</a>';
		return [
			[
				new HTMLTag('a', ['href' => '/'], 'Link', $outer_html, 0),
				'a',
				$outer_html,
			],
			[
				new HTMLTag('a', ['href' => '/'], 'Link', $outer_html, 0),
				'a',
				$outer_html . '<a>Loop</a>',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $tag
	 * @param $mixed
	 * @return void
	 * @dataProvider data_extractTagObject
	 * @throws NotFoundException
	 * @covers ::extractTag
	 */
	public function test_extract_tag_object($expected, $tag, $mixed): void
	{
		$this->assertEquals($expected, HTML::extractTag($tag, $mixed));
	}

	/**
	 * @return void
	 * @covers ::hidden
	 */
	public function test_hidden(): void
	{
		$this->assertEquals('<input name="name" type="hidden" value="secret" />', HTML::hidden('name', 'secret'));
	}

	/**
	 * @return void
	 * @covers ::img_compat
	 */
	public function test_img_compat(): void
	{
		$src = 'https://www.example.com/image.gif';
		$w = null;
		$h = null;
		$text = '';
		$attrs = [];
		$this->assertEquals('<img alt="" title="" src="https://www.example.com/image.gif" />', HTML::img_compat($this->application, $src, $w, $h, $text, $attrs));
	}

	public static function data_input(): array
	{
		return [
			['<input class="none" name="hidden-input" type="hidden" value="&lt;&gt;[]:&quot;" />', 'hidden', 'hidden-input', '<>[]:"', ['class' => 'none']],
		];
	}

	public static function data_inputButton(): array
	{
		return [
			['<input name="name" value="random" type="button" id="name" />', 'name', 'random', []],
		];
	}

	public static function data_inputHidden(): array
	{
		return [
			['<input name="inputName" type="hidden" value="random" />', 'inputName', 'random', []],
		];
	}

	public static function data_inputSubmit(): array
	{
		return [
			['<input data-test="hello" name="inputName" value="random" type="submit" id="inputName" />', 'inputName', 'random', ['data-test' => 'hello']],
		];
	}

	/**
	 * @param string $expected
	 * @param string $type
	 * @param string $name
	 * @param string $value
	 * @param array|string $attributes
	 * @return void
	 * @covers ::input
	 * @dataProvider data_input
	 */
	public function test_input(string $expected, string $type, string $name, string $value, array|string $attributes): void
	{
		$this->assertEquals($expected, HTML::input($type, $name, $value, $attributes));
	}

	/**
	 * @param string $expected
	 * @param string $name
	 * @param string $value
	 * @param array|string $attributes
	 * @return void
	 * @covers ::input_button
	 * @dataProvider data_inputButton
	 */
	public function test_input_button(string $expected, string $name, string $value, array|string $attributes): void
	{
		$this->assertEquals($expected, HTML::input_button($name, $value, $attributes));
	}

	/**
	 * @param string $expected
	 * @param string $name
	 * @param string $value
	 * @param array|string $attributes
	 * @return void
	 * @covers ::inputHidden
	 * @dataProvider data_inputHidden
	 */
	public function test_inputHidden(string $expected, string $name, string $value, array|string $attributes): void
	{
		$this->assertEquals($expected, HTML::inputHidden($name, $value, $attributes));
	}

	/**
	 * @return void
	 * @covers ::input_submit
	 * @dataProvider data_inputSubmit
	 */
	public function test_inputSubmit(string $expected, string $name, string $value, array|string $attributes): void
	{
		$this->assertEquals($expected, HTML::input_submit($name, $value, $attributes));
	}

	public static function data_insertInsideEnd(): array
	{
		return [
			['<a>Dude<strong>*</strong></a>', '<a>Dude</a>', '<strong>*</strong>'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $html
	 * @param string $insert_html
	 * @return void
	 * @dataProvider data_insertInsideEnd
	 * @covers ::insertInsideEnd
	 */
	public function test_insert_inside_end(string $expected, string $html, string $insert_html): void
	{
		$this->assertEquals($expected, HTML::insertInsideEnd($html, $insert_html));
	}

	public static function data_isEndTag(): array
	{
		return [
			[
				'a',
				'</a>',
			],
			[
				'a',
				'</a   >',
			],
			[
				'a',
				'</    a   >',
			],
			[
				'waybetter',
				'</    waybetter   >',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $tag
	 * @return void
	 * @dataProvider data_isEndTag
	 * @covers ::isEndTag
	 */
	public function test_is_end_tag($expected, $tag): void
	{
		$this->assertEquals($expected, HTML::isEndTag($tag));
	}

	public static function data_matchTags(): array
	{
		return [
			[
				[
					[
						'<a>',
						'a',
						'',
					],
					[
						'<b>',
						'b',
						'',
					],
					[
						'<strong>',
						'strong',
						'',
					],
					[
						'<li>',
						'li',
						'',
					],
				],
				'<a><b></b><strong></strong><li>',
			],
		];
	}

	/**
	 * @param array $expected
	 * @param string $string
	 * @return void
	 * @dataProvider data_matchTags
	 * @covers ::matchTags
	 */
	public function test_match_tags(array $expected, string $string): void
	{
		$this->assertEquals($expected, HTML::matchTags($string));
	}

	public static function data_mixedToString(): array
	{
		$tag = new HTMLTag('tag');
		$tag->setInnerHTML('Yoyoyo');
		return [
			[
				'',
				null,
			],
			[
				'',
				5123,
			],
			[
				'',
				99.2,
			],
			[
				'',
				new stdClass(),
			],
			[
				'string',
				'string',
			],
			[
				'Yoyoyo',
				$tag,
			],
			[
				'Yoyoyo',
				['ho' => $tag],
			],
		];
	}

	/**
	 * @param $expected
	 * @param $mixed
	 * @dataProvider data_mixedToString
	 * @return void
	 * @covers ::mixedToString
	 */
	public function test_mixed_to_string($expected, $mixed): void
	{
		$this->assertEquals($expected, HTML::mixedToString($mixed));
	}

	public static function data_parseStyles(): array
	{
		return [
			[[
				'background-color' => 'red',
				'position' => 'absolute',
				'z-index' => '2000',
			], 'background-color: red; position: absolute; z-index: 2000'],
		];
	}

	/**
	 * @dataProvider data_parseStyles
	 * @covers ::parseStyles
	 * @param array $expected
	 * @param string $parse
	 * @return void
	 */
	public function test_parseStyles(array $expected, string $parse): void
	{
		$this->assertEquals($expected, HTML::parseStyles($parse));
	}

	public static function data_parseTags(): array
	{
		return [
			[
				['a' => ['href' => '/']],
				'<a href="/">Link</a>',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @return void
	 * @dataProvider data_parseTags
	 * @covers ::parseTags
	 */
	public function test_parseTags($expected, $string): void
	{
		$this->assertEquals($expected, HTML::parseTags($string));
	}

	public static function data_removeTags(): array
	{
		return [
			[
				'',
				'a',
				'<a href="/">Link</a>',
				true,
			],
			[
				'Link',
				'a',
				'<a href="/">Link</a>',
				false,
			],
		];
	}

	/**
	 * @param $expected
	 * @param $tag
	 * @param $contents
	 * @param $delete
	 * @return void
	 * @dataProvider data_removeTags
	 * @covers ::removeTags
	 */
	public function test_removeTags($expected, $tag, $contents, $delete): void
	{
		$this->assertEquals($expected, HTML::removeTags($tag, $contents, $delete));
	}

	public static function data_select(): array
	{
		return [
			[
				'<select name="ThingsToBuy" id="ThingsToBuy"><option value="opt" selected="selected">car</option><option value="opt2">home</option><option value="opt3">insurance</option><option value="opt99">fun</option></select>',
				'ThingsToBuy',
				'opt',
				[
					'opt' => 'car',
					'opt2' => 'home',
					'opt3' => 'insurance',
					'opt99' => 'fun',
				],
				[],
			]];
	}

	/**
	 * @param string $expected
	 * @param string $name
	 * @param string $value
	 * @param array $options
	 * @param array $attributes
	 * @return void
	 * @dataProvider data_select
	 * @covers ::select
	 */
	public function test_select(string $expected, string $name, string $value, array $options, array $attributes): void
	{
		$this->assertEquals($expected, HTML::select($name, $value, $options, $attributes));
	}

	public static function data_specialChars(): array
	{
		return [
			['&quot;', '"'],
			['&amp;', '&'],
			['&lt;', '<'],
			['&gt;', '>'],
			[['&lt;', '&gt;'], ['<', '>']],
		];
	}

	/**
	 * @param string|array $expected
	 * @param string|array $test
	 * @return void
	 * @dataProvider data_specialChars
	 * @covers ::specialChars
	 */
	public function test_specialChars(string|array $expected, string|array $test): void
	{
		$this->assertEquals($expected, HTML::specialChars($test));
	}

	public static function data_specials(): array
	{
		return [
			['&quot;', '"'],
			['&amp;', '&'],
			['&lt;', '<'],
			['&gt;', '>'],
			['“Order Confirmation” page…', '“Order Confirmation” page…'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $test
	 * @return void
	 * @dataProvider data_specials
	 * @covers ::specials
	 * @return void
	 */
	public function test_specials(string $expected, string $test): void
	{
		$this->assertEquals($expected, HTML::specials($test));
	}

	public static function data_strip(): array
	{
		return [
			['Dude', '<strong>Dude</strong>'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $test
	 * @return void
	 * @covers ::strip
	 * @dataProvider data_strip
	 */
	public function test_strip(string $expected, string $test): void
	{
		$this->assertEquals($expected, HTML::strip($test));
	}

	public static function data_strlen(): array
	{
		return [
			[
				4,
				'<a href="/">Link</a>',
			],
			[
				5,
				'<a href="/">Links</a>',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $html
	 * @return void
	 * @dataProvider data_strlen
	 * @covers ::strlen
	 */
	public function test_strlen($expected, $html): void
	{
		$this->assertEquals($expected, HTML::strlen($html));
	}

	public static function data_style_units(): array
	{
		return [
			[
				'3px',
				'3',
				'px',
			],
			[
				'3em',
				'3em',
				'px',
			],
			[
				'32em',
				'32em',
				'px',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $item
	 * @param $default_unit
	 * @return void
	 * @dataProvider data_style_units
	 * @covers ::styleUnits
	 */
	public function test_style_units($expected, $item, $default_unit): void
	{
		$this->assertEquals($expected, HTML::styleUnits($item, $default_unit));
	}

	public static function data_substring(): array
	{
		$sample = '<h1>Heading</h1><p>Once upon a time</p>';
		return [
			[
				'<h1>H</h1>',
				$sample,
				0,
				1,
			],
			[
				'<h1>Heading</h1><p>Onc</p>',
				$sample,
				0,
				10,
			],
			[
				'<h1>Heading</h1><p>Once upon a t</p>',
				$sample,
				0,
				20,
			],
			[
				'<h1>Heading</h1><p>Once upon a tim</p>',
				$sample,
				0,
				22,
			],
			[
				$sample,
				$sample,
				0,
				30,
			],
			[
				$sample,
				'<h1>Heading</h1><p>Once upon a time</p>',
				0,
				50,
			],
		];
	}

	/**
	 * @param $expected
	 * @param $html
	 * @param $offset
	 * @param $length
	 * @return void
	 * @dataProvider data_substring
	 * @covers ::substring
	 */
	public function test_substring($expected, $html, $offset, $length): void
	{
		$this->assertEquals($expected, HTML::substring($html, $offset, $length));
	}

	public static function data_tag(): array
	{
		return [
			[
				'<h1>Heading</h1>',
				'h1',
				[],
				'Heading',
			],
			[
				'<h1 class="dude">Heading</h1>',
				'h1',
				'.dude',
				'Heading',
			],
			[
				'<h1 id="dude">Heading</h1>',
				'h1',
				'#dude',
				'Heading',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $name
	 * @param $mixed
	 * @param $content
	 * @return void
	 * @dataProvider data_tag
	 * @covers ::tag
	 */
	public function test_tag($expected, $name, $mixed, $content): void
	{
		$this->assertEquals($expected, HTML::tag($name, $mixed, $content));
	}

	/**
	 * @throws SemanticsException
	 * @covers ::tagClose
	 */
	public function test_tagClose(): void
	{
		$this->expectException(SemanticsException::class);
		HTML::tagClose('p');
	}

	public static function data_tagOpen(): array
	{
		return [
			['<div id="dude"></div>', 'div', ['id' => 'dude'], ],
			['<div id="dude"><a id="dude"><b id="dude"></b></a></div>', ['div', 'a', 'b'], ['id' => 'dude'], ],
			['<z><e><s><k></k></s></e></z>', ['z', 'e', 's', 'k'], [], ],
		];
	}

	/**
	 * @param string $expected
	 * @param string|array $tags
	 * @param array $attributes
	 * @return void
	 * @throws SemanticsException
	 * @dataProvider data_tagOpen
	 * @covers ::tagOpen ::tagClose
	 * @throws SemanticsException
	 */
	public function test_tagOpen(string $expected, string|array $tags, array $attributes): void
	{
		$content = '';
		$tags = Types::toList($tags);
		foreach ($tags as $tag) {
			$content .= HTML::tagOpen($tag, $attributes);
		}
		foreach (array_reverse($tags) as $tag) {
			$content .= HTML::tagClose($tag);
		}
		$this->assertEquals($expected, $content);
	}

	public static function data_tags(): array
	{
		$separator = "\n";
		return [
			[
				"<h1 class=\"main\">Heading</h1>\n<h1 class=\"main\">Another</h1>\n",
				'h1',
				'.main',
				[
					'Heading',
					'Another',
				],
				$separator,
			],
			[
				"<h1 class=\"main\">Heading</h1>\n\n\nboo\n\n\n<h1 class=\"main\">Another</h1>\n\n\nboo\n\n\n",
				'h1',
				'.main',
				[
					'Heading',
					'Another',
				],
				"\n\n\nboo\n\n\n",
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $name
	 * @param array|string $attributes
	 * @param array $items
	 * @param string $separator
	 * @return void
	 * @dataProvider data_tags
	 * @covers ::tags
	 */
	public function test_tags(string $expected, string $name, array|string $attributes, iterable $items, string $separator): void
	{
		$this->assertEquals($expected, HTML::tags($name, $attributes, $items, $separator));
	}

	public static function data_entitiesReplace(): array
	{
		return [
			['', ''],
			['"Hello, world."', '&ldquo;Hello, world.&rdquo;'],
			['\'Hello, world.\'', '&lsquo;Hello, world.&rsquo;'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $html
	 * @return void
	 * @covers ::entitiesReplace
	 * @dataProvider data_entitiesReplace
	 */
	public function test_entitiesReplace(string $expected, string $html): void
	{
		$this->assertEquals($expected, HTML::entitiesReplace($html));
	}

	public static function data_tags_boom(): array
	{
		return [
			["<li id=\"another\">a</li>\n<li id=\"another\">b</li>\n<li id=\"another\">c</li>\n", 'li', '#id #another', [
				'a',
				'b',
				'c',
			]],
		];
	}

	/**
	 * @param string $expected
	 * @param string $tagName
	 * @param string|array $attributes
	 * @param iterable $items
	 * @return void
	 * @dataProvider data_tags_boom
	 * @covers ::tags
	 * This used to throw exceptions now it handles edge cases
	 */
	public function test_tags_boom(string $expected, string $tagName, string|array $attributes, iterable $items): void
	{
		$this->assertEquals($expected, HTML::tags($tagName, $attributes, $items));
	}

	public static function data_toAttributes(): array
	{
		return [
			[
				['class' => 'dude'],
				'.dude',
			],
			[
				['id' => 'dude'],
				'#dude',
			],
			[
				['class' => 'dude'],
				'dude',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $mixed
	 * @return void
	 * @dataProvider data_toAttributes
	 * @covers ::toAttributes
	 */
	public function test_toAttributes($expected, $mixed): void
	{
		$this->assertEquals($expected, HTML::toAttributes($mixed));
	}

	public static function data_trimBlankSpace(): array
	{
		return [
			[
				'<a href=".">link</a>',
				'   <a href=".">link</a><p>&nbsp;&nbsp;</p><p>    </p><br /><br><br> <p /> ',
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $html
	 * @return void
	 * @dataProvider data_trimBlankSpace
	 * @covers ::trimBlankSpace
	 */
	public function test_trimBlankSpace(string $expected, string $html): void
	{
		$this->assertEquals($expected, HTML::trimBlankSpace($html));
	}

	/**
	 * @return array[]
	 */
	public static function data_trimWords(): array
	{
		$test_phrase_1 = '<h1>one two three four five <em>six seven eight</em> nine ten</h1>';
		return [
			[
				$test_phrase_1,
				$test_phrase_1,
				10,
			],
			[
				'<h1>one two three four five <em>six seven eight</em> nine </h1>',
				$test_phrase_1,
				9,
			],
			[
				'<h1>one two three four five <em>six seven eight</em></h1>',
				$test_phrase_1,
				8,
			],
			[
				'<h1>one two three four five <em>six seven </em></h1>',
				$test_phrase_1,
				7,
			],
			[
				'<h1>one two three four five <em>six </em></h1>',
				$test_phrase_1,
				6,
			],
			[
				'<h1>one two three four five </h1>',
				$test_phrase_1,
				5,
			],
			[
				'<h1>one two three four </h1>',
				$test_phrase_1,
				4,
			],
			[
				'<h1>one two three </h1>',
				$test_phrase_1,
				3,
			],
			[
				'<h1>one two </h1>',
				$test_phrase_1,
				2,
			],
			[
				'<h1>one </h1>',
				$test_phrase_1,
				1,
			],
			[
				'',
				$test_phrase_1,
				0,
			],
			[
				'',
				$test_phrase_1,
				-1,
			],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @param $wordCount
	 * @return void
	 * @dataProvider data_trimWords
	 * @covers ::trimWords
	 */
	public function test_trimWords($expected, $string, $wordCount): void
	{
		$this->assertEquals($expected, HTML::trimWords($string, $wordCount));
	}

	public static function data_urlify(): array
	{
		return [
			[
				'<a href="http://example.com">http://example.com</a>',
				'http://example.com',
				[],
			],
		];
	}

	/**
	 * @param $expected
	 * @param $text
	 * @param $attributes
	 * @return void
	 * @dataProvider data_urlify
	 * @covers ::urlify
	 */
	public function test_urlify($expected, $text, $attributes): void
	{
		$this->assertEquals($expected, HTML::urlify($text, $attributes));
	}
}
