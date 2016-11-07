<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/zesk_legacy/html-legacy.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 * ZESK_TEST_SKIP
 * @todo Remove this
 */
use zesk\HTML;
use zesk\URL;
use zesk\JavaScript;

function html_attributes($oAttr) {
	zesk()->deprecated();
	$attr = HTML::parse_attributes($oAttr);
	return HTML::attributes($attr);
}

function html_tag($tagName, $oAttr = false, $single = false) {
	zesk()->deprecated();
	if ($tagName === "") {
		return "";
	}
	if (is_string($oAttr)) {
		$oAttr = HTML::parse_attributes($oAttr);
	}
	return "<$tagName" . HTML::attributes($oAttr) . ($single ? " />" : ">");
}

function html_tag_single($tagName, $oAttr = null) {
	zesk()->deprecated();
	return html_tag($tagName, $oAttr, true);
}

function html_input($type, $n, $v, $id = null) {
	zesk()->deprecated();
	if ($id === null)
		$id = $n;
	return html_tag("input", array(
		"type" => $type,
		"name" => $n,
		"value" => $v,
		"id" => $id
	), true);
}

function input_submit($n, $v, $attrs = false) {
	zesk()->deprecated();
	return HTML::input_submit($n, $v, $attrs);
}

function input_button($n, $v, $attrs = false) {
	zesk()->deprecated();
	return HTML::input_button($n, $v, $attrs);
}

function form_preserve_request($include = false, $exclude = false) {
	zesk()->obsolete();
}

function form_clean_variable($variable) {
	zesk()->deprecated();
	return trim(preg_replace("/_+/", "_", preg_replace("/[^A-Za-z0-9_]/", "_", $variable)), "_");
}

function html_clean_anchor($text) {
	zesk()->deprecated();
	return strtolower(trim(preg_replace("/-+/", "-", preg_replace("/[^A-Za-z0-9_]/", "-", $text)), "-"));
}

function input_text($n, $v, $nChars = 20) {
	zesk()->deprecated();
	return html_tag("input", "type=\"text\" size=\"$nChars\" name=\"$n\" id=\"$n\" value=\"" . htmlspecialchars($v) . "\"", true);
}

function table_tr($tr_attrs, $td_attrs, $row, $header_row = false) {
	zesk()->deprecated();
	$tr = array();
	$tr[] = html_tag("tr", $tr_attrs);
	$colspan = 1;
	$row_tag = $header_row ? "th" : "td";
	if (is_array($td_attrs)) {
		foreach ($td_attrs as $i => $attrs) {
			if ($colspan-- > 1)
				continue;
			$colspan = intval(avalue($attrs, 'colspan', 1));
			$tr[] = html_tag($row_tag, $attrs) . avalue($row, $i, '&nbsp;') . HTML::tag_close($row_tag);
		}
	}
	$tr[] = HTML::tag_close("tr");

	return implode("\n", $tr);
}

function href($hr, $encode = true, $pass_ref = false) {
	zesk()->deprecated();
	$hr = zesk::href($hr);
	if ($pass_ref) {
		$ref = is_string($pass_ref) ? URL::query_remove($pass_ref, "message;message_key") : URL::current_uri(true);
		if (!empty($ref)) {
			$hr = URL::query_format($hr, array(
				"ref" => $ref
			));
		}
	}
	if ($encode) {
		return htmlspecialchars($hr);
	}
	return $hr;
}

function toggle_edit($button, $id, $contents) {
	zesk()->deprecated();
	HTML::jquery();
	return tag("div", "id='" . $id . "_button'", html_tag_single("input", HTML::parse_attributes("value='" . $button . "' type='button' onclick=\"toggle_edit('" . $id . "')\""))) . tag("div", "style='display: none' id='" . $id . "_edit'", $contents);
}

function a_cond($hr, $text, $cond, $pass_ref = false) {
	zesk()->deprecated();
	return aa_cond($hr, "", $text, $cond, $pass_ref);
}

function aa_cond($hr, $attr, $text, $cond, $pass_ref = false) {
	zesk()->deprecated();
	if ($cond) {
		return "<span class=\"a\">" . $text . "</span>";
	}
	$attr = HTML::parse_attributes($attr);
	$attr["href"] = href($hr, false, $pass_ref);
	return HTML::tag_open("a", $attr) . $text . HTML::tag_close("a");
}

function a_end($hr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa_end($hr, "", $text, $pass_ref);
}

function aa_end($hr, $attr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa_cond($hr, $attr, $text, str::ends(URL::current_uri(), $hr), $pass_ref);
}

function a_begin($hr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa_begin($hr, "", $text, $pass_ref);
}

function aa_begin($hr, $attr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa_cond($hr, $attr, $text, str::begins(URL::current_uri(), $hr), $pass_ref);
}

function a($hr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa($hr, "", $text, $pass_ref);
}

function aa($hr, $attr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa_cond($hr, $attr, $text, URL::current_uri() === $hr, $pass_ref);
}

function a_match($hr, $text) {
	zesk()->deprecated();
	return aa_match($hr, "", $text);
}

function aa_match($hr, $attr, $text, $pass_ref = false) {
	zesk()->deprecated();
	$here = URL::current_uri();
	return aa_cond($hr, $attr, $text, strpos($here, $hr) !== false, $pass_ref);
}

function a_script($hr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa_script($hr, "", $text, $pass_ref);
}

function aa_script($hr, $attr, $text, $pass_ref = false) {
	zesk()->deprecated();
	$here = basename($_SERVER["SCRIPT_NAME"]);
	return aa_cond($hr, $attr, $text, $here == basename($hr), $pass_ref);
}

function a_uri($hr, $text, $pass_ref = false) {
	zesk()->deprecated();
	return aa_uri($hr, "", $text, $pass_ref);
}

function aa_uri($hr, $attr, $text, $pass_ref = false) {
	zesk()->deprecated();
	$here = avalue($_SERVER, "REQUEST_URI");
	return aa_cond($hr, $attr, $text, $here == $hr, $pass_ref);
}

function img($src, $w = false, $h = false, $text = "", $attrs = false) {
	zesk()->deprecated();
	$attrs["alt"] = $text;
	$attrs["title"] = avalue($attrs, 'title', $text);
	$attrs["src"] = href($src, false);

	if ($w === true || $h === true) {
		$img_size = getimagesize(path(zesk::web_root(), $src));
		if (is_array($img_size)) {
			if ($w === true) {
				$w = $img_size[0];
			}
			if ($h === true) {
				$h = $img_size[1];
			}
		}
	}
	if ($w) {
		$attrs["width"] = $w;
	}
	if ($h) {
		$attrs["height"] = $h;
	}
	$attrs["border"] = 0;
	$result = html_tag("img", $attrs, true);
	return $result;
}

function tag($name, $attrs, $content) {
	zesk()->deprecated();
	return html_tag($name, HTML::parse_attributes($attrs)) . $content . HTML::tag_close($name);
}

function div($mixed, $content = null) {
	zesk()->deprecated();
	if (is_string($mixed)) {
		if ($mixed[0] === '#') {
			$mixed = array(
				'id' => substr($mixed, 1)
			);
		} else if ($mixed[0] === '.') {
			$mixed = array(
				'class' => substr($mixed, 1)
			);
		} else {
			$mixed = array(
				'class' => $mixed
			);
		}
	}
	return tag('div', $mixed, $content, $content === null);
}

function etag($name, $attrs, $content) {
	zesk()->deprecated();
	if (empty($content)) {
		return "";
	}
	return tag($name, $attrs, $content);
}

function extract_string($x, $s, $e) {
	zesk()->deprecated();
	$result = "";
	$soff = strpos($x, $s);
	if ($soff === false) {
		return $result;
	}
	$eoff = strpos($x, $e, $soff + strlen(s));
	if ($eoff === false) {
		return $result;
	}
	$soff += strlen(s);
	return trim(substr($x, $soff, $eoff));
}

function clean_href($x) {
	zesk()->deprecated();
	return str_replace("&", "&amp;", str_replace("&amp;", "&", $x));
}

function email_encrypt($e, $hr = false, $noscript = true) {
	zesk()->deprecated();
	return mail::encrypt($e, $hr, $noscript);
}

function _auto_html_extras($x) {
	zesk()->deprecated();
	if (substr($x, -1) == ",") {
		return array(
			substr($x, 0, -1),
			substr($x, -1)
		);
	}
	return array(
		$x,
		""
	);
}

function html_encrypt_emails($html) {
	zesk()->deprecated();
	$matches = false;
	$pattern = '/([^A-Za-z_0-9])([A-Za-z_0-9]+@[-A-Za-z.]+\.[A-Za-z]+)([^A-Za-z])/';
	if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$html = str_replace($match[2], email_encrypt($match[2], $match[2]), $html);
		}
	}
	return $html;
}

function auto_html($x) {
	zesk()->deprecated();
	$matches = false;
	$patterns = array(
		'/\s([A-Za-z_0-9]+@[-A-Za-z.]+\.[A-Za-z]+)/',
		'/([A-Za-z_0-9]+@[-A-Za-z.]+\.[A-Za-z]+)\s/'
	);
	foreach ($patterns as $pattern) {
		if (preg_match_all($pattern, $x, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$x = str_replace($match[1], email_encrypt($match[1], $match[1]), $x);
			}
		}
	}
	$pattern = '/"([^"]+)"\s*\(\s*(http:[^)]+)\s*\)/';
	if (preg_match_all($pattern, $x, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$x = str_replace($match[0], "<a target=\"_blank\" href=\"" . clean_href($match[2]) . "\">" . $match[1] . "</a>", $x);
		}
	}

	$pattern = '/"([^"]+)":(([^ <>]|\\\\ )+)/';
	if (preg_match_all($pattern, $x, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			list($href, $extra) = _auto_html_extras($match[2]);
			$x = str_replace($match[0], "<a target=\"_blank\" href=\"" . clean_href($href) . "\">" . $match[1] . "</a>" . $extra, $x);
		}
	}

	$pattern = '/[^"](https?:[^!=<>\s]+[^!="<>])/';
	if (preg_match_all($pattern, $x, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$href = $match[1];
			$x = str_replace($href, "<a target=\"_blank\" href=\"" . clean_href($href) . "\">" . $href . "</a>", $x);
		}
	}
	$pattern = '/^(https?:[^!=<>\s]+[^!="<>])/';
	if (preg_match_all($pattern, $x, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$href = $match[1];
			$x = str_replace($href, "<a target=\"_blank\" href=\"" . clean_href($href) . "\">" . $href . "</a>", $x);
		}
	}
	return $x;
}

function format_text($s) {
	zesk()->deprecated();
	return auto_html(nl2br($s));
}

function format_key() {
	global $key_id;

	zesk()->deprecated();
	if (!isset($key_id))
		$key_id = 0;
	$key_id++;

	$t = <<<EOF
<table class="format-key" id="format_key_$key_id">
	<tr><th colspan="2">Formatting Key</th></tr>
	<tr>
		<td class="label">#Bold#</td><td><strong>Bold</strong></td>
	</tr>
	<tr>
		<td class="label">##</td><td># (Pound Symbol)</td>
	</tr>
	<tr>
		<td class="label">~Italic~</td><td><em>Italic</em></td>
	</tr>
	<tr>
		<td class="label">- Item 1<br />- Item 2<br />- Item 3</td><td><ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul></td>
	</tr>
	<tr>
		<td class="label">All work and no play makes Jack a dull boy.&para; <br />Paragraph&para;<br />&para;<br />All work and no play makes Jack a dull boy.&para;</td><td>All work and no play makes Jack a dull boy.<br />Paragraph<br /><br />All work and no play makes Jack a dull boy.</td>
	</tr>
	<tr>
		<td class="label">http://www.example.com/</td><td><a href="http://www.example.com/">http://www.example.com/</a></td>
	</tr>
	<tr>
		<td class="label">"Named&nbsp;Link"&nbsp;(http://www.example.com/)</td><td><a href="http://www.example.com/">Named&nbsp;Link</a></td>
	</tr>
	<tr>
		<td class="label">automatic_email@example.com</td><td><a href="mailto:automatic_email@example.com">automatic_email@example.com</a></td>
	</tr>
</table>
EOF;
	return "<br />" . toggle_edit("Formatting Key", "format_key_$key_id", $t);
}

function format_para($r, $useBR = false) {
	zesk()->deprecated();
	if (empty($r))
		return "";

	$r = str_replace("##", "%POUND%", $r);
	$pattern = '/~([^~]+)~/';
	$r = preg_replace($pattern, '<em>$1</em>', $r);

	$pattern = '/#([^#]+)#/';
	$r = preg_replace($pattern, '<strong>$1</strong>', $r);
	$r = str_replace("#", "", $r);
	$r = str_replace("~", "", $r);
	$lines = explode("\n", $r);
	$r = array();
	$cur_list = array();
	foreach ($lines as $L) {
		if (substr($L, 0, 1) == "-") {
			$cur_list[] = trim(substr($L, 1));
		} else {
			if (count($cur_list) !== 0) {
				$r[] = "<ul><li>" . implode("</li><li>", $cur_list) . "</li></ul>";
				$cur_list = array();
			}
			$r[] = $L;
		}
	}
	if (count($cur_list) !== 0) {
		$r[] = "<ul><li>" . implode("</li><li>", $cur_list) . "</li></ul>";
	}
	$r = implode("\n", $r);
	if ($useBR) {
		$r = "<p>" . str_replace("\n", "</p>\n" . "<p>", $r) . "</p>";
		$r = preg_replace('/<p>\s*<\/p>/', "", $r);
	} else {
		$r = str_replace("\n", "<br />", $r);
	}
	$r = auto_html($r);
	$r = str_replace("%POUND%", "#", $r);

	return $r;
}

function format_list($s) {
	zesk()->deprecated();
	$s = "<ul><li>" . str_replace("\n", "</li>\n" . "<li>", $s) . "</li></ul>";
	$s = str_replace("<li></li>", "", $s);
	return auto_html($s);
}

function actionlink($x, $href, $src, $w, $h, $title, $confirm = false) {
	zesk()->deprecated();
	$attr = $confirm ? "onclick=\"return confirm('Are you sure?')\"" : "";
	return aa(map($href, $x), $attr, img($src, $w, $h, map($title, $x)), false);
}

/*===================================================================================*\
*	Text-related
\*-----------------------------------------------------------------------------------*/
function errors_show($x, $error_class = "error", $prefix = false) {
	zesk()->deprecated();
	if (!is_array($x)) {
		return "";
	}

	$error_prefix = is_string($prefix) ? $prefix : __("Please fix the following:");
	if (avalue($x, "continue", false)) {
		unset($x["continue"]);
		$error_class = "continue";
		$error_prefix = "";
	}
	$e = implode("</li>\n" . "<li>", $x);

	if (empty($e))
		return "";
	$r = "";
	if ($error_prefix != "") {
		$r = $r . "<p class=\"" . $error_class . "\">" . $error_prefix . "</p>";
	}
	return $r . "<ul class=\"" . $error_class . "\">\n" . "<li>" . $e . "</li>\n" . "</ul>\n";
}

function error_show($errors, $k) {
	zesk()->deprecated();
	if (!array_key_exists($k, $errors)) {
		return "";
	}
	return "<div class=\"error\">" . $errors[$k] . "</div>";
}

function buttonbar(Options $opts) {
	zesk()->deprecated();
	if ($opts->option_bool("hide_button_bar")) {
		return "";
	}

	$ajax_id = $opts->option('ajax_id');
	$cancel_button = $opts->option("CancelButton", __("Cancel"));

	$v = $opts->option("OKButton", __("Save"));
	$r = "";
	$r .= html_tag("div", array(
		'class' => "buttonbar"
	));
	$is_jquery = zesk::getb('ajax-jquery');

	if ($ajax_id) {
		$uri = URL::current_path();
		if ($is_jquery) {
			$submit_onclick = "ajax_jquery_form_submit('$ajax_id', " . JavaScript::null($opts->option('ajax_form_submit')) . ")";
			$cancel_onclick = "ajax_jquery_form_cancel('$ajax_id', " . JavaScript::null($opts->option('ajax_form_cancel')) . ")";
		} else {
			$submit_onclick = "ajax_form_submit(this, '$ajax_id', '$uri')";
			$cancel_onclick = "ajax_form_cancel('$ajax_id')";
		}
		$input_attrs = array(
			"type" => "button",
			"onclick" => $submit_onclick
		);
	} else {
		$submit_onclick = $opts->option('ok_onclick', null);
		$cancel_onclick = $opts->option('cancel_onclick', null);
		$input_attrs = array(
			"type" => ($submit_onclick ? "button" : "submit"),
			'onclick' => $submit_onclick
		);
	}
	$input_attrs['name'] = __("OK");
	$input_attrs['value'] = $v;

	if (!empty($v)) {
		$r .= div('ok-button', html_tag_single("input", $input_attrs));
	}

	if ($cancel_button) {
		if ($ajax_id || $cancel_onclick) {
			$input_attrs = array(
				"type" => "button",
				"onclick" => $cancel_onclick
			);
		} else {
			$input_attrs = array(
				"type" => "submit"
			);
		}
		$input_attrs['name'] = __("Cancel");
		$input_attrs['class'] = "cancel";
		$input_attrs['value'] = $cancel_button;

		$r .= div('cancel-button', html_tag_single("input", $input_attrs));
	}

	$r .= HTML::tag_close("div");
	return $r;
}

/*===================================================================================*\
*	Select (popup)
\*-----------------------------------------------------------------------------------*/
function ajax_counter() {
	zesk::obsolete();
}

function is_embedded($min_level = 1) {
	zesk::obsolete();
}

function arrow_down_right($title, $id, $is_open = false, $class = false, $tag = "h2", $state_url = false) {
	zesk()->deprecated();
	HTML::jquery("var frag = document.URL.split('#'); if (frag[1]) { $('#'+frag[1]).show(); }");
	HTML::cdn_javascript("/share/zesk/js/zesk.js", array('weight' => 'first'));

	$opts = array(
		"title" => $title,
		"id_class" => $id,
		"is_open" => $is_open,
		"label_tag" => $tag
	);

	$opts['jquery'] = true;
	if (is_string($class)) {
		$opts['class'] = $class;
	}
	if (is_string($state_url)) {
		$opts['state_url'] = $state_url;
	}
	$t = new Template("arrow_down_right.inc", $opts);
	return $t->render();
}

function section_down_right($title, $content, $id, $is_open = false, $class = false, $tag = "h2", $state_url = false) {
	zesk()->deprecated();
	return section_down_right_begin($title, $content, $id, $is_open, $class, $tag, $state_url) . section_down_right_end();
}

function section_down_right_begin($title, $id = false, $is_open = false, $class = false, $tag = "h2", $state_url = false) {
	zesk()->deprecated();
	HTML::jquery("var frag = document.URL.split('#'); if (frag[1]) { $('#'+frag[1]).show(); }");
	if (empty($id)) {
		$id = "section-dr-" . HTML::id_counter();
	}
	return arrow_down_right($title, $id, $is_open, $class, $tag, $state_url) . html_tag("div", array(
		"id" => $id,
		"class" => "toggle-arrow-content",
		"style" => "display: " . ($is_open ? "block" : "none")
	));
}

function section_down_right_end() {
	zesk()->deprecated();
	return HTML::tag_close("div");
}

function arrow_widget($title, $contents, $id, $is_open, Options $options) {
	zesk()->deprecated();
	return arrow_widget_begin($title, $id, $is_open, $options) . $contents . arrow_widget_end();
}

function arrow_widget_begin($title, $id, $is_open, Options $options) {
	zesk()->deprecated();
	$url = $options->option("url");

	$js = "javascript:arrow_down_right('$id','$url')";

	$img_attrs = $options->option_array("img_attrs", array());
	$img_attrs['src'] = cdn::url("/share/images/toggle/small-" . ($is_open ? "down" : "right") . ".gif");
	if (!array_key_exists('alt', $img_attrs)) {
		$img_attrs['alt'] = '';
	}
	$img_attrs['id'] = $id . "_img";

	$img = html_tag_single("img", $img_attrs);

	$label_tag = $options->option("label_tag", "h2");
	$label_attrs = $options->option_array("label_attrs", array());
	$label_a_attrs = $options->option_array("label_a_attrs", array());
	$label_a_attrs['href'] = $js;

	$contents_attrs = $options->option_array("contents_attrs", array());
	$contents_attrs['id'] = $id;
	$contents_attrs['style'] = "display: " . ($is_open ? "block" : "none");
	if (!array_key_exists('class', $contents_attrs)) {
		$contents_attrs['class'] = 'toggle-arrow-content';
	}

	$inner_link = tag("a", $label_a_attrs, $title);

	if ($label_tag) {
		$inner_link = tag($label_tag, $label_attrs, $inner_link);
	}
	return tag("div", array(
		"class" => "toggle-arrow"
	), tag("a", array(
		"class" => "toggle-arrow",
		"href" => $js
	), $img) . $inner_link) . html_tag("div", $contents_attrs);
}

function arrow_widget_end() {
	zesk()->deprecated();
	return "</div>";
}

/**
 * Returns the number of words delimited by spaces found in string.
 *
 * @param string $string A string to count words in
 * @param integer $limit Max words to count
 * @return integer The number of words found
 */
function text_count_words($string, $limit = -1) {
	zesk()->deprecated();
	return count(preg_split('/\s+/', trim($string), $limit));
}

function html_closebox($content, $template_file = false, $options = false) {
	zesk()->deprecated();
	if (empty($template_file)) {
		$template_file = ZESK_ROOT . "theme/zesk/widgets/closebox/default.tpl";
	}
	$options['content'] = $content;
	$t = new Template($template_file, $options);
	return $t->render();
}

/**
 * Displays errors returned by a widget in a standard way
 *
 * @param array $e
 * @param boolean $just_numeric
 * @return string
 */
function html_errors($e, $just_numeric = false) {
	zesk()->deprecated();
	if (is_string($e)) {
		$e = array(
			$e
		);
	}
	if (!is_array($e)) {
		return array();
	}
	if (count($e) == 0) {
		return $e;
	}
	$result[] = '<p class="error">Please fix the following:</p>';
	if ($just_numeric) {
		$ee = array();
		foreach ($e as $k => $v) {
			if (is_numeric($k)) {
				$ee[] = $v;
			}
		}
		if (count($ee) == 0) {
			return implode("\n", $result);
		}
		$result[] = '<ul class="error">';
		$result[] = "<li>" . implode("</li>\n<li>", $ee) . "</li>";
	} else {
		$result[] = '<ul class="error">';
		$result[] = "<li>" . implode("</li>\n<li>", $e) . "</li>";
	}
	$result[] = "</ul>";
	return implode("\n", $result);
}

/**
 * Enter description here...
 *
 * @param unknown_type $e
 * @param unknown_type $name
 * @param unknown_type $prefix
 * @return unknown
 */
function html_error($e, $name, $prefix = "<br />") {
	zesk()->deprecated();
	if (!is_array($e)) {
		return "";
	}
	$err = avalue($e, $name);
	if (!$err) {
		return "";
	}
	return $prefix . '<span class="error">' . $err . "</span>";
}

/**
 * Enter description here...
 *
 * @param unknown_type $html
 * @param unknown_type $tag
 * @param unknown_type $maxn
 * @return unknown
 */
function linklist($html, $tag = "dt", $maxn = -1) {
	zesk()->deprecated();
	$matches = false;
	if (!preg_match_all('/(?U)<' . $tag . '[^>]*>(.*)<\/' . $tag . '[^>]*>/', $html, $matches)) {
		return "";
	}
	$n = 0;
	foreach ($matches[1] as $xx) {
		$aname = false;
		if (preg_match("/name=\"([^\"]*)\">/", $xx, $aname)) {
			if ($maxn > 0 && ++$n >= $maxn) {
				$result[$aname[1]] = "More ...";
				break;
			} else {
				$result[$aname[1]] = HTML::strip($xx);
			}
		}
	}
	$z[] = "<ul>";
	foreach ($result as $k => $v) {
		$z[] = "<li><a href=\"#$k\">$v</a></li>";
	}
	$z[] = "</ul>";
	return implode("\n", $z);
}

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @param unknown_type $opts
 * @param unknown_type $value
 * @param unknown_type $attrs
 * @return unknown
 */
function input_select($name, $opts, $value, $attrs = false) {
	zesk()->deprecated();
	if (!is_array($opts) || count($opts) === 1) {
		foreach ($opts as $k => $v) {
			return "<input type=\"hidden\" name=\"$name\" value=\"$k\" />$v";
		}
	}
	$result[] = "<select name=\"$name\"" . HTML::attributes($attrs) . ">";
	foreach ($opts as $n => $v) {
		$result[] = "<option value=\"$n\"" . (($value == $n) ? " selected" : "") . ">$v</option>";
	}
	$result[] = "</select>";
	return implode("\n", $result);
}

/**
 * Enter description here...
 *
 * @param unknown_type $formname
 * @param unknown_type $name
 * @param unknown_type $value
 * @param unknown_type $showclear
 * @return unknown
 */
function input_date($formname, $name, $value = "Not set.", $showclear = false) {
	zesk()->deprecated();
	$result = "
		<input type=\"text\"
			name=\"Show_$name\"
			maxlength=\"10\"
			size=\"12\"
			style=\"border: 0;\"
			class=\"input-text\"
			value=\"$value\"
			onchange=\"this.form.$name.value = this.value;\"
		/>
		<input type=\"hidden\" name=\"$name\" value=\"$value\" />
		<div id=\"CalendarDiv_$name\" style=\"position:absolute; visibility:hidden; z-index: 10000;\"></div>
		<a href=\"javascript:noop()\" onclick=\"Date_Popup(document.$formname, '$name', true); return false;\"><img
			name=\"Set_$name\"
			src=\"/theme/zesk/control/datetime/date-button.gif\"
			width=\"16\" height=\"16\" border=\"0\"
		/></a>
	";
	if ($showclear) {
		$result .= "<input
				type=\"image\"
				name=\"Clear_$name\"
				src=\"/theme/zesk/control/datetime/date-zero.gif\"
				width=\"16\" height=\"16\"
				onclick=\"return datetimewidget_toggleempty(this, this.form.$name, this.form.show_$name, '', '!set.', '!set.')\"
			/>";
	}
	return $result;
}

/**
 * Word-wraps a string at character values
 *
 * @param string $s String to wrap
 * @param string $br Newline character to wrap with
 * @param integer $nchars Number of characters per line
 * @return string word-wrapped string
 */
function wrap_string($s, $br = "<br /> ", $nchars = 35) {
	zesk()->deprecated();
	$i = 0;
	$ss = array();
	while ($i < strlen($s)) {
		$ss[] = substr($s, $i, $nchars);
		$i += $nchars;
	}
	return implode($br, $ss);
}
