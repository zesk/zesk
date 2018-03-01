<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:15:53 EDT 2010 21:15:53
 */
namespace zesk;

class View extends Widget {
	function validate() {
		return true;
	}
	function submitted() {
		return false;
	}
	function hidden_input($set = null) {
		if ($set !== null) {
			return $this->set_option("hidden_input", to_bool($set));
		}
		return $this->option_bool('hidden_input');
	}
	function theme_variables() {
		return array(
			'hidden_input' => $this->hidden_input()
		) + parent::theme_variables();
	}
}
