<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/link.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:18:06 EDT 2010 21:18:06
 */
class View_Link extends View_Text {

	function format($set = null) {
		if ($set !== null) {
			return $this->set_option("format", $set);
		}
		return $this->option('format');
	}

	function action($set = null) {
		if ($set !== null) {
			return $this->set_option("action", $set);
		}
		return $this->option('action');
	}

	function href($set = null) {
		if ($set !== null) {
			return $this->set_option("href", $set);
		}
		return $this->option('href');
	}

	function render() {
		$object = $this->object;
		$pp = $this->option("format", null);
		if ($pp === null) {
			$pp = parent::render($object);
		}
		$text = $object->apply_map($pp);
		$href = $this->option("href", "");
		if (empty($href)) {
			$action = $this->option('action');
			$href = $this->request->get_route($action, $object);
		} else {
			$href = $object->apply_map($href);
		}
		$is_empty = false;
		if (empty($text)) {
			$is_empty = true;
			$text = $this->empty_string();
		} else if (!$this->option_bool("html")) {
			$text = htmlspecialchars($text);
		}
		$result = "";
		if ($is_empty && $this->option_bool('empty_no_link', false)) {
			return $text;
		}
		$attrs = $object->apply_map($this->options_include("target;class;onclick;title;id"));
		$add_ref = $this->option("add_ref", url::query_remove(url::current_uri(), "message"));
		if ($add_ref) {
			$href = url::query_format(url::query_remove($href, "ref"), array(
				"ref" => $add_ref
			));
		}
		$attrs['href'] = $href;
		$attrs['title'] = avalue($attrs, 'title', $href);
		$show_size = $this->show_size();
		$text = $show_size > 0 ? html::ellipsis($text, $this->show_size(), $this->option("ellipsis", "&hellip;")) : $text;
		$result = html::tag("a", $attrs, $text);
		return $result;
	}
}

