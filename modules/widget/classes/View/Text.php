<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Text.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 15:59:24 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Text extends View {
	function format($set = null) {
		return $set === null ? $this->option('format') : $this->set_option('format', $set);
	}
	function render() {
		$showSize = $this->show_size();
		if ($this->has_option('value')) {
			$v = $this->option('value');
		} else {
			$v = $this->value();
		}
		if ($v === null) {
			$v = $this->empty_string();
			if (to_bool($this->empty_string_no_wrap)) {
				return $v;
			}
		}
		$transform = $this->option("transform");
		$is_html = $this->option_bool("HTML");
		switch ($transform) {
			case "html":
				$v = htmlspecialchars($v);
				break;
			case "no-html":
				$v = HTML::strip($v);
				break;
			case "camel":
				$v = StringTools::capitalize($v);
				break;
		}
		$close_ellip = "";
		$old_v = "";
		$my_id = null;
		if ($showSize > 0) {
			$allow_show_all = $this->option_bool("allow_show_all");
			$ellip = $this->option($is_html ? "html_ellipsis" : "text_ellipsis", '...');
			if ($allow_show_all) {
				$this->response()->html()->javascript("/share/zesk/js/zesk.js", array(
					'weight' => 'first'
				));

				$my_id = HTML::id_counter();
				$ellip = "<a onclick=\"ellipsis_toggle('$my_id')\">$ellip</a>";
				$close_ellip = "<a onclick=\"ellipsis_toggle('$my_id')\">&lt;&lt;</a>";
				$old_v = $v;
			}
			if ($is_html) {
				$v = HTML::ellipsis($v, $showSize, $ellip);
			} else {
				$v = HTML::ellipsis($v, $showSize, $ellip); // TODO?
			}
			if ($allow_show_all && $old_v !== $v) {
				$v = "<span id=\"ellipsis-$my_id\">$v</span><span style=\"display: none\" id=\"ellipsis-$my_id-all\">$old_v $close_ellip</span>";
			}
		}
		if ($this->has_option("format")) {
			$v = $this->object->apply_map($this->option("format"));
		}
		if ($this->option_bool('debug')) {
			dump($this->object);
			echo "$v<br />";
		}
		return $this->render_finish($v);
	}
}
