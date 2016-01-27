<?php
class test_html extends Test_Unit {

	function test_style_clean() {
		$attr = array(
			'type' => 'text/javascript',
			'src' => 'style.css',
			'crap' => 'nope'
		);
		$allowed = true;
		$disallowed = 'crap';
		$result = html::style_clean($attr, $allowed, $disallowed);
		debug::output($result);
		$this->assert_arrays_equal($result, array(
			'type' => 'text/javascript',
			'src' => 'style.css'
		));
	}

	/**
	 * @expected_exception Exception_Semantics
	 */
	function test_javascript_end() {
		html::script_end();
	}

	function test_parse_attribute() {
		$attrString = null;
		html::parse_attributes($attrString);

		$this->assert_arrays_equal(html::parse_attributes('template="volunteer-help" article="12"'), array(
			"article" => "12",
			"template" => "volunteer-help"
		));
	}

	function test_extract_tags() {
		$tag = "a";
		$contents = file_get_contents(dirname(__FILE__) . '/html-extract_tags.html');
		$recursive = true;
		$tags = html::extract_tags($tag, $contents, $recursive);

		$result_tags = array(
			new HTML_Tag('a', array(
				'href' => '/'
			), '<img src="/_img/iana-logo-pageheader.png" alt="Homepage"/>', '<a href="/"><img src="/_img/iana-logo-pageheader.png" alt="Homepage"/></a>', 960),
			new HTML_Tag('a', array(
				'href' => '/domains/'
			), 'Domains', '<a href="/domains/">Domains</a>', 1072),
			new HTML_Tag('a', array(
				'href' => '/numbers/'
			), 'Numbers', '<a href="/numbers/">Numbers</a>', 1111),
			new HTML_Tag('a', array(
				'href' => '/protocols/'
			), 'Protocols', '<a href="/protocols/">Protocols</a>', 1150),
			new HTML_Tag('a', array(
				'href' => '/about/'
			), 'About IANA', '<a href="/about/">About IANA</a>', 1194),
			new HTML_Tag('a', array(
				'href' => '/go/rfc2606'
			), 'RFC 2606', '<a href="/go/rfc2606">RFC 2606</a>', 1384),
			new HTML_Tag('a', array(
				'href' => '/about/'
			), 'About', '<a href="/about/">About</a>', 1817),
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

		foreach ($result_tags as $result_tag) {
			$result_tag->offset += 22;
		}
		$contents = str_repeat('.', 22) . $contents;
		$tags = html::extract_tags($tag, $contents, $recursive);

		$this->assert_arrays_equal($tags, $result_tags);
	}

	function test_body_attributes() {
		$add = null;
		html::body_attributes("goo", "bar");
		html::body_attributes(array(
			"goo" => "actual"
		));
		html::body_attributes(array(
			"poo" => "bar"
		));
		html::body_attributes("poo", "actual");
		html::body_attributes(array(
			"dee" => "foofla"
		));
		html::body_attributes("dee", "actual");
		html::body_attributes("loo", "actual");

		$attrs = html::body_attributes();

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
		html::a($href, $mixed);
	}

	function test_a_condition() {
		$condition = null;
		$href = null;
		$mixed = null;
		html::a_condition($condition, $href, $mixed);
	}

	function test_a_match() {
		$href = null;
		$mixed = null;
		html::a_match($href, $mixed);
	}

	function test_a_path() {
		$href = null;
		$mixed = null;
		html::a_path($href, $mixed);
	}

	function test_a_prefix() {
		$href = null;
		$mixed = null;
		html::a_prefix($href, $mixed);
	}

	function test_attributes() {
		$attributes = null;
		html::attributes($attributes);
	}

	function test_body_begin() {
		html::body_begin();
	}

	function test_body_end() {
		html::body_end();
	}

	function test_cdn() {
		$uri = null;
		html::cdn($uri);
	}

	function test_cdn_css() {
		$path = null;
		$options = null;
		html::cdn_css($path, $options);
	}

	function test_cdn_img() {
		$src = null;
		$text = '';
		$attrs = false;
		html::cdn_img($src, $text, $attrs);
	}

	function test_cdn_shortcut_icon() {
		$path = null;
		html::cdn_shortcut_icon($path);
	}

	function test_clean_style_attributes() {
		$string = null;
		$include = true;
		$exclude = false;
		html::clean_style_attributes($string, $include, $exclude);
	}

	function test_clean_tags() {
		$string = null;
		$allowed_tags = true;
		$remove_tags = false;
		html::clean_tags($string, $allowed_tags, $remove_tags);
	}

	function test_clean_tags_attributes() {
		$string = null;
		$include = true;
		$exclude = false;
		html::clean_tags_attributes($string, $include, $exclude);
	}

	function test_clean_tags_without_attributes() {
		$tags = null;
		$html = null;
		html::clean_tags_without_attributes($tags, $html);
	}

	function test_count_end_tags() {
		$tag = null;
		$mixed = null;
		html::count_end_tags($tag, $mixed);
	}

	function test_count_until_tag() {
		$string = "All good <strong>things</strong> must come to an <em>end</em>.<h1>Hello!</h1>";
		//        01234567890123456789012345678901234567890123456789
		$offset = 17;
		$nWords = null;
		$tagName = "strong";
		$this->assert(html::count_until_tag($string, $tagName, $nWords) === $offset, "html::count_until_tag($string, $tagName, $nWords) " . html::count_until_tag($string, $tagName, $nWords) . " === $offset");
		$this->assert($nWords === 2);
		$this->assert($tagName === "strong", "$tagName === strong");
	}

	function test_css() {
		$path = null;
		$root_dir = null;
		$media = null;
		$browser = null;
		$type = null;
		html::css($path, $root_dir, $media, $browser, $type);
	}

	function test_div() {
		$mixed = null;
		$content = null;
		html::div($mixed, $content);
	}

	function test_ellipsis() {
		$s = null;
		$n = 20;
		$dot_dot_dot = '...';
		html::ellipsis($s, $n, $dot_dot_dot);
	}

	function test_etag() {
		$name = null;
		$mixed = null;
		html::etag($name, $mixed);
	}

	function test_extract_body() {
		$mixed = null;
		html::extract_body($mixed);
	}

	function test_extract_emails() {
		$content = null;
		html::extract_emails($content);
	}

	function test_extract_links() {
		$content = null;
		html::extract_links("Goo <a href=\"dude\">Dude</a>");
	}

	function test_extract_tag() {
		$tag = null;
		$mixed = null;
		html::extract_tag($tag, $mixed);
	}

	function test_extract_tag_contents() {
		$tag = null;
		$mixed = null;
		html::extract_tag_contents($tag, $mixed);
	}

	function test_extract_tag_object() {
		$tag = null;
		$mixed = null;
		html::extract_tag_object($tag, $mixed);
	}

	function test_head() {
		html::head();
	}

	function test_hidden() {
		$name = null;
		$value = null;
		html::hidden($name, $value);
	}

	function test_id_counter() {
		html::id_counter();
	}

	function test_img() {
		$src = null;
		$text = '';
		$attrs = false;
		html::img($src, $text, $attrs);
	}

	function test_img_compat() {
		$src = null;
		$w = false;
		$h = false;
		$text = '';
		$attrs = false;
		html::img_compat($src, $w, $h, $text, $attrs);
	}

	function test_inline_styles() {
		html::inline_styles();
	}

	function test_input() {
		$type = null;
		$name = null;
		$value = null;
		$attributes = null;
		html::input($type, $name, $value, $attributes);
	}

	function test_input_button() {
		$n = null;
		$v = null;
		$attrs = false;
		html::input_button($n, $v, $attrs);
	}

	function test_input_hidden() {
		$name = null;
		$value = null;
		$attributes = null;
		html::input_hidden($name, $value, $attributes);
	}

	function test_input_submit() {
		$n = null;
		$v = null;
		$attrs = false;
		html::input_submit($n, $v, $attrs);
	}

	function test_insert_inside_end() {
		$html = null;
		$insert_html = null;
		html::insert_inside_end($html, $insert_html);
	}

	function test_is_end_tag() {
		$string = null;
		html::is_end_tag($string);
	}

	function test_javascript() {
		$path = null;
		$root_dir = null;
		$options = null;
		html::javascript($path, $root_dir, $options);
	}

	function test_scripts() {
		$type = "text/javascript";
		$script = "alert('Hello, world!');";
		html::javascript_inline($script, array(
			'browser' => 'ie'
		));

		$scripts = html::scripts();

		$this->assert(strpos($scripts, $script) !== false);
		$this->assert(strpos($scripts, "<!--") !== false);
		$this->assert(strpos($scripts, "[if IE]") !== false);
		$this->assert(strpos($scripts, "<![endif]-->") !== false);

		$this->assert_equal($scripts, '<!--[if IE]><script type="text/javascript">alert(\'Hello, world!\');</script><![endif]-->');
	}

	function test_javascript_inline() {
		$script = null;
		$browser = null;
		$type = null;
		html::javascript_inline($script, $browser, $type);
	}

	function test_jquery() {
		$add_ready_script = null;
		html::jquery($add_ready_script);
	}

	function test_match_tags() {
		$string = null;
		html::match_tags($string);
	}

	function test_meta() {
		$name = null;
		$content = null;
		html::meta($name, $content);
	}

	function test_meta_description() {
		$content = null;
		html::meta_description($content);
	}

	function test_meta_http() {
		$name = null;
		$content = null;
		html::meta_http($name, $content);
	}

	function test_meta_keywords() {
		$content = null;
		html::meta_keywords($content);
	}

	function test_meta_nocache() {
		html::meta_nocache();
	}

	function test_metas() {
		html::metas();
	}

	function test_mixed_to_string() {
		$mixed = null;
		html::mixed_to_string($mixed);
	}

	function test_parse_styles() {
		$style_string = null;
		html::parse_styles($style_string);
	}

	function test_parse_tags() {
		$string = null;
		html::parse_tags($string);
	}

	function test_remove_tags() {
		$tag = null;
		$mixed = null;
		$delete = true;
		html::remove_tags($tag, $mixed, $delete);
	}

	function test_select() {
		$name = null;
		$value = null;
		$options = array(
			"opt" => "opt",
			"opt2" => "opt2"
		);
		$attributes = null;
		html::select($name, $value, $options, $attributes);
	}

	function test_shortcut_icon() {
		$path = null;
		$root_dir = null;
		html::shortcut_icon($path, $root_dir);
	}

	function test_specialchars() {
		$mixed = null;
		html::specialchars($mixed);

		$string = 'o	The second step in the checkout process where the customer reviews the information they entered before placing the order
		o	Action Page: “Preview Order” page...can’t see the URL
		o	Thank You page: It would be the “Order Confirmation” page…can’t see the URL';
	}

	function test_specials() {
		$string = null;
		html::specials($string);
	}

	function test_strip() {
		$x = null;
		html::strip($x);
	}

	function test_strlen() {
		$s = null;
		html::strlen($s);
	}

	function test_style_units() {
		$item = null;
		$default_unit = 'px';
		html::style_units($item, $default_unit);
	}

	function test_styles() {
		$styles = array(
			"list-type" => "none"
		);
		$delim = "\n";
		html::styles($styles, $delim);
	}

	function test_substr() {
		$s = null;
		$offset = 0;
		$length = null;
		html::substr($s, $offset, $length);
	}

	function test_tag() {
		$name = null;
		$mixed = null;
		html::tag($name, $mixed);
	}

	/**
	 * @expected_exception Exception_Semantics
	 */
	function test_tag_close() {
		$tagName = null;
		html::tag_close('p');
	}

	function test_tag_open() {
		$attributes = array(
			"id" => "dude"
		);
		html::tag_open("div", $attributes);
		html::tag_close("DIV");
	}

	function test_tags() {
		$name = null;
		$mixed = null;
		html::tags($name, $mixed);
	}

	function test_title() {
		$set = null;
		html::title($set);
	}

	function test_to_attributes() {
		$mixed = null;
		$default = null;
		html::to_attributes($mixed, $default);
	}

	function test_trim_white_space() {
		$html = null;
		html::trim_white_space($html);
	}

	function test_trim_words() {
		$string = null;
		$wordCount = null;
		html::trim_words($string, $wordCount);
	}

	function test_urlify() {
		$text = null;
		$attributes = array();
		html::urlify($text, $attributes);
	}

	function test_css_inline() {
		$data = "strong { color: blue; }";
		$type = "text/css";
		html::css_inline($data, $type);
	}
}
