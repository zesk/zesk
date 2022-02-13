<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class Control_Icon extends Control {
	public function render() {
		$icons = $this->optionArray("icons", []);
		$v = $this->value();
		$src = $icons[strval($v)] ?? null;
		if (!$src) {
			return "";
		}
		$attrs["href"] = "javascript: " . $this->option("onclick");
		$attrs["onclick"] = $this->option("onclick");
		//$attrs["onrclick"] = $this->option("onrclick");
		$attrs["ondblclick"] = $this->option("ondblclick");
		$img_attrs = $this->option_array("img_attributes");
		$img_attrs['width'] = $this->option("img_width");
		$img_attrs['height'] = $this->option("img_height");
		$result = HTML::tag("a", $attrs, HTML::img($this->application, $src, $this->option("alt", ""), $img_attrs));
		if ($this->hasOption("js_variable")) {
			$result .= HTML::tag("script", [
				"type" => "text/javascript",
			], "var " . $this->option("js_variable") . " = '" . $v . "'");
		}
		return $result;
	}
}
