<?php
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class View_Template extends View {
	/**
	 * 
	 * @param unknown $set
	 * @return void|mixed|string
	 */
	public function template($set = null) {
		return ($set !== null) ? $this->set_option('template', $set) : $this->option('template');
	}
	function render() {
		$template = $this->option("template");
		if ($this->application->theme_exists($template)) {
			$attr['object'] = $this->object;
			$attr['widget'] = $this;
			return $this->application->theme($this->option("template"), $attr);
		} else {
			return $this->empty_string();
		}
	}
}

