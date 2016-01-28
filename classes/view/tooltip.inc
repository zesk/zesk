<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/tooltip.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
class View_Tooltip extends View {

	function render() {
		$this->response->jquery();
		$this->response->cdn_javascript("/share/zesk/jquery/jquery.hoverIntent.js");
		$this->response->cdn_javascript("/share/zesk/jquery/jquery.corners.min.js");
		$this->response->cdn_javascript("/share/zesk/widgets/hoverbubble/hoverbubble.js");

		$col = $this->column();
		$id = "hover-bubble-" + $this->response->id_counter();

		/*
		 *  sensitivity: 7, // number = sensitivity threshold (must be 1 or higher)
		 *	interval: 100,   // number = milliseconds of polling interval
		 *	timeout: 0,   // number = milliseconds delay before onMouseOut function call
		 */
		$options = $this->options_include("sensitivity;interval;timeout");
		$this->response->jquery('$(\'#' + $this->option("target_id") + '\').hoverBubble(\'#' + $id + '\',' . json_encode($options) . ");");
		return html::tag("div", array(
			"id" => $id,
			"style" => "display: none"
		), $this->value());
	}

	public static function tooltip($target_id, $content, array $opts = array()) {
		$opts['target_id'] = $target_id;
		$opts['column'] = 'data';
		$w = new View_Tooltip($opts);
		$x['data'] = $content;
		return $w->execute($x);
	}
}

/**
 * @todo Move this elsewhere to get autoloaded
 *
 * @param unknown_type $target_id
 * @param unknown_type $content
 * @param unknown_type $opts
 * @return unknown
 */
function tooltip($target_id, $content, $opts = false) {
	return View_Tooltip::tooltip($target_id, $content, $opts);
}
