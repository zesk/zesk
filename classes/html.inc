<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/html.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

// /** Single HTML attribute */
// define('RE_HTML_ATTRIBUTE_SINGLE', '([A-Za-z][A-Za-z0-9]*)()');
//
// /** Unquoted HTML attribute */
// define('RE_HTML_ATTRIBUTE_UNQUOTED', RE_HTML_ATTRIBUTE_SINGLE . '=([^\'"\s/>]*)');
//
// /** Single-quoted HTML attribute */
// define('RE_HTML_ATTRIBUTE_QUOTED_SINGLE', RE_HTML_ATTRIBUTE_SINGLE . "='([^']*)'");
//
// /** Double-quoted HTML attribute */
// define('RE_HTML_ATTRIBUTE_QUOTED_DOUBLE', RE_HTML_ATTRIBUTE_SINGLE . '="([^"]*)"');
//
// /** HTML Attribute name/value pairs */
// define('RE_HTML_ATTRIBUTE', '(' . RE_HTML_ATTRIBUTE_SINGLE . '|' . RE_HTML_ATTRIBUTE_UNQUOTED . '|' .
// RE_HTML_ATTRIBUTE_QUOTED_SINGLE . '|' . RE_HTML_ATTRIBUTE_QUOTED_DOUBLE . ')(?=[\s>\\/])');


/*
 * http://www.w3.org/TR/xml/#NT-Name NameStartChar	 ::= 	":" | [A-Z] | "_" | [a-z] | [#xC0-#xD6] | [#xD8-#xF6] |
 * [#xF8-#x2FF] | [#x370-#x37D] | [#x37F-#x1FFF] | [#x200C-#x200D] | [#x2070-#x218F] | [#x2C00-#x2FEF] | [#x3001-#xD7FF]
 * | [#xF900-#xFDCF] | [#xFDF0-#xFFFD] | [#x10000-#xEFFFF] NameChar	 ::= 	NameStartChar | "-" | "." | [0-9] | #xB7 |
 * [#x0300-#x036F] | [#x203F-#x2040] Name	 	::= 	NameStartChar (NameChar)*
 */

/**
 * Tag start character class
 *
 * @var string
 */
define('RE_TAG_NAME_START_CHAR', ':A-Za-z_\xC0-\xD6\xD8-\xF6');

/**
 * Tag character class (additional characters)
 *
 * @var string
 */
define('RE_TAG_NAME_CHAR', '-' . RE_TAG_NAME_START_CHAR . '.0-9\xB7');

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
class html {
	
	/**
	 * List of tags which will have a hook called to alter the attributes before output
	 * 
	 * @var array
	 */
	private static $attributes_alter = array();
	
	/**
	 * Loose definition of HTML attributes (for parsing bad code)
	 *
	 * @var string
	 */
	const RE_ATTRIBUTES = '(?:[^"\'\\>]|"[^"]*"|\'[^\']*\')*'; // Loose definition
	

	/**
	 * Replacement character for start tags (for nesting)
	 *
	 * @var string
	 */
	const RE_TAG_START_CHAR = "\xFE";
	
	/**
	 * Replacement character for end tags (for nesting)
	 *
	 * @var string
	 */
	const RE_TAG_END_CHAR = "\xFF";
	
	/**
	 * Tag name pattern without delimiters
	 *
	 * @var string
	 */
	const RE_TAG_NAME = RE_TAG_NAME;
	
	/**
	 * Backwards compatibility with html::....
	 * which moved to Response
	 *
	 * @deprecated 2012-10
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic($method, array $arguments) {
		zesk::obsolete();
		$response = Response::instance(null, "text/html");
		if ($response instanceof Response_HTML) {
			return call_user_func_array(array(
				$response,
				$method
			), $arguments);
		}
	}
	
	/**
	 *
	 * @param unknown_type $uri
	 * @return Ambigous <string, mixed>
	 */
	static function cdn($uri) {
		zesk::deprecated();
		return cdn::path($uri);
	}
	private static function _img($src, $text, $attrs, $full_path) {
		$attrs["alt"] = $text;
		$attrs["title"] = avalue($attrs, 'title', $text);
		$attrs["src"] = $src;
		if (!array_key_exists('width', $attrs) || !array_key_exists('height', $attrs)) {
			$img_size = is_file($full_path) ? getimagesize($full_path) : null;
			if (is_array($img_size)) {
				if (!array_key_exists('width', $attrs)) {
					$attrs['width'] = $img_size[0];
				}
				if (!array_key_exists('height', $attrs)) {
					$attrs['height'] = $img_size[1];
				}
			}
		}
		$attrs["border"] = avalue($attrs, 'border', 0);
		$result = self::tag("img", $attrs, null);
		return $result;
	}
	public static function cdn_img($src, $text = "", $attrs = false) {
		return self::_img(cdn::url($src), $text, $attrs, cdn::path($src));
	}
	public static function img_compat($src, $w = false, $h = false, $text = "", $attrs = false) {
		$attrs = to_array($attrs, array());
		if ($w === false) {
			$attrs['width'] = avalue($attrs, 'width', null);
		}
		if ($h === false) {
			$attrs['height'] = avalue($attrs, 'height', null);
		}
		return self::img($src, $text, $attrs);
	}
	public static function img($src, $text = "", $attrs = false) {
		return self::_img(zesk::href($src), $text, $attrs, path(zesk::document_root(), $src));
	}
	
	/**
	 * Output an `<a>` tag
	 *
	 * @param string $href HREF to link to
	 * @param mixed $mixed (optional) an array of attributes, or a class or ID description (".class1 .class2", or "#idoflink")
	 * @param string $text the text for the link
	 * @return string
	 */
	public static function a($href, $mixed) {
		if (is_array($mixed) || func_num_args() > 2) {
			$attributes = self::to_attributes($mixed);
			$args = func_get_args();
			$text = avalue($args, 2, null);
		} else {
			$attributes = array();
			$text = $mixed;
		}
		$attributes['href'] = $href;
		return self::tag("a", $attributes, $text);
	}
	public static function a_condition($condition, $href, $mixed) {
		if (is_array($mixed)) {
			$attributes = $mixed;
			$args = func_get_args();
			$text = avalue($args, 3, null);
		} else {
			$attributes = array();
			$text = $mixed;
		}
		if ($condition) {
			$attributes['class'] = lists::append(avalue($attributes, 'class', ''), 'selected', ' ');
		}
		return self::a($href, $attributes, $text);
	}
	public static function a_prefix($href, $mixed) {
		$args = func_get_args();
		return self::a_condition(begins(url::current_uri(), $href), $href, $mixed, avalue($args, 2));
	}
	public static function a_path($href, $mixed) {
		$args = func_get_args();
		return self::a_condition(url::current_path() === $href, $href, $mixed, avalue($args, 2));
	}
	public static function a_match($href, $mixed) {
		$args = func_get_args();
		return self::a_condition(url::current_uri() === $href, $href, $mixed, avalue($args, 2));
	}
	static function to_attributes($mixed, $default = null) {
		if (is_array($mixed)) {
			return $mixed;
		}
		if (!is_string($mixed) || strlen($mixed) === 0) {
			return $default;
		}
		$mixed = to_list($mixed, array(), " ");
		$result = array();
		foreach ($mixed as $term) {
			$char = substr($term, 0, 1);
			if ($char === '#') {
				if (array_key_exists('id', $result)) {
					throw new Exception_Semantics(__CLASS__ . "::to_attributes - multiple IDs specified: {id0} {id1}", array(
						'id0' => $result['id'],
						'id1' => $term
					));
				}
				$result['id'] = substr($term, 1);
			} else if ($char === '.') {
				$result['class'] = css::add_class(avalue($result, 'class', ''), substr($term, 1));
			} else {
				$result['class'] = css::add_class(avalue($result, 'class', ''), $term);
			}
		}
		return $result;
	}
	static function div($mixed, $content) {
		$mixed = self::to_attributes($mixed);
		return self::tag('div', $mixed, $content);
	}
	static function span($mixed, $content = null) {
		$mixed = self::to_attributes($mixed);
		return self::tag('span', $mixed, $content);
	}
	static function etag($name, $mixed) {
		if (func_num_args() > 2) {
			$content = func_get_arg(2);
			if (empty($content)) {
				return "";
			}
			return self::tag($name, $mixed, $content);
		} else if (empty($mixed)) {
			return "";
		}
		return self::tag($name, $mixed);
	}
	static function clean_tag_name($tag) {
		return strtolower(preg_replace('#[^' . RE_TAG_NAME_CHAR . ']#', '', $tag));
	}
	
	/**
	 * For speed, you must register your tag hook here in addition to zesk()->hooks->add
	 * Use the name returned as the hook name
	 * 
	 * @param string $name
	 */
	static function tag_attributes_alter_hook_name($name) {
		$name = self::clean_tag_name($name);
		self::$attributes_alter[$name] = true;
		return __CLASS__ . "::tag::$name";
	}
	
	/**
	 * Output an open/close tag
	 * @param string $name
	 * @param mixed $mixed Content or attributes
	 * @param string $content Pass a third value as content makes 2nd parameter attributes
	 * @return string
	 */
	static function tag($name, $mixed) {
		if (is_array($mixed)) {
			$attributes = $mixed;
			$args = func_get_args();
			$content = avalue($args, 2, null);
		} else if (func_num_args() > 2) {
			$attributes = self::to_attributes($mixed);
			$content = func_get_arg(2);
		} else {
			$attributes = array();
			$content = $mixed;
		}
		$name = self::clean_tag_name($name);
		if (is_array($content)) {
			backtrace();
		}
		if (array_key_exists($name, self::$attributes_alter)) {
			global $zesk;
			$result = $zesk->hooks->call_arguments(__METHOD__ . "::$name", array(
				$attributes,
				$content
			), $attributes);
			if (is_array($result)) {
				$attributes = $result;
			}
		}
		return "<$name" . self::attributes($attributes) . ($content === null ? " />" : ">$content</$name>");
	}
	
	/**
	 * self::tags('li', array('first item','second item', etc.)) or
	 * self::tags('li', array('class' => 'highlight'), array('first item', 'second item'))
	 *
	 * @param string $name
	 * @param unknown_type $mixed
	 * @return unknown
	 */
	static function tags($name, $mixed) {
		if (func_num_args() > 2) {
			$attributes = self::to_attributes($mixed);
			$list = func_get_arg(2);
		} else {
			$attributes = array();
			$list = $mixed;
		}
		$list = to_list($list, array());
		if (count($list) === 0) {
			return "";
		}
		$result = array();
		foreach ($list as $item) {
			$result[] = self::tag($name, $attributes, $item);
		}
		return implode("\n", $result) . "\n";
	}
	public static function input_attribute_names($types = null) {
		if (empty($types)) {
			$types = "core;events;input";
		}
		$attr_list = "";
		$types = is_array($types) ? arr::change_value_case($types) : explode(";", strtolower($types));
		if (in_array("core", $types)) {
			$attr_list = lists::append($attr_list, "id;class;style;title;placeholder");
		}
		if (in_array("events", $types)) {
			$attr_list = lists::append($attr_list, "onclick;ondblclick;onmousedown;onmouseup;onmouseover;onmousemove;onmouseout;onkeypress;onkeydown;onkeyup");
		}
		if (in_array("input", $types)) {
			$attr_list = lists::append($attr_list, "type;name;value;checked;disabled;readonly;size;maxlength;src;alt;usemap;ismap;tabindex;accesskey;onfocus;onblur;onselect;onchange;accept");
		}
		return explode(";", $attr_list);
	}
	static function filter_tag_attributes($tag, array $attributes) {
		static $tag_types = array(
			'input' => null,
			'select' => null,
			'button' => null
		);
		$tag_filter = self::input_attribute_names(avalue($tag_types, $tag, 'core'));
		return arr::kfilter($attributes, $tag_filter) + arr::kprefix(array_merge(arr::kunprefix($attributes, "data_", true), arr::kunprefix($attributes, "data-", true)), "data-");
	}
	static function specialchars($mixed) {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$mixed[$k] = self::specialchars($v);
			}
			return $mixed;
		}
		return htmlspecialchars($mixed);
	}
	
	/**
	 * Preserve html entities e.g. foreign languages in strings,
	 * particularly when embedded in HTML attributes.
	 *
	 * @param string $string
	 * @return string
	 */
	static function specials($string) {
		if (is_array($string)) {
			foreach ($string as $k => $v) {
				$string[$k] = self::specials($v);
			}
			return $string;
		}
		$matches = null;
		if (!preg_match('/&(#[0-9]+|[A-Za-z0-9]+);/', $string, $matches)) {
			return htmlspecialchars($string);
		}
		$search = $replace = array();
		foreach ($matches as $i => $match) {
			$search[] = "{\x01$i\x02}";
			$replace[] = $match[0];
		}
		$string = str_replace($replace, $search, $string);
		$string = htmlspecialchars($string);
		$string = str_replace($search, $replace, $string);
		return $string;
	}
	static function tag_class($class, $add = null, $remove = null) {
		$class = to_list($class);
		$add = to_list($add);
		$remove = to_list($remove);
		return implode(" ", arr::include_exclude($class, $add, $remove));
	}
	/**
	 * Extract Data attributes, supporting data_id formats (converted to data-id)
	 *
	 * @param array $attributes
	 * @return array Data attributes
	 */
	static function data_attributes(array $attributes) {
		return arr::flatten(arr::filter_prefix(arr::kreplace(array_change_key_case($attributes), "_", "-"), "data-", true));
	}
	/**
	 * Add a class to an array of attributes
	 *
	 * @param array $attributes
	 * @param mixed $class
	 * @return array
	 */
	static function add_class(array $attributes, $class) {
		$attributes['class'] = css::add_class(avalue($attributes, 'class'), $class);
		return $attributes;
	}
	static function remove_class(array $attributes, $class) {
		$attributes['class'] = css::remove_class(avalue($attributes, 'class'), $class);
		return $attributes;
	}
	static function attributes($attributes) {
		if (!is_array($attributes)) {
			return "";
		}
		$result = array();
		foreach ($attributes as $name => $value) {
			if ($value === null || $value === false) {
				continue;
			}
			if ($value === true) {
				$value = $name;
			}
			if ($value instanceof Object) {
				$value = $value->__toString();
			} else if ($value instanceof Control) {
				continue;
			} else if (is_object($value)) {
				$value = strval($value);
			}
			$result[] = strtolower($name) . "=\"" . self::specials($value) . "\"";
		}
		if (count($result) === 0) {
			return "";
		}
		return " " . implode(" ", $result);
	}
	private static function _html_tag_patterns() {
		/* After replaced, how to match a tag */
		$RE_TAG_START_CHAR_DEF = '<' . self::RE_TAG_START_CHAR . '(' . self::RE_ATTRIBUTES . ')\s*>';
		
		/* match a single, properly closed tag in the source */
		$RE_HTML_TAG_SINGLE = '/<' . self::RE_TAG_START_CHAR . '(' . self::RE_ATTRIBUTES . ')\s*\/\s*>/si';
		
		/* match a start tag in the source */
		$RE_HTML_TAG_START = '/' . $RE_TAG_START_CHAR_DEF . '/si';
		
		/* match a start/end tag in the source */
		$RE_HTML_TAG_DOUBLE = '/' . $RE_TAG_START_CHAR_DEF . '([^' . self::RE_TAG_START_CHAR . self::RE_TAG_END_CHAR . ']*)' . self::RE_TAG_END_CHAR . '/si';
		
		return array(
			$RE_HTML_TAG_SINGLE,
			$RE_HTML_TAG_DOUBLE,
			$RE_HTML_TAG_START
		);
	}
	
	/**
	 * Extract HTML_Tags from some content.
	 * Tags are returned in the order they are found in a document.
	 *
	 * @param
	 *        	A string of the tag to extract, or $tag
	 * @param mixed $mixed
	 *        	A string, HTML_Tag, object with toString function, or array
	 * @param boolean $recursive
	 *        	Whether to recurse within the HTML tag to find tags within tags (e.g. div inside
	 *        	of
	 *        	another div)
	 * @return array of HTML_Tag or false if nothing found
	 */
	public static function extract_tags($tag, $mixed, $recursive = true) {
		/* Handle a variety of inputs */
		$contents = self::mixed_to_string($mixed);
		if (is_array($contents)) {
			$results = array();
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
			return array();
		}
		
		$tag = strtolower($tag);
		$endTag = "</$tag>";
		$endTagPattern = '/<\/\s*' . $tag . '\s*>/si';
		
		$results = array();
		$rsearch = array(
			self::RE_TAG_START_CHAR,
			self::RE_TAG_END_CHAR
		);
		$rreplace = array(
			$tag,
			$endTag
		);
		
		$search = array();
		$replace = array();
		
		$search[] = '/<\s*' . $tag . '(\s+' . self::RE_ATTRIBUTES . ')\s*>/si';
		$replace[] = "<" . self::RE_TAG_START_CHAR . '\1>';
		
		$search[] = '/<\s*' . $tag . '\s*>/si';
		$replace[] = "<" . self::RE_TAG_START_CHAR . '\1>';
		
		$search[] = $endTagPattern;
		$replace[] = self::RE_TAG_END_CHAR;
		
		$contents = preg_replace($search, $replace, $contents);
		
		/*
		 * Match patterns in order of probably valid order. This handles, in order: <tag /> <tag> ... </tag> <tag> (no
		 * end tag)
		 */
		$patterns = self::_html_tag_patterns();
		foreach ($patterns as $pattern) {
			$matches = false;
			while (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
				$search = array();
				$replace = array();
				foreach ($matches as $match) {
					list($matched, $matched_offset) = array_shift($match);
					list($attrs) = array_shift($match);
					
					$options = self::parse_attributes($attrs);
					
					if (count($match) !== 0) {
						list($tag_contents) = array_shift($match);
					} else {
						$tag_contents = false;
					}
					
					$results[] = new HTML_Tag($tag, $options, $tag_contents, str_replace($rsearch, $rreplace, $matched), $matched_offset);
					
					$token = "#@" . count($results) . "@#";
					
					$search[] = $matched;
					$replace[] = $token;
					
					$rsearch[] = $token;
					$rreplace[] = $matched;
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
			$inner_html = $result->inner_html();
			if (is_string($inner_html)) {
				$outer_html = $result->outer_html();
				while (preg_match("/#@[0-9]+@#|[" . self::RE_TAG_START_CHAR . self::RE_TAG_END_CHAR . "]/", $inner_html)) {
					$inner_html = str_replace($rsearch, $rreplace, $inner_html);
					$outer_html = str_replace($rsearch, $rreplace, $outer_html);
				}
				$result->inner_html($inner_html);
				$result->outer_html($outer_html);
			}
		}
		
		return $results;
	}
	private static $tag_stack = array();
	
	/**
	 * Open a tag
	 *
	 * @param tag name $name
	 * @param string $attributes
	 * @return string
	 */
	public static function tag_open($name, $attributes = false) {
		if ($name === "") {
			return "";
		}
		self::$tag_stack[] = $name;
		return '<' . strtolower($name) . self::attributes(self::to_attributes($attributes)) . '>';
	}
	
	/**
	 * Close a tag. Must balance tag_open calls or an error is thrown.
	 *
	 * @param string $name
	 * @throws Exception_Semantics
	 * @return string
	 */
	public static function tag_close($name = null) {
		if (count(self::$tag_stack) === 0) {
			throw new Exception_Semantics("Closing tag without open ($name)");
		}
		$top_name = array_pop(self::$tag_stack);
		if ($name === null) {
			$name = $top_name;
		} else if (strcasecmp($name, $top_name) !== 0) {
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
	public static function div_open($attributes = null) {
		return self::tag_open('div', $attributes);
	}
	
	/**
	 * Like etag but for divs
	 *
	 * @return string
	 */
	public static function ediv($mixed) {
		$args = array_merge(array(
			'div'
		), func_get_args());
		return call_user_func_array(array(
			__CLASS__,
			'etag'
		), $args);
	}
	
	/**
	 * Like etag but for divs
	 *
	 * @return string
	 */
	public static function espan($mixed) {
		$args = array_merge(array(
			'span'
		), func_get_args());
		return call_user_func_array(array(
			__CLASS__,
			'etag'
		), $args);
	}
	
	/**
	 * Common tag_close case
	 *
	 * @return string
	 */
	public static function div_close() {
		return self::tag_close('div');
	}
	/**
	 * Extract the first tag contents of given type from HTML
	 *
	 * @param string $tag
	 *        	Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *        	HTML string, HTML_Tag object, or an object which can be converted
	 * @return string Contents of the tag
	 */
	public static function extract_tag_contents($tag, $mixed) {
		$result = self::extract_tag_object($tag, $mixed);
		if ($result instanceof HTML_Tag) {
			return $result->inner_html();
		}
		return false;
	}
	
	/**
	 * Extract the first tag object of given type from HTML
	 *
	 * @param string $tag
	 *        	Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *        	HTML string, HTML_Tag object, or an object which can be converted
	 * @return HTML_Tag Found tag, or false
	 */
	public static function extract_tag_object($tag, $mixed) {
		$result = self::extract_tags($tag, $mixed, false);
		if (!is_array($result)) {
			return false;
		}
		if (count($result) === 0) {
			return false;
		}
		$htmlTag = array_shift($result);
		return $htmlTag;
	}
	
	/**
	 * Given a string like:
	 * a="bcd e f " goo="1423" e1231="agerd"
	 * Generates the following map
	 * <pre>
	 * x = array();
	 * x["a"] = "bcd e f"
	 * x("goo"] = "1423"
	 * x["e1232"] = "agerd"
	 * </pre>
	 * If a is already a map, { just returns the map as is
	 *
	 * @param unknown_type $mixed
	 * @return array
	 */
	public static function parse_attributes($mixed) {
		if (is_array($mixed)) {
			return $mixed;
		}
		if (!is_string($mixed)) {
			return array();
		}
		$mixed = trim($mixed);
		if (empty($mixed)) {
			return array();
		}
		$matches = false;
		$mixed .= " ";
		if (preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(\'[^\']*\'|\"[^\"]*\"|[^\'\"]+)\s/', $mixed, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$search[] = $match[0];
				$replace[] = "";
				$attr[strtolower($match[1])] = unquote($match[2]);
			}
			$mixed = str_replace($search, $replace, $mixed);
		}
		$mixed = trim(preg_replace('/\s+/', " ", $mixed));
		if (strlen($mixed) > 0) {
			$singles = explode(" ", $mixed);
			foreach ($singles as $single) {
				$attr[strtolower($single)] = true;
			}
		}
		return $attr;
	}
	
	//
	//
	// function self::parse_attributes($mixed)
	// {
	// if (is_array($mixed)) return $mixed;
	// if (!is_string($mixed)) return array();
	//
	// $pattern = '/ ([A-Za-z][A-Za-z0-9]*)=('.'"[^"]*"'.'|'."'[^']*'".')/';
	//
	// $matches = false;
	// $attrs = array();
	// if (!preg_match_all($pattern, " $mixed", $matches, PREG_SET_ORDER)) {
	// return $attrs;
	// }
	// foreach ($matches as $match) {
	// $attrs[$match[1]] = htmlspecialchars_decode(unquote($match[2]));
	// }
	// return $attrs;
	// }
	public static function ellipsis($s, $n = 20, $dot_dot_dot = "...") {
		if ($n < 0) {
			return $s;
		}
		return self::strlen($s) > $n ? self::substr($s, 0, $n) . " " . $dot_dot_dot : $s;
	}
	public static function strlen($s) {
		return strlen(self::strip($s));
	}
	
	/**
	 * Retrieve a substring of HTML while keeping the HTML valid.
	 *
	 * @param string $html String to parse
	 * @param number $offset TODO this is broken, and is ignored
	 * @param number $length How much content you want to include, in non-HTML characters.
	 * @return string|HTML_Tag
	 */
	public static function substr($html, $offset = 0, $length = null) {
		$matches = false;
		if (!preg_match_all('/(<[A-Za-z0-9:_]+\s*[^>]*>|<\/[A-Za-z0-9:_]+>)/', $html, $matches, PREG_OFFSET_CAPTURE)) {
			$length = $length === null ? strlen($html) : $length;
			return substr($html, $offset, $length);
		}
		$stack = array();
		$text_offset = 0;
		$html_offset = 0;
		$result = "";
		foreach ($matches[0] as $match) {
			$tag_offset = 0;
			list($tag, $tag_offset) = $match;
			if ($tag_offset > $html_offset) {
				$add_chars = $tag_offset - $html_offset;
				if ($text_offset + $add_chars > $length) {
					$add_chars = $length - $text_offset;
					$result .= substr($html, $html_offset, $add_chars);
					$html_offset += $add_chars;
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
				if (is_array($tags)) {
					foreach (array_keys($tags) as $start_tag) {
						$stack[] = $start_tag;
					}
				}
			}
		}
		while (count($stack) > 0) {
			$end_tag = array_pop($stack);
			$result .= "</$end_tag>";
		}
		return $result;
	}
	public static function match_tags($string) {
		$matches = false;
		if (!preg_match_all('#<([A-Za-z][A-Za-z0-9]*)([^>]*)\/?>#i', $string, $matches, PREG_SET_ORDER)) {
			return false;
		}
		return $matches;
	}
	public static function parse_tags($string) {
		$matches = self::match_tags($string);
		if (!$matches)
			return false;
		
		$result = array();
		foreach ($matches as $match) {
			$result[$match[1]] = self::parse_attributes($match[2]);
		}
		return $result;
	}
	
	/**
	 * Remove any HTML tags from a string
	 *
	 * @param string $x
	 * @return string
	 */
	public static function strip($x) {
		if (is_array($x)) {
			foreach ($x as $i => $v) {
				$x[$i] = is_string($v) ? html::strip($v) : $v;
			}
			return $x;
		}
		return preg_replace('/ +/', ' ', trim(preg_replace("/<[^>]+>/", " ", $x)));
	}
	public static function style_clean(array $attr, $allowed = null, $disallowed = null) {
		return arr::kfilter($attr, $allowed, $disallowed, true);
	}
	public static function clean_tags_without_attributes($tags, $html) {
		$empty_tags = explode(";", $tags);
		$empty_tags = implode("|", arr::preg_quote($empty_tags));
		$html = preg_replace('|<(' . $empty_tags . ')>([^<>]*)</\2>|i', '$2', $html);
		return $html;
	}
	public static function clean_tags_attributes($string, $include = true, $exclude = false) {
		$matches = self::match_tags($string);
		if (!$matches)
			return $string;
		
		$include = to_list($include, $include);
		$exclude = to_list($exclude, $exclude);
		
		$search = array();
		$replace = array();
		foreach ($matches as $match) {
			if ($include === false) {
				$attr = array();
			} else {
				$attr = self::parse_attributes($match[2]);
				$attr = arr::include_exclude($attr, $include, $exclude, false);
			}
			$ss = $match[0];
			$single = ends($match[0], "/>") ? "/" : "";
			$rr = "<" . strtolower($match[1]) . self::attributes($attr) . $single . ">";
			if ($ss !== $rr) {
				$search[] = $ss;
				$replace[] = $rr;
			}
		}
		if (preg_match_all('#<\\\s*([A-Za-z][A-Za-z0-9]*)\s*>#i', $string, $matches)) {
			foreach ($matches as $match) {
				$ss = $match[0];
				$rr = '<\\' . strtolower($match[1]) . '>';
			}
			if ($ss !== $rr) {
				$search[] = $ss;
				$replace[] = $rr;
			}
		}
		if (count($search) === 0)
			return $string;
		return str_replace($search, $replace, $string);
	}
	public static function clean_style_attributes($string, $include = true, $exclude = false) {
		$matches = self::match_tags($string);
		if (!$matches)
			return $string;
		
		$include = to_list($include, $include);
		$exclude = to_list($exclude, $exclude);
		
		$search = array();
		$replace = array();
		foreach ($matches as $match) {
			$attr = self::parse_attributes($match[2]);
			if ($attr) {
				$styles = avalue($attr, 'style');
				if ($styles) {
					$styles = self::parse_styles($styles);
					if ($styles) {
						$styles = arr::include_exclude($styles, $include, $exclude, true);
						if (count($styles) == 0) {
							unset($attr['style']);
						} else {
							$attr['style'] = self::styles($styles);
						}
						$ss = $match[0];
						$single = ends($match[0], "/>") ? "/" : "";
						$rr = "<" . strtolower($match[1]) . self::attributes($attr) . $single . ">";
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
	public static function clean_tags($string, $allowed_tags = true, $remove_tags = false) {
		$allowed_tags = to_list($allowed_tags, true);
		$remove_tags = to_list($remove_tags, false);
		if (is_array($allowed_tags)) {
			$allowed_tags = arr::change_value_case($allowed_tags);
		}
		if (is_array($remove_tags)) {
			$remove_tags = arr::change_value_case($remove_tags);
		}
		$found_tags = self::parse_tags($string);
		if (!$found_tags) {
			return $string;
		}
		$found_tags = array_unique(array_keys($found_tags));
		foreach ($found_tags as $k) {
			$k = strtolower($k);
			if (is_array($allowed_tags) && !in_array($k, $allowed_tags)) {
				$string = self::remove_tags($k, $string, false);
			} else if (is_array($remove_tags) && in_array($k, $remove_tags)) {
				$string = self::remove_tags($k, $string, false);
			}
		}
		return $string;
	}
	public static function is_end_tag($string) {
		$string = trim($string);
		$match = false;
		if (preg_match('/<\/(' . RE_TAG_NAME . ')\s*>/', $string, $match)) {
			return $match[1];
		}
		return false;
	}
	
	/**
	 * Extract the first tag of given type from HTML
	 *
	 * @param string $tag
	 *        	Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *        	HTML string, HTML_Tag object, or an object which can be converted
	 * @deprecated Use self::extract_tag_contents
	 * @return string Contents of the tag
	 */
	public static function extract_tag($tag, $mixed) {
		return self::extract_tag_contents($tag, $mixed);
	}
	
	/**
	 * Extract the body, ignoring extra body tags
	 *
	 * @param string $tag
	 *        	Tag to extract (e.g. "title")
	 * @param mixed $mixed
	 *        	HTML string, HTML_Tag object, or an object which can be converted
	 * @deprecated Use self::extract_tag_contents
	 * @return string Contents of the tag
	 */
	public static function extract_body($mixed) {
		$mixed = self::mixed_to_string($mixed);
		if (is_array($mixed)) {
			$result = array();
			foreach ($mixed as $k => $x) {
				$result[$k] = self::extract_body($x);
			}
			return $result;
		} else if (!is_string($mixed)) {
			return $mixed;
		}
		$begin_tag = stripos($mixed, "<body");
		$end_tag = strripos($mixed, "</body>");
		if ($begin_tag < 0) {
			$begin_tag = 0;
		} else {
			$begin_tag_len = strpos($mixed, ">", $begin_tag);
			$begin_tag = $begin_tag_len;
		}
		if ($end_tag < 0) {
			$end_tag = strlen($mixed);
		} else {
			$end_tag -= $begin_tag;
		}
		return substr($mixed, $begin_tag, $end_tag);
	}
	public static function extract_links($content) {
		$matches = false;
		$result = preg_match_all('/(http:\/\/|https:\/\/|ftp:\/\/|mailto:)[^\s\'"\/]+(\/[^\s\'"><]*)+/i', $content, $matches, PREG_PATTERN_ORDER);
		if ($result) {
			return $matches[0];
		}
		return array();
	}
	public static function extract_emails($content) {
		$matches = false;
		$result = preg_match_all('/(' . PREG_PATTERN_EMAIL . ')/i', $content, $matches, PREG_PATTERN_ORDER);
		if ($result) {
			return $matches[0];
		}
		return array();
	}
	public static function mixed_to_string($mixed) {
		if (is_string($mixed)) {
			return $mixed;
		} else if ($mixed instanceof HTML_Tag) {
			return $mixed->inner_html();
		} else if (method_exists($mixed, "__toString")) {
			return $mixed->__toString();
		} else if (is_array($mixed)) {
			return $mixed;
		} else {
			return null;
		}
	}
	public static function count_end_tags($tag, $mixed) {
		$contents = self::mixed_to_string($mixed);
		$matches = false;
		if (preg_match_all('|</\s*' . strtolower($tag) . '\s*>|im', $contents, $matches, PREG_SET_ORDER)) {
			return count($matches);
		}
		return 0;
	}
	public static function remove_tags($tag, $mixed, $delete = true) {
		/* Handle a variety of inputs */
		if (is_string($mixed)) {
			$contents = $mixed;
		} else if ($mixed instanceof HTML_Tag) {
			$contents = $mixed->inner_html();
		} else if (method_exists($mixed, "toString")) {
			$contents = $mixed->toString();
		} else if (is_array($mixed)) {
			$results = array();
			foreach ($mixed as $k => $v) {
				$temp = self::remove_tags($tag, $v, $delete);
				if (is_string($temp)) {
					$results[$k] = $results;
				}
			}
			return $results;
		} else {
			return false;
		}
		
		if (empty($contents)) {
			return false;
		}
		
		if (is_array($tag)) {
			foreach ($tag as $t) {
				$contents = self::remove_tags($t, $contents, $delete);
			}
			return $contents;
		}
		
		$tag = strtolower($tag);
		// $endTag = self::tag_close($tag);
		$endTagPattern = '/<\/\s*' . $tag . '\s*>/si';
		
		// $rsearch = array(self::RE_TAG_START_CHAR,self::RE_TAG_END_CHAR);
		// $rreplace = array($tag, $endTag);
		

		$search = array();
		$replace = array();
		
		$search[] = '/<\s*' . $tag . '(' . self::RE_ATTRIBUTES . ')\s*>/si';
		$replace[] = "<" . self::RE_TAG_START_CHAR . '\1>';
		
		$search[] = $endTagPattern;
		$replace[] = self::RE_TAG_END_CHAR;
		
		$contents = preg_replace($search, $replace, $contents);
		
		/*
		 * Match patterns in order of probably valid order. This handles, in order: <tag /> <tag> ... </tag> <tag> (no
		 * end tag)
		 */
		$patterns = self::_html_tag_patterns();
		foreach ($patterns as $pattern) {
			$matches = false;
			while (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
				$search = array();
				$replace = array();
				foreach ($matches as $match) {
					$search[] = $match[0];
					$replace[] = $delete ? "" : avalue($match, 2, "");
				}
				$contents = str_replace($search, $replace, $contents);
			}
		}
		return $contents;
	}
	public static function style_units($item, $default_unit = "px") {
		$matches = null;
		if (preg_match('/([0-9.]+)(em|ex|pt|%|in|cm|mm|pc)?/', $item, $matches)) {
			$num = $matches[1];
			$units = aevalue($matches, 2, $default_unit);
			return $num . $units;
		}
		return null;
	}
	public static function parse_styles($style_string) {
		$style_string = trim($style_string);
		if (empty($style_string)) {
			return false;
		}
		$matches = false;
		if (!preg_match_all('/([-a-zA-Z]+)\s*:\s*([^;]*);/', "$style_string;", $matches, PREG_SET_ORDER)) {
			return false;
		}
		foreach ($matches as $match) {
			$styles[trim(strtolower($match[1]))] = trim($match[2]);
		}
		return $styles;
	}
	public static function styles($styles, $delim = " ") {
		if ($styles === null) {
			return null;
		}
		$r = array();
		foreach ($styles as $k => $v) {
			$r[] = strtolower($k) . ": " . $v . ";";
		}
		return implode($delim, $r);
	}
	
	/**
	 * Count the number of words until the next tag
	 *
	 * @param string $string
	 * @param string $tagName
	 *        	(Returned) The next found tag
	 * @param integer $nWords
	 *        	(Returned) The number of words found until the next tag
	 * @return integer The offset until the end of the tag
	 */
	public static function count_until_tag($string, &$tagName, &$nWords) {
		$matches = false;
		if (!preg_match('/<(\/?' . self::RE_TAG_NAME . ")" . self::RE_ATTRIBUTES . '(\/?)>/', $string, $matches, PREG_OFFSET_CAPTURE)) {
			return false;
		}
		
		//		dump($matches);
		list($tag, $offset) = array_shift($matches);
		list($tagName) = array_shift($matches);
		list($tagClose) = array_shift($matches);
		
		// 		dump($tag);
		// 		dump($offset);
		// 		dump($tagName);
		// 		dump($tagClose);
		

		$tagName .= $tagClose;
		
		$nWords = text::count_words(substr($string, 0, $offset));
		
		return $offset + strlen($tag);
	}
	public static function trim_words($string, $wordCount) {
		$stack = array();
		$result = "";
		$tagName = null;
		while (($wordCount >= 0) && (strlen($string) > 0)) {
			$tagName = $nWords = null;
			$offset = self::count_until_tag($string, $tagName, $nWords);
			if ($offset === false) {
				// NB ===
				$result .= text::trim_words($string, $wordCount);
				break;
			}
			if ($nWords >= $wordCount) {
				$result .= text::trim_words($string, $wordCount);
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
		$result .= "<!-- trimWords -->";
		while (count($stack) > 0) {
			$result .= self::tag_close(array_pop($stack));
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
			$html = str_replace($matches[0], "", $html);
		}
		// End of String
		while (preg_match('`' . $white_spaces . $html_white_space . '*$`', $html, $matches)) {
			$html = substr($html, 0, -strlen($matches[0]));
		}
		return $html;
	}
	public static function insert_inside_end($html, $insert_html) {
		$pattern = '~(</[A-Za-z][A-Za-z0-9-:]*>\s*)$~';
		if (preg_match($pattern, $html)) {
			return preg_replace($pattern, $insert_html . '$1', $html);
		}
		return $html . $insert_html;
	}
	private static function browser_conditionals($browser) {
		$prefix = "";
		$suffix = "";
		switch (strtolower($browser)) {
			case "ie":
				$prefix = "<!--[if IE]>";
				$suffix = "<![endif]-->";
				break;
			case "ie6":
				$prefix = "<!--[if lte IE 6]>";
				$suffix = "<![endif]-->";
				break;
			case "ie7":
				$prefix = "<!--[if IE 7]>";
				$suffix = "<![endif]-->";
				break;
		}
		return array(
			$prefix,
			$suffix
		);
	}
	public static function hidden($name, $value = null, $attributes = array()) {
		if (is_array($name)) {
			$result = array();
			foreach ($name as $k => $v) {
				$result[] = self::hidden($k, $v);
			}
			return implode("\n", $result);
		}
		return self::input_hidden($name, $value, $attributes);
	}
	public static function select($name, $value, array $options, $attributes = null) {
		$attributes = array_change_key_case(to_array($attributes, array()));
		$options_html = array();
		foreach ($options as $option_value => $label) {
			if (is_array($label)) {
				$option_attrs = $label;
				$label = avalue($option_attrs, 'label', "");
				unset($option_attrs['label']);
			} else {
				$option_attrs = array();
			}
			$option_attrs['value'] = $option_value;
			if ("$value" === "$option_value") {
				$option_attrs['selected'] = 'selected';
			}
			$options_html[] = self::tag('option', $option_attrs, $label);
		}
		return self::tag('select', array(
			'name' => $name
		) + $attributes + array(
			'id' => $name
		), implode("", $options_html));
	}
	public static function input_submit($n, $v, $attrs = false) {
		$attrs['name'] = $n;
		$attrs['value'] = $v;
		$attrs['type'] = 'submit';
		$attrs['id'] = avalue($attrs, 'id', $n);
		return self::tag("input", $attrs, null);
	}
	public static function input_button($n, $v, $attrs = false) {
		$attrs['name'] = $n;
		$attrs['value'] = $v;
		$attrs['type'] = 'button';
		$attrs['id'] = avalue($attrs, 'id', $n);
		return self::tag("input", $attrs, null);
	}
	public static function input_hidden($name, $value, $attributes = null) {
		if (is_array($value)) {
			$result = "";
			$no_key = arr::is_list($value);
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
		$attributes = is_array($attributes) ? $attributes : array();
		$type = strtolower($type);
		$attributes['name'] = $name;
		if (!array_key_exists('id', $attributes)) {
			$attributes['id'] = $name;
		}
		if ($type === "textarea") {
			return self::tag($type, $attributes, htmlspecialchars($value));
		} else {
			$attributes['type'] = $type;
			$attributes['value'] = $value;
		}
		return self::tag('input', $attributes, null);
	}
	public static function urlify($text, $attributes = array()) {
		$links = self::extract_links($text);
		$map = array();
		foreach ($links as $link) {
			$map[$link] = self::a($link, $attributes, $link);
		}
		$emails = self::extract_emails($text);
		foreach ($emails as $email) {
			$map[$email] = self::a('mailto:' . $email, $attributes, $email);
		}
		return strtr($text, $map);
	}
	
	/**
	 * Simplistic tool to make URLs absolute in HTML. Useful for formatting emails.
	 *
	 * @param string $content
	 * @param string $domain_prefix
	 * @return string
	 */
	public static function make_absolute_urls($content, $domain_prefix) {
		$domain_prefix = rtrim($domain_prefix, '/');
		$map = array();
		foreach (array(
			'href',
			'src'
		) as $attr) {
			foreach (array(
				'"',
				"'"
			) as $quote) {
				foreach (array(
					"/" => '',
					"." => '/'
				) as $prefix => $append) {
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
		$html = strtr($html, array(
			"&ldquo;" => '"',
			"&rdquo;" => '"',
			"&lsquo;" => "'",
			"&rsquo;" => "'"
		));
		return html_entity_decode($html);
	}
	
	/**
	 *
	 * @deprecated use tag_open
	 * @param string $tagName
	 * @return string
	 */
	public static function tag_end($tagName) {
		return self::tag_close($tagName);
	}
}

