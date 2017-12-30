<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Slider.php $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class Control_Slider extends Control {
	function render() {
		$this->response->jquery();
		$this->response->css("/share/zesk/widgets/slider/slider.css");
		$this->response->javascript("/share/zesk/jquery/ui/ui.core.js");
		$this->response->javascript("/share/zesk/jquery/ui/ui.slider.js");
		
		$id = $this->column() . "_slider";
		$opts = $this->options_include("min;max;vertical;step;range;steps");
		$styles = $this->options_include("width;height");
		$opts['value'] = $this->value();
		$opts = json_encode($opts);
		$this->response->jquery("var opts = $opts;\n" . "opts['slide'] = function(event,ui) { $('#${id}_value').html(ui.value); };\n" . "$('#" . $id . "').slider(opts);");
		$result = HTML::tag("div", array(
			"id" => $id,
			"style" => HTML::styles($styles)
		), "") . HTML::tag("div", array(
			"id" => $id . "_value"
		), "");
		return $this->render_finish($result);
	}
}

