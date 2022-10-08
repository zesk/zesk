<?php
declare(strict_types=1);

/**
 * @version $URL$
 * @author $Author$
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */

namespace zesk;

/**
 * Tag start character class
 *
 * @var string
 */
define('RE_TAG_NAME_START_CHAR', ":A-Za-z_\xC0-\xD6\xD8-\xF6");

/**
 * Tag character class (additional characters)
 *
 * @var string
 */
define('RE_TAG_NAME_CHAR', '-' . RE_TAG_NAME_START_CHAR . ".0-9\xB7");

/**
 * Tag name pattern without delimiters.
 * Start char, then rest of chars. Minimum one char.
 *
 * @var string
 */
define('RE_TAG_NAME', '[' . RE_TAG_NAME_START_CHAR . ']' . '[' . RE_TAG_NAME_CHAR . ']*');

/**
 * Abstraction of HTML markup language, with tools for generating and parsing HTML
 *
 * @author kent
 */
class HTML {
	/**
	 * Global HTML attributes allowed on any HTML tag
	 *
	 * @var array
	 */
	private static array $global_attributes = [
		'accesskey',
		'class',
		'contenteditable',
		'contextmenu',
		'data-*',
		'dir',
		'draggable',
		'dropzone',
		'hidden',
		'id',
		'lang',
		'spellcheck',
		'style',
		'tabindex',
		'title',
		'translate',
	];

	/**
	 * Allowed tag attributes via HTML::tag_attributes
	 *
	 * @var array[]
	 */
	private static array $tag_attributes = [
		'a' => ['href', 'hreflang', 'title', 'target', 'type', 'media', 'download', ],
		'input' => [
			'accept',
			'alt',
			'checked',
			'disabled',
			'ismap',
			'maxlength',
			'name',
			'onblur',
			'onchange',
			'onclick',
			'ondblclick',
			'onfocus',
			'onkeydown',
			'onkeypress',
			'onkeyup',
			'onmousedown',
			'onmousemove',
			'onmouseout',
			'onmouseover',
			'onmouseup',
			'onselect',
			'placeholder',
			'readonly',
			'size',
			'src',
			'type',
			'usemap',
			'value',
		],
		'select' => 'input',
		'li' => ['value', ],
		'link' => [
			'charset',
			'crossorigin',
			'href',
			'hreflang',
			'media',
			'rel',
			'sizes',
			'type',
		],
	];

	/**
	 *
	 * @see self::$tag_attributes
	 * @var array
	 */
	private static array $tag_attributes_cache = [];

	/**
	 * List of tags which will have a hook called to alter the attributes before output
	 *
	 * @var array
	 */
	private static array $attributes_alter = [];

	/**
	 * Loose definition of HTML attributes (for parsing bad code)
	 *
	 * @var string
	 */
	public const RE_ATTRIBUTES_LOOSE = '(?:[\\^"\'>]|"[^"]*"|\'[^\']*\')*'; // Loose definition

	/**
	 * Wicked loose definition, assumes no bad quoted > in tags
	 *
	 * @var string
	 */
	public const RE_ATTRIBUTES_REALLY_LOOSE = '[^>]*'; // Super Loose definition

	/**
	 * RE_ATTRIBUTES_LOOSE started breaking with PHP8 and not sure why
	 *
	 * @var string
	 */
	public const RE_ATTRIBUTES = self::RE_ATTRIBUTES_REALLY_LOOSE;

	/**
	 * Replacement character for start tags (for nesting)
	 *
	 * @var string
	 */
	private static string $RE_TAG_START_CHAR = "\xFE";

	/**
	 * Replacement character for end tags (for nesting)
	 *
	 * 2016-10-04: Test for extraction of a script tag containing anything with the letter "x" would
	 * fail when this was \xFF
	 * Bizarre. Changing to \xFD and it parsed correctly. WTF? -KMD
	 *
	 * @var string
	 */
	private static string $RE_TAG_END_CHAR = "\xFD";

	/**
	 * Tag name pattern without delimiters
	 *
	 * @var string
	 */
	public const RE_TAG_NAME = RE_TAG_NAME;

	/**
	 *
	 * @param string $src
	 * @param string $text
	 * @param array $attrs
	 * @param string $full_path
	 * @return string
	 */
	private static function _img(string $src, string $text, array $attrs = [], string $full_path = '') {
		$attrs['alt'] = $text;
		$attrs['title'] ??= $text;
		$attrs['src'] = $src;
		if (!array_key_exists('width', $attrs) || !array_key_exists('height', $attrs)) {
			if ($full_path && is_file($full_path)) {
				$img_size = getimagesize($full_path);
				if (is_array($img_size)) {
					if (!array_key_exists('width', $attrs)) {
						$attrs['width'] = $img_size[0];
					}
					if (!array_key_exists('height', $attrs)) {
						$attrs['height'] = $img_size[1];
					}
				}
			}
		}
		$attrs['border'] ??= 0;
		return self::tag('img', $attrs, null);
	}

	/**
	 *
	 * @param string $src
	 * @param string $w
	 * @param string $h
	 * @param string $text
	 * @param string $attrs
	 * @return string
	 */
	public static function img_compat(Application $app, $src, $w = null, $h = null, $text = '', $attrs = false) {
		$attrs = to_array($attrs, []);
		$attrs['width'] = $w ?? $attrs['width'] ?? null;
		$attrs['height'] = $h ?? $attrs['height'] ?? null;
		return self::img($app, $src, $text, $attrs);
	}

	/**
	 * Add document_root_prefix to href if needed
	 *
	 * Uses Application global
	 *
	 * @param string $src
	 * @return string
	 */
	public static function href(Application $application, $src) {
		if (URL::valid($src)) {
			return $src;
		}
		$prefix = $application->documentRootPrefix();
		if ($prefix) {
			return path($prefix, $src);
		}
		return $src;
	}

	/**
	 * Consider adding Application as a parameter, add logic for document_root
	 *
	 * @param string $src
	 * @param string $text
	 * @param array $attrs
	 * @return string
	 */
	public static function img(Application $app, string $src, string $text = '', array $attrs = []) {
		return self::_img(self::href($app, $src), $text, $attrs, path($app->document_root(), $src));
	}

	/**
	 * Output an `<a>` tag
	 *
	 * @param string $href
	 *            HREF to link to
	 * @param mixed $mixed
	 *            (optional) an array of attributes, or a class or ID description (".class1
	 *            .class2", or "#idoflink")
	 * @param string $text
	 *            the text for the link
	 * @return string
	 */
	public static function a($href, $mixed) {
		if (is_array($mixed) || func_num_args() > 2) {
			$attributes = self::toAttributes($mixed);
			$args = func_get_args();
			$text = $args[2] ?? null;
		} else {
			$attributes = [];
			$text = $mixed;
		}
		$attributes['href'] = $href;
		return self::tag('a', $attributes, $text);
	}

	/**
	 * Output a link which is a telephone number.
	 * Pass in 1, 2, or 3 parameters, like so:
	 *
	 * <code>
	 * echo HTML::atel("+1 800-555-1212"); // Prints <a href="tel:+18005551212">+1 800-555-1212</a>
	 * echo HTML::atel("+1 800-555-1212", "Call me"); // Prints <a href="tel:+18005551212">Call
	 * me</a>
	 * echo HTML::atel("+1 800-555-1212", array("class" => tel"), "Call me"); // Prints <a
	 * class="tel" href="tel:+18005551212">Call me</a>
	 * </code>
	 *
	 * @param string $phone
	 * @param array|string $mixed
	 * @param string $text
	 * @return string
	 */
	public static function atel($phone, $mixed = null) {
		if (is_array($mixed)) {
			$attributes = self::toAttributes($mixed);
			$args = func_get_args();
			$text = $args[2] ?? null;
		} else {
			$attributes = [];
			$text = $mixed ? $mixed : $phone;
		}
		$attributes['href'] = 'tel:' . preg_replace('/[^+0-9,]/', '', $phone);
		return self::tag('a', $attributes, $text);
	}

	/**
	 *
	 * @param boolean $condition
	 * @param string $href
	 * @param string|array $mixed
	 * @param string $extra
	 *            Optional extra parameter to pass in content
	 * @return string
	 */
	public static function a_condition($condition, $href, $mixed) {
		if (is_array($mixed)) {
			$attributes = $mixed;
			$args = func_get_args();
			$text = $args[3] ?? null;
		} else {
			$attributes = [];
			$text = $mixed;
		}
		if ($condition) {
			$attributes['class'] = Lists::append($attributes['class'] ?? '', 'selected', ' ');
		}
		return self::a($href, $attributes, $text);
	}

	/**
	 *
	 * @param string $href
	 * @param string $mixed
	 * @return string
	 */
	public static function a_prefix(Request $request, $href, $mixed) {
		$args = func_get_args();
		return self::a_condition(begins($request->uri(), $href), $href, $mixed, $args[3] ?? null);
	}

	/**
	 *
	 * @param Request $request
	 * @param string $href
	 * @param string $mixed
	 * @return string
	 */
	public static function a_path(Request $request, $href, $mixed) {
		$args = func_get_args();
		return self::a_condition($request->path() === $href, $href, $mixed, $args[3] ?? null);
	}

	/**
	 *
	 * @param string $href
	 * @param string $mixed
	 * @return string
	 */
	public static function a_match(Request $request, $href, $mixed) {
		$args = func_get_args();
		return self::a_condition($request->uri() === $href, $href, $mixed, $args[3] ?? null);
	}

	/**
	 * @param string|array $mixed
	 * @return array
	 * @throws Exception_Semantics
	 */
	public static function toAttributes(string|array $mixed): array {
		if (is_array($mixed)) {
			return $mixed;
		}

		$mixed = to_list($mixed, [], ' ');
		$result = [];
		foreach ($mixed as $term) {
			$char = substr($term, 0, 1);
			if ($char === '#') {
				if (array_key_exists('id', $result)) {
					throw new Exception_Semantics(__CLASS__ . '::toAttributes - multiple IDs specified: {id0} {id1}', [
						'id0' => $result['id'],
						'id1' => $term,
					]);
				}
				$result['id'] = substr($term, 1);
			} elseif ($char === '.') {
				$result['class'] = CSS::addClass($result['class'] ?? '', substr($term, 1));
			} else {
				$result['class'] = CSS::addClass($result['class'] ?? '', $term);
			}
		}
		return $result;
	}

	public static function div(string|array $mixed, $content) {
		$mixed = self::toAttributes($mixed);
		return self::tag('div', $mixed, $content);
	}

	public static function span(string|array $mixed, $content = null) {
		$mixed = self::toAttributes($mixed);
		return self::tag('span', $mixed, $content);
	}

	public static function etag(string $name, string|array|null $mixed): string {
		if (func_num_args() > 2) {
			$content = func_get_arg(2);
			if (empty($content)) {
				return '';
			}
			return self::tag($name, $mixed, $content);
		} elseif (empty($mixed)) {
			return '';
		}
		return self::tag($name, $mixed);
	}

	public static function clean_tag_name($tag) {
		return strtolower(preg_replace('#[^' . RE_TAG_NAME_CHAR . ']#', '', $tag));
	}

	/**
	 * For speed, you must register your tag hook here in addition to $application->hooks->add
	 * Use the name returned as the hook name
	 *
	 * @param string $name
	 */
	public static function tag_attributes_alter_hook_name($name) {
		$name = self::clean_tag_name($name);
		self::$attributes_alter[$name] = true;
		return __CLASS__ . "::tag::$name";
	}

	/**
	 * Output an open/close tag
	 *
	 * @param string $name
	 * @param mixed $mixed
	 *            Content or attributes
	 * @param string $content
	 *            Pass a third value as content makes 2nd parameter attributes
	 * @return string
	 */
	public static function tag(string $name, string|array $mixed): string {
		if (is_array($mixed)) {
			$attributes = $mixed;
			$args = func_get_args();
			$content = $args[2] ?? null;
		} elseif (func_num_args() > 2) {
			$attributes = self::toAttributes($mixed);
			$content = func_get_arg(2);
		} else {
			$attributes = [];
			$content = strval($mixed);
		}
		$name = self::clean_tag_name($name);
		if (is_array($content)) {
			backtrace();
		}
		if (array_key_exists($name, self::$attributes_alter)) {
			// TODO - avoid globals, but this is used EVERYWHERE without a context
			$result = Kernel::singleton()->hooks->call_arguments(__METHOD__ . "::$name", [
				$attributes,
				$content,
			], $attributes);
			if (is_array($result)) {
				$attributes = $result;
			}
		}
		return "<$name" . self::attributes($attributes) . ($content === null ? ' />' : ">$content</$name>");
	}

	/**
	 * self::tags('li', array('first item','second item', etc.)) or
	 * self::tags('li', array('class' => 'highlight'), array('first item', 'second item'))
	 *
	 * @param string $name
	 * @param mixed $mixed
	 * @return string
	 */
	public static function tags(string $name, string|array $mixed): string {
		if (func_num_args() > 2) {
			$attributes = self::toAttributes($mixed);
			$list = func_get_arg(2);
		} else {
			$attributes = [];
			$list = $mixed;
		}
		$list = to_list($list, []);
		if (count($list) === 0) {
			return '';
		}
		$result = [];
		foreach ($list as $item) {
			$result[] = self::tag($name, $attributes, $item);
		}
		return implode("\n", $result) . "\n";
	}

	/**
	 *
	 * @param array $types
	 */
	public static function inputAttributeNames(array $types = []): array {
		if (count($types) === 0) {
			$types = ['core', 'events', 'input'];
		} else {
			$types = ArrayTools::changeValueCase($types);
		}
		$attr_list = [];
		if (in_array('core', $types)) {
			$attr_list = array_merge($attr_list, ['id', 'class', 'style', 'title', 'placeholder']);
		}
		if (in_array('events', $types)) {
			$attr_list = array_merge($attr_list, [
				'onclick',
				'ondblclick',
				'onmousedown',
				'onmouseup',
				'onmouseover',
				'onmousemove',
				'onmouseout',
				'onkeypress',
				'onkeydown',
				'onkeyup',
			]);
		}
		if (in_array('input', $types)) {
			$attr_list = array_merge($attr_list, [
				'type',
				'name',
				'value',
				'checked',
				'disabled',
				'readonly',
				'size',
				'maxlength',
				'src',
				'alt',
				'usemap',
				'ismap',
				'tabindex',
				'accesskey',
				'onfocus',
				'onblur',
				'onselect',
				'onchange',
				'accept',
			]);
		}
		return $attr_list;
	}

	/**
	 * @param array|string $mixed
	 * @return array|string
	 */
	public static function specialchars(array|string $mixed): array|string {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$mixed[$k] = self::specialchars($v);
			}
			return $mixed;
		}
		return htmlspecialchars($mixed);
	}

	/**
	 * Preserve html entities e.g.
	 * foreign languages in strings,
	 * particularly when embedded in HTML attributes.
	 *
	 * @param iterable $iter
	 * @return iterable
	 */
	public static function specialsIterator(iterable $iter): iterable {
		$result = [];
		foreach ($iter as $k => $v) {
			$result[$k] = self::specials($v);
		}
		return $result;
	}

	/**
	 * Preserve html entities e.g.
	 * foreign languages in strings,
	 * particularly when embedded in HTML attributes.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function specials(string $string): string {
		$matches = null;
		if (!preg_match('/&(#[0-9]+|[A-Za-z0-9]+);/', $string, $matches)) {
			return htmlspecialchars($string);
		}
		$search = $replace = [];
		foreach ($matches as $i => $match) {
			$search[] = "{\x01$i\x02}";
			$replace[] = $match[0];
		}
		$string = str_replace($replace, $search, $string);
		$string = htmlspecialchars($string);
		$string = str_replace($search, $replace, $string);
		// Leave this here for debugging
		return $string;
	}

	public static function tag_class($class, $add = null, $remove = null): string {
		$class = to_list($class);
		$add = to_list($add);
		$remove = to_list($remove);
		return implode(' ', ArrayTools::include_exclude($class, $add, $remove));
	}

	/**
	 * Extract Data attributes, supporting data_id formats (converted to data-id)
	 *
	 * @param array $attributes
	 * @return array Data attributes
	 */
	public static function data_attributes(array $attributes) {
		return ArrayTools::flatten(ArrayTools::filterPrefixedValues(ArrayTools::keysReplace(array_change_key_case($attributes), '_', '-'), 'data-', true));
	}

	/**
	 * Includes standard HTML tags and data- tags.
	 *
	 * @param string $tag
	 * @param array $attributes
	 * @return array
	 */
	public static function tag_attributes($tag, array $attributes) {
		$tag = self::clean_tag_name($tag);
		if (!isset(self::$tag_attributes_cache[$tag])) {
			$allowed = self::$tag_attributes[$tag] ?? [];
			while (is_string($allowed)) {
				$allowed = self::$tag_attributes[$allowed] ?? [];
			}
			self::$tag_attributes_cache[$tag] = $allowed = array_merge($allowed, self::$global_attributes);
		} else {
			$allowed = self::$tag_attributes_cache[$tag];
		}
		return ArrayTools::filter($attributes, $allowed) + self::data_attributes($attributes);
	}

	/**
	 * Add a class to an array of attributes
	 *
	 * @param array $attributes
	 * @param mixed $class
	 * @return array
	 */
	public static function addClass(array $attributes, string $class = ''): array {
		$attributes['class'] = CSS::addClass($attributes['class'] ?? '', $class);
		return $attributes;
	}

	/**
	 * Remove a class from an attributes "class" field
	 *
	 * @param array $attributes
	 * @param string $class
	 * @return array
	 */
	public static function removeClass(array $attributes, string $class = ''): array {
		$attributes['class'] = CSS::removeClass($attributes['class'] ?? '', $class);
		return $attributes;
	}

	/**
	 * Convert attributes into a string easily appended after a tag
	 *
	 * @param array $attributes
	 * @return string
	 */
	public static function attributes(array $attributes): string {
		$result = [];
		foreach ($attributes as $name => $value) {
			if ($value === null || $value === false) {
				continue;
			}
			if ($value === true) {
				$value = $name;
			}
			if ($value instanceof Control) {
				continue;
			} elseif (is_object($value)) {
				$value = method_exists($value, '__toString') ? $value->__toString() : strval($value);
			}
			if (!is_numeric($name)) {
				$result[] = strtolower($name) . '="' . self::specials(strval($value)) . '"';
			}
		}
		if (count($result) === 0) {
			return '';
		}
		return ' ' . implode(' ', $result);
	}

	/**
	 * @return string[]
	 */
	private static function _html_tag_patterns(): array {
		/* After replaced, how to match a tag */
		$RE_TAG_START_CHAR_DEF = '<' . self::$RE_TAG_START_CHAR . '(' . self::RE_ATTRIBUTES . ')\s*>';

		/* match a single, properly closed tag in the source */
		$RE_HTML_TAG_SINGLE = '/<' . self::$RE_TAG_START_CHAR . '(' . self::RE_ATTRIBUTES . ')\s*\/\s*>/si';

		/* match a start tag in the source */
		$RE_HTML_TAG_START = '/' . $RE_TAG_START_CHAR_DEF . '/si';

		/* match a start/end tag in the source */
		$RE_HTML_TAG_DOUBLE = '/' . $RE_TAG_START_CHAR_DEF . '([^' . self::$RE_TAG_START_CHAR . self::$RE_TAG_END_CHAR . ']*)' . self::$RE_TAG_END_CHAR . '/si';

		return [$RE_HTML_TAG_SINGLE, $RE_HTML_TAG_DOUBLE, $RE_HTML_TAG_START, ];
	}

	/**
	 * Extract HTML_Tags from some content.
	 * Tags are returned in the order they are found in a document.
	 *
	 * @param
	 *            A string of the tag to extract, or $tag
	 * @param mixed $mixed
	 *            A string, HTML_Tag, object with toString function, or array
	 * @param boolean $recursive
	 *            Whether to recurse within the HTML tag to find tags within tags (e.g. div inside
	 *            of
	 *            another div)
	 * @return HTML_Tag[]
	 */
	public static function extract_tags($tag, $mixed, $recursive = true) {
		/* Handle a variety of inputs */
		$contents = self::mixed_to_string($mixed);
		if (is_array($contents)) {
			$results = [];
			foreach ($contents as $k => $v) {
				$temp = self::extract_tags($tag, $v, $recursive);
				if (is_array($temp)) {
					$results[$k] = $temp;
				}
			}
			return $results;
		}

		if (!is_string($contents)) {
			return $contents;
		}

		if (empty($contents)) {
			return [];
		}

		$tag = strtolower($tag);
		$endTag = "</$tag>";
		$endTagPattern = '#</\s*' . $tag . '\s*>#si';

		$results = [];
		$reverse_search = [
			self::$RE_TAG_START_CHAR,
			self::$RE_TAG_END_CHAR,
		];
		$reverse_replace = [
			$tag,
			$endTag,
		];

		$search = [];
		$replace = [];

		$search[] = '/<\s*' . $tag . '(\s+' . self::RE_ATTRIBUTES . ')>/si';
		$replace[] = '<' . self::$RE_TAG_START_CHAR . '$1>';

		$search[] = '/<\s*' . $tag . '\s*>/si';
		$replace[] = '<' . self::$RE_TAG_START_CHAR . '$1>';

		$search[] = $endTagPattern;
		$replace[] = self::$RE_TAG_END_CHAR;

		$contents = preg_replace($search, $replace, $contents);

		/*
		 * Match patterns in order of probably valid order. This handles, in order: <tag /> <tag> ... </tag> <tag> (no
		 * end tag)
		 */
		$patterns = self::_html_tag_patterns();
		foreach ($patterns as $pattern) {
			$matches = [];
			while (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
				$search = [];
				$replace = [];
				foreach ($matches as $match) {
					[$matched, $matched_offset] = array_shift($match);
					[$attrs] = array_shift($match);

					$options = self::parseAttributes($attrs);

					if (count($match) !== 0) {
						[$tag_contents] = array_shift($match);
					} else {
						$tag_contents = false;
					}

					$results[] = new HTML_Tag($tag, $options, $tag_contents, str_replace($reverse_search, $reverse_replace, $matched), $matched_offset);

					$token = '#@' . count($results) . '@#';

					$search[] = $matched;
					$replace[] = $token;

					$reverse_search[] = $token;
					$reverse_replace[] = $matched;
				}
				$contents = str_replace($search, $replace, $contents);

				if (!$recursive) {
					break;
				}
			}
		}
		/*
		 * Since we modify the contents as we parse, the undos all modifications in results
		 */
		foreach ($results as $result) {
			/* @var $result HTML_Tag */
			$inner_html = $result->innerHTML();
			if (is_string($inner_html)) {
				$outer_html = $result->outerHTML();
				while (preg_match('/#@[0-9]+@#|[' . self::$RE_TAG_START_CHAR . self::$RE_TAG_END_CHAR . ']/', $inner_html)) {
					$inner_html = str_replace($reverse_search, $reverse_replace, $inner_html);
					$outer_html = str_replace($reverse_search, $reverse_replace, $outer_html);
				}
				$result->setInnerHTML($inner_html);
				$result->setOuterHTML($outer_html);
			}
		}

		return $results;
	}

	private static $tag_stack = [];

	/**
	 * Open a tag
	 *
	 * @param
	 *            tag name $name
	 * @param string $attributes
	 * @return string
	 */
	public static function tag_open($name, string|array $attributes = []) {
		if ($name === '') {
			return '';
		}
		self::$tag_stack[] = $name;
		return '<' . strtolower($name) . self::attributes(self::toAttributes($attributes)) . '>';
	}

	/**
	 * Close a tag.
	 * Must balance tag_open calls or an error is thrown.
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception_Semantics
	 */
	public static function tag_close(string $name = null): string {
		if (count(self::$tag_stack) === 0) {
			throw new Exception_Semantics("Closing tag without open ($name)");
		}
		$top_name = array_pop(self::$tag_stack);
		if ($name !== null && strcasecmp($name, $top_name) !== 0) {
			throw new Exception_Semantics("Closing tag $name when it should be $top_name");
		}
		return '</' . $top_name . '>';
	}

	/**
	 * Common tag_open case
	 *
	 * @param string $attributes
	 * @return string
	 */
	public static function div_open(string|array $attributes = []): string {
		return self::tag_open('div', $attributes);
	}

	/**
	 * Common tag_open case
	 *
	 * @param string $attributes
	 * @return string
	 */
	public static function span_open(string|array $attributes = []): string {
		return self::tag_open('span', $attributes);
	}

	/**
	 * Like etag but for divs
	 *
	 * @return string
	 */
	public static function ediv(string|array $attributes = []): string {
		$args = array_merge(['div', ], func_get_args());
		return call_user_func_array([__CLASS__, 'etag', ], $args);
	}

	/**
	 * Like etag but for divs
	 *
	 * @return string
	 */
	public static function espan(string|array $attributes = []): string {
		$args = array_merge(['span', ], func_get_args());
		return call_user_func_array([__CLASS__, 'etag', ], $args);
	}

	/**
	 * Common tag_close case
	 *
	 * @return string
	 */
	public static function div_close(): string {
		return self::tag_close('div');
	}

	/**
	 * Common tag_close case
	 *
	 * @return string
	 */
	public static function span_close(): string {
		return self::tag_close('span');
	}

	/**
	 * Extract the first tag contents of given type from HTML
	 *
	 * @param string $tag
	 *            Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *            HTML string, HTML_Tag object, or an object which can be converted
	 * @return string|null Contents of the tag
	 */
	public static function extractTagContents(string $tag, string $mixed): ?string {
		$result = self::extract_tag_object($tag, $mixed);
		if ($result instanceof HTML_Tag) {
			return $result->innerHTML();
		}
		return null;
	}

	/**
	 * Extract the first tag object of given type from HTML
	 *
	 * @param string $tag Tag to extract (e.g. "title")
	 * @param mixed $mixed HTML string, HTML_Tag object, or an object which can be converted
	 * @return HTML_Tag|null Found tag, or null
	 */
	public static function extract_tag_object(string $tag, mixed $mixed) {
		$result = self::extract_tags($tag, $mixed, false);
		if (!is_array($result)) {
			return null;
		}
		if (count($result) === 0) {
			return null;
		}
		$htmlTag = array_shift($result);
		return $htmlTag;
	}

	/**
	 * Given a string like:
	 *
	 *     "bcd e f " goo="1423" e1231="agerd"
	 *
	 * Generates the following map
	 *
	 *     x = array();
	 *     x["a"] = "bcd e f"
	 *     x("goo"] = "1423"
	 *     x["e1232"] = "agerd"
	 *
	 * If `$mixed` is already an array, just returns as is
	 *
	 * @param array|string $mixed
	 * @return array
	 */
	public static function parseAttributes(array|string $mixed): array {
		if (is_array($mixed)) {
			return $mixed;
		}
		if ($mixed === '') {
			return [];
		}
		$mixed = trim($mixed);
		if (empty($mixed)) {
			return [];
		}
		$matches = [];
		$mixed .= ' ';
		$attr = [];
		if (preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(\'[^\']*\'|\"[^\"]*\"|[^\'\"]+)\s/', $mixed, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$search[] = $match[0];
				$replace[] = '';
				$attr[strtolower($match[1])] = unquote($match[2]);
			}
			$mixed = str_replace($search, $replace, $mixed);
		}
		$mixed = trim(preg_replace('/\s+/', ' ', $mixed));
		if (strlen($mixed) > 0) {
			$singles = explode(' ', $mixed);
			foreach ($singles as $single) {
				$attr[strtolower($single)] = true;
			}
		}
		return $attr;
	}

	/**
	 * @param string $html_content HTML to cut off
	 * @param int $cutoff_length Substring length to use
	 * @param string $dot_dot_dot Append suffix IFF string is cut off
	 * @return string Revised string or original string if no cutoff is needed
	 */
	public static function ellipsis(string $html_content, int $cutoff_length = 20, string $dot_dot_dot = ' ...'): string {
		if ($cutoff_length < 0) {
			return $html_content;
		}
		return self::strlen($html_content) > $cutoff_length ? self::substr($html_content, 0, $cutoff_length) . $dot_dot_dot : $html_content;
	}

	/**
	 * String length of HTML minus the HTML
	 *
	 * @param $html_content
	 * @return int
	 */
	public static function strlen($html_content) {
		return strlen(self::strip($html_content));
	}

	/**
	 * Retrieve a substring of HTML while keeping the HTML valid.
	 *
	 * @param string $html
	 *            String to parse
	 * @param int $offset
	 *            TODO this is broken, and is ignored and should be zero always
	 * @param ?int $length
	 *            How much content you want to include, in non-HTML characters.
	 * @return string
	 */
	public static function substr(string $html, int $offset = 0, int $length = null): string {
		$matches = false;
		if (!preg_match_all('/(<[A-Za-z0-9:_]+\s*[^>]*>|<\/[A-Za-z0-9:_]+>)/', $html, $matches, PREG_OFFSET_CAPTURE)) {
			$length = $length === null ? strlen($html) : $length;
			return substr($html, $offset, $length);
		}
		$stack = [];
		$text_offset = 0;
		$html_offset = 0;
		$result = '';
		foreach ($matches[0] as $match) {
			[$tag, $tag_offset] = $match;
			if ($tag_offset > $html_offset) {
				$add_chars = $tag_offset - $html_offset;
				if ($text_offset + $add_chars > $length) {
					$add_chars = $length - $text_offset;
					$result .= substr($html, $html_offset, $add_chars);
					// $html_offset += $add_chars;
					break;
				} else {
					$result .= substr($html, $html_offset, $add_chars);
					$html_offset += $add_chars;
					$text_offset += $add_chars;
				}
			}
			$html_offset += strlen($tag);
			$end_tag = self::is_end_tag($tag);
			if ($end_tag) {
				while (count($stack) > 0) {
					$stack_top = array_pop($stack);
					if ($stack_top === $end_tag) {
						$result .= $tag;
						$tag = false;

						break;
					} else {
						$result .= "<$end_tag><!-- Inserted missing start tag --></$end_tag>";
					}
				}
				if ($tag) {
					$result .= "<$end_tag><!-- Inserted missing start tag --></$end_tag>";
				}
			} else {
				$result .= $tag;
				$tags = self::parse_tags($tag);
				foreach (array_keys($tags) as $start_tag) {
					$stack[] = $start_tag;
				}
			}
		}
		while (count($stack) > 0) {
			$end_tag = array_pop($stack);
			$result .= "</$end_tag>";
		}
		return $result;
	}

	/**
	 * @param string $string
	 * @return array
	 */
	public static function match_tags(string $string): array|null {
		$matches = [];
		if (!preg_match_all('#<([A-Za-z][A-Za-z0-9]*)([^>]*)/?>#i', $string, $matches, PREG_SET_ORDER)) {
			return [];
		}
		return $matches;
	}

	public static function parse_tags(string $string): array {
		$matches = self::match_tags($string);
		$result = [];
		foreach ($matches as $match) {
			$result[$match[1]] = self::parseAttributes($match[2]);
		}
		return $result;
	}

	/**
	 * Remove any HTML tags from a string
	 *
	 * @param string $x
	 * @return string
	 */
	public static function strip(string $x): string {
		return preg_replace('/ +/', ' ', trim(preg_replace('/<[^>]+>/', ' ', $x)));
	}

	/**
	 * @param array $attr
	 * @param $allowed
	 * @param $disallowed
	 * @return array
	 */
	public static function style_clean(array $attr, array $allowed = null, array $disallowed = []) {
		return ArrayTools::filterKeys($attr, $allowed, $disallowed, true);
	}

	/**
	 * Remove any solo tags which do not have attributes in them; useless tags etc.
	 *
	 * @param array $tags
	 * @param string $html
	 * @return string
	 */
	public static function cleanTagsWithoutAttributes(array $tags, string $html) {
		$empty_tags = implode('|', ArrayTools::preg_quote($tags, '|'));
		$html = preg_replace('|<(' . $empty_tags . ')>([^<>]*)</\2>|i', '$2', $html);
		return $html;
	}

	/**
	 * Replace tag attributes in an HTML string
	 *
	 * @param string $string
	 * @param array|null $include
	 * @param array $exclude
	 * @return string
	 */
	public static function cleanTagsAttributes(string $string, array $include = null, array $exclude = []): string {
		$matches = self::match_tags($string);
		if (!$matches) {
			return $string;
		}

		$search = [];
		$replace = [];
		foreach ($matches as $match) {
			if ($include === null) {
				$attr = [];
			} else {
				$attr = self::parseAttributes($match[2]);
				$attr = ArrayTools::filterKeys($attr, $include, $exclude, false);
			}
			$ss = $match[0];
			$single = str_ends_with($match[0], '/>') ? '/' : '';
			$rr = '<' . strtolower($match[1]) . self::attributes($attr) . $single . '>';
			if ($ss !== $rr) {
				$search[] = $ss;
				$replace[] = $rr;
			}
		}
		if (preg_match_all('#<\\\s*([A-Za-z][A-Za-z0-9]*)\s*>#i', $string, $matches)) {
			foreach ($matches as $match) {
				$ss = $match[0];
				$rr = '<\\' . strtolower($match[1]) . '>';
				if ($ss !== $rr) {
					$search[] = $ss;
					$replace[] = $rr;
				}
			}
		}
		if (count($search) === 0) {
			return $string;
		}
		return str_replace($search, $replace, $string);
	}

	public static function clean_style_attributes(string $string, array $include = null, array $exclude = []): string {
		$matches = self::match_tags($string);
		if (!$matches) {
			return $string;
		}

		$search = [];
		$replace = [];
		foreach ($matches as $match) {
			$attr = self::parseAttributes($match[2]);
			if ($attr) {
				$styles = $attr['style'] ?? null;
				if ($styles) {
					$styles = self::parse_styles($styles);
					if ($styles) {
						$styles = ArrayTools::filterKeys($styles, $include, $exclude, true);
						if (count($styles) == 0) {
							unset($attr['style']);
						} else {
							$attr['style'] = self::styles($styles);
						}
						$ss = $match[0];
						$rr = '<' . strtolower($match[1]) . self::attributes($attr) . (str_ends_with($ss, '/>') ? '/' : '') . '>';
						if ($ss !== $rr) {
							$search[] = $ss;
							$replace[] = $rr;
						}
					}
				}
			}
		}
		if (count($search) === 0) {
			return $string;
		}
		return str_replace($search, $replace, $string);
	}

	/**
	 * Remove certain tags from an HTML string
	 *
	 * @param $string String containing HTML
	 * @param array|null $allowed_tags List of allowed tags or null to allow all tags
	 * @param array $remove_tags List of tags to explicitly remove
	 * @return string
	 */
	public static function cleanTags(string $string, array $allowed_tags = null, array $remove_tags = []): string {
		if (is_array($allowed_tags)) {
			$allowed_tags = ArrayTools::changeValueCase($allowed_tags);
		}
		$remove_tags = ArrayTools::changeValueCase($remove_tags);
		$found_tags = self::parse_tags($string);
		if (!$found_tags) {
			return $string;
		}
		$found_tags = array_unique(array_keys($found_tags));
		foreach ($found_tags as $k) {
			$k = strtolower($k);
			if (is_array($allowed_tags) && !in_array($k, $allowed_tags)) {
				$string = self::remove_tags($k, $string, false);
			} elseif (in_array($k, $remove_tags)) {
				$string = self::remove_tags($k, $string, false);
			}
		}
		return $string;
	}

	/**
	 * Is this an end tag?
	 *
	 * @param $string
	 * @return false|string
	 */
	public static function is_end_tag($string) {
		return self::isEndTag($string);
	}

	/**
	 * Is this an end tag?
	 *
	 * @param $string
	 * @return false|string
	 */
	public static function isEndTag($string) {
		$string = trim($string);
		$match = false;
		if (preg_match('/<\/\s*(' . RE_TAG_NAME . ')\s*>/', $string, $match)) {
			return $match[1];
		}
		return false;
	}

	public static function extract_links($content) {
		$matches = false;
		$result = preg_match_all('/(http:\/\/|https:\/\/|ftp:\/\/|mailto:)[^\s\'"\/]+(\/[^\s\'"><]*)+/i', $content, $matches, PREG_PATTERN_ORDER);
		if ($result) {
			return $matches[0];
		}
		return [];
	}

	/**
	 * @param $content
	 * @return array
	 * @deprecated 2022-02
	 */
	public static function extract_emails($content) {
		return self::extractEmails($content);
	}

	/**
	 * @param string $content
	 * @return array
	 */
	public static function extractEmails(string $content): array {
		$matches = false;
		$result = preg_match_all('/(' . PREG_PATTERN_EMAIL . ')/i', $content, $matches, PREG_PATTERN_ORDER);
		if ($result) {
			return $matches[0];
		}
		return [];
	}

	/**
	 * @param mixed $mixed
	 * @return string
	 */
	public static function mixedToString(mixed $mixed): string {
		if ($mixed === null) {
			return '';
		} elseif (is_string($mixed)) {
			return $mixed;
		} elseif ($mixed instanceof HTML_Tag) {
			return $mixed->innerHTML();
		} elseif (method_exists($mixed, '__toString')) {
			return $mixed->__toString();
		} elseif (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$mixed[$k] = self::mixed_to_string($v);
			}
			return implode('', $mixed);
		} else {
			return '';
		}
	}

	/**
	 * Count the number of end tags found in this string which match the tag supplied
	 *
	 * @param string $tag
	 * @param string $contents
	 * @return int
	 */
	public static function countEndTags(string $tag, string $contents): int {
		$matches = [];
		if (preg_match_all('|</\s*' . strtolower($tag) . '\s*>|im', $contents, $matches, PREG_SET_ORDER)) {
			return count($matches);
		}
		return 0;
	}

	/**
	 * @param $tag
	 * @param $mixed
	 * @param $delete
	 * @return mixed
	 */
	public static function remove_tags($tag, $mixed, $delete = true) {
		return self::removeTags($tag, $mixed, $delete);
	}

	/**
	 * @param string $tag
	 * @param string $content
	 * @param bool $delete
	 * @return string
	 */
	public static function removeTags(string $tag, string $contents, bool $delete = true): string {
		if (empty($contents)) {
			return '';
		}

		$tag = strtolower($tag);
		$endTagPattern = '/<\/\s*' . $tag . '\s*>/si';

		$search = [];
		$replace = [];

		$search[] = '/<\s*' . $tag . '(' . self::RE_ATTRIBUTES . ')\s*>/si';
		$replace[] = '<' . self::$RE_TAG_START_CHAR . '\1>';

		$search[] = $endTagPattern;
		$replace[] = self::$RE_TAG_END_CHAR;

		$contents = preg_replace($search, $replace, $contents);

		/*
		 * Match patterns in order of probably valid order. This handles, in order: <tag /> <tag> ... </tag> <tag> (no
		 * end tag)
		 */
		$patterns = self::_html_tag_patterns();
		foreach ($patterns as $pattern) {
			$matches = [];
			while (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
				$search = [];
				$replace = [];
				foreach ($matches as $match) {
					$search[] = $match[0];
					$replace[] = $delete ? '' : ($match[2] ?? '');
				}
				$contents = str_replace($search, $replace, $contents);
			}
		}
		return $contents;
	}

	public static function style_units($item, $default_unit = 'px') {
		$matches = [];
		if (preg_match('/([0-9.]+)(em|ex|pt|%|in|cm|mm|pc)?/', $item, $matches)) {
			$num = $matches[1];
			$units = $matches[2] ?? $default_unit;
			return $num . $units;
		}
		return null;
	}

	public static function parse_styles(string $style_string) {
		$style_string = trim($style_string);
		if (empty($style_string)) {
			return false;
		}
		$matches = [];
		if (!preg_match_all('/([-a-zA-Z]+)\s*:\s*([^;]*);/', "$style_string;", $matches, PREG_SET_ORDER)) {
			return false;
		}
		foreach ($matches as $match) {
			$styles[trim(strtolower($match[1]))] = trim($match[2]);
		}
		return $styles;
	}

	public static function styles($styles, $delim = ' ') {
		if ($styles === null) {
			return null;
		}
		$r = [];
		foreach ($styles as $k => $v) {
			$r[] = strtolower($k) . ': ' . $v . ';';
		}
		return implode($delim, $r);
	}

	/**
	 * Count the offset until the tag indicated
	 *
	 * @param string $string
	 * @param string $tagName
	 *            (Returned) The next found tag
	 * @param int $nWords
	 *            (Returned) The number of words found until the next tag
	 * @return integer The offset until the end of the tag
	 */
	public static function count_until_tag(string $string, string &$tagName, int &$nWords): int {
		$matches = [];
		if (!preg_match('/<(\/?' . self::RE_TAG_NAME . ')' . self::RE_ATTRIBUTES . '(\/?)>/', $string, $matches, PREG_OFFSET_CAPTURE)) {
			return -1;
		}

		//		dump($matches);
		[$tag, $offset] = array_shift($matches);
		[$tagName] = array_shift($matches);
		[$tagClose] = array_shift($matches);

		// 		dump($tag);
		// 		dump($offset);
		// 		dump($tagName);
		// 		dump($tagClose);

		$tagName .= $tagClose;

		$nWords = Text::count_words(substr($string, 0, $offset));

		return $offset + strlen($tag);
	}

	public static function trim_words($string, $wordCount) {
		$stack = [];
		$result = '';
		$tagName = null;
		while (($wordCount >= 0) && (strlen($string) > 0)) {
			$tagName = '';
			$nWords = 0;
			$offset = self::count_until_tag($string, $tagName, $nWords);
			if ($offset < 0) {
				// NB ===
				$result .= Text::trim_words($string, $wordCount);

				break;
			}
			if ($nWords >= $wordCount) {
				$result .= Text::trim_words($string, $wordCount);

				break;
			}
			$wordCount -= $nWords;
			$n = strlen($tagName);
			$tagName = strtolower($tagName);
			if ($tagName[$n - 1] === '/') {
				$isSingle = true;
				$tagName = substr($tagName, 1);
			} else {
				$isSingle = false;
			}
			if ($tagName[0] === '/') {
				$isClose = true;
				$tagName = substr($tagName, 0, -1);
			} else {
				$isClose = false;
			}
			if (!$isSingle) {
				if ($isClose) {
					if (count($stack) > 0) {
						$top = $stack[count($stack) - 1];
						if ($top == $tagName) {
							array_pop($stack);
						}
					}
				} else {
					// Is open tag
					$stack[] = $tagName;
				}
			}
			$result .= substr($string, 0, $offset);
			$string = substr($string, $offset);
		}
		$result .= '<!-- trimWords -->';
		while (count($stack) > 0) {
			$tag_name = array_pop($stack);
			$result .= "</$tag_name>";
		}
		return $result;
	}

	public static function trim_white_space($html) {
		$matches = false;
		$html_white_space = '(?:&nbsp;|\s)';
		$white_spaces = '(<p>' . $html_white_space . '*</p>|<br\s*/>|<p\s*/>)';
		// Beginning of String
		while (preg_match('`^' . $html_white_space . '*' . $white_spaces . '`', $html, $matches)) {
			$html = substr($html, strlen($matches[0]));
		}
		// Middle of string
		while (preg_match('`(' . $white_spaces . '){2}`', $html, $matches)) {
			$html = str_replace($matches[0], $matches[1], $html);
		}
		// Middle of string
		while (preg_match('`(<p>' . $html_white_space . '*</p>)`', $html, $matches)) {
			$html = str_replace($matches[0], '', $html);
		}
		// End of String
		while (preg_match('`' . $white_spaces . $html_white_space . '*$`', $html, $matches)) {
			$html = substr($html, 0, -strlen($matches[0]));
		}
		return $html;
	}

	/**
	 * @param string $html
	 * @param string $insert_html
	 * @return array|string|string[]|null
	 */
	public static function insertInsideEnd(string $html, string $insert_html) {
		$pattern = '~(</[A-Za-z][A-Za-z0-9-:]*>\s*)$~';
		if (preg_match($pattern, $html)) {
			return preg_replace($pattern, $insert_html . '$1', $html);
		}
		return $html . $insert_html;
	}

	/**
	 * @param string $name Input hidden name
	 * @param mixed $value
	 * @param array $attributes
	 * @return string HTML
	 */
	public static function hidden($name, $value = null, $attributes = []) {
		if (is_array($name)) {
			$result = [];
			foreach ($name as $k => $v) {
				$result[] = self::hidden($k, $v);
			}
			return implode("\n", $result);
		}
		return self::input_hidden($name, $value, $attributes);
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param array $options Selection item options
	 * @param array $attributes
	 * @return string HTML
	 */
	public static function select($name, $value, array $options, $attributes = null) {
		$attributes = array_change_key_case(to_array($attributes, []));
		$options_html = [];
		foreach ($options as $option_value => $label) {
			if (is_array($label)) {
				$option_attrs = $label;
				$label = $option_attrs['label'] ?? '';
				unset($option_attrs['label']);
			} else {
				$option_attrs = [];
			}
			$option_attrs['value'] = $option_value;
			if ("$value" === "$option_value") {
				$option_attrs['selected'] = 'selected';
			}
			$options_html[] = self::tag('option', $option_attrs, $label);
		}
		return self::tag('select', ['name' => $name, ] + $attributes + ['id' => $name, ], implode('', $options_html));
	}

	public static function input_submit($n, $v, $attrs = false) {
		$attrs['name'] = $n;
		$attrs['value'] = $v;
		$attrs['type'] = 'submit';
		$attrs['id'] ??= $n;
		return self::tag('input', $attrs, null);
	}

	public static function input_button($n, $v, $attrs = false) {
		$attrs['name'] = $n;
		$attrs['value'] = $v;
		$attrs['type'] = 'button';
		$attrs['id'] ??= $n;
		return self::tag('input', $attrs, null);
	}

	public static function input_hidden($name, $value, $attributes = null) {
		if (is_array($value)) {
			$result = '';
			$no_key = ArrayTools::isList($value);
			$attributes['id'] = null;
			foreach ($value as $k => $v) {
				$suffix = $no_key ? '[]' : '[' . $k . ']';
				$result .= self::input_hidden($name . $suffix, $v, $attributes);
			}
			return $result;
		}
		return self::input('hidden', $name, $value, $attributes);
	}

	public static function input($type, $name, $value, $attributes = null) {
		$attributes = is_array($attributes) ? $attributes : [];
		$type = strtolower($type);
		$attributes['name'] = $name;
		if ($type === 'textarea') {
			return self::tag($type, $attributes, htmlspecialchars($value));
		} else {
			$attributes['type'] = $type;
			$attributes['value'] = $value;
		}
		return self::tag('input', $attributes, null);
	}

	/**
	 * Convert plain text into HTML which has links
	 *
	 * @param string $text
	 * @param array $attributes
	 * @return string
	 */
	public static function urlify(string $text, array $attributes = []): string {
		$links = self::extract_links($text);
		$map = [];
		foreach ($links as $link) {
			$map[$link] = self::a($link, $attributes, $link);
		}
		$emails = self::extractEmails($text);
		foreach ($emails as $email) {
			$map[$email] = self::a('mailto:' . $email, $attributes, $email);
		}
		return strtr($text, $map);
	}

	/**
	 * Simplistic tool to make URLs absolute in HTML.
	 * Useful for formatting emails.
	 *
	 * @param string $content
	 * @param string $domain_prefix
	 * @return string
	 */
	public static function make_absolute_urls($content, $domain_prefix) {
		$domain_prefix = rtrim($domain_prefix, '/');
		$map = [];
		foreach (['href', 'src', ] as $attr) {
			foreach (['"', '\'', ] as $quote) {
				foreach (['/' => '', '.' => '/', ] as $prefix => $append) {
					$map[$attr . '=' . $quote . $prefix] = $attr . '=' . $quote . $domain_prefix . $append . $prefix;
				}
			}
		}
		return strtr($content, $map);
	}

	/**
	 * Handles conversion of HTML entities into text
	 *
	 * @param string $html
	 * @return string
	 */
	public static function entities_replace($html) {
		$html = strtr($html, ['&ldquo;' => '"', '&rdquo;' => '"', '&lsquo;' => '\'', '&rsquo;' => '\'', ]);
		return html_entity_decode($html);
	}

	/**
	 * Wrapping mapping function (_W)
	 *
	 * Mapping function which understands tags better. To apply styles or links certain elements within
	 * a i18n phrase, use brackets
	 * to delineate tags to add to the phrase, as follows:
	 *
	 * <pre>HTML::wrap(__('This is [0:bold text] and this is [1:italic].'), '<strong>[]</strong>',
	 * '<em>[italic]</em>') =
	 * "This is <strong>bold text</strong> and this is <em>italic</em>."</pre>
	 *
	 * Supplying <strong>no</strong> positional information will replace values in order, e.g.
	 *
	 * <pre>HTML::wrap(__('This is [bold text] and this is [italic].'), '<strong>[]</strong>',
	 * '<em>[italic]</em>') =
	 * "This is <strong>bold text</strong> and this is <em>italic</em>."</pre>
	 *
	 * Positional indicators are delimited with a number and a colon after the opening bracket. It also
	 * handles nested brackets, however,
	 * the inner brackets is indexed before the outer brackets, e.g.
	 *
	 * <pre>HTML::wrap('[[a][b]]','<strong>[]</strong>','<em>[]</em>','<div>[]</div>') =
	 * "<div><strong>a</strong><em>b</em></div>";
	 *
	 * @param string $phrase
	 *            Phrase to map
	 * @return string The phrase with the links embedded.
	 */
	public static function wrap(string $phrase): string {
		$args = func_get_args();
		array_shift($args);
		if (count($args) === 1 && is_array($args[0])) {
			$args = $args[0];
		}
		$skip_s = [];
		$skip_r = [];
		$match = false;
		$global_match_index = 0;
		while (preg_match('/\[([0-9]+:)?([^\[\]]*)]/', $phrase, $match, PREG_OFFSET_CAPTURE)) {
			$match_len = strlen($match[0][0]);
			$match_off = $match[0][1];
			$match_string = $match[2][0];
			$index = null;
			if ($match[1][1] < 0) {
				$index = $global_match_index;
			} else {
				$index = intval($match[1][0]);
			}
			$global_match_index++;
			$replace_value = $args[$index] ?? '[]';
			[$left, $right] = explode('[]', $replace_value, 2) + [null, '', ];
			if ($left === null) {
				$replace_value = '(*' . count($skip_s) . '*)';
				$skip_s[] = $replace_value;
				$skip_r[] = $match[0][0];
			} else {
				$replace_value = $left . $match_string . $right;
			}
			$phrase = substr($phrase, 0, $match_off) . $replace_value . substr($phrase, $match_off + $match_len);
		}

		if (count($skip_s) === 0) {
			return $phrase;
		}
		return str_replace($skip_s, $skip_r, $phrase);
	}

	/**
	 *
	 * @param string $tagName
	 * @return string
	 * @deprecated use tag_open
	 */
	public static function tag_end($tagName) {
		return self::tag_close($tagName);
	}

	/**
	 * Extract the first tag of given type from HTML
	 *
	 * @param string $tag
	 *            Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *            HTML string, HTML_Tag object, or an object which can be converted
	 * @return string Contents of the tag
	 * @deprecated Use self::extract_tag_contents
	 */
	public static function extract_tag($tag, $mixed) {
		return self::extract_tag_contents($tag, $mixed);
	}

	/**
	 * Extract the body, ignoring extra body tags
	 *
	 * @param string $tag
	 *            Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *            HTML string, HTML_Tag object, or an object which can be converted
	 * @return string|array Contents of the tag
	 * @deprecated Use self::extract_tag_contents
	 */
	public static function extract_body($mixed) {
		$mixed = self::mixed_to_string($mixed);
		if (is_array($mixed)) {
			$result = [];
			foreach ($mixed as $k => $x) {
				$result[$k] = self::extract_body($x);
			}
			return $result;
		} elseif (!is_string($mixed)) {
			return $mixed;
		}
		$begin_tag = stripos($mixed, '<body');
		$end_tag = strripos($mixed, '</body>');
		if ($begin_tag < 0) {
			$begin_tag = 0;
		} else {
			$begin_tag_len = strpos($mixed, '>', $begin_tag);
			$begin_tag = $begin_tag_len;
		}
		if ($end_tag < 0) {
			$end_tag = strlen($mixed);
		} else {
			$end_tag -= $begin_tag;
		}
		return substr($mixed, $begin_tag, $end_tag);
	}

	/**
	 * Given a string like:
	 *
	 *     "bcd e f " goo="1423" e1231="agerd"
	 *
	 * Generates the following map
	 *
	 *     x = array();
	 *     x["a"] = "bcd e f"
	 *     x("goo"] = "1423"
	 *     x["e1232"] = "agerd"
	 *
	 * If `$mixed` is already an array, just returns as is
	 *
	 * @param string|array $mixed
	 * @return array
	 * @deprecated 2022-02 PSR
	 */
	public static function parse_attributes(array|string $mixed): array {
		return self::parseAttributes($mixed);
	}

	/**
	 * @param string $string
	 * @param array|null $allowed_tags
	 * @param array $remove_tags
	 * @return string
	 * @deprecated 2022-02 PSR
	 */
	public static function clean_tags(string $string, array $allowed_tags = null, array $remove_tags = []): string {
		return self::cleanTags($string, $allowed_tags, $remove_tags);
	}

	/**
	 * @param $string
	 * @param $include
	 * @param $exclude
	 * @return string
	 * @deprecated 2022-02 PSR
	 */
	public static function clean_tags_attributes(string $string, array $include = null, array $exclude = []): string {
		return self::cleanTagsAttributes($string, $include, $exclude);
	}

	/**
	 * @param array $tags
	 * @param string $html
	 * @return string
	 * @deprecated 2022-02 PSR
	 */
	public static function clean_tags_without_attributes(array $tags, string $html): string {
		return self::cleanTagsWithoutAttributes($tags, $html);
	}

	/**
	 * @param string $tag
	 * @param string $contents
	 * @return int
	 * @deprecated 2022-02 PSR
	 */
	public static function count_end_tags(string $tag, string $contents): int {
		return self::countEndTags($tag, $contents);
	}

	/**
	 * @param mixed $mixed
	 * @return string
	 * @deprecated 2022-02 PSR
	 */
	public static function mixed_to_string(mixed $mixed): string {
		return self::mixedToString($mixed);
	}

	/**
	 * Extract the first tag contents of given type from HTML
	 *
	 * @param string $tag
	 *            Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *            HTML string, HTML_Tag object, or an object which can be converted
	 * @return string Contents of the tag
	 * @deprecated 2022-02 PSR
	 */
	public static function extract_tag_contents(string $tag, string $mixed) {
		return self::extractTagContents($tag, $mixed);
	}

	/**
	 * @param string $html
	 * @param string $insert_html
	 * @return array|string|string[]|null
	 * @deprecated 2022-02 PSR
	 */
	public static function insert_inside_end(string $html, string $insert_html) {
		return self::insertInsideEnd($html, $insert_html);
	}

	/**
	 * Convert string into an attributes for HTML, adding one or more classes and setting and ID
	 *
	 * Supports:
	 *
	 * #id_name
	 * .class_name
	 * class_name
	 *
	 * @param string $mixed
	 * @return array
	 * @throws Exception_Semantics
	 * @deprecated 2022-02 PSR
	 */
	public static function to_attributes(string|array $mixed): array {
		return self::toAttributes($mixed);
	}
}
