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
class HTML_Test extends UnitTest {
	public function test_style_clean(): void {
		$attr = [
			'type' => 'text/javascript',
			'src' => 'style.css',
			'crap' => 'nope',
		];
		$allowed = null;
		$disallowed = ['crap'];
		$result = HTML::style_clean($attr, $allowed, $disallowed);
		Debug::output($result);
		$this->assert_arrays_equal($result, [
			'type' => 'text/javascript',
			'src' => 'style.css',
		]);
	}

	/**
	 *
	 */
	public function test_parse_attribute(): void {
		$this->assert_equal(HTML::parseAttributes([]), []);
		$this->assert_equal(HTML::parseAttributes([
			1,
			2,
			3,
		]), [
			1,
			2,
			3,
		]);
		$this->assert_equal(HTML::parseAttributes('1'), ['1' => true]);

		$this->assert_arrays_equal(HTML::parseAttributes('template="volunteer-help" article="12"'), [
			'article' => '12',
			'template' => 'volunteer-help',
		]);
	}

	/**
	 * @param HTML_Tag[] $tags
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
		$tags = HTML::extract_tags($tag, $contents, true);
		$expected_tags = [
			new HTML_Tag('a', [
				'href' => '/',
			], '<img src="https://zesk.com/test/_img/iana-logo-pageheader.png" alt="Homepage" />', '<a href="/"><img src="https://zesk.com/test/_img/iana-logo-pageheader.png" alt="Homepage" /></a>', 1107),
			new HTML_Tag('a', [
				'href' => '/domains/',
			], 'Domains', '<a href="/domains/">Domains</a>', 1240),
			new HTML_Tag('a', [
				'href' => '/numbers/',
			], 'Numbers', '<a href="/numbers/">Numbers</a>', 1279),
			new HTML_Tag('a', [
				'href' => '/protocols/',
			], 'Protocols', '<a href="/protocols/">Protocols</a>', 1318),
			new HTML_Tag('a', [
				'href' => '/about/',
			], 'About IANA', '<a href="/about/">About IANA</a>', 0),
			new HTML_Tag('a', [
				'href' => '/go/rfc2606',
			], 'RFC 2606', '<a href="/go/rfc2606">RFC 2606</a>', 0),
			new HTML_Tag('a', [
				'href' => '/about/',
			], 'About', '<a href="/about/">About</a>', 0),
			new HTML_Tag('a', [
				'href' => '/about/presentations/',
			], 'Presentations', '<a href="/about/presentations/">Presentations</a>', 1867),
			new HTML_Tag('a', [
				'href' => '/about/performance/',
			], 'Performance', '<a href="/about/performance/">Performance</a>', 1936),
			new HTML_Tag('a', [
				'href' => '/reports/',
			], 'Reports', '<a href="/reports/">Reports</a>', 1986),
			new HTML_Tag('a', [
				'href' => '/domains/',
			], 'Domains', '<a href="/domains/">Domains</a>', 2052),
			new HTML_Tag('a', [
				'href' => '/domains/root/',
			], 'Root Zone', '<a href="/domains/root/">Root Zone</a>', 2092),
			new HTML_Tag('a', [
				'href' => '/domains/int/',
			], '.INT', '<a href="/domains/int/">.INT</a>', 2135),
			new HTML_Tag('a', [
				'href' => '/domains/arpa/',
			], '.ARPA', '<a href="/domains/arpa/">.ARPA</a>', 2173),
			new HTML_Tag('a', [
				'href' => '/domains/idn-tables/',
			], 'IDN Repository', '<a href="/domains/idn-tables/">IDN Repository</a>', 2212),
			new HTML_Tag('a', [
				'href' => '/protocols/',
			], 'Protocols', '<a href="/protocols/">Protocols</a>', 2274),
			new HTML_Tag('a', [
				'href' => '/numbers/',
			], 'Number Resources', '<a href="/numbers/">Number Resources</a>', 2329),
			new HTML_Tag('a', [
				'href' => '/abuse/',
			], 'Abuse Information', '<a href="/abuse/">Abuse Information</a>', 2378),
			new HTML_Tag('a', [
				'href' => 'http://www.icann.org/',
			], 'Internet Corporation for Assigned Names and Numbers', '<a href="http://www.icann.org/">Internet Corporation for Assigned Names and Numbers</a>', 2523),
			new HTML_Tag('a', [
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
		$href = null;
		$mixed = null;
		HTML::a($href, $mixed);
	}

	public function test_a_condition(): void {
		$condition = null;
		$href = null;
		$mixed = null;
		HTML::a_condition($condition, $href, $mixed);
	}

	public function dataprovider_request() {
		$request0 = $this->application->factory(Request::class, $this->application);
		$request0->initializeFromSettings([
			'url' => 'http://localhost/path/',
		]);
		return [
			[
				$request0,
			],
		];
	}

	/**
	 * @dataProvider dataprovider_request
	 */
	public function test_a_match(Request $request): void {
		$href = null;
		$mixed = null;
		HTML::a_match($request, $href, $mixed);
	}

	/**
	 * @dataProvider dataprovider_request
	 */
	public function test_a_path(Request $request): void {
		$href = null;
		$mixed = null;
		HTML::a_path($request, $href, $mixed);
	}

	/**
	 * @dataProvider dataprovider_request
	 */
	public function test_a_prefix(Request $request): void {
		$href = '';
		$mixed = 'null';
		HTML::a_prefix($request, $href, $mixed);
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
		$base = '<a style="background-url: foo.gif; background-color: red" href="/">Hello world</a>';
		$this->assertEquals('<a style="background-color: red;" href="/">Hello world</a>', HTML::clean_style_attributes($base, ['background-color'], []));
		$this->assertEquals('<a style="background-url: foo.gif" href="/">Hello world</a>', HTML::clean_style_attributes($base, ['background-url'], []));
		$this->assertEquals($base, HTML::clean_style_attributes($base, [
			'background-url',
			'background-color',
		], []));
		$this->assertEquals('<a href="/">Hello world</a>', HTML::clean_style_attributes($base, [], [
			'background-url',
			'background-color',
		]));
		$this->assertEquals('<a href="/">Hello world</a>', HTML::clean_style_attributes($base, ['background-url'], ['background-url']));
	}

	public function dataCleanTags() {
		$simple_html = '<a href="/"><strong>Bold</strong><em>Italic</em><p>Paragraphs</p></a><h1>Heading</h1><h2>Heading</h2>';
		return [
			[
				$simple_html,
				[],
				[],
				$simple_html,
			],
			[
				$simple_html,
				[],
				['h2'],
				'<a href="/"><strong>Bold</strong><em>Italic</em><p>Paragraphs</p></a><h1>Heading</h1>',
			],
		];
	}

	/**
	 * @return void
	 * @dataProvider dataCleanTags
	 */
	public function test_cleanTags($string, $allowed_tags, $remove_tags, $expected): void {
		$this->assertEquals($expected, HTML::cleanTags($string, $allowed_tags, $remove_tags), 'HTML::clean_tags');
	}

	public function dataCleanTagAttributes() {
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

	public function dataCleanTagsWithoutAttributes() {
		return [
			[['a'], '<a href=\'dude\'>Link</a>', '<a href=\'dude\'>Link</a>'],
			[['a'], '<a href=\'dude\'>Link</a><a>Nolink</a>', '<a href=\'dude\'>Link</a>Nolink'],
		];
	}

	public function testCleanTagsWithoutAttributes($tags, $html, $expected): void {
		$this->assertEquals($expected, HTML::cleanTagsWithoutAttributes($tags, $html));
	}

	public function dataCountEndTags(): array {
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

	public function test_count_until_tag(): void {
		$string = 'All good <strong>things</strong> must come to an <em>end</em>.<h1>Hello!</h1>';
		//        01234567890123456789012345678901234567890123456789
		$offset = 17;
		$nWords = 0;
		$tagName = 'strong';
		$this->assert(HTML::count_until_tag($string, $tagName, $nWords) === $offset, "HTML::count_until_tag($string, $tagName, $nWords) " . HTML::count_until_tag($string, $tagName, $nWords) . " === $offset");
		$this->assert($nWords === 2);
		$this->assert($tagName === 'strong', "$tagName === strong");
	}

	public function test_div(): void {
		$this->assertEquals('<div class="hello world">Dude</div>', HTML::div('.hello world', 'Dude'));
	}

	public function dataEllipsis(): array {
		return [
			[
				'<a href="link.gif">this is a word with a lot of</a> ...',
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

	public function data_eTag(): array {
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
	 * @return void
	 * @dataProvider data_eTag
	 */
	public function test_etag($expected, $name, $mixed, $content): void {
		$this->assertEquals($expected, HTML::etag($name, $mixed, $content));
	}

	public function data_extractEmails(): array {
		return [
			[['info@example.com', 'nowhere@none.com'], '<a href="mailto:info@example.com">nowhere@noone.com</a>'],
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
		$content = null;
		HTML::extract_links('Goo <a href="dude">Dude</a>');
	}

	public function data_extractTagContents(): array {
		return [
			['Link', 'a', '<a href="">Link</a>'],
			['Link', 'a', '<a href="">Link</a><a>Loop</a>'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_extractTagContents
	 */
	public function test_extract_tag_contents($expected, $tag, $mixed): void {
		$this->assertEquals($expected, HTML::extractTagContents($tag, $mixed));
	}

	public function data_extractTagObject(): array {
		return [
			[new HTML_Tag('a', ['href' => '/'], 'Link'), 'a', '<a href="/">Link</a>'],
			[new HTML_Tag('a', ['href' => '/'], 'Link'), 'a', '<a href="">Link</a><a>Loop</a>'],
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
		$this->assertEquals($expected, HTML::extract_tag_object($tag, $mixed));
	}

	public function test_hidden(): void {
		$name = null;
		$value = null;
		HTML::hidden($name, $value);
	}

	public function test_img(): void {
		$src = 'image.gif';
		$text = '';
		$attrs = ['alt' => 'dude'];
		$this->assertEquals('<img alt="" title="" src="image.gif" border="0" />', HTML::img($this->application, $src, $text, $attrs));
	}

	public function test_img_compat(): void {
		$src = 'image.gif';
		$w = false;
		$h = false;
		$text = '';
		$attrs = [];
		$this->assertEquals('', HTML::img_compat($this->application, $src, $w, $h, $text, $attrs));
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
		$n = null;
		$v = null;
		$attrs = false;
		HTML::input_submit($n, $v, $attrs);
	}

	public function test_insert_inside_end(): void {
		$html = '<a>Dude</a>';
		$insert_html = '<strong>*</strong>';
		$this->assertEquals('<a>Dude<strong>*</strong></a>', HTML::insertInsideEnd($html, $insert_html));
	}

	public function data_isEndTag() {
		return [
			[true, '</a>'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_isEndTag
	 */
	public function test_is_end_tag($expected, $tag): void {
		$this->assertEquals($expected, HTML::is_end_tag($tag));
	}

	/**
	 * @todo move to zesk\Response_Test after HTML merged into parent
	 */
	public function TODO_test_scripts(): void {
		$type = 'text/javascript';
		$script = 'alert(\'Hello, world!\');';
		HTML::javascript_inline($script, [
			'browser' => 'ie',
		]);

		$scripts = HTML::scripts();

		$this->assert(str_contains($scripts, $script));
		$this->assert(str_contains($scripts, '<!--'));
		$this->assert(str_contains($scripts, '[if IE]'));
		$this->assert(str_contains($scripts, '<![endif]-->'));

		$this->assert_equal($scripts, '<!--[if IE]><script type="text/javascript">alert(\'Hello, world!\');</script><![endif]-->');
	}

	public function data_matchTags() {
		return [
			[['a', 'b', 'strong', 'li'], '<a><b></b><strong></strong><li>'],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @return void
	 * @dataProvider data_matchTags
	 */
	public function test_match_tags($expected, $string): void {
		$this->assertEquals($expected, HTML::match_tags($string));
	}

	public function test_mixed_to_string(): void {
		$mixed = null;
		HTML::mixed_to_string($mixed);
	}

	public function test_parse_styles(): void {
		$this->assertEquals([
			'background-color' => 'red',
			'position' => 'absolute',
			'z-index' => '2000',
		], HTML::parse_styles('background-color: red; position: absolute; z-index: 2000'));
	}

	public function data_parseTags(): array {
		return [
			[['', ], '<a href="/">Link</a>'],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @return void
	 * @dataProvider data_parseTags
	 */
	public function test_parse_tags($expected, $string): void {
		$this->assertEquals($expected, HTML::parse_tags($string));
	}

	public function data_removeTags(): array {
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
		$this->assertEquals($expected, HTML::remove_tags($tag, $contents, $delete));
	}

	public function test_select(): void {
		$name = null;
		$value = null;
		$options = [
			'opt' => 'opt',
			'opt2' => 'opt2',
		];
		$attributes = null;
		HTML::select($name, $value, $options, $attributes);
	}

	public function test_specialchars(): void {
		$string = 'o	The second step in the checkout process where the customer reviews the information they entered before placing the order
		o	Action Page: “Preview Order” page...can’t see the URL
		o	Thank You page: It would be the “Order Confirmation” page…can’t see the URL';
		HTML::specialchars($string);
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

	public function data_strlen(): array {
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

	public function data_style_units(): array {
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

	public function data_substr(): array {
		return [
			['<h1>Heading</h1><p>Once upon a time</p>', '<h1>Heading</h1><p>Once upon a time</p>', 0, 1],
			['<h1>Heading</h1><p>Once upon a time</p>', '<h1>Heading</h1><p>Once upon a time</p>', 0, 10],
			['<h1>Heading</h1><p>Once upon a time</p>', '<h1>Heading</h1><p>Once upon a time</p>', 0, 20],
			['<h1>Heading</h1><p>Once upon a time</p>', '<h1>Heading</h1><p>Once upon a time</p>', 0, 30],
			['<h1>Heading</h1><p>Once upon a time</p>', '<h1>Heading</h1><p>Once upon a time</p>', 0, 50],
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

	public function data_tag(): array {
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

	/**
	 * @expectedException zesk\Exception_Semantics
	 */
	public function test_tag_close(): void {
		$tagName = null;
		HTML::tag_close('p');
	}

	public function test_tag_open(): void {
		$attributes = [
			'id' => 'dude',
		];
		HTML::tag_open('div', $attributes);
		HTML::tag_close('DIV');
	}

	public function data_tags(): array {
		return [
			['<h1>Heading</h1><h1>Another</h1>', 'h1', ['Heading', 'Another']],
		];
	}

	/**
	 * @param $expected
	 * @param $name
	 * @param $mixed
	 * @return void
	 * @dataProvider data_tags
	 */
	public function test_tags($expected, $name, $mixed): void {
		$this->assertEquals($expected, HTML::tags($name, $mixed));
	}

	public function data_toAttributes(): array {
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

	public function data_trimWhiteSpace(): array {
		return [
			['<a>href</a>', '   <a  > href < /a >  '],
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

	public function data_trimWords(): array {
		$test_phrase_1 = '<h1>one two three four five <em>six seven eight</em> nine ten</h1>';
		return [
			[$test_phrase_1, $test_phrase_1, 10],
			['<h1>one two three four five <em>six seven eight</em> nine </h1>', '<h1>one two three four</h1>', 9],
			['<h1>one two three four five <em>six seven eight</em></h1>', '<h1>one two three four</h1>', 8],
			['<h1>one</h1>', '<h1>one two three four</h1>', 1],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_trimWords
	 */
	public function test_trim_words($expected, $string, $wordCount): void {
		$this->assertEquals($expected, HTML::trim_words($string, $wordCount));
	}

	public function data_urlify() {
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
