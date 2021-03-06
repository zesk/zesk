<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Tooltip extends View {
	public function render() {
		$response = $this->response();
		$html = $response->html();
		$html->jquery();
		$html->javascript("/share/zesk/jquery/jquery.hoverIntent.js");
		$html->javascript("/share/zesk/jquery/jquery.corners.min.js");
		$html->javascript("/share/zesk/widgets/hoverbubble/hoverbubble.js");

		$col = $this->column();
		$id = "hover-bubble-" + $response->id_counter();

		/*
		 *  sensitivity: 7, // number = sensitivity threshold (must be 1 or higher)
		 *	interval: 100,   // number = milliseconds of polling interval
		 *	timeout: 0,   // number = milliseconds delay before onMouseOut function call
		 */
		$options = $this->options_include("sensitivity;interval;timeout");
		$html->jquery('$(\'#' + $this->option("target_id") + '\').hoverBubble(\'#' + $id + '\',' . json_encode($options) . ");");
		return HTML::tag("div", array(
			"id" => $id,
			"style" => "display: none",
		), $this->value());
	}
}
