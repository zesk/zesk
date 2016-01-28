<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/classes/control/icon.inc $
* @package zesk
* @subpackage default
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2008, Market Acumen, Inc.
*/
class Control_Icon extends Control {

	function render() {
		$col = $this->column();
		$icons = $this->option_array("icons", array());
		$v = $this->value();
		$src = avalue($icons, strval($v));
		$attrs["href"] = "javascript: " . $this->option("onclick");
		$attrs["onclick"] = $this->option("onclick");
		//$attrs["onrclick"] = $this->option("onrclick");
		$attrs["ondblclick"] = $this->option("ondblclick");
		$img_attrs = $this->option_array("img_attributes");
		$img_attrs['width'] = $this->option("img_width");
		$img_attrs['height'] = $this->option("img_height");
		$result = html::tag("a", $attrs, html::img($src, $this->option("alt", ""), $img_attrs));
		if ($this->has_option("js_variable")) {
			$result .= html::tag("script", array(
				"type" => "text/javascript"
			), "var " . $this->option("js_variable") . " = '" . $v . "'");
		}
		return $result;
	}
}
