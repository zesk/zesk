<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/object.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:02:41 EDT 2008
 */
class View_Object extends View {

	function format($set = null) {
		if ($set !== null) {
			return $this->set_option("format", $set);
		}
		return $this->option('format');
	}

	function display_method($set = null, $set_args = array()) {
		if ($set !== null) {
			$this->set_option('display_method', $set);
			return $this->set_option('display_method_arguments', $set_args);
		}
		return $this->option('display_method');
	}
	function theme_variables() {
		return array(
			'object' => $this->value(),
			'format' => $this->format,
			'display_method' => $this->option('display_method'),
			'display_method_arguments' => $this->option_array('display_method_arguments'),
			"object_class" => $this->class,
			"hidden_input" => $this->hidden_input(),
			"class_object" => Object::cache_class($this->option('class', $this->class), 'class')
		) + parent::theme_variables();
	}

}
