<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/template.inc $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
class View_Template extends View {

	public function template($set = null) {
		return ($set !== null) ? $this->set_option('template', $set) : $this->option('template');
	}
	function render() {
		$template = $this->option("template");
		if (Template::would_exist($template)) {
			$attr['object'] = $this->object;
			$attr['widget'] = $this;
			return Template::instance($this->option("template"), $attr);
		} else {
			return $this->empty_string();
		}
	}
}

