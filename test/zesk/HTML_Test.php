<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use stdClass;
use zesk\Exception\SemanticsException;

/**
 *
 * @author kent
 *
 */
class HTML_Test extends UnitTest {
	public function test_style_clean(): void {
		$attr = [
			'type' => 'text/javascript',
			'src' => 'style.css',
			'crap' => 'nope',
		];
		$allowed = null;
		$disallowed = ['crap'];
		$result = HTML::styleClean($attr, $allowed, $disallowed);
		$this->assertEquals([
			'type' => 'text/javascript',
			'src' => 'style.css',
		], $result);
	}

	/**
	 *
	 */
	public function test_parse_attribute(): void {
		$this->assertEquals([], HTML::parseAttributes([]));
		$this->assertEquals([
			1,
			2,
			3,
		], HTML::parseAttributes([
			1,
			2,
			3,
		]));
		$this->assertEquals(['1' => true], HTML::parseAttributes('1'));

		$this->assertEquals([
			'article' => '12',
			'template' => 'volunteer-help',
		], HTML::parseAttributes('template="volunteer-help" article="12"'));
	}

	/**
	 * @param HTMLTag[] $tags
	 * @return array
	 */
	public function cleanTagWhitespace(array $tags): array {
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

	public function test_extract_tags(): void {
		$tag = 'a';
		$contents = file_get_contents(dirname(__DIR__) . '/test-data/html-extract_tags.html');
		$tags = HTML::extractTags($tag, $contents, true);
		$expected_tags = [
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
		];
		$tags = $this->cleanTagWhitespace($tags);
		$expected_tags = $this->cleanTagWhitespace($expected_tags);

		foreach ($expected_tags as $index => $result_tag) {
			$tags[$index]->offset = $result_tag->offset;
		}

		$this->assertEquals($expected_tags, $tags);
	}

	public function test_a(): void {
		$this->assertEquals('<a hey="99" href="boo" />', HTML::a('boo', ['hey' => 99]));
	}

	public static function dataprovider_request(): array {
		return [
			[function () {
				$app = self::app();
				$request0 = $app->factory(Request::class, $app);
				$request0->initializeFromSettings([
					'url' => 'http://localhost/path/',
				]);
				return $request0;
			}],
		];
	}

	public function test_attributes(): void {
		$attributes = ['hello' => 'world'];
		HTML::attributes($attributes);
	}

	public function test_cdn_img(): void {
		$src = '';
		$text = '';
		$attrs = [];
		HTML::img($this->application, $src, $text, $attrs);
	}

	public function test_clean_style_attributes(): void {
		$base = '<a style="background-url: foo.gif; background-color: red;" href="/">Hello world</a>';
		$this->assertEquals('<a style="background-color: red;" href="/">Hello world</a>', HTML::cleanStyleAttributes($base, ['background-color'], []));
		$this->assertEquals('<a style="background-url: foo.gif;" href="/">Hello world</a>', HTML::cleanStyleAttributes($base, ['background-url'], []));
		$this->assertEquals($base, HTML::cleanStyleAttributes($base, [
			'background-url',
			'background-color',
		], []));
		$this->assertEquals('<a href="/">Hello world</a>', HTML::cleanStyleAttributes($base, [], [
			'background-url',
			'background-color',
		]));
		$this->assertEquals('<a href="/">Hello world</a>', HTML::cleanStyleAttributes($base, ['background-url'], ['background-url']));
	}

	public static function dataCleanTags(): array {
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
				['a', 'strong', 'p', 'h1', 'em'],
				[],
			],
		];
	}

	/**
	 * @return void
	 * @dataProvider dataCleanTags
	 */
	public function test_cleanTags($expected, $string, $allowed_tags, $remove_tags): void {
		$this->assertEquals($expected, HTML::cleanTags($string, $allowed_tags, $remove_tags), 'HTML::clean_tags');
	}

	public static function dataCleanTagAttributes(): array {
		return [
			['<a class="dude">Link</a>', [], ['class'], '<a>Link</a>'],
		];
	}

	/**
	 * @return void
	 * @dataProvider dataCleanTagAttributes
	 */
	public function test_clean_tags_attributes(string $string, $include, $exclude, $expected): void {
		$this->assertEquals($expected, HTML::cleanTagsAttributes($string, $include, $exclude), 'HTML::clean_tags_attributes');
	}

	public static function dataCleanTagsWithoutAttributes(): array {
		return [
			['<a href=\'dude\'>Link</a>', ['a'], '<a href=\'dude\'>Link</a>'],
			['<a href=\'dude\'>Link</a>Nolink', ['a'], '<a href=\'dude\'>Link</a><a>Nolink</a>'],
		];
	}

	/**
	 * @param $tags
	 * @param $html
	 * @param $expected
	 * @return void
	 * @dataProvider dataCleanTagsWithoutAttributes
	 */
	public function testCleanTagsWithoutAttributes(string $expected, array $tags, string $html): void {
		$this->assertEquals($expected, HTML::cleanTagsWithoutAttributes($tags, $html));
	}

	public static function dataCountEndTags(): array {
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
	 */
	public function test_count_end_tags($expected, $tag, $mixed): void {
		$this->assertEquals($expected, HTML::countEndTags($tag, $mixed));
	}

	public function test_countUntilTag(): void {
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

	public function test_div(): void {
		$this->assertEquals('<div class="hello world">Dude</div>', HTML::div('.hello world', 'Dude'));
	}

	public static function dataEllipsis(): array {
		return [
			[
				'<a href="link.gif">this is a word with </a> ...',
				'<a href="link.gif">this is a word with a lot of other words in it</a>',
				20,
				' ...',
			],
			[
				'<a href="link.gif">this</a>&mdash;',
				'<a href="link.gif">this is a word with a lot of other words in it</a>',
				4,
				'&mdash;',
			],
		];
	}

	/**
	 * @dataProvider dataEllipsis
	 */
	public function test_ellipsis($expected, $string, $length, $dot_dot_dot): void {
		$this->assertEquals($expected, HTML::ellipsis($string, $length, $dot_dot_dot));
	}

	public static function data_eTag(): array {
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
	 */
	public function test_etag($expected, $name, $mixed, $content): void {
		$this->assertEquals($expected, HTML::etag($name, $mixed, $content));
	}

	public static function data_extractEmails(): array {
		return [
			[['info@example.com', 'nowhere@noone.com'], '<a href="mailto:info@example.com">nowhere@noone.com</a>'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_extractEmails
	 */
	public function test_extractEmails($expected, $content): void {
		$this->assertEquals($expected, HTML::extractEmails($content));
	}

	public function test_extract_links(): void {
		HTML::extractLinks('Goo <a href="dude">Dude</a>');
	}

	public static function data_extractTagContents(): array {
		return [
			['Link', 'a', '<a href="">Link</a>'],
			['Link', 'a', '<a href="">Link</a><a>Loop</a>'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_extractTagContents
	 */
	public function test_extractTagContents($expected, $tag, $mixed): void {
		$this->assertEquals($expected, HTML::extractTagContents($tag, $mixed));
	}

	public static function data_extractTagObject(): array {
		$outer_html = '<a href="/">Link</a>';
		return [
			[new HTMLTag('a', ['href' => '/'], 'Link', $outer_html, 0), 'a', $outer_html],
			[new HTMLTag('a', ['href' => '/'], 'Link', $outer_html, 0), 'a', $outer_html . '<a>Loop</a>'],
		];
	}

	/**
	 * @param $expected
	 * @param $tag
	 * @param $mixed
	 * @return void
	 * @dataProvider data_extractTagObject
	 */
	public function test_extract_tag_object($expected, $tag, $mixed): void {
		$this->assertEquals($expected, HTML::extractTag($tag, $mixed));
	}

	public function test_hidden(): void {
		$this->assertEquals('<input name="name" type="hidden" value="secret" />', HTML::hidden('name', 'secret'));
	}

	public function test_img(): void {
		$src = 'image.gif';
		$text = '';
		$attrs = ['alt' => 'dude'];
		$this->assertEquals('<img alt="" title="" src="image.gif" border="0" />', HTML::img($this->application, $src, $text, $attrs));
	}

	public function test_img_compat(): void {
		$src = 'image.gif';
		$w = null;
		$h = null;
		$text = '';
		$attrs = [];
		$this->assertEquals('<img alt="" title="" src="image.gif" border="0" />', HTML::img_compat($this->application, $src, $w, $h, $text, $attrs));
	}

	public function test_input(): void {
		$type = 'hidden';
		$name = 'hidden-input';
		$value = '<>[]:"';
		$attributes = ['class' => 'none'];
		$this->assertEquals('<input class="none" name="hidden-input" type="hidden" value="&lt;&gt;[]:&quot;" />', HTML::input($type, $name, $value, $attributes));
	}

	public function test_input_button(): void {
		$n = 'name';
		$v = 'random';
		$attrs = [];
		$this->assertEquals('<input name="name" value="random" type="button" id="name" />', HTML::input_button($n, $v, $attrs));
	}

	public function test_input_hidden(): void {
		$name = 'inputname';
		$value = 'random';
		$attributes = [];
		$this->assertEquals('<input name="inputname" type="hidden" value="random" />', HTML::input_hidden($name, $value, $attributes));
	}

	public function test_input_submit(): void {
		$name = 'inputname';
		$value = 'random';
		$attributes = ['data-test' => 'hello'];
		$this->assertEquals(
			'<input data-test="hello" name="inputname" value="random" type="submit" id="inputname" />',
			HTML::input_submit($name, $value, $attributes)
		);
	}

	public function test_insert_inside_end(): void {
		$html = '<a>Dude</a>';
		$insert_html = '<strong>*</strong>';
		$this->assertEquals('<a>Dude<strong>*</strong></a>', HTML::insertInsideEnd($html, $insert_html));
	}

	public static function data_isEndTag(): array {
		return [
			['a', '</a>'],
			['a', '</a   >'],
			['a', '</    a   >'],
			['waybetter', '</    waybetter   >'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_isEndTag
	 */
	public function test_is_end_tag($expected, $tag): void {
		$this->assertEquals($expected, HTML::isEndTag($tag));
	}

	public static function data_matchTags(): array {
		return [
			[
				[['<a>', 'a', ''], ['<b>', 'b', ''], ['<strong>', 'strong', ''], ['<li>', 'li', '']],
				'<a><b></b><strong></strong><li>',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @return void
	 * @dataProvider data_matchTags
	 */
	public function test_match_tags(array $expected, string $string): void {
		$this->assertEquals($expected, HTML::matchTags($string));
	}

	public static function data_mixedToString(): array {
		$tag = new HTMLTag('tag');
		$tag->setInnerHTML('Yoyoyo');
		return [
			['', null],
			['', 5123],
			['', 99.2],
			['', new stdClass()],
			['string', 'string'],
			['Yoyoyo', $tag],
			['Yoyoyo', ['ho' => $tag]],
		];
	}

	/**
	 * @param $expected
	 * @param $mixed
	 * @dataProvider data_mixedToString
	 * @return void
	 */
	public function test_mixed_to_string($expected, $mixed): void {
		$this->assertEquals($expected, HTML::mixedToString($mixed));
	}

	public function test_parse_styles(): void {
		$this->assertEquals([
			'background-color' => 'red',
			'position' => 'absolute',
			'z-index' => '2000',
		], HTML::parse_styles('background-color: red; position: absolute; z-index: 2000'));
	}

	public static function data_parseTags(): array {
		return [
			[['a' => ['href' => '/']], '<a href="/">Link</a>'],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @return void
	 * @dataProvider data_parseTags
	 */
	public function test_parseTags($expected, $string): void {
		$this->assertEquals($expected, HTML::parseTags($string));
	}

	public static function data_removeTags(): array {
		return [
			['', 'a', '<a href="/">Link</a>', true],
			['Link', 'a', '<a href="/">Link</a>', false],
		];
	}

	/**
	 * @param $expected
	 * @param $tag
	 * @param $contents
	 * @param $delete
	 * @return void
	 * @dataProvider data_removeTags
	 */
	public function test_removeTags($expected, $tag, $contents, $delete): void {
		$this->assertEquals($expected, HTML::removeTags($tag, $contents, $delete));
	}

	public function test_select(): void {
		$name = 'ThingsToBuy';
		$value = 'opt';
		$options = [
			'opt' => 'car',
			'opt2' => 'home',
			'opt3' => 'insurance',
			'opt99' => 'fun',
		];
		$attributes = [];
		$this->assertEquals('<select name="ThingsToBuy" id="ThingsToBuy"><option value="opt" selected="selected">car</option><option value="opt2">home</option><option value="opt3">insurance</option><option value="opt99">fun</option></select>', HTML::select($name, $value, $options, $attributes));
	}

	public function test_specialchars(): void {
		$string = 'o	The second step in the checkout process where the customer reviews the information they entered before placing the order
		o	Action Page: “Preview Order” page...can’t see the URL
		o	Thank You page: It would be the “Order Confirmation” page…can’t see the URL';
		HTML::specialChars($string);
	}

	public function test_specials(): void {
		$string = 'o	The second step in the checkout process where the customer reviews the information they entered before placing the order
		o	Action Page: “Preview Order” page...can’t see the URL
		o	Thank You page: It would be the “Order Confirmation” page…can’t see the URL';
		HTML::specials($string);
	}

	public function test_strip(): void {
		$x = '<strong>Dude</strong>';
		$this->assertEquals('Dude', HTML::strip($x));
	}

	public static function data_strlen(): array {
		return [
			[4, '<a href="/">Link</a>'],
			[5, '<a href="/">Links</a>'],
		];
	}

	/**
	 * @param $expected
	 * @param $html
	 * @return void
	 * @dataProvider data_strlen
	 */
	public function test_strlen($expected, $html): void {
		$this->assertEquals($expected, HTML::strlen($html));
	}

	public static function data_style_units(): array {
		return [
			['3px', '3', 'px'],
			['3em', '3em', 'px'],
			['32em', '32em', 'px'],
		];
	}

	/**
	 * @param $expected
	 * @param $item
	 * @param $default_unit
	 * @return void
	 * @dataProvider data_style_units
	 */
	public function test_style_units($expected, $item, $default_unit): void {
		$this->assertEquals($expected, HTML::style_units($item, $default_unit));
	}

	public static function data_substr(): array {
		$sample = '<h1>Heading</h1><p>Once upon a time</p>';
		return [
			['<h1>H</h1>', $sample, 0, 1],
			['<h1>Heading</h1><p>Onc</p>', $sample, 0, 10],
			['<h1>Heading</h1><p>Once upon a t</p>', $sample, 0, 20],
			['<h1>Heading</h1><p>Once upon a tim</p>', $sample, 0, 22],
			[$sample, $sample, 0, 30],
			[$sample, '<h1>Heading</h1><p>Once upon a time</p>', 0, 50],
		];
	}

	/**
	 * @param $expected
	 * @param $html
	 * @param $offset
	 * @param $length
	 * @return void
	 * @dataProvider data_substr
	 */
	public function test_substr($expected, $html, $offset, $length): void {
		$this->assertEquals($expected, HTML::substr($html, $offset, $length));
	}

	public static function data_tag(): array {
		return [
			['<h1>Heading</h1>', 'h1', [], 'Heading'],
			['<h1 class="dude">Heading</h1>', 'h1', '.dude', 'Heading'],
			['<h1 id="dude">Heading</h1>', 'h1', '#dude', 'Heading'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_tag
	 */
	public function test_tag($expected, $name, $mixed, $content): void {
		$this->assertEquals($expected, HTML::tag($name, $mixed, $content));
	}

	public function test_tag_close(): void {
		$this->expectException(SemanticsException::class);
		HTML::tag_close('p');
	}

	public function test_tag_open(): void {
		$attributes = [
			'id' => 'dude',
		];
		HTML::tag_open('div', $attributes);
		HTML::tag_close('DIV');
	}

	public static function data_tags(): array {
		$separator = "\n";
		return [
			[
				"<h1 class=\"main\">Heading</h1>\n<h1 class=\"main\">Another</h1>\n",
				'h1',
				'.main',
				['Heading', 'Another'],
				$separator,
			],
			[
				"<h1 class=\"main\">Heading</h1>\n\n\nboo\n\n\n<h1 class=\"main\">Another</h1>\n\n\nboo\n\n\n",
				'h1',
				'.main',
				['Heading', 'Another'],
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
	 */
	public function test_tags(string $expected, string $name, array|string $attributes, array $items, string $separator): void {
		$this->assertEquals($expected, HTML::tags($name, $attributes, $items, $separator));
	}

	public function test_tags_boom(): void {
		// 2023-02 removed this exception
		// $this->expectException(Semantics::class);
		HTML::tags('li', '#id #another', ['a', 'b', 'c']);
	}

	public static function data_toAttributes(): array {
		return [
			[['class' => 'dude'], '.dude'],
			[['id' => 'dude'], '#dude'],
			[['class' => 'dude'], 'dude'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_toAttributes
	 */
	public function test_toAttributes($expected, $mixed): void {
		$this->assertEquals($expected, HTML::toAttributes($mixed));
	}

	public static function data_trimWhiteSpace(): array {
		return [
			['<a href=".">link</a>', '   <a href=".">link</a><p>&nbsp;&nbsp;</p><p>    </p><br /><br><br> <p /> '],
		];
	}

	/**
	 * @param $expected
	 * @param $html
	 * @return void
	 * @dataProvider data_trimWhiteSpace
	 */
	public function test_trim_white_space($expected, $html): void {
		$this->assertEquals($expected, HTML::trim_white_space($html));
	}

	public static function data_trimWords(): array {
		$test_phrase_1 = '<h1>one two three four five <em>six seven eight</em> nine ten</h1>';
		return [
			[$test_phrase_1, $test_phrase_1, 10],
			['<h1>one two three four five <em>six seven eight</em> nine </h1>', $test_phrase_1, 9],
			['<h1>one two three four five <em>six seven eight</em></h1>', $test_phrase_1, 8],
			['<h1>one two three four five <em>six seven </em></h1>', $test_phrase_1, 7],
			['<h1>one two three four five <em>six </em></h1>', $test_phrase_1, 6],
			['<h1>one two three four five </h1>', $test_phrase_1, 5],
			['<h1>one two three four </h1>', $test_phrase_1, 4],
			['<h1>one two three </h1>', $test_phrase_1, 3],
			['<h1>one two </h1>', $test_phrase_1, 2],
			['<h1>one </h1>', $test_phrase_1, 1],
			['', $test_phrase_1, 0],
			['', $test_phrase_1, -1],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_trimWords
	 */
	public function test_trimWords($expected, $string, $wordCount): void {
		$this->assertEquals($expected, HTML::trimWords($string, $wordCount));
	}

	public static function data_urlify(): array {
		return [
			['<a href="http://example.com">http://example.com</a>', 'http://example.com', []],
		];
	}

	/**
	 * @param $expected
	 * @param $text
	 * @param $attributes
	 * @return void
	 * @dataProvider data_urlify
	 */
	public function test_urlify($expected, $text, $attributes): void {
		$this->assertEquals($expected, HTML::urlify($text, $attributes));
	}
}
