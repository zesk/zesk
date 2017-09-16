<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Time/Zone.php $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:32:26 EDT 2010 21:32:26
 *
 * @todo Remove http.inc and fix URL::query_remove, etc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class View_Time_Zone extends View_Text {
	function render() {
		$pp = $this->option("format", null);
		if ($pp === null) {
			$pp = parent::render();
		}
		$object = $this->object;
		$text = $object->apply_map($pp);
		$href = $object->apply_map($this->option("href", ""));
		if (empty($text)) {
			$text = $this->empty_string();
		} else if (!$this->option_bool("HTML")) {
			$text = htmlspecialchars($text);
		}
		$attrs = $object->apply_map($this->options_include("target;class;onclick"));
		$uri = $this->request->uri();
		$add_ref = $this->option("add_ref", URL::query_remove($uri, "message"));
		if ($add_ref) {
			$href = URL::query_format(URL::query_remove($href, "ref"), array(
				"ref" => $add_ref
			));
		}
		$attrs['href'] = $href;
		$result = HTML::tag("a", $attrs, HTML::ellipsis($text, $this->option("ShowSize", -1), $this->option("Ellipsis", "...")));
		return $this->render_finish($object, $result);
	}
}
