<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:18:06 EDT 2010 21:18:06
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Link extends View_Text {
	public function format($set = null) {
		if ($set !== null) {
			return $this->set_option("format", $set);
		}
		return $this->option('format');
	}

	public function action($set = null) {
		if ($set !== null) {
			return $this->set_option("action", $set);
		}
		return $this->option('action');
	}

	public function href($set = null) {
		if ($set !== null) {
			return $this->set_option("href", $set);
		}
		return $this->option('href');
	}

	public function render() {
		$object = $this->object;
		$pp = $this->option("format", null);
		if ($pp === null) {
			$pp = parent::render($object);
		}
		$text = $object->apply_map($pp);
		$href = $this->option("href", "");
		if (empty($href)) {
			$action = $this->option('action');
			$href = $this->application->router()->get_route($action, $object);
		} else {
			$href = $object->apply_map($href);
		}
		$is_empty = false;
		if (empty($text)) {
			$is_empty = true;
			$text = $this->empty_string();
		} elseif (!$this->option_bool("html")) {
			$text = htmlspecialchars($text);
		}
		$result = "";
		if ($is_empty && $this->option_bool('empty_no_link', false)) {
			return $text;
		}
		$attrs = $object->apply_map($this->options_include("target;class;onclick;title;id"));
		$add_ref = $this->option("add_ref", URL::query_remove($this->request->uri(), "message"));
		if ($add_ref) {
			$href = URL::query_format(URL::query_remove($href, "ref"), array(
				"ref" => $add_ref,
			));
		}
		$attrs['href'] = $href;
		$attrs['title'] = avalue($attrs, 'title', $href);
		$show_size = $this->show_size();
		$text = $show_size > 0 ? HTML::ellipsis($text, $this->show_size(), $this->option("ellipsis", "&hellip;")) : $text;
		$result = HTML::tag("a", $attrs, $text);
		return $result;
	}
}
