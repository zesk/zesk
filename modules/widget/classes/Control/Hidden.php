<?php
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
namespace zesk;

class Control_Hidden extends Control {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Widget::is_visible()
	 */
	public function is_visible() {
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Widget::render()
	 */
	public function render() {
		$col = $this->column();
		$input_name = $this->name();
		
		return HTML::hidden($this->name(), $this->value(), $this->input_attributes() + $this->data_attributes());
	}
}
