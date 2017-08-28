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
class HTML_Test extends Test_Unit {
	function test_style_clean() {
		$attr = array(
			'type' => 'text/javascript',
			'src' => 'style.css',
			'crap' => 'nope'
		);
		$allowed = true;
		$disallowed = 'crap';
		$result = HTML::style_clean($attr, $allowed, $disallowed);
		Debug::output($result);
		$this->assert_arrays_equal($result, array(
			'type' => 'text/javascript',
			'src' => 'style.css'
		));
	}
	
	/**
	 * 
	 */
	function test_parse_attribute() {
		$this->assert_equal(HTML::parse_attributes(array()), array());
		$this->assert_equal(HTML::parse_attributes(array(
			1,
			2,
			3
		)), array(
			1,
			2,
			3
		));
		$this->assert_equal(HTML::parse_attributes(1), array());
		$this->assert_equal(HTML::parse_attributes(null), array());
		$this->assert_equal(HTML::parse_attributes(true), array());
		$this->assert_equal(HTML::parse_attributes(false), array());
		
		$this->assert_arrays_equal(HTML::parse_attributes('template="volunteer-help" article="12"'), array(
			"article" => "12",
			"template" => "volunteer-help"
		));
	}
	function test_extract_tags() {
		$tag = "a";
		$contents = file_get_contents(dirname(__DIR__) . '/test-data/html-extract_tags.html');
		$recursive = true;
		$tags = HTML::extract_tags($tag, $contents, $recursive);
		
		$result_tags = array(
			new HTML_Tag('a', array(
				'href' => '/'
			), '<img src="https://zesk.com/test/_img/iana-logo-pageheader.png" alt="Homepage"/>', '<a href="/"><img src="https://zesk.com/test/_img/iana-logo-pageheader.png" alt="Homepage"/></a>', 1107),
			new HTML_Tag('a', array(
				'href' => '/domains/'
			), 'Domains', '<a href="/domains/">Domains</a>', 1240),
			new HTML_Tag('a', array(
				'href' => '/numbers/'
			), 'Numbers', '<a href="/numbers/">Numbers</a>', 1279),
			new HTML_Tag('a', array(
				'href' => '/protocols/'
			), 'Protocols', '<a href="/protocols/">Protocols</a>', 1318),
			new HTML_Tag('a', array(
				'href' => '/about/'
			), 'About IANA', '<a href="/about/">About IANA</a>', 0),
			new HTML_Tag('a', array(
				'href' => '/go/rfc2606'
			), 'RFC 2606', '<a href="/go/rfc2606">RFC 2606</a>', 0),
			new HTML_Tag('a', array(
				'href' => '/about/'
			), 'About', '<a href="/about/">About</a>', 0),
			new HTML_Tag('a', array(
				'href' => '/about/presentations/'
			), 'Presentations', '<a href="/about/presentations/">Presentations</a>', 1867),
			new HTML_Tag('a', array(
				'href' => '/about/performance/'
			), 'Performance', '<a href="/about/performance/">Performance</a>', 1936),
			new HTML_Tag('a', array(
				'href' => '/reports/'
			), 'Reports', '<a href="/reports/">Reports</a>', 1986),
			new HTML_Tag('a', array(
				'href' => '/domains/'
			), 'Domains', '<a href="/domains/">Domains</a>', 2052),
			new HTML_Tag('a', array(
				'href' => '/domains/root/'
			), 'Root Zone', '<a href="/domains/root/">Root Zone</a>', 2092),
			new HTML_Tag('a', array(
				'href' => '/domains/int/'
			), '.INT', '<a href="/domains/int/">.INT</a>', 2135),
			new HTML_Tag('a', array(
				'href' => '/domains/arpa/'
			), '.ARPA', '<a href="/domains/arpa/">.ARPA</a>', 2173),
			new HTML_Tag('a', array(
				'href' => '/domains/idn-tables/'
			), 'IDN Repository', '<a href="/domains/idn-tables/">IDN Repository</a>', 2212),
			new HTML_Tag('a', array(
				'href' => '/protocols/'
			), 'Protocols', '<a href="/protocols/">Protocols</a>', 2274),
			new HTML_Tag('a', array(
				'href' => '/numbers/'
			), 'Number Resources', '<a href="/numbers/">Number Resources</a>', 2329),
			new HTML_Tag('a', array(
				'href' => '/abuse/'
			), 'Abuse Information', '<a href="/abuse/">Abuse Information</a>', 2378),
			new HTML_Tag('a', array(
				'href' => 'http://www.icann.org/'
			), 'Internet Corporation for Assigned Names and Numbers', '<a href="http://www.icann.org/">Internet Corporation for Assigned Names and Numbers</a>', 2523),
			new HTML_Tag('a', array(
				'href' => 'mailto:iana@iana.org?subject=General%20website%20feedback'
			), 'iana@iana.org', '<a href="mailto:iana@iana.org?subject=General%20website%20feedback">iana@iana.org</a>', 2723)
		);
		
		$this->assert_arrays_equal($tags, $result_tags);
		
		// 		foreach ($result_tags as $result_tag) {
		// 			$result_tag->offset += 22;
		// 		}
		
		// 		$contents = str_repeat('_', 22) . $contents;
		// 		$tags = HTML::extract_tags($tag, $contents, $recursive);
		
		// 		$this->assert_arrays_equal($tags, $result_tags);
	}
	/**
	 * Move to zesk\Response_Test 
	 * @todo
	 */
	function TODO_test_body_attributes() {
		$add = null;
		HTML::body_attributes("goo", "bar");
		HTML::body_attributes(array(
			"goo" => "actual"
		));
		HTML::body_attributes(array(
			"poo" => "bar"
		));
		HTML::body_attributes("poo", "actual");
		HTML::body_attributes(array(
			"dee" => "foofla"
		));
		HTML::body_attributes("dee", "actual");
		HTML::body_attributes("loo", "actual");
		
		$attrs = HTML::body_attributes();
		
		$compare_result = array(
			"goo" => "actual",
			"poo" => "actual",
			"dee" => "actual",
			"loo" => "actual"
		);
		
		dump($attrs, $compare_result);
		$this->assert_arrays_equal($attrs, $compare_result);
	}
	function test_a() {
		$href = null;
		$mixed = null;
		HTML::a($href, $mixed);
	}
	function test_a_condition() {
		$condition = null;
		$href = null;
		$mixed = null;
		HTML::a_condition($condition, $href, $mixed);
	}
	function test_a_match() {
		$href = null;
		$mixed = null;
		HTML::a_match($this->application->request(), $href, $mixed);
	}
	function test_a_path() {
		$href = null;
		$mixed = null;
		HTML::a_path($this->application->request(), $href, $mixed);
	}
	function test_a_prefix() {
		$href = null;
		$mixed = null;
		HTML::a_prefix($this->application->request(), $href, $mixed);
	}
	function test_attributes() {
		$attributes = null;
		HTML::attributes($attributes);
	}
	function test_cdn_img() {
		$src = null;
		$text = '';
		$attrs = false;
		HTML::img($this->application, $src, $text, $attrs);
	}
	function test_clean_style_attributes() {
		$string = null;
		$include = true;
		$exclude = false;
		HTML::clean_style_attributes($string, $include, $exclude);
	}
	function test_clean_tags() {
		$string = null;
		$allowed_tags = true;
		$remove_tags = false;
		HTML::clean_tags($string, $allowed_tags, $remove_tags);
	}
	function test_clean_tags_attributes() {
		$string = null;
		$include = true;
		$exclude = false;
		HTML::clean_tags_attributes($string, $include, $exclude);
	}
	function test_clean_tags_without_attributes() {
		$tags = null;
		$html = null;
		HTML::clean_tags_without_attributes($tags, $html);
	}
	function test_count_end_tags() {
		$tag = null;
		$mixed = null;
		HTML::count_end_tags($tag, $mixed);
	}
	function test_count_until_tag() {
		$string = "All good <strong>things</strong> must come to an <em>end</em>.<h1>Hello!</h1>";
		//        01234567890123456789012345678901234567890123456789
		$offset = 17;
		$nWords = null;
		$tagName = "strong";
		$this->assert(HTML::count_until_tag($string, $tagName, $nWords) === $offset, "HTML::count_until_tag($string, $tagName, $nWords) " . HTML::count_until_tag($string, $tagName, $nWords) . " === $offset");
		$this->assert($nWords === 2);
		$this->assert($tagName === "strong", "$tagName === strong");
	}
	function test_div() {
		$mixed = null;
		$content = null;
		HTML::div($mixed, $content);
	}
	function test_ellipsis() {
		$s = null;
		$n = 20;
		$dot_dot_dot = '...';
		HTML::ellipsis($s, $n, $dot_dot_dot);
	}
	function test_etag() {
		$name = null;
		$mixed = null;
		HTML::etag($name, $mixed);
	}
	function test_extract_emails() {
		$content = null;
		HTML::extract_emails($content);
	}
	function test_extract_links() {
		$content = null;
		HTML::extract_links("Goo <a href=\"dude\">Dude</a>");
	}
	function test_extract_tag_contents() {
		$tag = null;
		$mixed = null;
		HTML::extract_tag_contents($tag, $mixed);
	}
	function test_extract_tag_object() {
		$tag = null;
		$mixed = null;
		HTML::extract_tag_object($tag, $mixed);
	}
	function test_hidden() {
		$name = null;
		$value = null;
		HTML::hidden($name, $value);
	}
	function test_img() {
		$src = null;
		$text = '';
		$attrs = false;
		HTML::img($this->application, $src, $text, $attrs);
	}
	function test_img_compat() {
		$src = null;
		$w = false;
		$h = false;
		$text = '';
		$attrs = false;
		HTML::img_compat($this->application, $src, $w, $h, $text, $attrs);
	}
	function test_input() {
		$type = null;
		$name = null;
		$value = null;
		$attributes = null;
		HTML::input($type, $name, $value, $attributes);
	}
	function test_input_button() {
		$n = null;
		$v = null;
		$attrs = false;
		HTML::input_button($n, $v, $attrs);
	}
	function test_input_hidden() {
		$name = null;
		$value = null;
		$attributes = null;
		HTML::input_hidden($name, $value, $attributes);
	}
	function test_input_submit() {
		$n = null;
		$v = null;
		$attrs = false;
		HTML::input_submit($n, $v, $attrs);
	}
	function test_insert_inside_end() {
		$html = null;
		$insert_html = null;
		HTML::insert_inside_end($html, $insert_html);
	}
	function test_is_end_tag() {
		$string = null;
		HTML::is_end_tag($string);
	}
	
	/**
	 * @todo move to zesk\Response_Test after HTML merged into parent
	 */
	function TODO_test_scripts() {
		$type = "text/javascript";
		$script = "alert('Hello, world!');";
		HTML::javascript_inline($script, array(
			'browser' => 'ie'
		));
		
		$scripts = HTML::scripts();
		
		$this->assert(strpos($scripts, $script) !== false);
		$this->assert(strpos($scripts, "<!--") !== false);
		$this->assert(strpos($scripts, "[if IE]") !== false);
		$this->assert(strpos($scripts, "<![endif]-->") !== false);
		
		$this->assert_equal($scripts, '<!--[if IE]><script type="text/javascript">alert(\'Hello, world!\');</script><![endif]-->');
	}
	function test_match_tags() {
		$string = null;
		HTML::match_tags($string);
	}
	function test_mixed_to_string() {
		$mixed = null;
		HTML::mixed_to_string($mixed);
	}
	function test_parse_styles() {
		$style_string = null;
		HTML::parse_styles($style_string);
	}
	function test_parse_tags() {
		$string = null;
		HTML::parse_tags($string);
	}
	function test_remove_tags() {
		$tag = null;
		$mixed = null;
		$delete = true;
		HTML::remove_tags($tag, $mixed, $delete);
	}
	function test_select() {
		$name = null;
		$value = null;
		$options = array(
			"opt" => "opt",
			"opt2" => "opt2"
		);
		$attributes = null;
		HTML::select($name, $value, $options, $attributes);
	}
	function test_specialchars() {
		$mixed = null;
		HTML::specialchars($mixed);
		
		$string = 'o	The second step in the checkout process where the customer reviews the information they entered before placing the order
		o	Action Page: “Preview Order” page...can’t see the URL
		o	Thank You page: It would be the “Order Confirmation” page…can’t see the URL';
	}
	function test_specials() {
		$string = null;
		HTML::specials($string);
	}
	function test_strip() {
		$x = null;
		HTML::strip($x);
	}
	function test_strlen() {
		$s = null;
		HTML::strlen($s);
	}
	function test_style_units() {
		$item = null;
		$default_unit = 'px';
		HTML::style_units($item, $default_unit);
	}
	function test_substr() {
		$s = null;
		$offset = 0;
		$length = null;
		HTML::substr($s, $offset, $length);
	}
	function test_tag() {
		$name = null;
		$mixed = null;
		HTML::tag($name, $mixed);
	}
	
	/**
	 * @expected_exception zesk\Exception_Semantics
	 */
	function test_tag_close() {
		$tagName = null;
		HTML::tag_close('p');
	}
	function test_tag_open() {
		$attributes = array(
			"id" => "dude"
		);
		HTML::tag_open("div", $attributes);
		HTML::tag_close("DIV");
	}
	function test_tags() {
		$name = null;
		$mixed = null;
		HTML::tags($name, $mixed);
	}
	function test_to_attributes() {
		$mixed = null;
		$default = null;
		HTML::to_attributes($mixed, $default);
	}
	function test_trim_white_space() {
		$html = null;
		HTML::trim_white_space($html);
	}
	function test_trim_words() {
		$string = null;
		$wordCount = null;
		HTML::trim_words($string, $wordCount);
	}
	function test_urlify() {
		$text = null;
		$attributes = array();
		HTML::urlify($text, $attributes);
	}
}
